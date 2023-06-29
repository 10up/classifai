<?php
/**
 * Metabox class.
 *
 * @since 2.2.3
 * @package Classifai
 */

namespace Classifai\Admin;

use Classifai\Providers\OpenAI\Embeddings;

defined( 'ABSPATH' ) || exit;

/**
 * This class generates the metabox for the classic editor post types.
 */
class Metabox {

	/**
	 * Current post type name.
	 *
	 * @var string
	 */
	protected $post_type = '';

	/**
	 * @var \Classifai\Providers\OpenAI\Embeddings
	 */
	private $embeddings;

	/**
	 * Checks whether this class's register method should run.
	 *
	 * @return bool
	 * @since 1.4.0
	 */
	public function can_register() {
		return is_admin();
	}

	/**
	 * Class constructor.
	 */
	public function register() {
		$this->embeddings = new Embeddings( false );
		add_action( 'add_meta_boxes', [ $this, 'add_metabox' ] );
		add_action( 'save_post', [ $this, 'save_metabox' ] );
	}

	/**
	 * Add metabox.
	 *
	 * @param string $post_type Post type name.
	 */
	public function add_metabox( $post_type ) {

		$this->post_type = $post_type;
		$settings        = $this->embeddings->get_settings();

		// Set up the embeddings metabox if the feature is enabled.
		if ( isset( $settings['enable_classification'] ) && 1 === (int) $settings['enable_classification'] ) {
			$embeddings_post_types = $this->embeddings->supported_post_types();
			$classic_post_types    = get_post_types( [ 'show_in_rest' => false ], 'names', 'and' );
			$post_types            = apply_filters( 'classifai_embeddings_classic_types', array_intersect( $embeddings_post_types, $classic_post_types ) );

			add_meta_box(
				'classifai_embeddings_metabox',
				__( 'ClassifAI', 'classifai' ),
				array( $this, 'render_metabox' ),
				$post_types,
				'side',
				'default'
			);
		}
	}

	/**
	 * Render metabox.
	 *
	 * @param WP_POST $post A WordPress post instance.
	 * @return void
	 */
	public function render_metabox( $post ) {

		$classifai_process_content = get_post_meta( $post->ID, '_classifai_process_content', true );
		$checked = 'no' === $classifai_process_content ? '' : 'checked="checked"'; 

		// Add nonce.
		wp_nonce_field( 'classifai_embeddings_save_posts', '_nonce' );
		?>
		<div class='classifai-metabox classifai-metabox-embeddings'>
			<p>
				<label for="classifai-process-content" class="classifai-preview-toggle">
					<input type="checkbox" value="yes" name="_classifai_process_content" id="classifai-process-content" <?php echo esc_html( $checked ); ?> />
					<strong><?php esc_html_e( 'Process content on update', 'classifai' ); ?></strong>
				</label>
			</p>
			<p class="howto">
				<?php esc_html_e( 'ClassifAI language processing is enabled', 'classifai' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Handles saving the metabox.
	 *
	 * @param int $post_id Current post ID.
	 * @return void
	 */
	public function save_metabox( $post_id ) {

		if ( empty( $_POST['_nonce'] ) ) {
			return;
		}

		// Add nonce for security and authentication.
		$nonce_action = 'classifai_embeddings_save_terms';

		// Check if nonce is valid.
		if ( ! wp_verify_nonce( $_POST['_nonce'], $nonce_action ) ) {
			return;
		}

		// Check if user has permissions to save data.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Check if not an autosave.
		if ( wp_is_post_autosave( $post_id ) ) {
			return;
		}

		// Check if not a revision.
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		$classifai_process_content = isset( $_POST['_classifai_process_content'] ) ? esc_url_raw( wp_unslash( $_POST['_classifai_process_content'] ) ) : '';

		if ( 'yes' !== $classifai_process_content ) {
			update_post_meta( $post_id, '_classifai_process_content', 'no' );
		}
	}
}
