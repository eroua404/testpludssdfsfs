<?php
/**
 * Plugin Name:     Tasty Recipes
 * Plugin URI:      https://www.wptasty.com/tasty-recipes
 * Description:     Tasty Recipes is the easiest way to publish recipes on your WordPress blog.
 * Author:          WP Tasty
 * Author URI:      https://www.wptasty.com
 * Text Domain:     tasty-recipes
 * Domain Path:     /languages
 * Version:         3.7.3
 *
 * @package         Tasty_Recipes
 */

define( 'TASTY_RECIPES_PLUGIN_VERSION', '3.7.3' );
define( 'TASTY_RECIPES_PLUGIN_FILE', __FILE__ );

if ( ! defined( 'TASTY_RECIPES_NUTRIFOX_DOMAIN' ) ) {
	define( 'TASTY_RECIPES_NUTRIFOX_DOMAIN', 'nutrifox.com' );
}

/**
 * Base controller class for the plugin.
 */
class Tasty_Recipes {

	/**
	 * Store of recipe JSON data for current view.
	 *
	 * Used to share state between Shortcode and Admin classes.
	 *
	 * @var array
	 */
	public $recipe_json = array();

	/**
	 * Singleton instance for this class.
	 *
	 * @var object
	 */
	private static $instance;

	/**
	 * Option name for customizations.
	 *
	 * @var string
	 */
	const CUSTOMIZATION_OPTION = 'tasty_recipes_customization';

	/**
	 * Option name for the default author link.
	 *
	 * @var string
	 */
	const DEFAULT_AUTHOR_LINK_OPTION = 'tasty_recipes_default_author_link';

	/**
	 * Option name for the Instagram handle.
	 *
	 * @var string
	 */
	const INSTAGRAM_HANDLE_OPTION = 'tasty_recipes_instagram_handle';

	/**
	 * Option name for the Instagram tag.
	 *
	 * @var string
	 */
	const INSTAGRAM_HASHTAG_OPTION = 'tasty_recipes_instagram_tag';

	/**
	 * Option name for the license key.
	 *
	 * @var string
	 */
	const LICENSE_KEY_OPTION = 'tasty_recipes_license_key';

	/**
	 * Option name for the ShareASale affiliate ID.
	 *
	 * @var string
	 */
	const SHAREASALE_OPTION = 'tasty_recipes_shareasale';

	/**
	 * Option name for plugin activation state.
	 *
	 * @var string
	 */
	const PLUGIN_ACTIVATION_OPTION = 'tasty_recipes_do_activation_redirect';

	/**
	 * Option name for the template.
	 *
	 * @var string
	 */
	const TEMPLATE_OPTION = 'tasty_recipes_template';

	/**
	 * Option name for the quick links.
	 *
	 * @var string
	 */
	const QUICK_LINKS_OPTION = 'tasty_recipes_quick_links';

	/**
	 * Option name for the card buttons.
	 *
	 * @var string
	 */
	const CARD_BUTTONS_OPTION = 'tasty_recipes_card_buttons';

	/**
	 * Option name for the unit conversion option.
	 *
	 * @var string
	 */
	const UNIT_CONVERSION_OPTION = 'tasty_recipes_unit_conversion';

	/**
	 * Option name for the automatic unit conversion option.
	 *
	 * @var string
	 */
	const AUTOMATIC_UNIT_CONVERSION_OPTION = 'tasty_recipes_automatic_unit_conversion';

	/**
	 * Option name for the ingredient checkboxes option.
	 *
	 * @var string
	 */
	const INGREDIENT_CHECKBOXES_OPTION = 'tasty_recipes_ingredient_checkboxes';

	/**
	 * Option name for the disable scaling option.
	 *
	 * @var string
	 */
	const DISABLE_SCALING_OPTION = 'tasty_recipes_disable_scaling';

	/**
	 * Option name for the copy to clipboard.
	 *
	 * @var string
	 */
	const COPY_TO_CLIPBOARD_OPTION = 'tasty_recipes_copy_to_clipboard';

