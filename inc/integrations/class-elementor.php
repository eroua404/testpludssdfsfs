<?php
/**
 * Integrates Tasty Recipes with Elementor
 *
 * @package Tasty_Recipes
 */

namespace Tasty_Recipes\Integrations;

/**
 * Integrates Tasty Recipes with Elementor.
 */
class Elementor {

	/**
	 * Registers the controls.
	 */
	public static function action_controls_registered() {
		\Elementor\Plugin::instance()->controls_manager->register_control(
			'tasty-recipe-control',
			new \Tasty_Recipes\Integrations\Elementor\Recipe_Control()
		);
	}

	/**
	 * Registers the widgets.
	 */
	public static function action_widgets_registered() {
		\Elementor\Plugin::instance()->widgets_manager->register_widget_type(
			new \Tasty_Recipes\Integrations\Elementor\Recipe_Widget()
		);
	}

	/**
	 * Runs before scripts are enqueued.
	 */
	public static function action_before_enqueue_scripts() {
		// Registers our TinyMCE settings before Elementor noops TinyMCE.
		add_action(
			'print_default_editor_scripts',
			function() {
				ob_start();
				wp_editor(
					'',
					'tasty-recipes-editor',
					array(
						'teeny' => true,
					)
				);
				ob_get_clean();
			}
		);
	}

	/**
	 * Renders the editor modal template
	 */
	public static function action_editor_footer() {
		\Tasty_Recipes\Assets::action_admin_footer_render_template();
	}

	/**
	 * Disable the 'Add Recipe' button in Elementor's RTE.
	 *
	 * @param boolean $retval    Existing return value.
	 * @param string  $editor_id ID of the editor.
	 * @return boolean
	 */
	public static function filter_tasty_recipes_add_media_button( $retval, $editor_id ) {
		if ( 'elementorwpeditor' === $editor_id ) {
			return false;
		}
		return $retval;
	}

}
