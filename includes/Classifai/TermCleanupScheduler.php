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
		add_action( $this->job_name, [ $this, 'run' ] );
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

				// Check if cancel request is made.
				if ( isset( $item['job_id'] ) && get_transient( 'classifai_cancel_term_cleanup_process' ) === $item['job_id'] ) {
					delete_transient( 'classifai_cancel_term_cleanup_process' );
					return;
				}

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

				/**
				 * Fires when a batch of similar terms are calculated.
				 *
				 * @since 3.2.0
				 * @hook classifai_feature_term_cleanup_get_similar_terms
				 *
				 * @param {array|bool|WP_Error} $res      Response from the get_similar_terms method.
				 * @param {string}              $taxonomy Taxonomy of terms we are comparing.
				 * @param {array}               $args     Arguments used for getting similar terms.
				 */
				do_action( 'classifai_feature_term_cleanup_get_similar_terms', $res, $taxonomy, $args );

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
			as_enqueue_async_action( $this->job_name, $args );
		}
	}

	/**
	 * Unschedule the term cleanup job.
	 *
	 * @return bool
	 */
	public function unschedule() {
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( $this->job_name );

			if ( ! class_exists( 'ActionScheduler_Store' ) ) {
				return false;
			}

			$store = ActionScheduler_Store::instance();

			// Check if the job is still in progress.
			$action_id = $store->find_action(
				$this->job_name,
				array(
					'status' => ActionScheduler_Store::STATUS_RUNNING,
				)
			);

			// If no action running, return true.
			if ( empty( $action_id ) ) {
				return true;
			}

			$action = $store->fetch_action( $action_id );
			$args   = $action->get_args();
			if ( ! empty( $args ) && isset( $args[0]['job_id'] ) ) {
				set_transient( 'classifai_cancel_term_cleanup_process', $args[0]['job_id'], 300 );
			}

			return true;
		}

		return false;
	}

	/**
	 * Check if job is in progress.
	 *
	 * @return bool
	 */
	public function in_progress(): bool {
		if ( function_exists( 'as_has_scheduled_action' ) ) {
			return as_has_scheduled_action( $this->job_name );
		}

		return false;
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

		$running_action_id = $store->find_action(
			$this->job_name,
			array(
				'status' => ActionScheduler_Store::STATUS_RUNNING,
			)
		);

		$pending_action_id = $store->find_action(
			$this->job_name,
			array(
				'status' => ActionScheduler_Store::STATUS_PENDING,
			)
		);

		if ( empty( $running_action_id ) && empty( $pending_action_id ) ) {
			return false;
		}

		$action_id = ! empty( $running_action_id ) ? $running_action_id : $pending_action_id;
		$action    = $store->fetch_action( $action_id );
		$args      = $action->get_args();

		return $args;
	}
}
