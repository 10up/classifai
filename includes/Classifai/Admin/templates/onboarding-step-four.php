<?php
/**
 * Step-4 template for ClassifAI Onboarding.
 *
 * @package ClassifAI
 */

$onboarding_options   = get_option( 'classifai_onboarding_options', array() );
$enabled_features     = $onboarding_options['enabled_features'] ?? array();
$configured_providers = $onboarding_options['configured_providers'] ?? array();
$onboarding           = new Classifai\Admin\Onboarding();
$features             = array_filter(
	$onboarding->get_features(),
	function( $feature ) use ( $enabled_features, $configured_providers ) {
		return ! empty( $feature['features'] ) && array_intersect( array_keys( $feature['features'] ), array_keys( $enabled_features ) ) && array_intersect( array_keys( $feature['features'] ), $configured_providers );
	}
);

$args = array(
	'step'       => 4,
	'title'      => __( 'Welcome to ClassifAI', 'classifai' ),
	'image'      => 'https://via.placeholder.com/334x334',
	'left_link'  => array(
		'text' => __( 'Adjust ClassifAI settings', 'classifai' ),
		'url'  => admin_url( 'tools.php?page=classifai' ),
	),
	'right_link' => array(
		'text'   => __( 'Done', 'classifai' ),
		'submit' => false,
		'url'    => admin_url(),
	),
);

// Header
require_once 'onboarding-header.php';
?>

<h2 class="classifai-setup-title">
	<?php esc_html_e( 'ClassifAI configured successfully!', 'classifai' ); ?>
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
					if ( ! in_array( $provider, $configured_providers, true ) ) {
						continue;
					}
					foreach ( $provider_features as $feature_key => $feature_name ) {
						if ( ! in_array( $feature_key, array_keys( $enabled_features[ $provider ] ?? array() ), true ) ) {
							continue;
						}
						?>
						<li class="classifai-enable-feature">
							<span class="dashicons dashicons-yes-alt"></span>
							<label class="classifai-feature-text">
								<?php echo esc_html( $feature_name ); ?>
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
}

// Footer
require_once 'onboarding-footer.php';