	/**
	 * Instantiates and gets the singleton instance for the class.
	 *
	 * @return object
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new Tasty_Recipes;
			self::$instance->require_files();
			self::$instance->setup_actions();
			self::$instance->setup_filters();
		}
		return self::$instance;
	}

	/**
	 * Loads required plugin files and registers autoloader.
	 */
	private static function require_files() {

		require_once dirname( __FILE__ ) . '/functions.php';

		load_plugin_textdomain( 'tasty-recipes', false, basename( dirname( __FILE__ ) ) . '/languages' );

		/**
		 * Register the class autoloader
		 */
		spl_autoload_register(
			function( $class ) {
					$class = ltrim( $class, '\\' );
				if ( 0 !== stripos( $class, 'Tasty_Recipes\\' ) ) {
					return;
				}

				$parts = explode( '\\', $class );
				array_shift( $parts ); // Don't need "Tasty_Recipes".
				$last    = array_pop( $parts ); // File should be 'class-[...].php'.
				$last    = 'class-' . $last . '.php';
				$parts[] = $last;
				$file    = dirname( __FILE__ ) . '/inc/' . str_replace( '_', '-', strtolower( implode( '/', $parts ) ) );
				if ( file_exists( $file ) ) {
					require $file;
				}

					// Might be a trait.
				$file = str_replace( '/class-', '/trait-', $file );
				if ( file_exists( $file ) ) {
					require $file;
				}
			}
		);
	}

