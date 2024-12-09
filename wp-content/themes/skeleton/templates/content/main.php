<div class="main container mx-auto">
	<?php if ( is_singular() ) : ?>
		<?= kaliroots_content_template( 'partials/post-content' ) ?>
	<?php else : ?>
		<?= kaliroots_loop_template( 'default' ) ?>
	<?php endif; ?>
</div>
