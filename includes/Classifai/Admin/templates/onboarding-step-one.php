<?php
/**
 * Step-1 template for ClassifAI Onboarding.
 *
 * @package ClassifAI
 */

$onboarding         = new Classifai\Admin\Onboarding();
$features           = $onboarding->get_features();
$has_configured     = $onboarding->has_configured_providers();
$onboarding_options = Classifai\Admin\Onboarding::get_onboarding_options();
$enabled_features   = $onboarding_options['enabled_features'] ?? array();

$args = array(
	'step'       => 1,
	'title'      => __( 'Welcome to ClassifAI', 'classifai' ),
	'image'      => esc_url( CLASSIFAI_PLUGIN_URL . 'assets/img/onboarding-1.png' ),
	'left_link'  => array(
		'text' => __( 'Skip for now', 'classifai' ),
		'url'  => admin_url(),
	),
	'right_link' => array(
		'text'   => __( 'Start Setup', 'classifai' ),
		'submit' => true,
	),
);

// Header
require_once 'onboarding-header.php';
?>

<h2 class="classifai-setup-title">
	<?php esc_html_e( 'Set up ClassifAI to meet your needs', 'classifai' ); ?>
</h2>
<?php
foreach ( $features as $key => $feature ) {
	if ( empty( $feature['title'] ) || empty( $feature['features'] ) ) {
		continue;
	}
	?>
	<div class="classifai-feature-box">
		<div class="classifai-feature-box-title">
			<?php echo esc_html( $feature['title'] ); ?>
		</div>
		<div class="classifai-features">
			<ul>
				<?php
				foreach ( $feature['features'] as $provider => $provider_features ) {
					foreach ( $provider_features as $feature_key => $feature_options ) {
						$checked = false;
						if ( $has_configured ) {
							// For existing users, enable features based on their saved configuration.
							$checked = $feature_options['enabled'] ?? false;
						} elseif ( ! empty( $enabled_features ) ) {
							// Enable features based on user selection.
							$checked = isset( $enabled_features[ $provider ][ $feature_key ] );
							if ( count( explode( '__', $feature_key ) ) > 1 ) {
								$keys    = explode( '__', $feature_key );
								$checked = isset( $enabled_features[ $provider ][ $keys[0] ][ $keys[1] ] );
							}
						} else {
							// Enable all features by default.
							$checked = true;
							if ( strpos( $feature_key, 'post_types__' ) !== false ) {
								if ( ! in_array( str_replace( 'post_types__', '', $feature_key ), array( 'post', 'page' ), true ) ) {
									$checked = false;
								}
							}
						}
						?>
						<li class="classifai-enable-feature">
							<label class="classifai-toggle">
								<span class="classifai-feature-text">
									<?php echo esc_html( $feature_options['title'] ); ?>
								</span>
								<input type="checkbox" class="classifai-toggle-checkbox" name="<?php echo esc_attr( 'classifai-features[' . $provider . '][' . str_replace( '__', '][', $feature_key ) . ']' ); ?>" value="1" <?php checked( $checked, '1', true ); ?>>
								<span class="classifai-toggle-switch"></span>
							</label>
						</li>
						<?php
					}
				}
				?>
			</ul>
		</div>
	</div>
	<?php
	if ( array_key_last( $features ) !== $key ) {
		?>
		<div class="classifai-feature-box-divider"></div>
		<?php
	}
}

// Footer
require_once 'onboarding-footer.php';
