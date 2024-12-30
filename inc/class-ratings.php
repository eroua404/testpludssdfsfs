<?php
/**
 * Manages ratings integration with comments.
 *
 * @package Tasty_Recipes
 */

namespace Tasty_Recipes;

use Tasty_Recipes;

/**
 * Manages ratings integration with comments.
 */
class Ratings {

	/**
	 * Meta key where ratings are stored.
	 *
	 * Defaults to 'ERRating' for historical compatibility with EasyRecipe.
	 *
	 * @var string
	 */
	const COMMENT_META_KEY = 'ERRating';

	/**
	 * Meta key where Cookbook ratings are stored.
	 *
	 * @var string
	 */
	const CB_COMMENT_META_KEY = 'cookbook_comment_rating';

	/**
	 * Meta key where Simple Recipe Pro ratings are stored.
	 *
	 * @var string
	 */
	const SRP_COMMENT_META_KEY = 'recipe_rating';

	/**
	 * Meta key where WP Recipe Maker ratings are stored.
	 *
	 * @var string
	 */
	const WPRM_COMMENT_META_KEY = 'wprm-comment-rating';

	/**
	 * Meta key where ZipList ratings are stored.
	 *
	 * @var string
	 */
	const ZRP_COMMENT_META_KEY = 'zrdn_post_recipe_rating';

	/**
	 * Whether or not ratings are enabled.
	 *
	 * @return boolean
	 */
	public static function is_enabled() {
		/**
		 * Permit ratings to be disabled by the end user.
		 *
		 * @param boolean
		 */
		return apply_filters( 'tasty_recipes_enable_ratings', true );
	}

	/**
	 * Renders ratings CSS in <head> when enabled.
	 */
	public static function action_wp_head() {
		if ( ! self::is_enabled() ) {
			return;
		}
		$styles = file_get_contents( dirname( dirname( __FILE__ ) ) . '/assets/dist/ratings.css' );
		echo '<style type="text/css">' . PHP_EOL . $styles . PHP_EOL . '</style>' . PHP_EOL;
	}

	/**
	 * Renders ratings CSS in <head> when enabled.
	 */
	public static function action_admin_head() {
		if ( ! self::is_enabled() ) {
			return;
		}
		$styles = file_get_contents( dirname( dirname( __FILE__ ) ) . '/assets/dist/ratings.css' );
		echo '<style type="text/css">' . PHP_EOL . $styles . PHP_EOL . '</style>' . PHP_EOL;
	}

	/**
	 * Recalculate total reviews and average rating for the embedded recipe
	 * when a comment changes in some way.
	 *
	 * @param integer $comment_id ID of the changed comment.
	 */
	public static function action_modify_comment_update_recipe_ratings( $comment_id ) {
		$comment = get_comment( $comment_id );
		if ( ! self::is_enabled() || ! $comment || ! $comment->comment_post_ID ) {
			return;
		}

		// Only process when there is one embedded recipe in a post.
		$recipes = Tasty_Recipes::get_recipes_for_post( $comment->comment_post_ID );
		if ( 1 !== count( $recipes ) ) {
			return;
		}

		$recipe = reset( $recipes );
		self::update_recipe_rating( $recipe, $comment->comment_post_ID );
	}

