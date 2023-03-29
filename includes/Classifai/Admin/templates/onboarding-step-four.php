<?php
/**
 * Step-1 template for ClassifAI Onboarding.
 *
 * @package ClassifAI
 */

use function Classifai\get_post_types_for_language_settings;

$onboarding_options   = get_option( 'classifai_onboarding_options', array() );
$enabled_features     = isset( $onboarding_options['enabled_features'] ) ? $onboarding_options['enabled_features'] : array();
$configured_providers = isset( $onboarding_options['configured_providers'] ) ? $onboarding_options['configured_providers'] : array();
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
				<?php esc_html_e( 'ClassifAI configured successfully!', 'classifai' ); ?>
			</h1>
			<?php
			$divider = false;
			if ( count( array_intersect( array( 'computer_vision', 'openai_chatgpt' ), $configured_providers ) ) > 1 && isset( $enabled_features['language'] ) ) {
				?>
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
								if ( ! isset( $features[ $posttype->name ] ) ) {
									continue;
								}
								?>
								<li class="classifai-enable-feature">
									<span class="dashicons dashicons-yes-alt"></span>
									<label class="classifai-feature-text">
									<?php
										// translators: %s is the post type label.
										printf( esc_html__( 'Automatically tag %s', 'classifai' ), esc_html( $posttype->label ) );
									?>
									</label>
								</li>
								<?php
							}
							if ( isset( $enabled_features['language']['excerpt_generation'] ) ) {
								?>
								<li class="classifai-enable-feature">
									<span class="dashicons dashicons-yes-alt"></span>
									<label class="classifai-feature-text">
										<?php esc_html_e( 'Automatic excerpt generation', 'classifai' ); ?>
									</label>
								</li>
								<?php
							}
							?>
						</ul>
					</div>
				</div>
				<?php
			}

			if ( in_array( 'computer_vision', $configured_providers, true ) && isset( $enabled_features['images'] ) ) {
				?>
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
								if ( ! isset( $enabled_features['images'][ $image_feature ] ) ) {
									continue;
								}
								?>
								<li class="classifai-enable-feature">
									<span class="dashicons dashicons-yes-alt"></span>
									<label class="classifai-feature-text">
										<?php echo esc_html( $image_feature_label ); ?>
									</label>
								</li>
								<?php
							}
							?>
						</ul>
					</div>
				</div>
				<?php
			}

			if ( in_array( 'personalizer', $configured_providers, true ) && isset( $enabled_features['recommended_content'] ) ) {
				?>
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
								<input type="checkbox" class="classifai-feature-checkbox" name="classifai-features[recommended_content]" value="yes">
							</li>
						</ul>
					</div>
				</div>
				<?php
			}
			?>

			<div class="classifai-setup-form">
				<div class="classifai-setup-footer">
					<span class="classifai-setup-footer__left">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=classifai_settings' ) ); ?>" class="classifai-setup-skip-link">
							<?php esc_html_e( 'Adjust ClassifAI settings', 'classifai' ); ?>
						</a>
					</span>
					<span class="classifai-setup-footer__right">
						<a class="classifai-button" href="<?php echo esc_url( admin_url() ); ?>">
							<?php esc_html_e( 'Done', 'classifai' ); ?>
						</a>
					</span>
				</div>
			</div>
		</div>
	</div>
</div>
