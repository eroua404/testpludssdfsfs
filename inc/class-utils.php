<?php
/**
 * Utility methods used across classes.
 *
 * @package Tasty_Recipes
 */

namespace Tasty_Recipes;

/**
 * Utility methods used across classes.
 */
class Utils {

	/**
	 * Get existing shortcode to convert
	 *
	 * @param string $content       Content to parse for a shortcode.
	 * @param string $shortcode_tag Shortcode tag to look for.
	 * @return string|false
	 */
	public static function get_existing_shortcode( $content, $shortcode_tag ) {
		if ( false === stripos( $content, $shortcode_tag ) ) {
			return false;
		}
		$backup_tags = $GLOBALS['shortcode_tags'];
		remove_all_shortcodes();
		add_shortcode( $shortcode_tag, '__return_false' );
		preg_match_all( '/' . get_shortcode_regex() . '/', $content, $matches, PREG_SET_ORDER );
		if ( empty( $matches ) ) {
			$GLOBALS['shortcode_tags'] = $backup_tags;
			return false;
		}

		$existing = false;
		foreach ( $matches as $shortcode ) {
			if ( $shortcode_tag === $shortcode[2] ) {
				$existing = $shortcode[0];
				break;
			}
		}
		$GLOBALS['shortcode_tags'] = $backup_tags;
		return $existing;
	}

	/**
	 * Transforms a fraction amount into a proper number.
	 *
	 * @param string $amount Existing amount to process.
	 * @return float
	 */
	public static function process_amount_into_float( $amount ) {
		$vulgar_fractions = array(
			'¼' => '1/4',
			'½' => '1/2',
			'¾' => '3/4',
			'⅐' => '1/7',
			'⅑' => '1/9',
			'⅒' => '1/10',
			'⅓' => '1/3',
			'⅔' => '2/3',
			'⅕' => '1/5',
			'⅖' => '2/5',
			'⅗' => '3/5',
			'⅘' => '4/5',
			'⅙' => '1/6',
			'⅚' => '5/6',
			'⅛' => '1/8',
			'⅜' => '3/8',
			'⅝' => '5/8',
			'⅞' => '7/8',
		);
		// Transform '1½' into '1 ½' to avoid interpretation as '11/2'.
		$amount = preg_replace( '#^([\d+])(' . implode( '|', array_keys( $vulgar_fractions ) ) . ')#', '$1 $2', $amount );
		// Now transform vulgar fractions to their numeric equivalent.
		$amount = str_replace( array_keys( $vulgar_fractions ), array_values( $vulgar_fractions ), $amount );

		// Replace unicode ⁄ with standard forward slash.
		$amount = str_replace( '⁄', '/', $amount );

		// Handle English language 1-10.
		$english_amounts = array(
			'one half of an' => 0.5,
			'one half of a'  => 0.5,
			'half of an'     => 0.5,
			'half of a'      => 0.5,
			'half an'        => 0.5,
			'half a'         => 0.5,
			'one'            => 1,
			'two'            => 2,
			'three'          => 3,
			'four'           => 4,
			'five'           => 5,
			'six'            => 6,
			'seven'          => 7,
			'eight'          => 8,
			'nine'           => 9,
			'ten'            => 10,
		);
		$amount          = str_replace( array_keys( $english_amounts ), array_values( $english_amounts ), $amount );

		// This is an amount with fractions.
		if ( false !== stripos( $amount, '/' ) ) {
			$bits = explode( ' ', $amount );
			// Something like "1 1/2".
			// Otherwise something like "1/4".
			if ( count( $bits ) === 2 ) {
				$base = (int) array_shift( $bits );
			} elseif ( count( $bits ) === 1 ) {
				$base = 0;
			}
			if ( isset( $base ) ) {
				$frac_bits = explode( '/', array_shift( $bits ) );
				$amount    = $base + ( $frac_bits[0] / $frac_bits[1] );
			}
		}
		return $amount;
	}

	/**
	 * Makes a unit singular.
	 *
	 * @param string $unit The unit to make singular.
	 * @return string
	 */
	public static function make_singular( $unit ) {
		return preg_replace( '#s$#', '', $unit );
	}