	/**
	 * Update rating for a recipe embedded within a given post.
	 *
	 * @param Recipe  $recipe  Existing recipe object.
	 * @param integer $post_id ID for the post with the recipe.
	 */
	public static function update_recipe_rating( $recipe, $post_id ) {
		global $wpdb;

		$ratings = $wpdb->get_results( $wpdb->prepare( "SELECT $wpdb->commentmeta.comment_id, $wpdb->commentmeta.meta_value FROM $wpdb->commentmeta LEFT JOIN $wpdb->comments ON $wpdb->commentmeta.comment_id = $wpdb->comments.comment_ID WHERE $wpdb->comments.comment_post_ID=%d AND $wpdb->comments.comment_approved=1 AND ( $wpdb->commentmeta.meta_key=%s OR $wpdb->commentmeta.meta_key=%s OR $wpdb->commentmeta.meta_key=%s OR $wpdb->commentmeta.meta_key=%s OR $wpdb->commentmeta.meta_key=%s )", $post_id, self::COMMENT_META_KEY, self::CB_COMMENT_META_KEY, self::SRP_COMMENT_META_KEY, self::WPRM_COMMENT_META_KEY, self::ZRP_COMMENT_META_KEY ) );
		// Some comments may have ER, Cookbook, and Simple Recipe Pro.
		$comment_ratings = array();
		foreach ( $ratings as $rating ) {
			if ( $rating->meta_value >= 1 ) {
				$comment_ratings[ $rating->comment_id ] = $rating->meta_value;
			}
		}
		$ratings        = array_values( $comment_ratings );
		$total_reviews  = count( $ratings );
		$create_ratings = get_post_meta( $recipe->get_id(), 'create_ratings', true );
		if ( ! empty( $create_ratings ) ) {
			$ratings        = array_merge( $ratings, array_fill( 0, $create_ratings['rating_count'], $create_ratings['rating'] ) );
			$total_reviews += $create_ratings['rating_count'];
		}
		$srp_ratings = get_post_meta( $recipe->get_id(), 'srp_ratings', true );
		if ( ! empty( $srp_ratings ) ) {
			$srp_ratings    = json_decode( $srp_ratings, true );
			$ratings        = array_merge( $ratings, array_values( $srp_ratings ) );
			$total_reviews += count( $srp_ratings );
		}
		$wprm_ratings = get_post_meta( $recipe->get_id(), 'wprm_ratings', true );
		if ( ! empty( $wprm_ratings ) ) {
			$ratings        = array_merge( $ratings, array( $wprm_ratings['total'] ) );
			$total_reviews += $wprm_ratings['count'];
		}
		$zrp_ratings = get_post_meta( $recipe->get_id(), 'zrp_ratings', true );
		if ( ! empty( $zrp_ratings ) ) {
			$zrp_ratings_data = wp_list_pluck( $zrp_ratings, 'rating' );
			$ratings          = array_merge( $ratings, array_values( $zrp_ratings_data ) );
			$total_reviews   += count( $zrp_ratings_data );
		}
		$average_rating = '';
		if ( $total_reviews ) {
			$average_rating = round( array_sum( $ratings ) / $total_reviews, 4 );
		}
		$recipe->set_total_reviews( $total_reviews );
		$recipe->set_average_rating( $average_rating );
		/**
		 * Fires when a recipe's rating has been updated.
		 *
		 * @param object  $recipe  Recipe object.
		 * @param integer $post_id ID for the post.
		 */
		do_action( 'tasty_recipes_updated_recipe_rating', $recipe, $post_id );
	}

	/**
	 * Processes comment submission for its rating (if set).
	 *
	 * @param array $commentdata Comment data to be saved.
	 * @return array
	 */
	public static function filter_preprocess_comment( $commentdata ) {
		if ( empty( $_POST['tasty-recipes-rating'] ) ) {
			return $commentdata;
		}
		if ( ! isset( $commentdata['comment_meta'] ) ) {
			$commentdata['comment_meta'] = array();
		}
		$commentdata['comment_meta'][ self::COMMENT_META_KEY ] = (int) $_POST['tasty-recipes-rating'];
		return $commentdata;
	}

	/**
	 * Handles a REST API request to create a new comment.
	 *
	 * @param object $comment New comment object.
	 * @param object $request Request object.
	 */
	public static function action_rest_insert_comment( $comment, $request ) {
		if ( ! empty( $request['meta']['tasty-recipes-rating'] ) ) {
			update_comment_meta(
				$comment->comment_ID,
				self::COMMENT_META_KEY,
				(int) $request['meta']['tasty-recipes-rating']
			);
		}
	}