	/**
	 * Registry of actions used in the plugin.
	 */
	private function setup_actions() {
		// Bootstrap.
		add_action( 'init', array( 'Tasty_Recipes\Block_Editor', 'action_init_register' ) );
		add_action( 'init', array( 'Tasty_Recipes\Content_Model', 'action_init_register_cron_events' ) );
		add_action( 'tasty_recipes_process_thumbnails', array( 'Tasty_Recipes\Content_Model', 'action_tasty_recipes_process_thumbnails' ) );
		add_action( 'tasty_recipes_enrich_youtube_embeds', array( 'Tasty_Recipes\Content_Model', 'action_tasty_recipes_enrich_youtube_embeds' ) );
		add_action( 'tasty_recipes_apply_unit_conversion', array( 'Tasty_Recipes\Content_model', 'action_tasty_recipes_apply_unit_conversion' ) );
		add_action( 'init', array( 'Tasty_Recipes\Content_Model', 'action_init_register_post_types' ) );
		add_action( 'init', array( 'Tasty_Recipes\Content_Model', 'action_init_register_rewrite_rules' ) );
		add_action( 'init', array( 'Tasty_Recipes\Content_Model', 'action_init_register_oembed_providers' ) );
		add_action( 'init', array( 'Tasty_Recipes\Shortcodes', 'action_init_register_shortcode' ) );
		// Frontend.
		add_action( 'wp_print_styles', array( 'Tasty_Recipes\Assets', 'action_wp_print_styles' ) );
		add_action( 'body_class', array( 'Tasty_Recipes\Content_Model', 'filter_body_class' ) );
		add_action( 'wp_head', array( 'Tasty_Recipes\Distribution_Metadata', 'action_wp_head_google_schema' ) );
		add_action( 'wp_head', array( 'Tasty_Recipes\Distribution_Metadata', 'action_wp_head_noindex' ) );
		add_action( 'wp_head', array( 'Tasty_Recipes\Ratings', 'action_wp_head' ) );
		add_action( 'admin_head', array( 'Tasty_Recipes\Ratings', 'action_admin_head' ) );
		add_action( 'wpseo_robots', array( 'Tasty_Recipes\Distribution_Metadata', 'action_wpseo_robots' ) );
		add_filter( 'wpseo_schema_graph_pieces', array( 'Tasty_Recipes\Distribution_Metadata', 'filter_wpseo_schema_graph_pieces' ), 10, 2 );
		foreach ( array( 'wp_insert_comment', 'wp_update_comment', 'wp_set_comment_status' ) as $hook ) {
			add_action( $hook, array( 'Tasty_Recipes\Ratings', 'action_modify_comment_update_recipe_ratings' ) );
		}
		add_action( 'rest_insert_comment', array( 'Tasty_Recipes\Ratings', 'action_rest_insert_comment' ), 10, 2 );
		// Admin.
		add_action( 'admin_init', array( 'Tasty_Recipes\Admin', 'action_admin_init' ) );
		add_action( 'http_request_args', array( 'Tasty_Recipes\Admin', 'filter_http_request_args' ), 10, 2 );
		add_action( 'admin_menu', array( 'Tasty_Recipes\Admin', 'action_admin_menu' ) );
		add_action( 'admin_notices', array( 'Tasty_Recipes\Admin', 'action_admin_notices_license_key' ) );
		add_filter( 'manage_posts_columns', array( 'Tasty_Recipes\Admin', 'action_manage_posts_columns' ) );
		add_filter( 'manage_pages_columns', array( 'Tasty_Recipes\Admin', 'action_manage_posts_columns' ) );
		add_action( 'manage_posts_custom_column', array( 'Tasty_Recipes\Admin', 'action_manage_posts_custom_column' ), 10, 2 );
		add_action( 'manage_pages_custom_column', array( 'Tasty_Recipes\Admin', 'action_manage_posts_custom_column' ), 10, 2 );
		add_action( 'quick_edit_custom_box', array( 'Tasty_Recipes\Admin', 'action_quick_edit_custom_box' ) );
		add_filter( 'hidden_columns', array( 'Tasty_Recipes\Admin', 'filter_hidden_columns' ) );
		add_action( 'admin_enqueue_scripts', array( 'Tasty_Recipes\Assets', 'action_admin_enqueue_scripts' ) );
		add_action( 'wp_enqueue_editor', array( 'Tasty_Recipes\Assets', 'action_wp_enqueue_editor' ) );
		add_action( 'enqueue_block_editor_assets', array( 'Tasty_Recipes\Assets', 'action_enqueue_block_editor_assets' ) );
		add_action( 'admin_notices', array( 'Tasty_Recipes\Editor', 'action_admin_notices' ) );
		add_action( 'in_admin_header', array( 'Tasty_Recipes\Admin', 'action_in_admin_header' ) );
		add_action( 'after_wp_tiny_mce', array( 'Tasty_Recipes\Editor', 'action_after_wp_tiny_mce' ) );
		add_action( 'add_option_' . self::LICENSE_KEY_OPTION, array( 'Tasty_Recipes\Admin', 'action_update_option_register_license' ) );
		add_action( 'update_option_' . self::LICENSE_KEY_OPTION, array( 'Tasty_Recipes\Admin', 'action_update_option_register_license' ) );
		add_action( 'update_option_' . self::LICENSE_KEY_OPTION, array( 'Tasty_Recipes\Admin', 'action_update_option_clear_transient' ) );
		add_action( 'media_buttons', array( 'Tasty_Recipes\Editor', 'action_media_buttons' ) );
		add_action( 'wp_ajax_tasty_recipes_remove_license_key', array( 'Tasty_Recipes\Admin', 'handle_wp_ajax_remove_license_key' ) );
		add_action( 'wp_ajax_tasty_recipes_preview_recipe_card', array( 'Tasty_Recipes\Admin', 'handle_wp_ajax_preview_recipe_card' ) );
		add_action( 'wp_ajax_tasty_recipes_get_count', array( 'Tasty_Recipes\Admin', 'handle_wp_ajax_get_count' ) );
		add_action( 'wp_ajax_tasty_recipes_convert', array( 'Tasty_Recipes\Admin', 'handle_wp_ajax_convert' ) );
		add_action( 'wp_ajax_tasty_recipes_ignore_convert', array( 'Tasty_Recipes\Editor', 'handle_wp_ajax_ignore_convert' ) );
		add_action( 'wp_ajax_tasty_recipes_convert_recipe', array( 'Tasty_Recipes\Editor', 'handle_wp_ajax_convert_recipe' ) );
		add_action( 'wp_ajax_tasty_recipes_parse_shortcode', array( 'Tasty_Recipes\Editor', 'handle_wp_ajax_parse_shortcode' ) );
		add_action( 'wp_ajax_tasty_recipes_modify_recipe', array( 'Tasty_Recipes\Editor', 'handle_wp_ajax_modify_recipe' ) );
	}

