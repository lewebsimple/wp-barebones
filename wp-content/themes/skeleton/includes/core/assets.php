<?php

$manifest_path = get_theme_file_path( 'dist/.vite/manifest.json' );
$is_prod       = file_exists( $manifest_path );

// Register frontend assets
add_action( 'init', 'skeleton_assets_register' );
function skeleton_assets_register() {
	global $manifest_path, $is_prod;
	$deps = array( 'jquery' );
	if ( $is_prod ) {
		// Production
		$version  = filemtime( get_theme_file_path( 'dist/assets/main.js' ) );
		$manifest = json_decode( file_get_contents( $manifest_path ), true );
		$main     = $manifest['src/main.ts'];
		foreach ( $main['imports'] ?? array() as $import ) {
			$handle = strtok( $import, '.' );
			wp_register_script( $handle, get_theme_file_uri( 'dist/' . $manifest[ $import ]['file'] ), array(), $version, true );
			$deps[] = $handle;
		}
		wp_register_script( 'main', get_theme_file_uri( 'dist/assets/main.js' ), $deps, $version, true );
		if ( ! empty( $main['css'] ) ) {
			wp_register_style( 'main', get_theme_file_uri( 'dist/' . $main['css'][0] ), array(), $version );
			add_editor_style( 'dist/' . $main['css'][0] );
		}
	} else {
		// Development
		$version = false;
		$prefix  = "https://localhost:3001";
		$deps[]  = 'vite-client';
		wp_register_script( 'vite-client', "$prefix/@vite/client", array(), $version, true );
		wp_register_script( 'main-ts', "$prefix/src/main.ts", $deps, $version, true );
		wp_register_script( 'main', get_theme_file_uri( 'dist/assets/main.js' ), array( 'main-ts' ), $version, true );
	}
}

// Filter script tag for enabling module imports
add_filter( 'script_loader_tag', 'skeleton_assets_script_loader_tag', 10, 3 );
function skeleton_assets_script_loader_tag( $tag, $handle, $src ) {
	if ( in_array( $handle, array( '_vendor', 'main-ts', 'vite-client' ) ) ) {
		return sprintf( '<script type="module" src="%s"></script>', esc_url( remove_query_arg( 'ver', $src ) ) );
	}
	return $tag;
}

// Enqueue frontend assets
add_action( 'wp_enqueue_scripts', 'skeleton_assets_enqueue' );
function skeleton_assets_enqueue() {
	wp_enqueue_script( 'main' );
	wp_enqueue_style( 'main' );
}