	/**
	 * Filters the comment form HTML to include a ratings input.
	 *
	 * @param string $comment_form Existing comment form HTML.
	 * @return string
	 */
	public static function filter_comment_form_field_comment( $comment_form ) {
		$post_id = get_queried_object_id();
		if ( ! self::is_enabled() || ! $post_id ) {
			return $comment_form;
		}
		$recipes = Tasty_Recipes::get_recipes_for_post( $post_id );
		if ( 1 !== count( $recipes ) ) {
			return $comment_form;
		}
		$icons = self::get_rating_icon_html();
		ob_start();
		?>
		<fieldset class="tasty-recipes-ratings tasty-recipes-comment-form">
			<legend>Recipe rating</legend>
			<span class="tasty-recipes-ratings-buttons">
			<?php for ( $i = 5; $i >= 1; $i-- ) : ?>
				<input aria-label="Rate this recipe <?php echo (int) $i; ?> stars" type="radio" name="tasty-recipes-rating" class="tasty-recipes-rating" value="<?php echo (int) $i; ?>" /><span><i class="checked"><?php echo $icons['checked']; ?></i><i class="unchecked"><?php echo $icons['unchecked']; ?></i></span>
			<?php endfor; ?>
			</span>
		</fieldset>
		<?php
		$rating_html = ob_get_clean();
		/**
		 * Control whether the rating HTML appears before or after the comment form.
		 *
		 * @param string $position Can be one of 'before' or 'after'
		 */
		$position = apply_filters( 'tasty_recipes_comment_form_rating_position', 'before' );
		if ( 'before' === $position ) {
			return $rating_html . $comment_form;
		} elseif ( 'after' === $position ) {
			return $comment_form . $rating_html;
		}
		// Invalid $position, so just return the comment form.
		return $comment_form;
	}

	/**
	 * Filters the rendered comment text to include a rating when it exists.
	 *
	 * @param string $comment_text Existing comment text.
	 * @param object $comment      Comment object, if included.
	 * @return string
	 */
	public static function filter_comment_text( $comment_text, $comment = null ) {

		if ( ! self::is_enabled()
			|| ! $comment
			|| ! Tasty_Recipes::has_recipe( $comment->comment_post_ID ) ) {
			return $comment_text;
		}

		$rating_keys = array(
			self::COMMENT_META_KEY,
			self::CB_COMMENT_META_KEY,
			self::SRP_COMMENT_META_KEY,
			self::WPRM_COMMENT_META_KEY,
			self::ZRP_COMMENT_META_KEY,
		);
		$rating      = '';
		foreach ( $rating_keys as $rating_key ) {
			$rating = get_comment_meta( $comment->comment_ID, $rating_key, true );
			if ( $rating ) {
				break;
			}
		}

		if ( ! $rating ) {
			return $comment_text;
		}

		$rating = PHP_EOL . '<p class="tasty-recipes-ratings">' . self::get_rendered_rating( $rating ) . '</p>';
		return $comment_text . $rating;
	}

	/**
	 * Get the HTML for a rendered rating.
	 *
	 * @param integer $rating        Rating value.
	 * @param string  $customization Integration with card customization.
	 * @param string  $style         Style to use.
	 * @return string
	 */
	public static function get_rendered_rating( $rating, $customization = '', $style = null ) {
		if ( ! $rating ) {
			return '';
		}

		if ( is_null( $style ) ) {
			$settings = Tasty_Recipes::get_customization_settings();
			$style    = $settings['star_ratings_style'];
		}
		if ( 'solid' === $style ) {
			$icons = self::get_rating_icon_html();
			$icon  = $icons['checked'];
		} elseif ( 'outline' === $style ) {
			$icon = file_get_contents( dirname( __DIR__ ) . '/assets/images/star-rating-clip.svg' );
		} else {
			return '';
		}

		$ret = '';
		for ( $i = 1; $i <= 5; $i++ ) {
			if ( ceil( $rating ) >= $i || 'outline' === $style ) {
				$diff = round( $rating, 1 ) - $i + 1;
				if ( $diff < 1 && $diff > 0 ) {
					$clip = 'tasty-recipes-clip-' . ( $diff * 100 );
				} elseif ( $diff >= 1 ) {
					$clip = 'tasty-recipes-clip-100';
				} else {
					$clip = 'tasty-recipes-clip-0';
				}
				$ret .= '<span';
				if ( ! empty( $customization ) ) {
					$ret .= ' data-tasty-recipes-customization="' . esc_attr( $customization ) . '"';
				}
				$ret .= ' class="' . esc_attr( 'tasty-recipes-rating ' . $clip . ' tasty-recipes-rating-' . $style ) . '">' . $icon . '</span>';
			}
		}
		return $ret;
	}

	/**
	 * Returns the rating icon HTML.
	 *
	 * @return array
	 */
	private static function get_rating_icon_html() {
		return array(
			'unchecked' => '&#9734;',
			'checked'   => '&#9733;',
		);
	}

}
