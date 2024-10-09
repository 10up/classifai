<?php

namespace Classifai;

use Classifai\Features\TermCleanup;
use ActionScheduler_Store;

class TermCleanupScheduler {

	/**
	 * The name of the job.
	 *
	 * @var string
	 */
	private $job_name = '';

	/**
	 * TermCleanupScheduler constructor.
	 *
	 * @param string $job_name The name of the job.
	 */
	public function __construct( string $job_name = '' ) {
		$this->job_name = $job_name;
	}

	/**
	 * Initialize the class.
	 */
	public function init() {
		add_action( 'classifai_schedule_term_cleanup_job', [ $this, 'run' ] );
	}

	/**
	 * Run the term cleanup job.
	 *
	 * @param array $item Item details to process.
	 */
	public function run( array $item = [] ) {
		$action = $item['action'];

		if ( ! $action ) {
			return;
		}

		switch ( $action ) {
			case 'term_cleanup':
				$started_by           = absint( $item['started_by'] );
				$taxonomy             = $item['taxonomy'];
				$thresold             = $item['thresold'];
				$term_cleanup         = new TermCleanup();
				$embeddings_generated = (bool) $item['embeddings_generated'];

				$original_user_id = get_current_user_id();

				// Set the user to the one who started the process, to avoid permission issues.
				wp_set_current_user( (int) $started_by );

				// Generate embeddings if not already generated.
				if ( ! $embeddings_generated ) {
					$results = $term_cleanup->generate_embeddings( $taxonomy );

					if ( is_wp_error( $results ) ) {
						$term_cleanup->add_notice(
							// translators: %s: error message.
							sprintf( esc_html__( 'Error in generating embeddings: %s', 'classifai' ), $results->get_error_message() ),
							'error'
						);

						return;
					}

					// If get we false, then there are no further terms to process.
					if ( false === $results ) {
						$item['embeddings_generated'] = true;
						$this->schedule( [ $item ] );
						return;
					}

					$this->schedule( [ $item ] );
					return;
				}

				// Find similar terms.
				$args = array(
					'processed' => $item['processed'] ?? 0,
					'term_id'   => $item['term_id'] ?? 0,
					'offset'    => $item['offset'] ?? 0,
				);
				$res  = $term_cleanup->get_similar_terms( $taxonomy, $thresold, $args );

				// Restore original user.
				wp_set_current_user( $original_user_id );

				if ( is_wp_error( $res ) ) {
					$term_cleanup->add_notice(
						// translators: %s: error message.
						sprintf( esc_html__( 'Error in finding similar terms: %s', 'classifai' ), $res->get_error_message() ),
						'error'
					);

					return;
				}

				if ( false === $res ) {
					$label = strtolower( $term_cleanup->get_taxonomy_label( $taxonomy, true ) );

					// Show notice to user.
					$term_cleanup->add_notice(
						// translators: %s: taxonomy label.
						sprintf( __( 'Process for finding similar %s has been completed.', 'classifai' ), $label ),
						'success'
					);

					// No more terms to process.
					return;
				}

				// Update item.
				$item['processed'] = $res['processed'];
				$item['term_id']   = $res['term_id'];
				$item['offset']    = $res['offset'];

				$this->schedule( [ $item ] );
				return;
			default:
				return;
		}
	}

	/**
	 * Schedule the term cleanup job.
	 *
	 * @param array $args Arguments to pass to the job.
	 */
	public function schedule( array $args = [] ) {
		if ( function_exists( 'as_enqueue_async_action' ) ) {
			as_enqueue_async_action( 'classifai_schedule_term_cleanup_job', $args );
		}
	}

	/**
	 * Check if job is in progress.
	 *
	 * @return bool
	 */
	public function in_progress(): bool {
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
	 * Get the arguments for the current job.
	 *
	 * @return array|bool
	 */
	public function get_args() {
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

		if ( empty( $action_id ) ) {
			return false;
		}

		$action = $store->fetch_action( $action_id );
		$args   = $action->get_args();

		return $args;
	}
}
