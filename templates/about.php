<?php
/**
 * Template for the about tab on the settings page.
 *
 * @package Tasty_Recipes
 */

?>

<style>
	main {
		max-width: 700px;
		margin-top: 1rem;
	}
	section::after {
		content: '';
		display: block;
		clear: both;
		margin-bottom: 2rem;
	}
	h2 {
		margin: 3rem auto 0;
	}
	section p {
		font-size: 1rem;
	}
	.wp-core-ui .button {
		font-size: 1rem;
		padding: 5px 15px 6px;
		height: unset;
	}
	section img {
		max-width: 300px;
		display: block;
		margin: auto;
	}
	@media screen and (min-width: 700px) {
		section img {
			margin: 1rem auto;
			margin-left: 1rem;
			float: right;
		}
		.first {
			margin: -3rem 0 0 1rem;
		}
	}
</style>

<div class="tasty-recipes-about"><main>

	<h1><?php esc_html_e( 'Welcome to Tasty Recipes! 🎉', 'tasty-recipes' ); ?></h1>

	<section>

		<img src="<?php echo esc_url( plugins_url( 'assets/images/theme-bold.png', __DIR__ ) ); ?>" class="first" data-pin-nopin="true" alt="Screenshot of the Bold recipe template" />

		<p><?php esc_html_e( 'Tasty Recipes is a fast, simple, and SEO-optimized recipe plugin for food bloggers. By purchasing Tasty Recipes, you\'re getting access to an impecable recipe creation experience, superior code quality, and a helpful support team ready to answer all your questions.', 'tasty-recipes' ); ?></p>

		<h2><?php esc_html_e( 'Getting Started', 'tasty-recipes' ); ?></h2>

		<p><?php esc_html_e( 'Tasty Recipes is extremely easy to configure. Visit the settings page to select a recipe card theme, add your Instagram information, and set the default Author URL.', 'tasty-recipes' ); ?></p>

		<p><a class="button" href="<?php menu_page_url( Tasty_Recipes\Admin::PAGE_SLUG, true ); ?>"><?php esc_html_e( 'Visit Settings', 'tasty-recipes' ); ?></a>

		<h2><?php esc_html_e( 'Convert Recipes', 'tasty-recipes' ); ?></h2>

		<p><?php esc_html_e( 'Tasty Recipes converts recipes from many sources, including WP Recipe Maker, Easy Recipe, Zip Recipes, and more. We recommend converting a single recipe first to try out the conversion process, then converting everything in bulk once you\'re satisfied.', 'tasty-recipes' ); ?></p>

		<p>
		<?php
		printf(
			esc_html__( 'Read more about %1$s and %2$s.', 'tasty-recipes' ),
			sprintf(
				'<a href="https://www.wptasty.com/convert-single" target="_blank">%s</a>',
				esc_html__( 'converting recipes individually', 'tasty-recipes' )
			),
			sprintf(
				'<a href="https://www.wptasty.com/convert-all" target="_blank">%s</a>',
				esc_html__( 'converting recipes in bulk', 'tasty-recipes' )
			)
		);
		?>
		</p>

	</section>

	<section>

		<img src="<?php echo esc_url( plugins_url( 'assets/images/tasty-recipes-block.png', __DIR__ ) ); ?>" class="" data-pin-nopin="true" alt="Screenshot of adding a Tasty Recipes block to a post" />

		<h2><?php esc_html_e( 'Create New Recipes', 'tasty-recipes' ); ?></h2>

		<p><?php esc_html_e( 'Creating new recipes is easy. Just add a new "Tasty Recipe" block to a post and you\'ll be on your way.', 'tasty-recipes' ); ?></p>

		<p><?php esc_html_e( 'We recommend filling out all the fields for the best SEO potential. Tasty Recipes creates amazing structured data - but it needs the proper information in order to do it!', 'tasty-recipes' ); ?></p>

		<h2><?php esc_html_e( 'Visit Our Documentation', 'tasty-recipes' ); ?></h2>

		<p><?php esc_html_e( 'We pride ourselves on our plugin documentation. If you have questions, head on over to our support site - your question is likely answered there! If not, send us a quick chat and we\'ll be happy to help.', 'tasty-recipes' ); ?></p>

		<p><a class="button" href="https://support.wptasty.com" target="_blank"><?php esc_html_e( 'Visit Documentation', 'tasty-recipes' ); ?></a></p>

	</section>

</main></div>
