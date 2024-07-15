<?php

namespace Classifai;

use ActionScheduler_Action;
use ActionScheduler_DBLogger;
use ActionScheduler_Store;

class EmbeddingsScheduler {

	/**
	 * The name of the job.
	 *
	 * @var string
	 */
	private $job_name = '';

	/**
	 * The name of the provider.
	 *
	 * @var string
	 */
	private $provider_name = '';

	/**
	 * EmbeddingsScheduler constructor.
	 *
	 * @param string $job_name      The name of the job.
	 * @param string $provider_name The label of the provider.
	 */
	public function __construct( $job_name = '', $provider_name = '' ) {
		$this->job_name      = $job_name;
		$this->provider_name = $provider_name;
	}

	/**
	 * Initialize the class.
	 */
	public function init() {
		add_filter( 'heartbeat_send', [ $this, 'check_embedding_generation_status' ] );
		add_action( 'classifai_before_feature_nav', [ $this, 'render_embeddings_generation_status' ] );
		add_action( 'action_scheduler_after_execute', [ $this, 'log_failed_embeddings' ], 10, 2 );
	}

	/**
	 * Check if embeddings generation is in progress.
	 *
	 * @return bool
	 */
	public function is_embeddings_generation_in_progress(): bool {
		if ( ! class_exists( 'ActionScheduler_Store' ) ) {
			return false;
		}

		$store = ActionScheduler_Store::instance();

		$action_id = $store->find_action(
			$this->job_name,
			array(
				'status' => ActionScheduler_Store::STATUS_PENDING,
			)
		);

		return ! empty( $action_id );
	}

	/**
	 * Render the embeddings generation status notice.
	 */
	public function render_embeddings_generation_status() {
		if ( ! $this->is_embeddings_generation_in_progress() ) {
			return;
		}
		?>

		<div class="notice notice-info classifai-classification-embeddings-message">
			<p>
			<?php
			printf(
				'<strong>%1$s</strong>: %2$s',
				esc_html( $this->provider_name ),
				esc_html__( 'Generation of embeddings is in progress.', 'classifai' )
			)
			?>
			</p>
		</div>

		<?php
	}

	/**
	 * AJAX callback to check the status of embeddings generation.
	 *
	 * @param array $response The heartbeat response.
	 * @return array
	 */
	public function check_embedding_generation_status( $response ) {
		$response['classifaiEmbedInProgress'] = $this->is_embeddings_generation_in_progress();

		return $response;
	}

	/**
	 * Logs failed embeddings.
	 *
	 * @param int                    $action_id The action ID.
	 * @param ActionScheduler_Action $action    The action object.
	 */
	public function log_failed_embeddings( $action_id, $action ) {
		if ( ! class_exists( 'ActionScheduler_DBLogger' ) ) {
			return;
		}

		if ( $this->job_name !== $action->get_hook() ) {
			return;
		}

		$args = $action->get_args();

		if ( ! isset( $args['args'] ) && ! isset( $args['args']['exclude'] ) ) {
			return;
		}

		$excludes = $args['args']['exclude'];

		if ( empty( $excludes ) || ( 1 === count( $excludes ) && in_array( 1, $excludes, true ) ) ) {
			return;
		}

		$logger = new ActionScheduler_DBLogger();
		$logger->log( $action_id, sprintf( 'Embeddings failed for terms: %s', implode( ', ', $excludes ) ) );
	}
}
