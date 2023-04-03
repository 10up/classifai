<?php
/**
 * Step-2 template for ClassifAI Onboarding.
 *
 * @package ClassifAI
 */

$args = array(
	'step'       => 2,
	'title'      => __( 'Register ClassifAI', 'classifai' ),
	'left_link'  => array(
		'text' => __( 'Skip for now', 'classifai' ),
		'url'  => wp_nonce_url( admin_url( 'admin-post.php?action=classifai_skip_step&step=2' ), 'classifai_skip_step_action', 'classifai_skip_step_nonce' ),
	),
	'right_link' => array(
		'text'   => __( 'Register', 'classifai' ),
		'submit' => true,
	),
);

// Header
require_once 'onboarding-header.php';
?>

<div class="classifai-setup-form">
	<?php
	Classifai\Admin\Onboarding::render_classifai_setup_settings( 'classifai_settings', array( 'email', 'registration-key' ) );
	?>
</div>

<?php
// Footer
require_once 'onboarding-footer.php';
