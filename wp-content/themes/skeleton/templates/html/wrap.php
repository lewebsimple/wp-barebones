<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<?= kaliroots_html_template( 'head' ); ?>
<body class="<?= join( ' ', get_body_class() ) ?>">
<?php is_singular() && the_post(); ?>
<div id="app">
	<header>
		<?= kaliroots_site_template( 'header' ) ?>
	</header>
	<main>
		<?= kaliroots_generic_template( '{base}' ) ?>
	</main>
	<footer>
		<?= kaliroots_site_template( 'footer' ) ?>
	</footer>
</div>
<?php wp_footer(); ?>
</body>
</html>
