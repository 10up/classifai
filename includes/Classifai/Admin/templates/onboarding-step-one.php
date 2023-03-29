<?php
/**
 * Step-1 template for ClassifAI Onboarding.
 *
 * @package ClassifAI
 */

use function Classifai\get_post_types_for_language_settings;

$onboarding_options = get_option( 'classifai_onboarding_options', array() );
$enabled_features   = isset( $onboarding_options['enabled_features'] ) ? $onboarding_options['enabled_features'] : array();

// Display any errors.
settings_errors( 'classifai-setup' );
?>
<h1 class="classifai-setup-heading">
	<?php esc_html_e( 'Welcome to ClassifAI', 'classifai' ); ?>
</h1>
<div class="classifai-spacer"></div>
<div class="classifai-setup__content__row">
	<div class="classifai-setup__content__row__column">
		<div class="classifai-setup-image">
			<img src="https://via.placeholder.com/334x334" alt="<?php esc_attr_e( 'ClassifAI Setup', 'classifai' ); ?>" />
		</div>
	</div>
	<div class="classifai-setup__content__row__column">
		<form method="POST" action="">
			<div class="classifai-step1-content">
			<h1 class="classifai-setup-title">
				<?php esc_html_e( 'Set up ClassifAI to meet your needs', 'classifai' ); ?>
			</h1>
			<div class="classifai-feature-box">
				<div class="classifai-feature-box-title">
					<?php esc_html_e( 'Language', 'classifai' ); ?>
				</div>
				<div class="classifai-features">
					<ul>
						<?php
						$types    = get_post_types_for_language_settings();
						$features = isset( $enabled_features['language']['classify'] ) ? $enabled_features['language']['classify'] : array();
						foreach ( $types as $posttype ) {
							$checked = isset( $features[ $posttype->name ] ) ? $features[ $posttype->name ] : '';
							?>
							<li class="classifai-enable-feature">
								<label class="classifai-feature-text">
									<?php
									// translators: %s is the post type label.
									printf( esc_html__( 'Automatically tag %s', 'classifai' ), esc_html( $posttype->label ) );
									?>
								</label>
								<input type="checkbox" class="classifai-feature-checkbox" name="classifai-features[language][classify][<?php echo esc_attr( $posttype->name ); ?>]" value="yes" <?php checked( $checked, 'yes', true ); ?>>
							</li>
							<?php
						}
						$checked = isset( $enabled_features['language']['excerpt_generation'] ) ? $enabled_features['language']['excerpt_generation'] : '';
						?>
						<li class="classifai-enable-feature">
							<label class="classifai-feature-text">
								<?php esc_html_e( 'Automatic excerpt generation', 'classifai' ); ?>
							</label>
							<input type="checkbox" class="classifai-feature-checkbox" name="classifai-features[language][excerpt_generation]" value="yes" <?php checked( $checked, 'yes', true ); ?>>
						</li>
					</ul>
				</div>
			</div>
			<div class="classifai-feature-box-divider"></div>

			<div class="classifai-feature-box">
				<div class="classifai-feature-box-title">
					<?php esc_html_e( 'Images', 'classifai' ); ?>
				</div>
				<div class="classifai-features">
					<ul>
						<?php
						$image_features = array(
							'image_captions' => __( 'Automatically add alt-text to Images', 'classifai' ),
							'image_tags'     => __( 'Automatically tag Images', 'classifai' ),
							'image_crop'     => __( 'Smart crop Images', 'classifai' ),
							'image_ocr'      => __( 'Scan images for text', 'classifai' ),
						);
						foreach ( $image_features as $image_feature => $image_feature_label ) {
							$checked = isset( $enabled_features['images'][ $image_feature ] ) ? $enabled_features['images'][ $image_feature ] : '';
							?>
							<li class="classifai-enable-feature">
								<label class="classifai-feature-text">
									<?php echo esc_html( $image_feature_label ); ?>
								</label>
								<input type="checkbox" class="classifai-feature-checkbox" name="classifai-features[images][<?php echo esc_attr( $image_feature ); ?>]" value="yes" <?php checked( $checked, 'yes', true ); ?> />
							</li>
							<?php
						}
						?>
					</ul>
				</div>
			</div>
			<div class="classifai-feature-box-divider"></div>

			<div class="classifai-feature-box">
				<div class="classifai-feature-box-title">
					<?php esc_html_e( 'Recommended Content', 'classifai' ); ?>
				</div>
				<div class="classifai-features">
					<ul>
						<li class="classifai-enable-feature">
							<label class="classifai-feature-text">
								<?php esc_html_e( 'Recommended content block', 'classifai' ); ?>
							</label>
							<?php
							$checked = isset( $enabled_features['recommended_content'] ) ? $enabled_features['recommended_content'] : '';
							?>
							<input type="checkbox" class="classifai-feature-checkbox" name="classifai-features[recommended_content]" value="yes" <?php checked( $checked, 'yes', true ); ?> >
						</li>
					</ul>
				</div>
			</div>

			<div class="classifai-setup-footer">
				<span class="classifai-setup-footer__left">
					<a href="<?php echo esc_url( admin_url() ); ?>" class="classifai-setup-skip-link">
						<?php esc_html_e( 'Skip for now', 'classifai' ); ?>
					</a>
				</span>
				<span class="classifai-setup-footer__right">
					<input name="classifai-setup-step" type="hidden" value="1" />
					<?php wp_nonce_field( 'classifai-setup-step-one-action', 'classifai-setup-step-one-nonce' ); ?>
					<input class="classifai-button" type="submit" value="<?php esc_attr_e( 'Start Setup', 'classifai' ); ?>" />
				</span>
			</div>
		</div>
		</form>
	</div>
</div>