	/**
	 * Registry of filters used in the plugin.
	 */
	private function setup_filters() {
		global $wp_embed;

		// Bootstrap.
		add_filter( 'rewrite_rules_array', array( 'Tasty_Recipes\Content_Model', 'filter_rewrite_rules_array' ) );

		add_filter( 'get_the_excerpt', array( 'Tasty_Recipes\Shortcodes', 'filter_get_the_excerpt_early' ), 1 );
		add_filter( 'get_the_excerpt', array( 'Tasty_Recipes\Shortcodes', 'filter_get_the_excerpt_late' ), 1000 );
		add_filter( 'the_content', array( 'Tasty_Recipes\Shortcodes', 'filter_the_content_late' ), 100 );
		add_filter( 'tasty_recipes_recipe_card_output', array( 'Tasty_Recipes\Shortcodes', 'filter_tasty_recipes_recipe_card_output' ), 10, 2 );

		// WordPress' standard text formatting filters.
		add_filter( 'tasty_recipes_the_title', 'wptexturize' );
		add_filter( 'tasty_recipes_the_title', 'convert_chars' );
		add_filter( 'tasty_recipes_the_title', 'trim' );
		add_filter( 'tasty_recipes_the_content', array( $wp_embed, 'autoembed' ), 8 );
		add_filter( 'tasty_recipes_the_content', array( 'Tasty_Recipes\Content_Model', 'autoembed_advanced' ), 8 );
		add_filter( 'tasty_recipes_the_content', 'wptexturize' );
		add_filter( 'tasty_recipes_the_content', 'convert_smilies', 20 );
		add_filter( 'tasty_recipes_the_content', 'wpautop' );
		add_filter( 'tasty_recipes_the_content', 'shortcode_unautop' );
		add_filter( 'tasty_recipes_the_content', 'prepend_attachment' );
		// Responsive images for WordPress 4.4 to 5.4.
		if ( function_exists( 'wp_make_content_images_responsive' ) && ! function_exists( 'wp_filter_content_tags' ) ) {
			add_filter( 'tasty_recipes_the_content', 'wp_make_content_images_responsive' );
		}
		// Lazyloading images for WP 5.5.
		if ( function_exists( 'wp_filter_content_tags' ) ) {
			add_filter( 'tasty_recipes_the_content', 'wp_filter_content_tags' );
		}

		// Plugin-specific filters.
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( 'Tasty_Recipes\Admin', 'filter_plugin_action_links' ) );
		add_filter( 'teeny_mce_buttons', array( 'Tasty_Recipes\Admin', 'filter_teeny_mce_buttons' ), 10, 2 );
		add_filter( 'teeny_mce_buttons', array( 'Tasty_Recipes\Assets', 'filter_teeny_mce_buttons' ), 10, 2 );
		add_filter( 'teeny_mce_before_init', array( 'Tasty_Recipes\Assets', 'filter_teeny_mce_before_init' ), 10, 2 );
		add_filter( 'update_post_metadata', array( 'Tasty_Recipes\Content_Model', 'filter_update_post_metadata_nutrifox_id' ), 10, 4 );
		add_filter( 'update_post_metadata', array( 'Tasty_Recipes\Content_Model', 'filter_update_post_metadata_ingredients' ), 10, 4 );
		add_filter( 'update_post_metadata', array( 'Tasty_Recipes\Content_Model', 'filter_update_post_metadata_video_url' ), 10, 4 );
		add_filter( 'update_post_metadata', array( 'Tasty_Recipes\Content_Model', 'filter_update_post_metadata_thumbnail_id' ), 10, 4 );
		add_filter( 'template_include', array( 'Tasty_Recipes\Content_Model', 'filter_template_include' ), 1000 );
		add_filter( 'preprocess_comment', array( 'Tasty_Recipes\Ratings', 'filter_preprocess_comment' ) );
		add_filter( 'comment_form_field_comment', array( 'Tasty_Recipes\Ratings', 'filter_comment_form_field_comment' ) );
		add_filter( 'comment_text', array( 'Tasty_Recipes\Ratings', 'filter_comment_text' ), 10, 2 );

