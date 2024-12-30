<?php
/**
 * Registers all scripts and styles.
 *
 * @package Tasty_Recipes
 */

namespace Tasty_Recipes;

use Tasty_Recipes;
use Tasty_Recipes\Objects\Recipe;

/**
 * Registers all scripts and styles.
 */
class Assets {

	/**
	 * ID used for TinyMCE instance in modal.
	 *
	 * @var string
	 */
	private static $editor_id = 'tasty-recipes-editor';

	/**
	 * Enqueues relevant scripts in the admin.
	 */
	public static function action_admin_enqueue_scripts() {
		global $wpdb;

		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}

		if ( 'settings_page_tasty-recipes' === $screen->id ) {
			$time = filemtime( dirname( dirname( __FILE__ ) ) . '/assets/js/settings.js' );
			wp_enqueue_script( 'tasty-recipes-settings', plugins_url( 'assets/js/settings.js?r=' . (int) $time, dirname( __FILE__ ) ), array( 'jquery', 'wp-util' ) );
			wp_localize_script(
				'tasty-recipes-settings',
				'tastyRecipesSettings',
				array(
					'nonce'          => wp_create_nonce( Admin::NONCE_KEY ),
					'pluginUrl'      => plugins_url( '', __DIR__ ),
					'isNutrifoxUser' => $wpdb->get_var( "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key='nutrifox_id';" ),
				)
			);
			$time = filemtime( dirname( dirname( __FILE__ ) ) . '/assets/dist/settings.css' );
			wp_enqueue_style( 'tasty-recipes-settings', plugins_url( 'assets/dist/settings.css?r=' . (int) $time, dirname( __FILE__ ) ), array( 'editor-buttons' ) );
			$time = filemtime( dirname( dirname( __FILE__ ) ) . '/assets/dist/settings.build.js' );
			wp_enqueue_script( 'tasty-recipes-settings-v2', plugins_url( 'assets/dist/settings.build.js?r=' . (int) $time, dirname( __FILE__ ) ), array( 'wp-i18n', 'wp-util', 'wplink' ), null, true );
		}

