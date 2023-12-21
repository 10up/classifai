<?php
/**
 * Step-4 template for ClassifAI Onboarding.
 *
 * @package ClassifAI
 */

$onboarding_options  = get_option( 'classifai_onboarding_options', array() );
$enabled_features    = $onboarding_options['enabled_features'] ?? array();
$configured_features = $onboarding_options['configured_features'] ?? array();
$onboarding          = new Classifai\Admin\Onboarding();
$features            = $onboarding->get_features();

$args = array(
	'step'       => 4,
	'title'      => __( 'Welcome to ClassifAI', 'classifai' ),
	'image'      => esc_url( CLASSIFAI_PLUGIN_URL . 'assets/img/onboarding-4.png' ),
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
				foreach ( $feature['features'] as $feature_key => $feature_class ) {
					$enabled = isset( $enabled_features[ $feature_key ] );
					if ( ! $enabled ) {
						continue;
					}

					$is_configured = $feature_class->is_feature_enabled();
					$icon_class    = $is_configured ? 'dashicons-yes-alt' : 'dashicons-dismiss';
					?>
					<li class="classifai-enable-feature">
						<span class="dashicons <?php echo esc_attr( $icon_class ); ?>"></span>
						<label class="classifai-feature-text">
							<?php echo esc_html( $feature_class->get_label() ); ?>
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

// Footer
require_once 'onboarding-footer.php';
