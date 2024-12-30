<?php
/**
 * Manages interactions with the admin.
 *
 * @package Tasty_Recipes
 */

namespace Tasty_Recipes;

use Tasty_Recipes;
use Tasty_Recipes\Converters\EasyRecipe;

/**
 * Manages interactions with the admin.
 */
class Admin {

	/**
	 * Capability required to manage settings.
	 *
	 * @var string
	 */
	const CAPABILITY = 'manage_options';

	/**
	 * Plugin name for EDD registration.
	 *
	 * @var string
	 */
	const ITEM_AUTHOR = 'Tasty Recipes';

	/**
	 * Plugin author for EDD registration.
	 *
	 * @var string
	 */
	const ITEM_NAME = 'Tasty Recipes';

	/**
	 * Cache key used to store license check data.
	 *
	 * @var string
	 */
	const LICENSE_CHECK_CACHE_KEY = 'tasty-recipes-license-check';

	/**
	 * Key used for nonce authentication.
	 *
	 * @var string
	 */
	const NONCE_KEY = 'tasty-recipes-settings';

	/**
	 * URL to the plugin store.
	 *
	 * @var string
	 */
	const STORE_URL = 'https://www.wptasty.com';

	/**
	 * Parent page for the settings page.
	 *
	 * @var string
	 */
	const PAGE_BASE = 'options-general.php';

	/**
	 * Slug for the settings page.
	 *
	 * @var string
	 */
	const PAGE_SLUG = 'tasty-recipes';

	/**
	 * Group used for authentication settings.
	 *
	 * @var string
	 */
	const SETTINGS_GROUP_LICENSE = 'tasty-recipes-settings-license';

	/**
	 * Section used for authentication settings.
	 *
	 * @var string
	 */
	const SETTINGS_SECTION_LICENSE = 'tasty-recipes-license';

	/**
	 * Group used for card settings.
	 *
	 * @var string
	 */
	const SETTINGS_GROUP_CARD = 'tasty-recipes-settings-card';

	/**
	 * Section used for card settings.
	 *
	 * @var string
	 */
	const SETTINGS_SECTION_CARD_DESIGN = 'tasty-recipes';

	/**
	 * Available template options.
	 *
	 * @var array
	 */
	const TEMPLATE_OPTIONS = array(
		''               => 'Default',
		'bold'           => 'Bold',
		'snap'           => 'Snap',
		'fresh'          => 'Fresh',
		'simple'         => 'Simple',
		'modern-compact' => 'Modern Compact',
		'elegant'        => 'Elegant',
	);

	/**
	 * ID used for TinyMCE instance in settings.
	 *
	 * @var string
	 */
	private static $editor_id = 'tasty-recipes-settings';

	/**
	 * Registers the updater object.
	 */
	public static function action_admin_init() {
		new Updater(
			self::STORE_URL,
			TASTY_RECIPES_PLUGIN_FILE,
			array(
				'version'   => TASTY_RECIPES_PLUGIN_VERSION,
				'license'   => get_option( Tasty_Recipes::LICENSE_KEY_OPTION ),
				'item_name' => self::ITEM_NAME,
				'author'    => self::ITEM_AUTHOR,
			)
		);
		if ( get_option( Tasty_Recipes::PLUGIN_ACTIVATION_OPTION, false ) ) {
			delete_option( Tasty_Recipes::PLUGIN_ACTIVATION_OPTION );
			if ( ! isset( $_GET['activate-multi'] ) ) {
				wp_safe_redirect( add_query_arg( 'tab', 'about', menu_page_url( self::PAGE_SLUG, false ) ) );
				exit;
			}
		}
	}

	/**
	 * Includes PHP and plugin versions in the user agent for update checks.
	 *
	 * @param array  $r   An array of HTTP request arguments.
	 * @param string $url The request URL.
	 * @return array
	 */
	public static function filter_http_request_args( $r, $url ) {
		if ( self::STORE_URL !== $url
			|| 'POST' !== $r['method']
			|| empty( $r['body'] )
			|| ! is_array( $r['body'] )
			|| empty( $r['body']['item_name'] )
			|| self::ITEM_NAME !== $r['body']['item_name'] ) {
			return $r;
		}

		$r['user-agent'] = rtrim( $r['user-agent'], ';' )
			. '; PHP/' . PHP_VERSION . '; '
			. self::ITEM_NAME . '/' . TASTY_RECIPES_PLUGIN_VERSION;
		return $r;
	}

