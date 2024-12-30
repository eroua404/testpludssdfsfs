<?php
/**
 * Bold recipe card template.
 *
 * @package Tasty_Recipes
 */

$print_view_options = tasty_recipes_get_print_view_options();
?>
<?php
echo $recipe_styles;
$ext           = is_feed() ? '.png' : '.svg';
$other_details = '';

?>

<?php
if ( '.svg' === $ext ) {
	echo str_replace( array( "\n", "\t" ), '', file_get_contents( __DIR__ . '/images/icon-sprite.svg' ) );
}
?>

<header class="tasty-recipes-entry-header" data-tasty-recipes-customization="primary-color.background">
	<?php if ( ! empty( $recipe_image ) && isset( $print_view_options['images'] ) ) : ?>
		<div class="tasty-recipes-image">
			<?php
			$recipe_image = str_replace( '<img ', '<img data-tasty-recipes-customization="primary-color.border-color" ', $recipe_image );
			echo $recipe_image;
			?>
		</div>
	<?php endif; ?>
	<h2 class="tasty-recipes-title" data-tasty-recipes-customization="h2-color.color h2-transform.text-transform"><?php echo $recipe_title; ?></h2>
	<hr data-tasty-recipes-customization="secondary-color.border-color secondary-color.background-color">
	<?php if ( ! empty( $recipe_rating_icons ) || ! empty( $recipe_rating_label ) ) : ?>
		<div class="tasty-recipes-rating">
			<?php if ( Tasty_Recipes\Ratings::is_enabled() ) : ?>
				<a href="#respond">
			<?php endif; ?>
				<?php if ( ! empty( $recipe_rating_icons ) ) : ?>
					<p><?php echo $recipe_rating_icons; ?></p>
				<?php endif; ?>
				<?php if ( ! empty( $recipe_rating_label ) ) : ?>
					<p><?php echo $recipe_rating_label; ?></p>
				<?php endif; ?>
			<?php if ( Tasty_Recipes\Ratings::is_enabled() ) : ?>
				</a>
			<?php endif; ?>
		</div>
	<?php endif; ?>
	<?php
	if ( ! empty( $recipe_details ) ) :
		$card_top_details = array_diff(
			array_keys( $recipe_details ),
			array( 'cook_time', 'prep_time', 'additional_time', 'method', 'cuisine', 'category' )
		);
		/**
		 * Allow the details on the top of the card to be filtered.
		 *
		 * @var array $card_top_details Array of the details to include at the top of the card.
		 */
		$card_top_details = apply_filters( 'tasty_recipes_card_top_details', $card_top_details );
		?>
		<div class="tasty-recipes-details">
			<ul>
				<?php foreach ( $recipe_details as $key => $detail ) : ?>
						<?php
						$icons = array(
							'cook_time'       => 'icon-clock',
							'prep_time'       => 'icon-clock',
							'additional_time' => 'icon-clock',
							'total_time'      => 'icon-clock',
							'method'          => 'icon-squares',
							'cuisine'         => 'icon-flag',
							'category'        => 'icon-folder',
							'yield'           => 'icon-cutlery',
						);
						if ( in_array( $key, $card_top_details, true ) ) :
							?>
						<li class="<?php echo esc_attr( $detail['class'] ); ?>"><span class="tasty-recipes-label" data-tasty-recipes-customization="detail-label-color.color">
							<?php
							if ( isset( $icons[ $key ] ) ) {
								if ( '.svg' === $ext ) {
									echo '<svg viewBox="0 0 24 24" class="detail-icon" aria-hidden="true"><use xlink:href="' . esc_attr( '#tasty-recipes-' . $icons[ $key ] ) . '" data-tasty-recipes-customization="icon-color.color"></use></svg>';
								} else {
									echo '<img nopin="nopin" data-pin-nopin="1" class="detail-icon" src="' . esc_url( plugins_url( 'images/' . $icons[ $key ] . $ext, __FILE__ ) ) . '">';
								}
							}
							?>
							<?php echo $detail['label']; ?>:</span> <?php echo $detail['value']; ?>
						</li>
							<?php
					else :
						$other_details .= '<li class="' . esc_attr( $detail['class'] ) . '"><span class="tasty-recipes-label" data-tasty-recipes-customization="detail-label-color.color">';
						if ( isset( $icons[ $key ] ) ) {
							if ( '.svg' === $ext ) {
								$other_details .= '<svg viewBox="0 0 24 24" class="detail-icon" aria-hidden="true" data-tasty-recipes-customization="icon-color.color"><use xlink:href="' . esc_attr( '#tasty-recipes-' . $icons[ $key ] ) . '"></use></svg>';
							} else {
								$other_details .= '<img nopin="nopin" data-pin-nopin="1" class="detail-icon" src="' . esc_url( plugins_url( 'images/' . $icons[ $key ] . $ext, __FILE__ ) ) . '">';
							}
						}
						$other_details .= $detail['label'] . ':</span> ' . $detail['value'] . '</li>';
					endif;
					?>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>