		if ( 'edit' === $screen->base ) {
			self::register_modal_editor_script();
			wp_enqueue_media();
			$time = filemtime( dirname( dirname( __FILE__ ) ) . '/assets/js/manage-posts.js' );
			wp_enqueue_script(
				'tasty-recipes-manage-posts',
				plugins_url( 'assets/js/manage-posts.js?r=' . (int) $time, dirname( __FILE__ ) ),
				array(
					'jquery',
					'tasty-recipes-editor-modal',
				)
			);
			$script_data = array(
				'recipeDataStore' => array(),
			);
			foreach ( $GLOBALS['wp_query']->posts as $post ) {
				foreach ( Tasty_Recipes::get_recipes_for_post( $post->ID ) as $recipe ) {
					/**
					 * Permit modification of the recipe JSON before it's returned.
					 *
					 * @param array $recipe_json Existing recipe JSON blob.
					 */
					$recipe_json = apply_filters( 'tasty_recipes_shortcode_response_recipe_json', $recipe->to_json() );
					// These fields are stored without paragraph tags,
					// so they need to be added for TinyMCE compat.
					foreach ( array(
						'description',
						'ingredients',
						'instructions',
						'notes',
					) as $field ) {
						$recipe_json[ $field ] = wpautop( $recipe_json[ $field ] );
					}
					$script_data['recipeDataStore'][ $recipe->get_id() ] = $recipe_json;
				}
			}
			wp_localize_script( 'tasty-recipes-manage-posts', 'tastyRecipesManagePosts', $script_data );
			$time = filemtime( dirname( dirname( __FILE__ ) ) . '/assets/dist/editor.css' );
			wp_enqueue_style( 'tasty-recipes-editor', plugins_url( 'assets/dist/editor.css?r=' . (int) $time, dirname( __FILE__ ) ), array( 'editor-buttons' ) );
			/**
			 * Allows the default author name to be modified.
			 *
			 * @param string $default_author_name
			 */
			$default_author_name = apply_filters( 'tasty_recipes_default_author_name', wp_get_current_user() ? wp_get_current_user()->display_name : '' );
			wp_localize_script(
				'tasty-recipes-editor',
				'tastyRecipesEditor',
				array(
					'i18n'              => array(
						'frameTitle'  => esc_html__( 'Media Library', 'tasty-recipes' ),
						'frameButton' => esc_html__( 'Select Image', 'tasty-recipes' ),
					),
					'currentPostId'     => 0,
					'defaultAuthorName' => $default_author_name,
					'parseNonce'        => wp_create_nonce( 'tasty_recipes_parse_shortcode' ),
					'modifyNonce'       => wp_create_nonce( 'tasty_recipes_modify_recipe' ),
					'pluginURL'         => plugins_url( '', dirname( __FILE__ ) ),
				)
			);
			/**
			 * Allow Tasty Links to enqueue its scripts.
			 */
			do_action( 'tasty_recipes_enqueue_editor_scripts' );
			if ( ! did_action( 'admin_footer' ) && ! doing_action( 'admin_footer' ) ) {
				add_action( 'admin_footer', array( __CLASS__, 'action_admin_footer_render_template' ) );
			} else {
				self::action_admin_footer_render_template();
			}
		}
	}

	/**
	 * Enqueues relevant scripts when the editor is loaded.
	 */
	public static function action_wp_enqueue_editor() {

		if ( ! Editor::is_tasty_recipes_editor_view() ) {
			return;
		}

		self::register_modal_editor_script();
		$time = filemtime( dirname( dirname( __FILE__ ) ) . '/assets/js/editor.js' );
		wp_enqueue_script(
			'tasty-recipes-editor',
			plugins_url( 'assets/js/editor.js?r=' . (int) $time, dirname( __FILE__ ) ),
			array(
				'jquery',
				'mce-view',
				'tasty-recipes-editor-modal',
			)
		);
		$time = filemtime( dirname( dirname( __FILE__ ) ) . '/assets/dist/editor.css' );
		wp_enqueue_style( 'tasty-recipes-editor', plugins_url( 'assets/dist/editor.css?r=' . (int) $time, dirname( __FILE__ ) ) );
		wp_localize_script(
			'tasty-recipes-editor',
			'tastyRecipesEditor',
			array(
				'i18n'              => array(
					'frameTitle'  => esc_html__( 'Media Library', 'tasty-recipes' ),
					'frameButton' => esc_html__( 'Select Image', 'tasty-recipes' ),
				),
				'currentPostId'     => self::get_current_post_id(),
				'defaultAuthorName' => wp_get_current_user() ? wp_get_current_user()->display_name : '',
				'parseNonce'        => wp_create_nonce( 'tasty_recipes_parse_shortcode' ),
				'modifyNonce'       => wp_create_nonce( 'tasty_recipes_modify_recipe' ),
				'pluginURL'         => plugins_url( '', dirname( __FILE__ ) ),
			)
		);
		/**
		 * Allow Tasty Links to enqueue its scripts.
		 */
		do_action( 'tasty_recipes_enqueue_editor_scripts' );
		if ( ! did_action( 'admin_footer' ) && ! doing_action( 'admin_footer' ) ) {
			add_action( 'admin_footer', array( __CLASS__, 'action_admin_footer_render_template' ) );
		} else {
			self::action_admin_footer_render_template();
		}
	}

	/**
	 * Registers data we need client-side as a part of the initial page load.
	 */
	public static function action_enqueue_block_editor_assets() {
		$blocks_data = array(
			'recipeBlockTitle' => 'Tasty Recipe',
			'recipeDataStore'  => array(),
			'editorNotices'    => array(),
		);
		$post_id     = self::get_current_post_id();
		if ( $post_id ) {
			foreach ( Tasty_Recipes::get_recipes_for_post( $post_id ) as $recipe ) {
				/**
				 * Permit modification of the recipe JSON before it's returned.
				 *
				 * @param array $recipe_json Existing recipe JSON blob.
				 */
				$recipe_json = apply_filters( 'tasty_recipes_shortcode_response_recipe_json', $recipe->to_json() );
				// These fields are stored without paragraph tags,
				// so they need to be added for TinyMCE compat.
				foreach ( array(
					'description',
					'ingredients',
					'instructions',
					'notes',
				) as $field ) {
					$recipe_json[ $field ] = wpautop( $recipe_json[ $field ] );
				}
				$blocks_data['recipeDataStore'][ $recipe->get_id() ] = $recipe_json;
			}
			$blocks_data['editorNotices'] = Editor::get_converter_messages( $post_id );
		}
		wp_localize_script( 'tasty-recipes-block-editor', 'tastyRecipesBlockEditor', $blocks_data );
	}

	/**
	 * Register the modal editor script with its localization.
	 */
	public static function register_modal_editor_script() {

		$time = 0;
		if ( file_exists( dirname( dirname( __FILE__ ) ) . '/assets/dist/recipe-editor.build.js' ) ) {
			$time = filemtime( dirname( dirname( __FILE__ ) ) . '/assets/dist/recipe-editor.build.js' );
		}
		wp_register_script( 'tasty-recipes-recipe-editor', plugins_url( 'assets/dist/recipe-editor.build.js?r=' . (int) $time, dirname( __FILE__ ) ), array( 'wp-util', 'wplink' ) );

		$cooking_attributes = Recipe::get_cooking_attributes();
		unset( $cooking_attributes['additional_time_label'] );
		unset( $cooking_attributes['additional_time_value'] );
		// Add Keywords to this set of editable fields.
		$general_attributes             = Recipe::get_general_attributes();
		$cooking_attributes['keywords'] = $general_attributes['keywords'];

		$nutrition_attributes = Recipe::get_nutrition_attributes();
		$nutrition_attributes = array_reverse( $nutrition_attributes, true );
		// Add Nutrifox ID to the beginning of this set of attributes.
		$nutrition_attributes['nutrifox_id'] = $general_attributes['nutrifox_id'];
		$nutrition_attributes                = array_reverse( $nutrition_attributes, true );

		ob_start();
		do_action( 'tasty_recipes_editor_after_video_url' );
		$after_video_url_markup = ob_get_clean();

		wp_localize_script(
			'tasty-recipes-recipe-editor',
			'tastyRecipesRecipeEditorData',
			array(
				'pluginURL'              => plugins_url( '', dirname( __FILE__ ) ),
				'after_video_url_markup' => $after_video_url_markup,
				'labels'                 => array(
					'select_image'      => esc_html__( 'Select Image', 'tasty-recipes' ),
					'details_heading'   => esc_html__( 'Details', 'tasty-recipes' ),
					'video_url_heading' => esc_html__( 'Video URL', 'tasty-recipes' ),
					'nutrition_heading' => esc_html__( 'Nutrition', 'tasty-recipes' ),
					'additional_time'   => array(
						'add_button_text'     => esc_html__( '+ Time', 'tasty-recipes' ),
						'remove_button_text'  => esc_html__( '- Time', 'tasty-recipes' ),
						'add_button_title'    => esc_html__( 'Add an additional time field', 'tasty-recipes' ),
						'remove_button_title' => esc_html__( 'Remove additional time field', 'tasty-recipes' ),
						'label_placeholder'   => esc_html__( 'Additional Time Label', 'tasty-recipes' ),
						'value_placeholder'   => esc_html__( 'Additional Time Value', 'tasty-recipes' ),
					),
				),
				'attributes'             => array(
					'general'   => $general_attributes,
					'cooking'   => $cooking_attributes,
					'nutrition' => $nutrition_attributes,
				),
				'video_settings'         => array(
					'label'    => esc_html__( 'Video Settings: ', 'tasty-recipes' ),
					'settings' => array(
						'autoplay'      => esc_html__( 'Autoplay', 'tasty-recipes' ),
						'mute'          => esc_html__( 'Mute', 'tasty-recipes' ),
						'loop'          => esc_html__( 'Loop', 'tasty-recipes' ),
						'hide-controls' => esc_html__( 'Hide Controls', 'tasty-recipes' ),
					),
				),
			)
		);
		$time = filemtime( dirname( dirname( __FILE__ ) ) . '/assets/js/editor-modal.js' );
		wp_register_script( 'tasty-recipes-editor-modal', plugins_url( 'assets/js/editor-modal.js?r=' . (int) $time, dirname( __FILE__ ) ), array( 'jquery', 'tasty-recipes-recipe-editor' ) );
		/**
		 * Allows the default author name to be modified.
		 *
		 * @param string $default_author_name
		 */
		$default_author_name = apply_filters( 'tasty_recipes_default_author_name', wp_get_current_user() ? wp_get_current_user()->display_name : '' );
		wp_localize_script(
			'tasty-recipes-editor-modal',
			'tastyRecipesEditorModalData',
			array(
				'i18n'              => array(
					'frameTitle'  => esc_html__( 'Media Library', 'tasty-recipes' ),
					'frameButton' => esc_html__( 'Select Image', 'tasty-recipes' ),
				),
				'currentPostId'     => self::get_current_post_id(),
				'defaultAuthorName' => $default_author_name,
				'modifyNonce'       => wp_create_nonce( 'tasty_recipes_modify_recipe' ),
				'parseNonce'        => wp_create_nonce( 'tasty_recipes_parse_shortcode' ),
				'pluginURL'         => plugins_url( '', dirname( __FILE__ ) ),
			)
		);
	}

	/**
	 * Stomp on all registered styles in the print view for a recipe.
	 */
	public static function action_wp_print_styles() {
		$recipe_id = (int) get_query_var( Content_Model::get_print_query_var() );
		if ( is_singular() && $recipe_id ) {
			$recipe_ids = Tasty_Recipes::get_recipe_ids_for_post( get_queried_object_id() );
			if ( in_array( $recipe_id, $recipe_ids, true ) ) {
				show_admin_bar( false );
				remove_action( 'wp_head', '_admin_bar_bump_cb' );
				$time = filemtime( dirname( dirname( __FILE__ ) ) . '/assets/dist/print.css' );
				wp_enqueue_style( 'tasty-recipes-print', plugins_url( 'assets/dist/print.css?r=' . (int) $time, dirname( __FILE__ ) ) );
			}
		}

	}

	/**
	 * Prints the recipe editor modal template, but only once.
	 */
	public static function action_admin_footer_render_template() {
		static $rendered_once;
		if ( isset( $rendered_once ) ) {
			return;
		}
		$rendered_once = true;
		// We don't actually care about rendering the textarea; we just want
		// the settings registered in an accessible way.
		ob_start();
		wp_editor(
			'',
			self::$editor_id,
			array(
				'teeny' => true,
			)
		);
		ob_get_clean();
		echo Tasty_Recipes::get_template_part( 'recipe-editor' );
	}

	/**
	 * Sets the TinyMCE editor buttons for our editor instance.
	 *
	 * @param array  $buttons   Existing TinyMCE buttons.
	 * @param string $editor_id ID for the editor instance.
	 * @return array
	 */
	public static function filter_teeny_mce_buttons( $buttons, $editor_id ) {
		if ( self::$editor_id !== $editor_id ) {
			return $buttons;
		}
		return array( 'tr_heading', 'bold', 'italic', 'underline', 'bullist', 'numlist', 'link', 'unlink', 'tr_image', 'tr_video', 'removeformat', 'fullscreen' );
	}

	/**
	 * Filters TinyMCE registration to include our custom TinyMCE plugins.
	 *
	 * @param array  $mce_init  Existing registration details.
	 * @param string $editor_id ID for the editor instance.
	 * @return array
	 */
	public static function filter_teeny_mce_before_init( $mce_init, $editor_id ) {
		if ( self::$editor_id !== $editor_id ) {
			return $mce_init;
		}
		$mce_init['plugins'] .= ',tr_heading,tr_image,tr_video';
		$external_plugins     = array();
		if ( isset( $mce_init['external_plugins'] ) ) {
			if ( is_string( $mce_init['external_plugins'] ) ) {
				$decoded_plugins  = json_decode( $mce_init['external_plugins'], true );
				$external_plugins = $decoded_plugins ? $decoded_plugins : array();
			} elseif ( is_array( $mce_init['external_plugins'] ) ) {
				$external_plugins = $mce_init['external_plugins'];
			}
		}
		$external_plugins['tr_heading'] = plugins_url(
			'assets/js/tinymce-tr-heading.js?v=' . TASTY_RECIPES_PLUGIN_VERSION,
			dirname( __FILE__ )
		);
		$external_plugins['tr_image']   = plugins_url(
			'assets/js/tinymce-tr-image.js?v=' . TASTY_RECIPES_PLUGIN_VERSION,
			dirname( __FILE__ )
		);
		$external_plugins['tr_video']   = plugins_url(
			'assets/js/tinymce-tr-video.js?v=' . TASTY_RECIPES_PLUGIN_VERSION,
			dirname( __FILE__ )
		);
		$mce_init['external_plugins']   = function_exists( 'wp_json_encode' ) ? wp_json_encode( $external_plugins ) : json_encode( $external_plugins );
		return $mce_init;
	}

	/**
	 * Gets the current post ID from query args when global $post isn't set.
	 *
	 * @return integer
	 */
	private static function get_current_post_id() {
		global $post;

		if ( $post ) {
			return $post->ID;
		}

		if ( ! empty( $_GET['post'] ) ) {
			return (int) $_GET['post'];
		}
		return 0;
	}

}