		// Integrations.
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::add_command( 'tasty-recipes', 'Tasty_Recipes\CLI' );
		}

		add_filter(
			'tasty_recipes_add_media_button',
			array(
				'Tasty_Recipes\Integrations\Elementor',
				'filter_tasty_recipes_add_media_button',
			),
			10,
			2
		);
		add_action(
			'elementor/controls/controls_registered',
			array(
				'Tasty_Recipes\Integrations\Elementor',
				'action_controls_registered',
			)
		);
		add_action(
			'elementor/widgets/widgets_registered',
			array(
				'Tasty_Recipes\Integrations\Elementor',
				'action_widgets_registered',
			)
		);
		add_action(
			'elementor/editor/before_enqueue_scripts',
			array(
				'Tasty_Recipes\Integrations\Elementor',
				'action_before_enqueue_scripts',
			)
		);
		add_action(
			'elementor/editor/footer',
			array(
				'Tasty_Recipes\Integrations\Elementor',
				'action_editor_footer',
			)
		);

		// Integrations.
		add_filter(
			'jetpack_content_options_featured_image_exclude_cpt',
			array(
				'Tasty_Recipes\Integrations\Jetpack',
				'filter_jetpack_content_options_featured_image_exclude_cpt',
			)
		);

		// Rank Math.
		add_action(
			'rank_math/admin/enqueue_scripts',
			array(
				'Tasty_Recipes\Integrations\Rank_Math',
				'action_admin_enqueue_scripts',
			)
		);

		// Thrive.
		add_filter(
			'thrive_theme_shortcode_prefixes',
			array(
				'Tasty_Recipes\Integrations\Thrive',
				'filter_thrive_theme_shortcode_prefixes',
			)
		);
		add_action(
			'tve_editor_print_footer_scripts',
			array(
				'Tasty_Recipes\Integrations\Thrive',
				'action_tve_editor_print_footer_scripts',
			)
		);

		add_filter(
			'wpdiscuz_after_comment_post',
			array(
				'Tasty_Recipes\Integrations\WpDiscuz',
				'action_wpdiscuz_after_comment_post',
			)
		);
	}

	/**
	 * Actions to perform when activating the plugin.
	 */
	public static function plugin_activation() {
		self::require_files();
		\Tasty_Recipes\Content_Model::action_init_register_rewrite_rules();
		flush_rewrite_rules();
		update_option( self::PLUGIN_ACTIVATION_OPTION, true );
	}

	/**
	 * Determine whether there's a recipe present in the post.
	 *
	 * @param integer $post_id ID for the post to inspect.
	 * @return boolean
	 */
	public static function has_recipe( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return false;
		}
		if ( false !== stripos( $post->post_content, '[' . Tasty_Recipes\Shortcodes::RECIPE_SHORTCODE ) ) {
			return true;
		}
		if ( false !== stripos( $post->post_content, '<!-- wp:' . Tasty_Recipes\Block_Editor::RECIPE_BLOCK_TYPE . ' ' ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Get the recipe ids embedded within a given post.
	 *
	 * @param integer $post_id ID for the post to parse.
	 * @param array   $options Any options to configure the bheavior.
	 * @return array
	 */
	public static function get_recipe_ids_for_post( $post_id, $options = array() ) {
		$post = get_post( $post_id );
		if ( ! $post_id || ! $post ) {
			return array();
		}
		return self::get_recipe_ids_from_content( $post->post_content, $options );
	}

	/**
	 * Get the recipe ids embedded within a given string.
	 *
	 * @param string $content Content to search for recipe ids.
	 * @param array  $options Configure return value behavior.
	 * @return array
	 */
	public static function get_recipe_ids_from_content( $content, $options = array() ) {

		$defaults = array(
			'disable-json-ld' => null,
			'full-result'     => false,
		);
		$options  = array_merge( $defaults, $options );

		$recipes = array();
		if ( preg_match_all( '#\[' . Tasty_Recipes\Shortcodes::RECIPE_SHORTCODE . '(.+)\]#Us', $content, $matches ) ) {
			foreach ( $matches[0] as $i => $shortcode ) {
				$atts = shortcode_parse_atts( $matches[1][ $i ] );
				if ( empty( $atts['id'] ) ) {
					continue;
				}

				if ( false === $options['disable-json-ld']
					&& in_array( 'disable-json-ld', $atts, true ) ) {
					continue;
				}

				if ( ! empty( $options['full-result'] ) ) {
					$recipes[] = $atts;
				} else {
					$recipes[] = (int) $atts['id'];
				}
			}
		}
		if ( function_exists( 'parse_blocks' ) ) {
			self::recursively_search_blocks( parse_blocks( $content ), $options, $recipes );
		}
		return $recipes;
	}

	/**
	 * Parses blocks recursively for recipe IDs.
	 *
	 * @param array $blocks  Blocks to inspect.
	 * @param array $options Inspection options.
	 * @param array $recipes Array of recipe ids.
	 */
	public static function recursively_search_blocks( $blocks, $options, &$recipes ) {
		foreach ( $blocks as $block ) {
			if ( is_object( $block ) && ! empty( $block->blockName )
				&& Tasty_Recipes\Block_Editor::RECIPE_BLOCK_TYPE === $block->blockName ) {
				$disable_json = isset( $block->attrs->disableJSON ) ? (bool) $block->attrs->disableJSON : false;
				if ( false === $options['disable-json-ld'] && $disable_json ) {
					continue;
				}
				if ( ! empty( $block->attrs->id ) ) {
					if ( ! empty( $options['full-result'] ) ) {
						$recipes[] = (array) $block->attrs;
					} else {
						$recipes[] = (int) $block->attrs->id;
					}
				}
			}
			if ( is_array( $block ) && ! empty( $block['blockName'] )
				&& Tasty_Recipes\Block_Editor::RECIPE_BLOCK_TYPE === $block['blockName'] ) {
				$disable_json = isset( $block['attrs']['disableJSON'] ) ? (bool) $block['attrs']['disableJSON'] : false;
				if ( false === $options['disable-json-ld'] && $disable_json ) {
					continue;
				}
				if ( ! empty( $block['attrs']['id'] ) ) {
					if ( ! empty( $options['full-result'] ) ) {
						$recipes[] = (array) $block['attrs'];
					} else {
						$recipes[] = (int) $block['attrs']['id'];
					}
				}
			}
			if ( ! empty( $block['innerBlocks'] ) ) {
				self::recursively_search_blocks( $block['innerBlocks'], $options, $recipes );
			}
		}
	}

	/**
	 * Get a dictionary of converters supported by Tasty Recipes.
	 *
	 * @return array
	 */
	public static function get_converters() {
		return array(
			'cookbook'       => array(
				'class' => 'Tasty_Recipes\Converters\Cookbook',
				'label' => 'Cookbook',
			),
			'create'         => array(
				'class' => 'Tasty_Recipes\Converters\Mediavine_Create',
				'label' => 'Mediavine Create',
			),
			'easyrecipe'     => array(
				'class' => 'Tasty_Recipes\Converters\EasyRecipe',
				'label' => 'EasyRecipe',
			),
			'mealplannerpro' => array(
				'class' => 'Tasty_Recipes\Converters\MealPlannerPro',
				'label' => 'Meal Planner Pro',
			),
			'srp'            => array(
				'class' => 'Tasty_Recipes\Converters\Simple_Recipe_Pro',
				'label' => 'Simple Recipe Pro',
			),
			'wpcom'          => array(
				'class' => 'Tasty_Recipes\Converters\WPCom',
				'label' => 'WordPress.com',
			),
			'wprm'           => array(
				'class' => 'Tasty_Recipes\Converters\WP_Recipe_Maker',
				'label' => 'WP Recipe Maker',
			),
			'wpur'           => array(
				'class' => 'Tasty_Recipes\Converters\WP_Ultimate_Recipe',
				'label' => 'WP Ultimate Recipe',
			),
			'yummly'         => array(
				'class' => 'Tasty_Recipes\Converters\Yummly',
				'label' => 'Yummly',
			),
			'yumprint'       => array(
				'class' => 'Tasty_Recipes\Converters\YumPrint',
				'label' => 'YumPrint Recipe Card',
			),
			'ziplist'        => array(
				'class' => 'Tasty_Recipes\Converters\ZipList',
				'label' => 'ZipList (or Zip Recipes)',
			),
		);
	}

	/**
	 * Get the recipes embedded within a given post.
	 *
	 * @param integer $post_id ID for the post to inspect.
	 * @param array   $options Any options to pass through to the parser.
	 * @return array
	 */
	public static function get_recipes_for_post( $post_id, $options = array() ) {
		$recipes = array();
		foreach ( self::get_recipe_ids_for_post( $post_id, $options ) as $id ) {
			$recipe = Tasty_Recipes\Objects\Recipe::get_by_id( $id );
			if ( $recipe ) {
				$recipes[] = $recipe;
			}
		}
		return $recipes;
	}

	/**
	 * Gets the customization options for Tasty Recipes.
	 *
	 * @return array
	 */
	public static function get_customization_settings() {
		$default_description = '<p>' . esc_html__( "Share a photo and tag us — we can't wait to see what you've made!", 'tasty-recipes' ) . '</p>';
		$instagram_handle    = get_option( self::INSTAGRAM_HANDLE_OPTION );
		if ( $instagram_handle ) {
			$default_description = '<p>' . esc_html__( 'Tag', 'tasty-recipes' ) . ' <a href="' . esc_url( 'https://www.instagram.com/' . $instagram_handle ) . '" target="_blank" rel="noreferrer noopener">@' . esc_html( $instagram_handle ) . '</a> ' . esc_html__( 'on Instagram', 'tasty-recipes' );
			$instagram_hashtag   = get_option( self::INSTAGRAM_HASHTAG_OPTION );
			if ( $instagram_hashtag ) {
				$default_description .= ' ' . esc_html__( 'and hashtag it', 'tasty-recipes' ) . ' <a href="' . esc_url( 'https://www.instagram.com/explore/tags/' . $instagram_hashtag ) . '" target="_blank" rel="noreferrer noopener">#' . esc_html( $instagram_hashtag ) . '</a>';
			}
			$default_description .= '</p>';
		}

		$settings = array_merge(
			array(
				'primary_color'            => '',
				'secondary_color'          => '',
				'icon_color'               => '',
				'button_color'             => '',
				'detail_label_color'       => '',
				'detail_value_color'       => '',
				'h2_color'                 => '',
				'h2_transform'             => '',
				'h3_color'                 => '',
				'h3_transform'             => '',
				'body_color'               => '',
				'star_ratings_style'       => 'solid',
				'nutrifox_display_style'   => 'label',
				'footer_social_platform'   => 'instagram',
				'footer_icon_color'        => '',
				'footer_heading'           => esc_html__( 'Did you make this recipe?', 'tasty-recipes' ),
				'footer_heading_color'     => '',
				'footer_description'       => $default_description,
				'footer_description_color' => '',
			),
			get_option( self::CUSTOMIZATION_OPTION, array() )
		);

		$settings['footer_description'] = htmlspecialchars_decode( html_entity_decode( $settings['footer_description'], ENT_QUOTES ), ENT_QUOTES );
		return $settings;
	}

	/**
	 * Gets the card button settings.
	 *
	 * @param string $template Name of the template.
	 * @return array
	 */
	public static function get_card_button_settings( $template = null ) {
		$value = get_option( Tasty_Recipes::CARD_BUTTONS_OPTION );
		// Set defaults based on template.
		if ( empty( $value ) ) {
			if ( null === $template ) {
				$template = get_option( Tasty_Recipes::TEMPLATE_OPTION, '' );
			}
			if ( in_array( $template, array( 'bold', 'fresh' ), true ) ) {
				$value = array(
					'first'  => 'print',
					'second' => 'pin',
				);
			} else {
				$value = array(
					'first'  => 'print',
					'second' => '',
				);
			}
		}
		return $value;
	}

	/**
	 * Get a fully-qualified path to a template.
	 *
	 * @param string $template Template name.
	 * @return string
	 */
	public static function get_template_path( $template ) {
		$full_path = dirname( __FILE__ ) . '/templates/' . $template . '.php';
		return apply_filters( 'tasty_recipes_template_path', $full_path, $template );
	}

	/**
	 * Get a rendered template.
	 *
	 * @param string $template Fully-qualified template path.
	 * @param array  $vars     Variables to pass into the template.
	 * @return string
	 */
	public static function get_template_part( $template, $vars = array() ) {
		$full_path = self::get_template_path( $template );
		// Provided template may already be a full path.
		if ( ! file_exists( $full_path ) ) {
			$full_path = $template;
		}
		if ( ! file_exists( $full_path ) ) {
			return '';
		}

		ob_start();
		// @codingStandardsIgnoreStart
		if ( ! empty( $vars ) ) {
			extract( $vars );
		}
		// @codingStandardsIgnoreEnd
		include $full_path;
		return ob_get_clean();
	}


}

/**
 * Access the Tasty Recipes instance.
 *
 * @return Tasty_Recipes
 */
// @codingStandardsIgnoreStart
function Tasty_Recipes() {
// @codingStandardsIgnoreEnd
	return Tasty_Recipes::get_instance();
}
add_action( 'plugins_loaded', 'Tasty_Recipes' );

/**
 * Register the plugin activation hook
 */
register_activation_hook( __FILE__, array( 'Tasty_Recipes', 'plugin_activation' ) );
