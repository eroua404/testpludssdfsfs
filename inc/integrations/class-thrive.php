<?php
/**
 * Integrates Tasty Recipes with Thrive
 *
 * @package Tasty_Recipes
 */

namespace Tasty_Recipes\Integrations;

/**
 * Integrates Tasty Recipes with Thrive.
 */
class Thrive {

	/**
	 * Adds our shortcode to the Thrive shortcodes.
	 *
	 * @param array $prefixes Existing prefixes.
	 * @return array
	 */
	public static function filter_thrive_theme_shortcode_prefixes( $prefixes ) {
		if ( ! empty( $_POST['action'] )
			&& in_array( $_POST['action'], array( 'tasty_recipes_modify_recipe', 'tasty_recipes_parse_shortcode' ), true ) ) {
			$prefixes[] = \Tasty_Recipes\Shortcodes::RECIPE_SHORTCODE;
		}
		return $prefixes;
	}

	/**
	 * Enqueues our templates for Thrive Editor.
	 */
	public static function action_tve_editor_print_footer_scripts() {
		wp_just_in_time_script_localization();
		add_action(
			'wp_footer',
			function() {
				\Tasty_Recipes\Assets::action_admin_footer_render_template();
			}
		);
	}

}
