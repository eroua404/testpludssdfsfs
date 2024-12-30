<?php
/**
 * Customizations to the WordPress editor experience.
 *
 * @package Tasty_Recipes
 */

namespace Tasty_Recipes;

use Tasty_Recipes;
use Tasty_Recipes\Objects\Recipe;

/**
 * Customizations to the WordPress editor experience.
 */
class Editor {

	/**
	 * Renders a notice when a convertable recipe is detected.
	 */
	public static function action_admin_notices() {
		$screen = get_current_screen();
		if ( 'post' !== $screen->base || empty( $_GET['post'] ) ) {
			return;
		}

		$post_id = (int) $_GET['post'];
		foreach ( self::get_converter_messages( $post_id ) as $message ) {
			if ( 'success' === $message['type'] ) {
				echo '<div class="notice updated is-dismissible"><p>' . $message['content_html'] . '</p></div>';
			} else {
				echo '<div class="notice"><p>' . $message['content_html'] . '</p></div>';
			}
		}
	}

	/**
	 * Gets the converter messages for a given post.
	 *
	 * @param integer $post_id ID for the post to inspect.
	 * @return array
	 */
	public static function get_converter_messages( $post_id ) {
		$messages = array();
		$post     = get_post( $post_id );
		if ( ! $post ) {
			return $messages;
		}

		// Convert to a Tasty Recipes block when this post has blocks.
		$is_block_editor = '0';
		if ( function_exists( 'has_blocks' )
			&& has_blocks( $post ) ) {
			$is_block_editor = '1';
		}

		$post_revisions = true;
		if ( defined( 'WP_POST_REVISIONS' ) && false === WP_POST_REVISIONS ) {
			$post_revisions = false;
		}
		if ( ! post_type_supports( $post->post_type, 'revisions' ) ) {
			$post_revisions = false;
		}

		// translators: Number of recipes detected.
		$detected_message = __( '%1$s recipe detected. Would you like to convert it to Tasty Recipes? <a class="button" href="%2$s">Convert</a>', 'tasty-recipes' );
		if ( ! $post_revisions ) {
			$detected_message .= '<br>' . __( '<em>Warning: Post revisions are not enabled, please back up post content before converting to Tasty Recipes. <a href="https://www.wptasty.com/convert-single" target="_blank">Learn more</a></em>.', 'tasty-recipes' );
		}
		$content = $post->post_content;
		// Correct Windows-style line endings.
		$content = str_replace( "\r\n", "\n", $content );
		$content = str_replace( "\r", "\n", $content );
		foreach ( Tasty_Recipes::get_converters() as $key => $config ) {
			$class = $config['class'];
			if ( ! $class::get_existing_to_convert( $content ) ) {
				continue;
			}
			if ( get_post_meta( $post->ID, 'tasty_recipes_ignore_convert_' . $key, true ) ) {
				continue;
			}
			$nonce        = wp_create_nonce( 'tasty_recipes_convert_recipe' . $post->ID );
			$query_args   = array(
				'action'          => 'tasty_recipes_convert_recipe',
				'nonce'           => $nonce,
				'post_id'         => $post->ID,
				'type'            => $key,
				'is_block_editor' => $is_block_editor,
			);
			$convert_url  = add_query_arg( $query_args, admin_url( 'admin-ajax.php' ) );
			$content_html = sprintf( $detected_message, $config['label'], $convert_url );
			// Strip out the <a> links with their text because we'll later process them to actions.
			$link_regex = '#<a[^>]+href="([^"]+)"[^>]*>([^<]+)</a>(\.)?#';
			$content    = str_replace( array( '<em>', '</em>' ), '', $content_html );
			$content    = preg_replace( $link_regex, '', $content );
			$message    = array(
				'id'             => md5( mt_rand() ),
				'type'           => 'info',
				'content_html'   => $content_html,
				'content'        => trim( strip_tags( $content ) ),
				'actions'        => array(),
				'dismissible'    => true,
				'dismiss_action' => add_query_arg(
					array(
						'action'  => 'tasty_recipes_ignore_convert',
						'type'    => $key,
						'post_id' => $post->ID,
						'nonce'   => $nonce,
					),
					admin_url( 'admin-ajax.php' )
				),
			);
			preg_match_all( $link_regex, $content_html, $matches );
			if ( ! empty( $matches ) ) {
				foreach ( $matches[1] as $i => $link ) {
					$action               = array(
						'url'   => $link,
						'label' => $matches[2][ $i ],
					);
					$message['actions'][] = $action;
				}
			}
			$messages[] = $message;
		}

		if ( ! empty( $_GET['tasty_recipes_message'] ) && 'converted_recipe_success' === $_GET['tasty_recipes_message'] ) {
			$content    = __( 'Successfully migrated the recipe to Tasty Recipes. Enjoy!', 'tasty-recipes' );
			$messages[] = array(
				'type'         => 'success',
				'content_html' => $content,
				'content'      => $content,
				'dismissible'  => true,
			);
		}
		return $messages;
	}