	/**
	 * Gets the ID from a YouTube URL, if one exists.
	 *
	 * @param string $url URL to inspect.
	 * @return string|false
	 */
	public static function get_youtube_id( $url ) {
		$url = trim( $url );
		if ( empty( $url ) ) {
			return false;
		}
		$host = parse_url( $url, PHP_URL_HOST );
		if ( ! $host ) {
			return false;
		}
		$base_host = implode( '.', array_slice( explode( '.', $host ), -2, 2 ) );
		if ( ! in_array( $base_host, array( 'youtube.com', 'youtu.be' ), true ) ) {
			return false;
		}
		if ( 'youtube.com' === $base_host ) {
			$path = parse_url( $url, PHP_URL_PATH );
			// Something like https://www.youtube.com/embed/HZpnBPiCYnA?feature=oembed.
			if ( 0 === stripos( $path, '/embed/' ) ) {
				return trim( str_replace( '/embed/', '', $path ), '/' );
			}
			// Something like https://youtube.com/shorts/JnLsjVy3soI.
			if ( 0 === stripos( $path, '/shorts/' ) ) {
				return trim( str_replace( '/shorts/', '', $path ), '/' );
			}
			parse_str( parse_url( $url, PHP_URL_QUERY ), $args );
			return ! empty( $args['v'] ) ? $args['v'] : false;
		} elseif ( 'youtu.be' === $base_host ) {
			return trim( parse_url( $url, PHP_URL_PATH ), '/' );
		}
		return false;
	}

	/**
	 * Gets an attribute value from a given element.
	 *
	 * @param string $html Original HTML.
	 * @param string $el   Base HTML element.
	 * @param string $attr Attribute name.
	 * @return string|false
	 */
	public static function get_element_attribute( $html, $el, $attr ) {
		if ( false === stripos( $html, '<' . $el ) ) {
			return false;
		}
		if ( preg_match( '#<' . $el . '[^>]+' . $attr . '=[\'"]([^\'"]+)[\'"][^>]*>#', $html, $matches ) ) {
			return $matches[1];
		}
		return false;
	}

	/**
	 * Minify a CSS string with PHP.
	 *
	 * @see https://github.com/matthiasmullie/minify/blob/master/src/CSS.php
	 *
	 * @param string $content Existing CSS.
	 * @return string
	 */
	public static function minify_css( $content ) {
		/*
		 * Remove whitespace
		 */
		// remove leading & trailing whitespace.
		$content = preg_replace( '/^\s*/m', '', $content );
		$content = preg_replace( '/\s*$/m', '', $content );
		// replace newlines with a single space.
		$content = preg_replace( '/\s+/', ' ', $content );
		// remove whitespace around meta characters.
		// inspired by stackoverflow.com/questions/15195750/minify-compress-css-with-regex.
		$content = preg_replace( '/\s*([\*$~^|]?+=|[{};,>~]|!important\b)\s*/', '$1', $content );
		$content = preg_replace( '/([\[(:>\+])\s+/', '$1', $content );
		$content = preg_replace( '/\s+([\]\)>\+])/', '$1', $content );
		$content = preg_replace( '/\s+(:)(?![^\}]*\{)/', '$1', $content );
		// whitespace around + and - can only be stripped inside some pseudo-
		// classes, like `:nth-child(3+2n)`
		// not in things like `calc(3px + 2px)`, shorthands like `3px -2px`, or
		// selectors like `div.weird- p`.
		$pseudos = array( 'nth-child', 'nth-last-child', 'nth-last-of-type', 'nth-of-type' );
		$content = preg_replace( '/:(' . implode( '|', $pseudos ) . ')\(\s*([+-]?)\s*(.+?)\s*([+-]?)\s*(.*?)\s*\)/', ':$1($2$3$4$5)', $content );
		// remove semicolon/whitespace followed by closing bracket.
		$content = str_replace( ';}', '}', $content );
		// Shorten colors.
		$content = preg_replace( '/(?<=[: ])#([0-9a-z])\\1([0-9a-z])\\2([0-9a-z])\\3(?:([0-9a-z])\\4)?(?=[; }])/i', '#$1$2$3$4', $content );
		// remove alpha channel if it's pointless...
		$content = preg_replace( '/(?<=[: ])#([0-9a-z]{6})ff?(?=[; }])/i', '#$1', $content );
		$content = preg_replace( '/(?<=[: ])#([0-9a-z]{3})f?(?=[; }])/i', '#$1', $content );
		return trim( $content );
	}

}