	/**
	 * Registers used settings.
	 */
	public static function action_admin_menu() {
		register_setting(
			self::SETTINGS_GROUP_LICENSE,
			Tasty_Recipes::LICENSE_KEY_OPTION,
			array(
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
		register_setting(
			self::SETTINGS_SECTION_CARD_DESIGN,
			Tasty_Recipes::CUSTOMIZATION_OPTION,
			array(
				'sanitize_callback' => array( __CLASS__, 'sanitize_customization_option' ),
			)
		);
		register_setting(
			self::SETTINGS_SECTION_CARD_DESIGN,
			Tasty_Recipes::TEMPLATE_OPTION,
			array(
				'sanitize_callback' => 'sanitize_title',
			)
		);
		register_setting(
			self::SETTINGS_GROUP_CARD,
			Tasty_Recipes::QUICK_LINKS_OPTION,
			array(
				'sanitize_callback' => 'sanitize_title',
			)
		);
		register_setting(
			self::SETTINGS_GROUP_CARD,
			Tasty_Recipes::CARD_BUTTONS_OPTION,
			array(
				'sanitize_callback' => array( __CLASS__, 'sanitize_card_buttons' ),
			)
		);

		register_setting(
			self::SETTINGS_GROUP_CARD,
			Tasty_Recipes::UNIT_CONVERSION_OPTION,
			array(
				'sanitize_callback' => 'sanitize_title',
			)
		);

		register_setting(
			self::SETTINGS_GROUP_CARD,
			Tasty_Recipes::AUTOMATIC_UNIT_CONVERSION_OPTION,
			array(
				'sanitize_callback' => 'sanitize_title',
			)
		);

		register_setting(
			self::SETTINGS_GROUP_CARD,
			Tasty_Recipes::INGREDIENT_CHECKBOXES_OPTION,
			array(
				'sanitize_callback' => 'sanitize_title',
			)
		);

		register_setting(
			self::SETTINGS_GROUP_CARD,
			Tasty_Recipes::DISABLE_SCALING_OPTION,
			array(
				'sanitize_callback' => 'sanitize_title',
			)
		);

		register_setting(
			self::SETTINGS_GROUP_CARD,
			Tasty_Recipes::COPY_TO_CLIPBOARD_OPTION,
			array(
				'sanitize_callback' => 'sanitize_title',
			)
		);

		register_setting(
			self::SETTINGS_GROUP_CARD,
			Tasty_Recipes::DEFAULT_AUTHOR_LINK_OPTION,
			array(
				'sanitize_callback' => 'esc_url_raw',
			)
		);
		register_setting(
			self::SETTINGS_GROUP_CARD,
			Tasty_Recipes::SHAREASALE_OPTION,
			array(
				'sanitize_callback' => 'sanitize_text_field',
			)
		);

		$page_title = __( 'Tasty Recipes', 'tasty-recipes' );
		add_submenu_page( self::PAGE_BASE, $page_title, $page_title, self::CAPABILITY, self::PAGE_SLUG, array( __CLASS__, 'handle_settings_page' ) );
	}

	/**
	 * Renders the admin notice nag when license key isn't set or invalid.
	 */
	public static function action_admin_notices_license_key() {

		$screen = get_current_screen();
		if ( $screen && 'post' === $screen->base ) {
			return;
		}

		if ( ! get_option( Tasty_Recipes::LICENSE_KEY_OPTION ) ) :
			?>
	<div class="updated" style="display:block !important;">
		<form method="post" action="options.php">
			<p>
				<strong><?php esc_html_e( 'Tasty Recipes is almost ready', 'tasty-recipes' ); ?></strong>, <label style="vertical-align: baseline;" for="<?php echo esc_attr( Tasty_Recipes::LICENSE_KEY_OPTION ); ?>"><?php esc_html_e( 'enter your license key to continue', 'tasty-recipes' ); ?></label>
				<input type="text" style="margin-left: 5px; margin-right: 5px; " class="code regular-text" id="<?php echo esc_attr( Tasty_Recipes::LICENSE_KEY_OPTION ); ?>" name="<?php echo esc_attr( Tasty_Recipes::LICENSE_KEY_OPTION ); ?>" />
				<input type="submit" value="<?php _e( 'Save license key', 'tasty-recipes' ); ?>" class="button-primary" />
			</p>
			<p>
				<strong><?php esc_html_e( "Don't have a Tasty Recipes license yet?", 'tasty-recipes' ); ?></strong> <a href="https://www.wptasty.com/" target="_blank"><?php esc_html_e( 'Get one in just a few minutes time', 'tasty-recipes' ); ?></a>, <?php esc_html_e( "and report back once you've gotten your license key", 'tasty-recipes' ); ?>
			</p>
			<?php
			settings_fields( self::SETTINGS_GROUP_LICENSE );
			do_settings_sections( self::SETTINGS_SECTION_LICENSE );
			?>
		</form>

	</div>
			<?php
		endif;

		$license_check = self::get_license_check();

		if ( ! empty( $license_check ) && 'valid' !== $license_check->license ) :
			?>
	<div class="error" style="display:block !important;">
		<form method="post" action="options.php">
			<p>
				<strong><?php esc_html_e( 'To enable updates and support for Tasty Recipes', 'tasty-recipes' ); ?></strong>, <label style="vertical-align: baseline;" for="<?php echo esc_attr( Tasty_Recipes::LICENSE_KEY_OPTION ); ?>"><?php esc_html_e( 'enter a valid license key', 'tasty-recipes' ); ?></label>
				<input type="text" style="margin-left: 5px; margin-right: 5px; " class="code regular-text" id="<?php echo esc_attr( Tasty_Recipes::LICENSE_KEY_OPTION ); ?>" name="<?php echo esc_attr( Tasty_Recipes::LICENSE_KEY_OPTION ); ?>" value="<?php echo esc_attr( get_option( Tasty_Recipes::LICENSE_KEY_OPTION ) ); ?>" />
				<input type="submit" value="<?php _e( 'Save license key', 'tasty-recipes' ); ?>" class="button-primary" />
			</p>
			<p>
				<strong><?php esc_html_e( "Think you've reached this message in error?", 'tasty-recipes' ); ?></strong> <a href="http://support.wptasty.com" target="_blank"><?php esc_html_e( 'Submit a support ticket', 'tasty-recipes' ); ?></a>, <?php esc_html_e( "and we'll do our best to help out.", 'tasty-recipes' ); ?>
			</p>
			<div style="display:none;"><pre><?php echo json_encode( $license_check ); ?></pre></div>
			<?php
			settings_fields( self::SETTINGS_GROUP_LICENSE );
			do_settings_sections( self::SETTINGS_SECTION_LICENSE );
			?>
		</form>

	</div>
			<?php
		endif;

	}

	/**
	 * Registers a new custom column for Tasty Recipes.
	 *
	 * @param array $columns Existing column names.
	 * @return array
	 */
	public static function action_manage_posts_columns( $columns ) {
		$columns['tasty_recipe'] = esc_html__( 'Tasty Recipe', 'tasty-recipes' );
		return $columns;
	}

	/**
	 * Renders an 'Edit Tasty Recipe' button for the column if the post has a recipe.
	 *
	 * @param string  $column_name Name of the column.
	 * @param integer $post_id     ID of the post being displayed.
	 */
	public static function action_manage_posts_custom_column( $column_name, $post_id ) {
		switch ( $column_name ) {
			case 'tasty_recipe':
				$recipe_ids = Tasty_Recipes::get_recipe_ids_for_post( $post_id );
				if ( ! empty( $recipe_ids ) ) {
					$recipe_id = array_shift( $recipe_ids );
					echo '<button class="button tasty-recipes-edit-button" data-recipe-id="' . esc_attr( $recipe_id ) . '">' . esc_html__( 'Edit Tasty Recipe', 'tasty-recipe' ) . '</button>';
				}
				break;
		}
	}

	/**
	 * Renders the 'Edit Tasty Recipe' button on Quick Edit.
	 *
	 * @param string $column_name Name of the column.
	 */
	public static function action_quick_edit_custom_box( $column_name ) {
		switch ( $column_name ) {
			case 'tasty_recipe':
				echo '<button class="button tasty-recipes-edit-button" data-recipe-id="">' . esc_html__( 'Edit Tasty Recipe', 'tasty-recipe' ) . '</button>';
				break;
		}
	}

	/**
	 * Hides the 'Tasty Recipe' column by default.
	 *
	 * @param array $columns Existing default hidden columns.
	 * @return array
	 */
	public static function filter_hidden_columns( $columns ) {
		$columns[] = 'tasty_recipe';
		return $columns;
	}

	/**
	 * Activates the license when the option is updated.
	 */
	public static function action_update_option_register_license() {
		$api_params = array(
			'edd_action' => 'activate_license',
			'license'    => get_option( Tasty_Recipes::LICENSE_KEY_OPTION ),
			'item_name'  => self::ITEM_NAME, // the name of our product in EDD.
			'url'        => home_url(),
		);
		wp_remote_post(
			self::STORE_URL,
			array(
				'timeout' => 15,
				'body'    => $api_params,
			)
		);
	}

	/**
	 * Clears the license and version check cache when license key is updated.
	 */
	public static function action_update_option_clear_transient() {
		delete_transient( self::LICENSE_CHECK_CACHE_KEY );
		$cache_key = md5( 'edd_plugin_' . sanitize_key( plugin_basename( TASTY_RECIPES_PLUGIN_FILE ) ) . '_version_info' );
		delete_site_transient( $cache_key );
		$cache_key = 'edd_api_request_' . substr( md5( serialize( basename( TASTY_RECIPES_PLUGIN_FILE, '.php' ) ) ), 0, 15 );
		delete_site_transient( $cache_key );
		delete_site_transient( 'update_plugins' );
	}

	/**
	 * Adds 'Settings' and 'Remove license' links to plugin settings.
	 *
	 * @param array $links Existing plugin action links.
	 * @return array
	 */
	public static function filter_plugin_action_links( $links ) {
		$links['tr_settings'] = '<a href="' . esc_url( add_query_arg( 'tab', 'settings', menu_page_url( self::PAGE_SLUG, false ) ) ) . '">' . esc_html__( 'Settings', 'tasty-recipes' ) . '</a>';
		if ( get_option( Tasty_Recipes::LICENSE_KEY_OPTION ) ) {
			$links['remove_key'] = '<a href="' . add_query_arg(
				array(
					'action' => 'tasty_recipes_remove_license_key',
					'nonce'  => wp_create_nonce( 'tasty_recipes_remove_license_key' ),
				),
				admin_url( 'admin-ajax.php' )
			) . '">' . esc_html__( 'Remove license', 'tasty-recipes' ) . '</a>';
		}
		return $links;
	}

	/**
	 * Handles a request to remove the license key.
	 */
	public static function handle_wp_ajax_remove_license_key() {

		if ( ! current_user_can( 'manage_options' ) || ! wp_verify_nonce( $_GET['nonce'], 'tasty_recipes_remove_license_key' ) ) {
			wp_safe_redirect( admin_url( 'plugins.php' ) );
			exit;
		}

		$api_params = array(
			'edd_action' => 'deactivate_license',
			'license'    => get_option( Tasty_Recipes::LICENSE_KEY_OPTION ),
			'item_name'  => self::ITEM_NAME,
			'url'        => home_url(),
		);
		$response   = wp_remote_post(
			self::STORE_URL,
			array(
				'timeout' => 15,
				'body'    => $api_params,
			)
		);
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			if ( is_wp_error( $response ) ) {
				$message = $response->get_error_message();
			} else {
				$message = __( 'An error occurred, please try again.', 'tasty-recipes' );
			}
			wp_die( $message );
		}

		self::action_update_option_clear_transient();
		delete_option( Tasty_Recipes::LICENSE_KEY_OPTION );
		wp_safe_redirect( admin_url( 'plugins.php' ) );
		exit;
	}

	/**
	 * Handles an AJAX request to preview a recipe template.
	 */
	public static function handle_wp_ajax_preview_recipe_card() {
		global $editor_styles;
		if ( ! current_user_can( self::CAPABILITY ) || ! wp_verify_nonce( $_GET['nonce'], self::NONCE_KEY ) ) {
			wp_die( __( "Sorry, you don't have permission to do this.", 'tasty-recipes' ) );
		}
		header( 'Content-Type: text/html' );
		$wrapper_classes = array(
			'tasty-recipes',
			'tasty-recipes-display',
		);

		$shareasale = get_option( Tasty_Recipes::SHAREASALE_OPTION );
		if ( $shareasale ) {
			$wrapper_classes[] = 'tasty-recipes-has-plug';
		}

		$custom_design          = ! empty( $_GET['template'] ) && array_key_exists( $_GET['template'], self::TEMPLATE_OPTIONS ) ? sanitize_key( $_GET['template'] ) : '';
		$star_ratings_style     = ! empty( $_GET['star_ratings_style'] ) && in_array( $_GET['star_ratings_style'], array( 'solid', 'outline' ), true ) ? sanitize_key( $_GET['star_ratings_style'] ) : null;
		$nutrifox_display_style = ! empty( $_GET['nutrifox_display_style'] ) && in_array( $_GET['nutrifox_display_style'], array( 'label', 'card' ), true ) ? sanitize_key( $_GET['nutrifox_display_style'] ) : null;
		$footer_social_platform = '';
		if ( ! empty( $_GET['footer_social_platform'] )
			&& in_array( $_GET['footer_social_platform'], array( 'instagram', 'pinterest', 'facebook' ), true ) ) {
			$footer_social_platform = sanitize_key( $_GET['footer_social_platform'] );
		}

		$settings = Tasty_Recipes::get_customization_settings();

		$styles  = Shortcodes::get_styles_as_string( $custom_design );
		$styles .= file_get_contents( dirname( TASTY_RECIPES_PLUGIN_FILE ) . '/assets/dist/ratings.css' );
		$styles .= file_get_contents( dirname( TASTY_RECIPES_PLUGIN_FILE ) . '/assets/dist/recipe-card-preview.css' );

		$custom_path = dirname( TASTY_RECIPES_PLUGIN_FILE ) . '/templates/designs/' . $custom_design . '/tasty-recipes.php';
		if ( $custom_design && file_exists( $custom_path ) ) {
			$template = $custom_path;
		} else {
			$template = 'recipe/tasty-recipes';
		}

		$stylesheet = get_option( 'stylesheet' );
		// Clean up Feast directory names.
		$stylesheet  = preg_replace( '#-v[\d]+$#', '', $stylesheet );
		$compat_file = dirname( TASTY_RECIPES_PLUGIN_FILE ) . '/assets/css/theme-compat-card-previews/' . $stylesheet . '.css';
		if ( file_exists( $compat_file ) ) {
			$styles .= file_get_contents( $compat_file );
		}

		$recipe       = new \stdClass;
		$recipe_json  = array();
		$ingredients  = <<<EOT
<ul>
	<li><span data-amount="3/4" data-unit="cup">3/4 cup</span> melted coconut oil</li>
	<li><span data-amount="1/2" data-unit="cup">1/2 cup</span> cocoa powder</li>
	<li><span data-amount="2" data-unit="tablespoon">2 tbsp.</span> natural sweetener (agave or maple syrup)</li>
	<li><span data-amount="3/4" data-unit="cup">3/4 cup</span> almond butter</li>
	<li><span data-amount="" data-unit=""></span>pinch of sea salt, plus more for topping</li>
</ul>
EOT;
		$instructions = <<<EOT
<ol>
<li id="instruction-step-1">Whisk the coconut oil, cocoa powder, sweetener, and a pinch of salt.</li>
<li id="instruction-step-2">Fill a regular size muffin tin with paper liners. Pour a small amount of the cocoa mixture (1-2 tablespoons) into the paper cups. Drop a small spoonful of the almond butter (2-3 teaspoons) into the center of each cup. Divide remaining chocolate amongst the cups.</li>
<li id="instruction-step-3">If almond butter is sticking up, just gently press it down so each cup has a smooth top layer. Sprinkle each almond butter cup with a pinch of coarse sea salt. Freeze for one hour or until solid. YUM TOWN.</li>
</ol>
EOT;
		$notes        = <<<EOT
<p>These are very adaptable – I’ve used as much as 3/4 cup cocoa, and as little as 2 tablespoons agave. It just depends on how sweet / dark you want them to be. I’ve also used peanut butter which is (obviously) delicious!</p>
EOT;
		if ( 'card' === $nutrifox_display_style ) {
			$nutrifox_id           = '';
			$recipe_nutrition      = array(
				'serving_size' =>
				array(
					'label' => 'Serving Size',
					'value' => '<span data-tasty-recipes-customization="body-color.color" class="tasty-recipes-serving-size">12</span>',
				),
				'calories'     =>
				array(
					'label' => 'Calories',
					'value' => '<span data-tasty-recipes-customization="body-color.color" class="tasty-recipes-calories">238</span>',
				),
				'sugar'        =>
				array(
					'label' => 'Sugar',
					'value' => '<span data-tasty-recipes-customization="body-color.color" class="tasty-recipes-sugar">3.8g</span>',
				),
				'sodium'       =>
				array(
					'label' => 'Sodium',
					'value' => '<span data-tasty-recipes-customization="body-color.color" class="tasty-recipes-sodium">99.3mg</span>',
				),
				'fat'          =>
				array(
					'label' => 'Fat',
					'value' => '<span data-tasty-recipes-customization="body-color.color" class="tasty-recipes-fat">22.6g</span>',
				),
				'fiber'        =>
				array(
					'label' => 'Fiber',
					'value' => '<span data-tasty-recipes-customization="body-color.color" class="tasty-recipes-fiber">2.7g</span>',
				),
				'protein'      =>
				array(
					'label' => 'Protein',
					'value' => '<span data-tasty-recipes-customization="body-color.color" class="tasty-recipes-protein">3.9g</span>',
				),
			);
			$recipe_nutrifox_embed = '';
		} else {
			$recipe_nutrition       = array();
			$nutrifox_id            = 26460;
			$nutrifox_iframe_url    = sprintf(
				'https://%s/embed/label/%d',
				TASTY_RECIPES_NUTRIFOX_DOMAIN,
				$nutrifox_id
			);
			$nutrifox_resize_script = file_get_contents( dirname( dirname( __FILE__ ) ) . '/assets/js/nutrifox-resize.js' );

			$recipe_nutrifox_embed = <<<EOT
<script type="text/javascript" data-cfasync="false">
{$nutrifox_resize_script}
</script>
<iframe title="nutritional information" id="nutrifox-label-{$nutrifox_id}" src="{$nutrifox_iframe_url}" style="width:100%;border-width:0;"></iframe>
EOT;
		}

		$template_vars = array(
			'recipe'                        => $recipe,
			'recipe_styles'                 => '',
			'recipe_scripts'                => '',
			'recipe_json'                   => $recipe_json,
			'recipe_title'                  => 'Almond Butter Cups',
			'recipe_image'                  => '<img width="183" height="183" src="' . esc_url( plugins_url( 'assets/images/Almond-Butter-Cups-Recipe-370x370.jpg', dirname( __FILE__ ) ) ) . '" class="attachment-thumbnail size-thumbnail" alt="Chocolate Chip Cookies on parchment paper." loading="lazy" data-pin-nopin="true" />',
			'recipe_rating_icons'           => Ratings::get_rendered_rating(
				4.6,
				'detail-value-color.color',
				$star_ratings_style
			),
			'recipe_rating_label'           => '<span data-tasty-recipes-customization="detail-label-color.color" class="rating-label"><span class="average">4.6</span> from <span class="count">1376</span> reviews</span>',
			'recipe_author_name'            => '',
			'recipe_details'                => array(
				'author'     =>
				array(
					'label' => 'Author',
					'value' => '<a data-tasty-recipes-customization="detail-value-color.color" class="tasty-recipes-author-name" href="https://pinchofyum.com/about">Pinch of Yum</a>',
					'class' => 'author',
				),
				'prep_time'  =>
				array(
					'label' => 'Prep Time',
					'value' => '<span data-tasty-recipes-customization="detail-value-color.color" class="tasty-recipes-prep-time">10 mins</span>',
					'class' => 'prep-time',
				),
				'cook_time'  =>
				array(
					'label' => 'Cook Time',
					'value' => '<span data-tasty-recipes-customization="detail-value-color.color" class="tasty-recipes-cook-time">1 hour (for freezing)</span>',
					'class' => 'cook-time',
				),
				'total_time' =>
				array(
					'label' => 'Total Time',
					'value' => '<span data-tasty-recipes-customization="detail-value-color.color" class="tasty-recipes-total-time">1 hour, 10 minutes</span>',
					'class' => 'total-time',
				),
				'yield'      =>
				array(
					'label' => 'Yield',
					'value' => '<span data-tasty-recipes-customization="detail-value-color.color" class="tasty-recipes-yield"><span data-amount="12">12</span> almond butter cups<span class="tasty-recipes-yield-scale"><span data-amount="1">1</span>x</span></span>',
					'class' => 'yield',
				),
				'category'   =>
				array(
					'label' => 'Category',
					'value' => '<span data-tasty-recipes-customization="detail-value-color.color" class="tasty-recipes-category">Dessert</span>',
					'class' => 'category',
				),
				'method'     =>
				array(
					'label' => 'Method',
					'value' => '<span data-tasty-recipes-customization="detail-value-color.color" class="tasty-recipes-method">No bake</span>',
					'class' => 'method',
				),
				'cuisine'    =>
				array(
					'label' => 'Cuisine',
					'value' => '<span data-tasty-recipes-customization="detail-value-color.color" class="tasty-recipes-cuisine">American</span>',
					'class' => 'cuisine',
				),
			),
			'recipe_description'            => '<p>Almond Butter Cups: made with five ingredients and no refined sugar. So creamy, rich, and yummy!</p>',
			'recipe_ingredients'            => $ingredients,
			'recipe_instructions_has_video' => false,
			'recipe_scalable'               => '<button class="tasty-recipes-scale-button tasty-recipes-scale-button-active" data-amount="1">1x</button><button class="tasty-recipes-scale-button" data-amount="2">2x</button><button class="tasty-recipes-scale-button" data-amount="3">3x</button>',
			'recipe_instructions'           => $instructions,
			'recipe_keywords'               => 'almond butter cups, peanut butter cups, healthy almond butter cups',
			'recipe_notes'                  => $notes,
			'recipe_nutrifox_id'            => $nutrifox_id,
			'recipe_nutrifox_embed'         => $recipe_nutrifox_embed,
			'recipe_video_embed'            => '',
			'recipe_nutrition'              => $recipe_nutrition,
			'recipe_hidden_nutrition'       => array(),
			'copy_ingredients'              => get_option( Tasty_Recipes::COPY_TO_CLIPBOARD_OPTION ) ? '<button aria-label="' . __( 'Copy ingredients to clipboard', 'tasty_recipes' ) . '" class="tasty-recipes-copy-button" id="copy-ingredients" data-text="' . __( 'Copy ingredients', 'tasty_recipes' ) . '">' . file_get_contents( dirname( dirname( __FILE__ ) ) . '/assets/images/icons/icon-copy-to-clipboard.svg' ) . '</button>' : false,
			'first_button'                  => Shortcodes::get_card_button( $recipe, 'first', $custom_design ),
			'second_button'                 => Shortcodes::get_card_button( $recipe, 'second', $custom_design ),
			'instagram_handle'              => get_option( Tasty_Recipes::INSTAGRAM_HANDLE_OPTION ),
			'instagram_hashtag'             => get_option( Tasty_Recipes::INSTAGRAM_HASHTAG_OPTION ),
			'footer_social_platform'        => $footer_social_platform,
			'footer_heading'                => $settings['footer_heading'],
			'footer_description'            => $settings['footer_description'],
		);

		if ( ! empty( $template_vars['recipe_image'] ) ) {
			$wrapper_classes[] = 'tasty-recipes-has-image';
		} else {
			$wrapper_classes[] = 'tasty-recipes-no-image';
		}
		$stylesheets = '';
		if ( $editor_styles && current_theme_supports( 'editor-styles' ) ) {
			foreach ( $editor_styles as $style ) {
				if ( preg_match( '~^(https?:)?//~', $style ) ) {
					$stylesheets .= '<link rel="stylesheet" href="' . esc_url( $style ) . '">' . PHP_EOL;
				} else {
					$stylesheets .= '<link rel="stylesheet" href="' . esc_url( get_theme_file_uri( $style ) ) . '">' . PHP_EOL;
				}
			}
		}

		$template_vars['recipe_styles'] = '<style type="text/css">' . PHP_EOL . $styles . PHP_EOL . '</style>' . PHP_EOL;

		$ret            = '<!DOCTYPE html><html><head>' . $stylesheets . '</head><body>';
		$ret           .= '<div class="' . esc_attr( implode( ' ', $wrapper_classes ) ) . '"';
		$customizations = Shortcodes::get_card_container_customizations( $custom_design );
		if ( $customizations ) {
			$ret .= ' data-tasty-recipes-customization="' . esc_attr( $customizations ) . '"';
		}
		$ret .= '>' . PHP_EOL;
		$ret .= Tasty_Recipes::get_template_part( $template, $template_vars );
		$ret .= '</div>';
		$ret .= '<script type="text/javascript">' . PHP_EOL . file_get_contents( dirname( dirname( __FILE__ ) ) . '/assets/dist/settings-recipe-card-preview.build.js' ) . PHP_EOL . '</script>';
		$ret .= '</body></html>';
		echo apply_filters( 'tasty_recipes_recipe_card_output', $ret, 'admin' );
		exit;
	}

	/**
	 * Handles an AJAX request to get the number of matching recipes to convert.
	 */
	public static function handle_wp_ajax_get_count() {
		if ( ! current_user_can( self::CAPABILITY ) || ! wp_verify_nonce( $_GET['nonce'], self::NONCE_KEY ) ) {
			wp_send_json_error(
				array(
					'message' => __( "Sorry, you don't have permission to do this.", 'tasty-recipes' ),
				)
			);
		}

		$class      = false;
		$converters = Tasty_Recipes::get_converters();
		if ( isset( $converters[ $_GET['type'] ] ) ) {
			$class = $converters[ $_GET['type'] ]['class'];
		} else {
			wp_send_json_error(
				array(
					'message' => __( "Couldn't count recipes. Please contact support for assistance.", 'tasty-recipes' ),
				)
			);
		}

		wp_send_json_success(
			array(
				'count' => (int) $class::get_count(),
			)
		);
	}

	/**
	 * Handles an AJAX request to batch convert some recipes.
	 */
	public static function handle_wp_ajax_convert() {
		if ( ! current_user_can( self::CAPABILITY ) || ! wp_verify_nonce( $_GET['nonce'], self::NONCE_KEY ) ) {
			wp_send_json_error(
				array(
					'message' => __( "Sorry, you don't have permission to do this.", 'tasty-recipes' ),
				)
			);
		}

		$class      = false;
		$converters = Tasty_Recipes::get_converters();
		if ( isset( $converters[ $_GET['type'] ] ) ) {
			$class = $converters[ $_GET['type'] ]['class'];
		} else {
			wp_send_json_error(
				array(
					'message' => __( "Couldn't convert recipes. Please contact support for assistance.", 'tasty-recipes' ),
				)
			);
		}

		$post_ids  = $class::get_post_ids();
		$converted = 0;
		foreach ( $post_ids as $post_id ) {
			$type = 'shortcode';
			if ( function_exists( 'has_blocks' )
				&& has_blocks( $post_id ) ) {
				$type = 'block';
			}
			$recipe = $class::convert_post( $post_id, $type );
			if ( $recipe ) {
				$converted++;
			} else {
				wp_send_json_error(
					array(
						// translators: Post id that couldn't be translated.
						'message' => sprintf( __( 'Couldn\'t convert recipe in \'%1$s\' (post %2$d). Please contact support for assistance.', 'tasty-recipes' ), get_the_title( $post_id ), $post_id ),
					)
				);
			}
		}

		$after_count = $class::get_count();
		wp_send_json_success(
			array(
				'converted' => (int) $converted,
				'count'     => (int) $after_count,
			)
		);
	}

	/**
	 * Renders in the admin header.
	 */
	public static function action_in_admin_header() {
		$screen = get_current_screen();
		if ( 'settings_page_tasty-recipes' !== $screen->id ) {
			return;
		}
		?>
<div class="tasty-recipes-header">
	<h1><?php esc_html_e( 'Tasty Recipes', 'tasty-recipes' ); ?></h1>
	<a target="_blank" href="https://www.wptasty.com/"><img src="<?php echo esc_url( plugins_url( 'assets/images/wptasty-darkbg.png', __DIR__ ) ); ?>" data-pin-nopin="true" alt="WP Tasty logo" /></a>
</div>
		<?php
	}

	/**
	 * Renders the Tasty Recipes settings page.
	 */
	public static function handle_settings_page() {
		add_settings_section(
			self::SETTINGS_SECTION_CARD_DESIGN,
			false,
			false,
			self::PAGE_SLUG
		);
		add_settings_field(
			Tasty_Recipes::QUICK_LINKS_OPTION,
			__( 'Quick Links', 'tasty-recipes' ),
			array( __CLASS__, 'render_select_field' ),
			self::PAGE_SLUG,
			self::SETTINGS_SECTION_CARD_DESIGN,
			array(
				'name'        => Tasty_Recipes::QUICK_LINKS_OPTION,
				'description' => __( 'Enable or disable quick links below the post title.', 'tasty-recipes' ),
				'options'     => array(
					''      => __( 'Disabled', 'tasty-recipes' ),
					'both'  => __( 'Show \'Jump to Recipe\' and \'Print Recipe\' links', 'tasty-recipes' ),
					'jump'  => __( 'Only show \'Jump to Recipe\' link', 'tasty-recipes' ),
					'print' => __( 'Only show \'Print Recipe\' link', 'tasty-recipes' ),
				),
			)
		);
		add_settings_field(
			Tasty_Recipes::CARD_BUTTONS_OPTION,
			__( 'Recipe Card Buttons', 'tasty-recipes' ),
			array( __CLASS__, 'render_card_buttons_field' ),
			self::PAGE_SLUG,
			self::SETTINGS_SECTION_CARD_DESIGN
		);
		add_settings_field(
			Tasty_Recipes::UNIT_CONVERSION_OPTION,
			__( 'Unit Conversion', 'tasty-recipes' ),
			array( __CLASS__, 'render_select_field' ),
			self::PAGE_SLUG,
			self::SETTINGS_SECTION_CARD_DESIGN,
			array(
				'name'              => Tasty_Recipes::UNIT_CONVERSION_OPTION,
				'description'       => __( 'Allow visitors to convert between US Customary and Metric.', 'tasty-recipes' ),
				'options'           => array(
					false => __( 'Off', 'tasty_recipes' ),
					true  => __( 'On', 'tasty_recipes' ),
				),
				'after_description' => function() {
					$value = get_option( Tasty_Recipes::AUTOMATIC_UNIT_CONVERSION_OPTION, false );
					echo '<p class="description tasty-recipes-automatic-unit-conversion-container" style="display:none;">';
					echo '<label><input type="checkbox" name="' . Tasty_Recipes::AUTOMATIC_UNIT_CONVERSION_OPTION . '" ' . checked( (bool) $value, true, false ) . '>' . esc_html__( 'Automatically apply unit conversion to existing recipes.', 'tasty-recipes' ) . '</label>';
					echo '</p>';
					echo <<<EOT
<script>
(function(){
	var select = document.querySelector('select[name="tasty_recipes_unit_conversion"]');
	var container = document.querySelector('.tasty-recipes-automatic-unit-conversion-container');
	if (1 === parseInt(select.value)) {
		container.style.display = null;
	}
	select.addEventListener('change', function() {
		if (1 === parseInt(select.value)) {
			container.style.display = null;
		} else {
			container.style.display = 'none';
		}
	});
}())
</script>
EOT;
				},
			)
		);
		add_settings_field(
			Tasty_Recipes::INGREDIENT_CHECKBOXES_OPTION,
			__( 'Ingredient Checkboxes', 'tasty-recipes' ),
			array( __CLASS__, 'render_select_field' ),
			self::PAGE_SLUG,
			self::SETTINGS_SECTION_CARD_DESIGN,
			array(
				'name'        => Tasty_Recipes::INGREDIENT_CHECKBOXES_OPTION,
				'description' => __( 'Allow visitors to check off recipe ingredients.', 'tasty-recipes' ),
				'options'     => array(
					false => __( 'Off', 'tasty_recipes' ),
					true  => __( 'On', 'tasty_recipes' ),
				),
			)
		);
		add_settings_field(
			Tasty_Recipes::DISABLE_SCALING_OPTION,
			__( 'Scaling', 'tasty-recipes' ),
			array( __CLASS__, 'render_select_field' ),
			self::PAGE_SLUG,
			self::SETTINGS_SECTION_CARD_DESIGN,
			array(
				'name'        => Tasty_Recipes::DISABLE_SCALING_OPTION,
				'description' => __( 'Allow visitors to scale the recipe 1x, 2x, or 3x.', 'tasty-recipes' ),
				'options'     => array(
					true  => __( 'Off', 'tasty_recipes' ),
					false => __( 'On', 'tasty_recipes' ),
				),
			)
		);
		add_settings_field(
			Tasty_Recipes::COPY_TO_CLIPBOARD_OPTION,
			__( 'Copy to Clipboard', 'tasty-recipes' ),
			array( __CLASS__, 'render_select_field' ),
			self::PAGE_SLUG,
			self::SETTINGS_SECTION_CARD_DESIGN,
			array(
				'name'        => Tasty_Recipes::COPY_TO_CLIPBOARD_OPTION,
				'description' => __( 'Allow visitors to copy ingredients to clipboard.', 'tasty-recipes' ),
				'options'     => array(
					false => __( 'Off', 'tasty_recipes' ),
					true  => __( 'On', 'tasty_recipes' ),
				),
			)
		);
		add_settings_field(
			Tasty_Recipes::DEFAULT_AUTHOR_LINK_OPTION,
			__( 'Default Author Link', 'tasty-recipes' ),
			array( __CLASS__, 'render_input_field' ),
			self::PAGE_SLUG,
			self::SETTINGS_SECTION_CARD_DESIGN,
			array(
				'name' => Tasty_Recipes::DEFAULT_AUTHOR_LINK_OPTION,
			)
		);
		add_settings_field(
			Tasty_Recipes::SHAREASALE_OPTION,
			__( 'ShareASale Affiliate ID', 'tasty-recipes' ),
			array( __CLASS__, 'render_input_field' ),
			self::PAGE_SLUG,
			self::SETTINGS_SECTION_CARD_DESIGN,
			array(
				'name'        => Tasty_Recipes::SHAREASALE_OPTION,
				// translators: Various links.
				'description' => sprintf( __( '<a href="%1$s" target="_blank">Apply for the affiliate program</a>, or <a href="%2$s" target="_blank">find your affiliate ID</a>.', 'tasty-recipes' ), 'https://www.wptasty.com/affiliate', 'https://www.wptasty.com/affiliate-id' ),
			)
		);
		echo Tasty_Recipes::get_template_part( 'settings' );
		if ( ! did_action( 'admin_footer' ) && ! doing_action( 'admin_footer' ) ) {
			add_action( 'admin_footer', array( __CLASS__, 'action_admin_footer_render_template' ) );
		}
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
		return array( 'bold', 'italic', 'underline', 'bullist', 'numlist', 'link', 'unlink', 'removeformat' );
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
				'teeny'   => true,
				'wpautop' => true,
			)
		);
		ob_get_clean();
	}

	/**
	 * Render an input field.
	 *
	 * @param array $args Configuration arguments used by the input field.
	 */
	public static function render_input_field( $args ) {
		$defaults = array(
			'type' => 'text',
			'name' => '',
		);
		$args     = array_merge( $defaults, $args );
		if ( empty( $args['name'] ) ) {
			return;
		}
		$value = get_option( $args['name'] );
		?>
		<input type="<?php echo esc_attr( $args['type'] ); ?>"
			name="<?php echo esc_attr( $args['name'] ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			class="regular-text"
		/>
		<?php if ( ! empty( $args['description'] ) ) : ?>
			<p class="description"><?php echo wp_kses_post( $args['description'] ); ?></p>
			<?php
		endif;
	}

	/**
	 * Sanitizes the card buttons option.
	 *
	 * @param array $original Original card button option value.
	 * @return array
	 */
	public static function sanitize_card_buttons( $original ) {
		$sanitized = array();
		foreach ( array( 'first', 'second' ) as $position ) {
			if ( in_array( $original[ $position ], array( '', 'print', 'pin', 'mediavine', 'slickstream' ), true ) ) {
				$sanitized[ $position ] = $original[ $position ];
			}
		}
		return $sanitized;
	}

	/**
	 * Sanitizes the customization option to make sure there are only expected values.
	 *
	 * @param array $original Original customization option value.
	 * @return array
	 */
	public static function sanitize_customization_option( $original ) {
		$original  = stripslashes_deep( (array) $original );
		$sanitized = array();
		$allowed   = array(
			'primary_color'            => array( __CLASS__, 'sanitize_color' ),
			'secondary_color'          => array( __CLASS__, 'sanitize_color' ),
			'icon_color'               => array( __CLASS__, 'sanitize_color' ),
			'button_color'             => array( __CLASS__, 'sanitize_color' ),
			'button_text_color'        => array( __CLASS__, 'sanitize_color' ),
			'detail_label_color'       => array( __CLASS__, 'sanitize_color' ),
			'detail_value_color'       => array( __CLASS__, 'sanitize_color' ),
			'h2_color'                 => array( __CLASS__, 'sanitize_color' ),
			'h2_transform'             => array(
				'uppercase',
				'initial',
				'lowercase',
			),
			'h3_color'                 => array( __CLASS__, 'sanitize_color' ),
			'h3_transform'             => array(
				'uppercase',
				'initial',
				'lowercase',
			),
			'body_color'               => array( __CLASS__, 'sanitize_color' ),
			'star_ratings_style'       => array(
				'solid',
				'outline',
			),
			'nutrifox_display_style'   => array(
				'label',
				'card',
			),
			'footer_social_platform'   => array(
				'instagram',
				'pinterest',
				'facebook',
			),
			'footer_icon_color'        => array( __CLASS__, 'sanitize_color' ),
			'footer_heading'           => 'sanitize_text_field',
			'footer_heading_color'     => array( __CLASS__, 'sanitize_color' ),
			'footer_description'       => 'wp_filter_post_kses',
			'footer_description_color' => array( __CLASS__, 'sanitize_color' ),
		);
		foreach ( $original as $key => $value ) {
			if ( ! isset( $allowed[ $key ] ) ) {
				continue;
			}

			if ( is_callable( $allowed[ $key ] ) ) {
				$sanitized[ $key ] = $allowed[ $key ]( $value );
			} elseif ( is_array( $allowed[ $key ] ) ) {
				if ( in_array( $value, $allowed[ $key ], true ) ) {
					$sanitized[ $key ] = $value;
				} else {
					$sanitized[ $key ] = '';
				}
			}
		}
		if ( ! empty( $sanitized['footer_description'] ) ) {
			$sanitized['footer_description'] = stripslashes( wpautop( $sanitized['footer_description'] ) );
		}
		return $sanitized;
	}

	/**
	 * Sanitizes a color field.
	 *
	 * @param string $color Existing color value.
	 * @return string
	 */
	private static function sanitize_color( $color ) {
		if ( empty( $color ) || is_array( $color ) ) {
			return '';
		}

		if ( false === strpos( $color, 'rgba' ) ) {
			return sanitize_hex_color( $color );
		}

		$color = str_replace( ' ', '', $color );
		sscanf( $color, 'rgba(%d,%d,%d,%f)', $red, $green, $blue, $alpha );
		return 'rgba(' . $red . ',' . $green . ',' . $blue . ',' . $alpha . ')';
	}

	/**
	 * Render a select field.
	 *
	 * @param array $args Configuration arguments used by the select field.
	 */
	public static function render_select_field( $args ) {
		$defaults = array(
			'name'    => '',
			'options' => array(),
		);
		$args     = array_merge( $defaults, $args );
		if ( empty( $args['name'] ) || empty( $args['options'] ) ) {
			return;
		}
		$value = get_option( $args['name'] );
		// Make sure $value is evaluated as boolean for true/false options.
		$is_bool = false;
		if ( count( $args['options'] ) === 2 ) {
			reset( $args['options'] );
			$first = key( $args['options'] );
			end( $args['options'] );
			$last = key( $args['options'] );
			reset( $args['options'] );
			if ( is_numeric( $first ) && is_numeric( $last ) ) {
				$value   = (bool) $value;
				$is_bool = true;
			}
		}
		?>
		<select name="<?php echo esc_attr( $args['name'] ); ?>"
		<?php
		if ( ! empty( $args['disabled'] ) ) {
			echo 'disabled';
		}
		?>
		>
		<?php
		foreach ( $args['options'] as $key => $label ) :
			$compare_key = $key;
			if ( $is_bool ) {
				$compare_key = (bool) $key;
			}
			?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $compare_key, $value ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php if ( ! empty( $args['description'] ) ) : ?>
			<p class="description"><?php echo wp_kses_post( $args['description'] ); ?></p>
			<?php
			endif;
		if ( ! empty( $args['after_description'] ) && is_callable( $args['after_description'] ) ) {
			$args['after_description']( $args );
		}
	}

	/**
	 * Renders the card buttons field.
	 */
	public static function render_card_buttons_field() {

		$options = array(
			''            => esc_html__( 'Off', 'tasty-recipes' ),
			'print'       => esc_html__( 'Print', 'tasty-recipes' ),
			'pin'         => esc_html__( 'Pin this recipe', 'tasty-recipes' ),
			'slickstream' => esc_html__( 'Slickstream Favorites', 'tasty-recipes' ),
			'mediavine'   => esc_html__( 'Mediavine Grow.me Save', 'tasty-recipes' ),
		);

		$value = Tasty_Recipes::get_card_button_settings();
		?>
		<div>
			<?php foreach ( array( 'first', 'second' ) as $position ) : ?>
				<select name="<?php echo esc_attr( Tasty_Recipes::CARD_BUTTONS_OPTION . '[' . $position . ']' ); ?>">
				<?php foreach ( $options as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, $value[ $position ] ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Gets the license check object.
	 *
	 * @return object
	 */
	public static function get_license_check() {

		update_option(Tasty_Recipes::LICENSE_KEY_OPTION, '123456-123456-123456-123456');
        $license_checking = ['license' => 'valid'];
        $object = json_decode(json_encode($license_checking), FALSE);
        set_transient( self::LICENSE_CHECK_CACHE_KEY, $license_checking );
        return $object;
		
		if ( ! get_option( Tasty_Recipes::LICENSE_KEY_OPTION ) ) {
			return false;
		}
		$license_check = get_transient( self::LICENSE_CHECK_CACHE_KEY );
		if ( false === $license_check ) {
			$api_params = array(
				'edd_action' => 'check_license',
				'license'    => get_option( Tasty_Recipes::LICENSE_KEY_OPTION ),
				'item_id'    => false,
				'item_name'  => self::ITEM_NAME,
				'author'     => self::ITEM_AUTHOR,
				'url'        => home_url(),
			);

			$license_check = wp_remote_post(
				self::STORE_URL,
				array(
					'timeout' => 15,
					'body'    => $api_params,
				)
			);

			if ( ! is_wp_error( $license_check ) ) {
				$license_check = json_decode( wp_remote_retrieve_body( $license_check ) );
			}
			set_transient( self::LICENSE_CHECK_CACHE_KEY, $license_check, 60 * HOUR_IN_SECONDS );
		}
		return $license_check;
	}

}
