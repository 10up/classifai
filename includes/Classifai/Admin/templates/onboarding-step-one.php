<?php
/**
 * Step-1 template for ClassifAI Onboarding.
 *
 * @package ClassifAI
 */

$onboarding         = new Classifai\Admin\Onboarding();
$features           = $onboarding->get_features();
$onboarding_options = Classifai\Admin\Onboarding::get_onboarding_options();
$enabled_features   = $onboarding_options['enabled_features'] ?? $onboarding->get_default_features();

$args = array(
	'step'       => 1,
	'title'      => __( 'Welcome to ClassifAI', 'classifai' ),
	'image'      => 'https://via.placeholder.com/334x334',
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

<h1 class="classifai-setup-title">
	<?php esc_html_e( 'Set up ClassifAI to meet your needs', 'classifai' ); ?>
</h1>
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
					foreach ( $provider_features as $feature_key => $feature_name ) {
						$checked = $enabled_features[ $provider ][ $feature_key ] ?? '';
						?>
						<li class="classifai-enable-feature">
							<label class="classifai-toggle">
								<span class="classifai-feature-text">
									<?php echo esc_html( $feature_name ); ?>
								</span>
								<input type="checkbox" class="classifai-toggle-checkbox" name="<?php echo esc_attr( 'classifai-features[' . $provider . '][' . $feature_key . ']' ); ?>" value="1" <?php checked( $checked, '1', true ); ?>>
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
