<?php
/**
 * Integrates Tasty Recipes with Rank Math
 *
 * @package Tasty_Recipes
 */

namespace Tasty_Recipes\Integrations;

/**
 * Integrates Tasty Recipes with Rank Math.
 */
class Rank_Math {

	/**
	 * Enqueue Rank Math JavaScript when Tasty Recipes is enqueued.
	 */
	public static function action_admin_enqueue_scripts() {
		$time = filemtime( dirname( TASTY_RECIPES_PLUGIN_FILE ) . '/assets/js/rank-math.js' );
		wp_enqueue_script(
			'tasty-recipes-rank-math',
			plugins_url( 'assets/js/rank-math.js?r=' . (int) $time, TASTY_RECIPES_PLUGIN_FILE ),
			array(
				'jquery',
				'wp-hooks',
				'tasty-recipes-block-editor',
				'rank-math-analyzer',
			),
			false,
			true
		);
	}
}
