<?php
/**
 * Manages block editor registration and configuration.
 *
 * @package Tasty_Recipes
 */

namespace Tasty_Recipes;

use \Tasty_Recipes\Objects\Recipe;

/**
 * Manages block editor registration and configuration.
 */
class Block_Editor {

	/**
	 * Block type name.
	 *
	 * @var string
	 */
	const RECIPE_BLOCK_TYPE = 'wp-tasty/tasty-recipe';

	/**
	 * Register the scripts and block type.
	 */
	public static function action_init_register() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}
		Assets::register_modal_editor_script();
		$time = filemtime( dirname( dirname( __FILE__ ) ) . '/assets/js/nutrifox-resize.js' );
		wp_register_script(
			'tasty-recipes-nutrifox-resize',
			plugins_url( 'assets/js/nutrifox-resize.js?r=' . (int) $time, dirname( __FILE__ ) )
		);
		$time = 0;
		if ( file_exists( dirname( dirname( __FILE__ ) ) . '/assets/dist/block-editor.build.js' ) ) {
			$time = filemtime( dirname( dirname( __FILE__ ) ) . '/assets/dist/block-editor.build.js' );
		}
		wp_register_script(
			'tasty-recipes-block-editor',
			plugins_url( 'assets/dist/block-editor.build.js?r=' . (int) $time, dirname( __FILE__ ) ),
			array(
				'tasty-recipes-editor-modal',
				'tasty-recipes-nutrifox-resize',
				'wp-blocks',
				'wp-block-editor',
				'wp-element',
				'wp-components',
				'wp-server-side-render',
				'wp-data',
				'wp-i18n',
			)
		);
		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'tasty-recipes-block-editor', 'tasty-recipes' );
		}
		register_block_type(
			self::RECIPE_BLOCK_TYPE,
			array(
				'attributes'      => array(
					'className'               => array(
						'type' => 'string',
					),
					'id'                      => array(
						'type' => 'number',
					),
					'lastUpdated'             => array(
						'type' => 'number',
					),
					'author_link'             => array(
						'type' => 'string',
					),
					'disableJSON'             => array(
						'type' => 'boolean',
					),
					'disable_unit_conversion' => array(
						'type' => 'boolean',
					),
					'disable_scaling'         => array(
						'type' => 'boolean',
					),
				),
				'editor_script'   => 'tasty-recipes-block-editor',
				'render_callback' => array( 'Tasty_Recipes\Shortcodes', 'render_tasty_recipe_shortcode' ),
			)
		);
	}

	/**
	 * Get the block for a given recipe.
	 *
	 * @param Recipe $recipe Recipe instance.
	 * @return string
	 */
	public static function get_block_for_recipe( Recipe $recipe ) {
		return '<!-- wp:wp-tasty/tasty-recipe {"id":' . $recipe->get_id() . ',"lastUpdated":' . time() . '} /-->';
	}

}
