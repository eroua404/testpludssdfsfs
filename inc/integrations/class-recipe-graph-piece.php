<?php
/**
 * Integrates Tasty Recipes with Yoast SEO's Open Graph.
 *
 * @package Tasty_Recipes
 */

namespace Tasty_Recipes\Integrations;

use WPSEO_Schema_IDs;

/**
 * Integrates Tasty Recipes with Yoast SEO's Open Graph.
 */
class Recipe_Graph_Piece implements \WPSEO_Graph_Piece {

	/**
	 * A value object with context variables.
	 *
	 * @var WPSEO_Schema_Context
	 */
	private $context;

	/**
	 * Recipe associated with this instance.
	 *
	 * @var array
	 */
	private $recipe;

	/**
	 * Whether or not an article is present on this page too.
	 *
	 * @var boolean
	 */
	private $using_article;

	/**
	 * Recipe_Graph_Piece constructor.
	 *
	 * @param \WPSEO_Schema_Context $context A value object with context variables.
	 */
	public function __construct( \WPSEO_Schema_Context $context ) {
		$this->context = $context;

		$this->using_article = false;
		add_filter( 'wpseo_schema_article', array( $this, 'filter_wpseo_schema_article' ) );
	}

	/**
	 * Keeps track of whether the Yoast SEO article schema is used.
	 *
	 * @param array $data Existing article schema data.
	 * @return array
	 */
	public function filter_wpseo_schema_article( $data ) {
		$this->using_article = true;
		if ( $this->is_needed() ) {
			// Use the recipe as the main entity of the page.
			unset( $data['mainEntityOfPage'] );
		}
		return $data;
	}

	/**
	 * Determines whether or not a piece should be added to the graph.
	 *
	 * @return boolean
	 */
	public function is_needed() {
		if ( ! is_singular() ) {
			return false;
		}
		$recipes = \Tasty_Recipes::get_recipes_for_post(
			$this->context->id,
			array(
				'disable-json-ld' => false,
			)
		);
		if ( empty( $recipes ) ) {
			return false;
		}
		$this->recipe = array_shift( $recipes );
		return true;
	}

	/**
	 * Returns Recipe Schema data.
	 *
	 * @return array|boolean Recipe data on success, false on failure.
	 */
	public function generate() {
		$schema                     = \Tasty_Recipes\Distribution_Metadata::get_enriched_google_schema_for_recipe( $this->recipe, get_post( $this->context->id ) );
		$schema['@id']              = $this->context->canonical . '#recipe';
		$schema['isPartOf']         = array(
			'@id' => $this->using_article ? $this->context->canonical . WPSEO_Schema_IDs::ARTICLE_HASH : $this->context->canonical . WPSEO_Schema_IDs::WEBPAGE_HASH,
		);
		$schema['mainEntityOfPage'] = $this->context->canonical . WPSEO_Schema_IDs::WEBPAGE_HASH;
		return $schema;
	}

}
