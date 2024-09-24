<?php

/**
 * Plugin Name: Plugins Autoloader
 * Plugin URI: https://github.com/lewebsimple/wp-boilerplate
 * Description: Environment-aware autoloader for must-use plugins (a.k.a. mu-plugins) based on the Bedrock Autoloader.
 * Version: 1.0.0
 * Author: Websimple
 * Author URI: https://rwww.websimple.com/
 * License: MIT
 */

class PluginsAutoloader {

	/** @var static Singleton instance */
	private static $instance;

	/** @var bool Whether the site is in production */
	private $isProd;

	/** @var array Store Autoloader cache and site option */
	private $cache;

	/** @var string Absolute path to mu-plugins dir */
	private $muPluginsPath;

	/** @var string Absolute path to env-plugins dir */
	private $envPluginsPath;

	/** @var array Autoloaded plugins */
	private $muPluginsBuiltin;

	/** @var array Autoloaded mu-plugins */
	private $muPlugins;

	/** @var array Autoloaded env-plugins */
	private $envPlugins;

	/** @var int Number of plugins */
	private $count;

	/** @var array Newly activated plugins */
	private $activated;

	/**
	 * Create singleton, populate vars and set WordPress hooks
	 */
	public function __construct() {
		if ( isset( self::$instance ) ) {
			return;
		}
		self::$instance       = $this;
		$this->isProd         = getenv( 'WP_ENV' ) !== 'development';
		$this->muPluginsPath  = WPMU_PLUGIN_DIR;
		$this->envPluginsPath = str_replace( 'mu-plugins', $this->isProd ? 'prod-plugins' : 'dev-plugins', $this->muPluginsPath );
		$this->loadPlugins();
		if ( is_admin() ) {
			add_filter( 'show_advanced_plugins', array( $this, 'showInAdmin' ), 0, 2 );
		}
		add_filter( 'plugins_url', array( $this, 'pluginsUrl' ), 10, 3 );
	}

	/**
	 * Run some checks then autoload our plugins.
	 */
	public function loadPlugins() {
		$this->checkCache();
		$this->validatePlugins();
		$this->countPlugins();
		array_map(
			function ( $path ) {
				include_once $path;
			},
			array_column( $this->cache['plugins'], 'path' )
		);
		add_action( 'plugins_loaded', array( $this, 'pluginHooks' ), -9999 );
	}

	/**
	 * Filter show_advanced_plugins to display the autoloaded plugins.
	 *
	 * @param $show bool Whether to show the advanced plugins for the specified plugin type.
	 * @param $type string The plugin type, i.e., `mustuse` or `dropins`
	 * @return bool We return `false` to prevent WordPress from overriding our work
	 * {@internal We add the plugin details ourselves, so we return false to disable the filter.}
	 */
	public function showInAdmin( $show, $type ) {
		$screen  = get_current_screen();
		$current = is_multisite() ? 'plugins-network' : 'plugins';
		if ( $screen->base !== $current || $type !== 'mustuse' || ! current_user_can( 'activate_plugins' ) ) {
			return $show;
		}
		$this->updateCache();
		$GLOBALS['plugins']['mustuse'] = array_unique( array_merge( $this->muPlugins, $this->envPlugins, $this->muPluginsBuiltin ), SORT_REGULAR );
		return false;
	}

	/**
	 * Filter plugins_url() to return the correct URL for mu-plugins.
	 *
	 * @param string $url    The complete URL to the plugins directory including scheme and path.
	 * @param string $path   Path relative to the plugins URL.
	 * @param string $plugin The plugin file path.
	 * @return string Correct URL for mu-plugins.
	 */
	public function pluginsUrl( $url, $path, $plugin ) {
		if ( strpos( $plugin, '/' ) !== 0 ) {
			$plugin = "/$plugin";
		}
		if ( strpos( $plugin, $this->envPluginsPath ) === 0 ) {
			$url = str_replace( "plugins$this->envPluginsPath", $this->isProd ? 'prod-plugins' : 'dev-plugins', $url );
		}
		return $url;
	}

	/**
	 * Load cache from database or regenerate it if necessary.
	 */
	private function checkCache() {
		$cache = get_site_option( 'plugins_autoloader_cache' );
		if ( $cache === false || ( isset( $cache['plugins'], $cache['count'] ) && count( $cache['plugins'] ) !== $cache['count'] ) ) {
			$this->updateCache();
			return;
		}
		$this->cache = $cache;
	}

	/**
	 * Get plugins / mu-plugins / env-plugins and remove duplicates.
	 * Check cache against current plugins for newly activated plugins.
	 * After that, we can update the cache.
	 */
	private function updateCache() {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		$this->muPluginsBuiltin = get_mu_plugins();
		$this->muPlugins        = get_plugins( '/../' . basename( $this->muPluginsPath ) );
		foreach ( $this->muPlugins as $plugin_file => &$plugin_info ) {
			$plugin_info['path']  = $this->muPluginsPath . '/' . $plugin_file;
			$plugin_info['Name'] .= ' [mu-plugins]';
		}
		$this->envPlugins = get_plugins( '/../' . basename( $this->envPluginsPath ) );
		foreach ( $this->envPlugins as $plugin_file => &$plugin_info ) {
			$plugin_info['path']  = $this->envPluginsPath . '/' . $plugin_file;
			$plugin_info['Name'] .= $this->isProd ? ' [prod-plugins]' : ' [dev-plugins]';
		}
		$allMuPlugins    = array_merge( $this->muPlugins, $this->envPlugins );
		$plugins         = array_diff_key( $allMuPlugins, $this->muPluginsBuiltin );
		$rebuild         = ! isset( $this->cache['plugins'] );
		$this->activated = $rebuild ? $plugins : array_diff_key( $plugins, $this->cache['plugins'] );
		$this->cache     = array(
			'plugins' => $plugins,
			'count'   => $this->countPlugins(),
		);
		update_site_option( 'plugins_autoloader_cache', $this->cache );
	}

	/**
	 * This accounts for the plugin hooks that would run if the plugins were
	 * loaded as usual. Plugins are removed by deletion, so there's no way
	 * to deactivate or uninstall.
	 */
	public function pluginHooks() {
		if ( ! is_array( $this->activated ) ) {
			return;
		}
		foreach ( $this->activated as $plugin_file => $plugin_info ) {
			do_action( 'activate_' . $plugin_file );
		}
	}

	/**
	 * Check that the plugin file exists, if it doesn't update the cache.
	 */
	private function validatePlugins() {
		foreach ( $this->cache['plugins'] as $plugin_info ) {
			if ( ! file_exists( $plugin_info['path'] ) ) {
				$this->updateCache();
				break;
			}
		}
	}

	/**
	 * Count the number of autoloaded plugins.
	 *
	 * Count our plugins (but only once) by counting the top level folders in
	 * mu-plugins / env-plugins. If it's more or less than last time, update the cache.
	 *
	 * @return int Number of autoloaded plugins.
	 */
	private function countPlugins() {
		if ( isset( $this->count ) ) {
			return $this->count;
		}
		$count = count( glob( $this->muPluginsPath . '/*/', GLOB_ONLYDIR | GLOB_NOSORT ) )
			+ count( glob( $this->envPluginsPath . '/*/', GLOB_ONLYDIR | GLOB_NOSORT ) );
		if ( ! isset( $this->cache['count'] ) || $count !== $this->cache['count'] ) {
			$this->count = $count;
			$this->updateCache();
		}
		return $this->count;
	}
}

if ( is_blog_installed() ) {
	new PluginsAutoloader();
}