</header>

<div class="tasty-recipes-entry-content">

	<?php if ( ! tasty_recipes_is_print() ) : ?>
	<div class="tasty-recipes-buttons">
		<?php if ( ! empty( $first_button ) ) : ?>
		<div class="tasty-recipes-button-wrap">
			<?php echo $first_button; ?>
		</div>
		<?php endif; ?>
		<?php if ( ! empty( $second_button ) ) : ?>
		<div class="tasty-recipes-button-wrap">
			<?php echo $second_button; ?>
		</div>
		<?php endif; ?>
	</div>
	<?php endif; ?>

	<?php
	$show_hr = false;
	if ( ! empty( $recipe_description ) && '<div itemprop="description"></div>' !== $recipe_description && isset( $print_view_options['description'] ) ) :
		$show_hr = true;
		?>
		<div class="tasty-recipes-description">
			<h3 data-tasty-recipes-customization="h3-color.color h3-transform.text-transform"><?php esc_html_e( 'Description', 'tasty-recipes' ); ?></h3>
			<div class="tasty-recipes-description-body" data-tasty-recipes-customization="body-color.color">
				<?php echo $recipe_description; ?>
			</div>
		</div>
	<?php endif; ?>

	<?php
	if ( $show_hr ) :
		$show_hr = false;
		?>
		<hr data-tasty-recipes-customization="secondary-color.border-color secondary-color.background-color">
	<?php endif; ?>

	<?php
	if ( ! empty( $recipe_ingredients ) ) :
		$show_hr = true;
		?>
		<div class="tasty-recipes-ingredients">
			<div class="tasty-recipes-ingredients-header">
				<div class="tasty-recipes-ingredients-clipboard-container">
					<h3 data-tasty-recipes-customization="h3-color.color h3-transform.text-transform"><?php esc_html_e( 'Ingredients', 'tasty-recipes' ); ?></h3>
					<?php if ( $copy_ingredients ) : ?>
						<?php echo $copy_ingredients; ?>
					<?php endif; ?>
				</div>
				<div class="tasty-recipes-units-scale-container">
					<?php if ( ! empty( $recipe_convertable ) ) : ?>
						<span class="tasty-recipes-convert-container">
							<span class="tasty-recipes-convert-label"><?php esc_html_e( 'Units', 'tasty-recipes' ); ?></span>
							<?php echo $recipe_convertable; ?>
						</span>
					<?php endif; ?>
					<?php if ( ! empty( $recipe_scalable ) ) : ?>
						<span class="tasty-recipes-scale-container">
							<span class="tasty-recipes-scale-label"><?php esc_html_e( 'Scale', 'tasty-recipes' ); ?></span>
							<?php echo $recipe_scalable; ?>
						</span>
					<?php endif; ?>
				</div>
			</div>
			<div data-tasty-recipes-customization="body-color.color">
				<?php echo $recipe_ingredients; ?>
			</div>
		</div>
	<?php endif; ?>

	<?php
	if ( $show_hr ) :
		$show_hr = false;
		?>
		<hr data-tasty-recipes-customization="secondary-color.border-color secondary-color.background-color">
	<?php endif; ?>

	<?php
	if ( ! empty( $recipe_instructions ) ) :
		$show_hr = true;
		?>
	<div class="tasty-recipes-instructions">
		<div class="tasty-recipes-instructions-header">
			<h3 data-tasty-recipes-customization="h3-color.color h3-transform.text-transform"><?php esc_html_e( 'Instructions', 'tasty-recipes' ); ?></h3>
			<?php if ( ! empty( $recipe_instructions_has_video ) ) : ?>
			<div class="tasty-recipes-video-toggle-container">
				<label for="tasty-recipes-video-toggle"><?php esc_html_e( 'Video', 'tasty-recipes' ); ?></label>
				<button type="button" role="switch" aria-checked="true" name="tasty-recipes-video-toggle">
					<span><?php esc_html_e( 'On', 'tasty-recipes' ); ?></span>
					<span><?php esc_html_e( 'Off', 'tasty-recipes' ); ?></span>
				</button>
			</div>
			<?php endif; ?>
		</div>
		<div data-tasty-recipes-customization="body-color.color">
			<?php echo $recipe_instructions; ?>
		</div>
	</div>
	<?php endif; ?>

	<?php
	if ( ! empty( $recipe_video_embed ) ) :
		$show_hr = false;
		?>
		<div class="tasty-recipe-video-embed" id="<?php echo esc_attr( 'tasty-recipe-video-embed-' . $recipe->get_id() ); ?>">
			<?php echo $recipe_video_embed; ?>
		</div>
	<?php endif; ?>

	<?php
	if ( $show_hr ) :
		$show_hr = false;
		?>
	<?php endif; ?>

	<?php
	if ( ! empty( $recipe_equipment ) ) :
		$show_hr = true;
		?>
		<div class="tasty-recipes-equipment">
			<h3 data-tasty-recipes-customization="h3-color.color h3-transform.text-transform"><?php esc_html_e( 'Equipment', 'tasty-recipes' ); ?></h3>
			<?php echo $recipe_equipment; ?>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $recipe_notes ) && isset( $print_view_options['notes'] ) ) : ?>
		<div class="tasty-recipes-notes" data-tasty-recipes-customization="secondary-color.background-color">
			<h3 data-tasty-recipes-customization="h3-color.color h3-transform.text-transform"><?php esc_html_e( 'Notes', 'tasty-recipes' ); ?></h3>
			<div class="tasty-recipes-notes-body" data-tasty-recipes-customization="body-color.color">
				<?php echo $recipe_notes; ?>
			</div>
		</div>
	<?php endif; ?>

	<?php if ( $other_details ) : ?>
		<div class="tasty-recipes-other-details" data-tasty-recipes-customization="secondary-color.background-color">
			<ul>
				<?php echo $other_details; ?>
			</ul>
		</div>
	<?php endif; ?>

	<?php do_action( 'tasty_recipes_card_before_nutrition' ); ?>

	<?php if ( ! empty( $recipe_nutrifox_embed ) && isset( $print_view_options['nutrition'] ) ) : ?>
		<div class="tasty-recipes-nutrifox">
			<?php echo $recipe_nutrifox_embed; ?>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $recipe_nutrition ) && isset( $print_view_options['nutrition'] ) ) : ?>
			<div class="tasty-recipes-nutrition">
			<h3 data-tasty-recipes-customization="h3-color.color h3-transform.text-transform"><?php esc_html_e( 'Nutrition', 'tasty-recipes' ); ?></h3>
			<ul>
				<?php foreach ( $recipe_nutrition as $nutrition ) : ?>
					<li><strong class="tasty-recipes-label" data-tasty-recipes-customization="body-color.color"><?php echo $nutrition['label']; ?>:</strong> <?php echo $nutrition['value']; ?></li>
				<?php endforeach; ?>
			</ul>
			</div>
	<?php endif; ?>

	<?php if ( ! empty( $recipe_keywords ) ) : ?>
		<div class="tasty-recipes-keywords" data-tasty-recipes-customization="secondary-color.background-color">
			<p data-tasty-recipes-customization="detail-value-color.color"><span class="tasty-recipes-label" data-tasty-recipes-customization="detail-label-color.color"><?php esc_html_e( 'Keywords', 'tasty-recipes' ); ?>:</span> <?php echo $recipe_keywords; ?></p>
		</div>
	<?php endif; ?>

	<footer class="tasty-recipes-entry-footer" data-tasty-recipes-customization="primary-color.background">
		<div class="tasty-recipes-footer-content">
			<?php if ( ! empty( $footer_social_platform ) ) : ?>
				<?php if ( '.svg' === $ext ) : ?>
					<svg viewBox="0 0 24 24" class="<?php echo esc_attr( 'svg-' . $footer_social_platform ); ?>" aria-hidden="true" data-tasty-recipes-customization="footer-icon-color.color"><use xlink:href="<?php echo esc_attr( '#tasty-recipes-icon-' . $footer_social_platform ); ?>"></use></svg>
				<?php else : ?>
					<img class="<?php echo esc_attr( 'svg-' . $footer_social_platform ); ?>" data-pin-nopin="true" src="<?php echo esc_url( plugins_url( 'images/icon-' . $footer_social_platform . '.png', __FILE__ ) ); ?>">
				<?php endif; ?>
			<?php endif; ?>
			<div class="tasty-recipes-footer-copy">
				<h3 data-tasty-recipes-customization="footer-heading-color.color h3-transform.text-transform footer-heading.innerText"><?php echo esc_html( $footer_heading ); ?></h3>
				<div data-tasty-recipes-customization="footer-description-color.color footer-description.innerHTML"><?php echo wp_kses_post( $footer_description ); ?></div>
			</div>
		</div>
	</footer>
</div>

<?php if ( tasty_recipes_is_print() && get_post() ) : ?>
<div class="tasty-recipes-source-link">
	<p><strong class="tasty-recipes-label"><?php esc_html_e( 'Find it online', 'tasty-recipes' ); ?></strong>: <a href="<?php echo esc_url( get_permalink( get_the_ID() ) ); ?>"><?php echo esc_url( get_permalink( get_the_ID() ) ); ?></a></p>
</div>
<?php endif; ?>

<?php echo $recipe_scripts; ?>