	/**
	 * Whether or not the Tasty Recipes button should display.
	 *
	 * @return boolean
	 */
	public static function is_tasty_recipes_editor_view() {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return true;
		}
		$screen = get_current_screen();
		if ( empty( $screen ) ) {
			return false;
		}
		return ! in_array( $screen->base, array( 'widgets', 'customize' ), true );
	}

	/**
	 * Registers an 'Add Recipe' button to Media Buttons.
	 *
	 * @param string $editor_id Editor instance to be displayed.
	 */
	public static function action_media_buttons( $editor_id ) {

		if ( ! self::is_tasty_recipes_editor_view() ) {
			return;
		}
		if ( ! apply_filters( 'tasty_recipes_add_media_button', true, $editor_id ) ) {
			return;
		}
		?>
		<button type="button" class="button tasty-recipes-add-recipe" data-editor="<?php echo esc_attr( $editor_id ); ?>">
			<span class="wp-media-buttons-icon dashicons dashicons-carrot"></span>
			<?php esc_html_e( 'Add Recipe', 'tasty-recipes' ); ?>
		</button>
		<?php
	}

	/**
	 * Adds a 'Add rel="nofollow" to link' checkbox to the WordPress link editor.
	 */
	public static function action_after_wp_tiny_mce() {
		?>
		<script>
			var originalWpLink;
			// Ensure both TinyMCE, underscores and wpLink are initialized.
			if ( typeof tinymce !== 'undefined' && typeof _ !== 'undefined' && typeof wpLink !== 'undefined' ) {
				// Ensure the #link-options div is present, because it's where we're appending our checkbox.
				if ( tinymce.$('#link-options').length ) {
					// Append our checkbox HTML to the #link-options div, which is already present in the DOM.
					tinymce.$('#link-options').append(<?php echo json_encode( '<div class="link-nofollow"><label><span></span><input type="checkbox" id="wp-link-nofollow" /> ' . esc_html__( 'Add rel="nofollow" to link', 'tasty-recipes' ) . '</label></div>' ); ?>);
					// Clone the original wpLink object so we retain access to some functions.
					originalWpLink = _.clone( wpLink );
					wpLink.addRelNofollow = tinymce.$('#wp-link-nofollow');
					// Override the original wpLink object to include our custom functions.
					wpLink = _.extend( wpLink, {
						/*
						* Fetch attributes for the generated link based on
						* the link editor form properties.
						*
						* In this case, we're calling the original getAttrs()
						* function, and then including our own behavior.
						*/
						getAttrs: function() {
							var attrs = originalWpLink.getAttrs();
							attrs.rel = wpLink.addRelNofollow.prop( 'checked' ) ? 'nofollow' : '';
							return attrs;
						},
						/*
						* Build the link's HTML based on attrs when inserting
						* into the text editor.
						*
						* In this case, we're completely overriding the existing
						* function.
						*/
						buildHtml: function( attrs ) {
							var html = '<a href="' + attrs.href + '"';

							if ( attrs.target ) {
								html += ' target="' + attrs.target + '"';
							}
							if ( attrs.rel ) {
								html += ' rel="' + attrs.rel + '"';
							}
							return html + '>';
						},
						/*
						* Set the value of our checkbox based on the presence
						* of the rel='nofollow' link attribute.
						*
						* In this case, we're calling the original mceRefresh()
						* function, then including our own behavior
						*/
						mceRefresh: function( searchStr, text ) {
							originalWpLink.mceRefresh( searchStr, text );
							var editor = window.tinymce.get( window.wpActiveEditor )
							if ( typeof editor !== 'undefined' && ! editor.isHidden() ) {
								var linkNode = editor.dom.getParent( editor.selection.getNode(), 'a[href]' );
								if ( linkNode ) {
									wpLink.addRelNofollow.prop( 'checked', -1 !== editor.dom.getAttrib( linkNode, 'rel' ).indexOf('nofollow') );
								}
							}
						}
					});
				}
			}
			if ( typeof jQuery !== 'undefined' ) {
				// Remove EasyRecipe's nofollow UI if it's present.
				jQuery(document).ready(setTimeout(function(){
					if ( jQuery('#link-options #link-nofollow-checkbox').length ) {
						jQuery('#link-options #link-nofollow-checkbox').closest('label').remove();
					}
				}, 1));
			}
		</script>
		<?php
	}

	/**
	 * Handles an AJAX request to convert a recipe.
	 */
	public static function handle_wp_ajax_convert_recipe() {
		if ( empty( $_GET['post_id'] ) || empty( $_GET['nonce'] ) ) {
			wp_die( esc_html__( "You don't have permission to do this.", 'tasty-recipes' ) );
		}

		$post = get_post( (int) $_GET['post_id'] );
		if ( empty( $post ) ) {
			wp_die( esc_html__( "You don't have permission to do this.", 'tasty-recipes' ) );
		}

		$post_type_object = get_post_type_object( $post->post_type );
		if ( ! current_user_can( $post_type_object->cap->edit_post, $post->ID )
			|| ! wp_verify_nonce( $_GET['nonce'], 'tasty_recipes_convert_recipe' . $post->ID ) ) {
			wp_die( esc_html__( "You don't have permission to do this.", 'tasty-recipes' ) );
		}

		$recipe     = false;
		$type       = ! empty( $_GET['is_block_editor'] ) ? 'block' : 'shortcode';
		$converters = Tasty_Recipes::get_converters();
		if ( isset( $converters[ $_GET['type'] ] ) ) {
			$class  = $converters[ $_GET['type'] ]['class'];
			$recipe = $class::convert_post( $post->ID, $type );
		}

		if ( ! $recipe ) {
			wp_die( esc_html__( 'Unknown error converting recipe.', 'tasty-recipes' ) );
		}

		$redirect_url = add_query_arg( 'tasty_recipes_message', 'converted_recipe_success', get_edit_post_link( $post->ID, 'raw' ) );
		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Handles an AJAX request to ignore a recipe conversion.
	 */
	public static function handle_wp_ajax_ignore_convert() {
		if ( empty( $_GET['post_id'] ) || empty( $_GET['nonce'] ) ) {
			wp_die( esc_html__( "You don't have permission to do this.", 'tasty-recipes' ) );
		}

		$post = get_post( (int) $_GET['post_id'] );
		if ( empty( $post ) ) {
			wp_die( esc_html__( "You don't have permission to do this.", 'tasty-recipes' ) );
		}

		$post_type_object = get_post_type_object( $post->post_type );
		if ( ! current_user_can( $post_type_object->cap->edit_post, $post->ID )
			|| ! wp_verify_nonce( $_GET['nonce'], 'tasty_recipes_convert_recipe' . $post->ID ) ) {
			wp_die( esc_html__( "You don't have permission to do this.", 'tasty-recipes' ) );
		}

		update_post_meta( $post->ID, 'tasty_recipes_ignore_convert_' . sanitize_key( $_GET['type'] ), true );
		status_header( 200 );
		echo 'Done';
		exit;
	}

	/**
	 * Handles an AJAX request to render a shortcode with its data.
	 */
	public static function handle_wp_ajax_parse_shortcode() {
		global $post;

		if ( empty( $_POST['shortcode'] ) || empty( $_POST['post_id'] ) || empty( $_POST['nonce'] ) ) {
			wp_send_json_error();
		}

		$post = get_post( (int) $_POST['post_id'] );
		if ( empty( $post ) ) {
			wp_send_json_error();
		}

		$post_type_object = get_post_type_object( $post->post_type );
		if ( ! current_user_can( $post_type_object->cap->edit_post, $post->ID )
			|| ! wp_verify_nonce( $_POST['nonce'], 'tasty_recipes_parse_shortcode' ) ) {
			wp_send_json_error();
		}

		$shortcode = wp_unslash( $_POST['shortcode'] );
		self::render_shortcode_response( $shortcode, $post );
	}

	/**
	 * Handles an AJAX request to modify a recipe.
	 */
	public static function handle_wp_ajax_modify_recipe() {
		global $post;

		if ( empty( $_POST['recipe'] ) || empty( $_POST['post_id'] ) || empty( $_POST['nonce'] ) ) {
			wp_send_json_error();
		}

		$post = get_post( (int) $_POST['post_id'] );
		if ( empty( $post ) ) {
			wp_send_json_error();
		}

		$post_type_object = get_post_type_object( $post->post_type );
		if ( ! current_user_can( $post_type_object->cap->edit_post, $post->ID )
			|| ! wp_verify_nonce( $_POST['nonce'], 'tasty_recipes_modify_recipe' ) ) {
			wp_send_json_error();
		}

		if ( ! empty( $_POST['recipe']['id'] ) ) {
			$recipe = Recipe::get_by_id( (int) $_POST['recipe']['id'] );
		} else {
			$recipe = Recipe::create();
		}

		if ( empty( $recipe ) ) {
			wp_send_json_error(
				array(
					'type'    => 'no-items',
					'message' => __( 'No recipe found.', 'tasty-recipes' ),
				)
			);
		}

		foreach ( Recipe::get_attributes() as $field => $meta ) {
			if ( ! isset( $_POST['recipe'][ $field ] ) ) {
				continue;
			}

			$setter = "set_{$field}";

			$sanitize_callback = 'sanitize_text_field';
			if ( ! empty( $meta['sanitize_callback'] ) ) {
				$sanitize_callback = $meta['sanitize_callback'];
			}
			if ( 'intval' === $sanitize_callback && empty( $_POST['recipe'][ $field ] ) ) {
				$data = '';
			} else {
				$data = wp_unslash( $sanitize_callback( $_POST['recipe'][ $field ] ) );
			}

			/**
			 * Permit modification of data before saving it to the database.
			 *
			 * @param mixed $data   Data to be saved.
			 * @param string $field Field to be saved.
			 */
			$data = apply_filters( 'tasty_recipes_pre_save_editor_data', $data, $field );

			if ( method_exists( $recipe, $setter ) ) {
				$recipe->$setter( $data );
				// Allow saving of any nutrition attributes that have been added.
			} elseif ( in_array( $field, Recipe::get_nutrition_attribute_keys(), true ) ) {
				update_post_meta( $recipe->get_id(), $field, $data );
			}
		}

		/**
		 * Allows Tasty Links to save its own data.
		 *
		 * @param array  $data   Recipe data to save.
		 * @param object $recipe Recipe instance.
		 */
		do_action( 'tasty_recipes_after_save_editor_data', $_POST['recipe'], $recipe );

		$image_id = $recipe->get_image_id();
		if ( $image_id ) {
			Content_Model::generate_attachment_image_sizes( $image_id );
		}

		$shortcode = Shortcodes::get_shortcode_for_recipe( $recipe );
		self::render_shortcode_response( $shortcode, $post );
	}

	/**
	 * Renders a shortcode response with its corresponding JSON.
	 *
	 * @param string $shortcode Shortcode string to render.
	 * @param object $post      Post containing the shortcode.
	 */
	private static function render_shortcode_response( $shortcode, $post ) {

		setup_postdata( $post );
		$parsed = do_shortcode( $shortcode );

		if ( empty( $parsed ) ) {
			wp_send_json_error(
				array(
					'type'    => 'no-items',
					'message' => __( 'No recipe found.', 'tasty-recipes' ),
				)
			);
		}

		$recipe_json = Tasty_Recipes::get_instance()->recipe_json;
		/**
		 * Permit modification of the recipe JSON before it's returned.
		 *
		 * @param array $recipe_json Existing recipe JSON blob.
		 */
		$recipe_json = apply_filters( 'tasty_recipes_shortcode_response_recipe_json', $recipe_json );
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
		wp_send_json_success(
			array(
				'head'      => '',
				'body'      => $parsed,
				'recipe'    => $recipe_json,
				'shortcode' => $shortcode,
			)
		);
	}

}
