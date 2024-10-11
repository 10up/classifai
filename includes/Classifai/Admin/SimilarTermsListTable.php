<?php

namespace Classifai\Admin;

use WP_List_Table;

/**
 * Class for displaying the list of similar terms for a given taxonomy.
 *
 * @see WP_List_Table
 */
class SimilarTermsListTable extends WP_List_Table {

	/**
	 * Taxonomy to get similar terms for.
	 *
	 * @var string
	 */
	protected $taxonomy;

	/**
	 * ID of last rendered term.
	 *
	 * @var int
	 */
	protected $last_item_id;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $taxonomy The taxonomy to get similar terms for.
	 */
	public function __construct( $taxonomy ) {
		$this->taxonomy = $taxonomy;

		// Set parent defaults.
		parent::__construct(
			array(
				'singular' => 'similar_term',
				'plural'   => 'similar_terms',
				'ajax'     => false,
			)
		);
	}

	/**
	 * Gets the list of columns.
	 *
	 * @return string[] Array of column titles keyed by their column name.
	 */
	public function get_columns() {
		$tax    = get_taxonomy( $this->taxonomy );
		$labels = get_taxonomy_labels( $tax );
		$label  = $labels->singular_name ?? __( 'Term', 'classifai' );

		return array(
			'term'         => $label,
			// translators: %s: Singular label of the taxonomy.
			'similar_term' => sprintf( __( 'Similar %s', 'classifai' ), $label ),
			'actions'      => __( 'Action', 'classifai' ),
		);
	}

	/**
	 * Prepares the list of items for displaying.
	 */
	public function prepare_items() {
		$per_page = $this->get_items_per_page( 'edit_post_per_page' );
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();
		$search   = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$total = wp_count_terms(
			[
				'taxonomy'     => $this->taxonomy,
				'hide_empty'   => false,
				'meta_key'     => 'classifai_similar_terms', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_compare' => 'EXISTS',
				'search'       => $search,
			]
		);

		$this->set_pagination_args(
			array(
				'total_items' => $total,  // WE have to calculate the total number of items.
				'per_page'    => $per_page, // WE have to determine how many items to show on a page.
				'total_pages' => ceil( $total / $per_page ), // WE have to calculate the total number of pages.
			)
		);

		$current = $this->get_pagenum();
		$offset  = ( $current - 1 ) * $per_page;

		$terms = get_terms(
			[
				'taxonomy'     => $this->taxonomy,
				'orderby'      => 'count',
				'order'        => 'DESC',
				'hide_empty'   => false,
				'fields'       => 'ids',
				'meta_key'     => 'classifai_similar_terms', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_compare' => 'EXISTS',
				'number'       => $per_page,
				'offset'       => $offset,
				'search'       => $search,
			]
		);

		$items = [];

		foreach ( $terms as $term_id ) {
			$similar_terms = get_term_meta( $term_id, 'classifai_similar_terms', true );

			if ( ! $similar_terms ) {
				continue;
			}

			foreach ( $similar_terms as $k => $v ) {
				$similar_term = get_term( $k );
				if ( $similar_term ) {
					$items[] = [
						'term'         => get_term( $term_id ),
						'similar_term' => $similar_term,
						'score'        => $v,
					];
				} else {
					unset( $similar_terms[ $k ] );
					update_term_meta( $term_id, 'classifai_similar_terms', $similar_terms );
				}
			}

			if ( empty( $similar_terms ) ) {
				delete_term_meta( $term_id, 'classifai_similar_terms' );
			}
		}

		$this->items = $items;
	}

	/**
	 * Generate term html to show it in Similar terms list table
	 *
	 * @param WP_Term $term         Term Object.
	 * @param WP_Term $similar_term Similar Term Object.
	 * @param float   $score        Similarity score.
	 * @return string
	 */
	public function generate_term_html( $term, $similar_term, $score = null ) {
		$args      = array(
			'action'   => 'classifai_merge_term',
			'taxonomy' => $this->taxonomy,
			'from'     => $similar_term->term_id,
			'to'       => $term->term_id,
			'paged'    => $this->get_pagenum(),
			's'        => isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : false, // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		);
		$merge_url = add_query_arg( $args, wp_nonce_url( admin_url( 'admin-post.php' ), 'classifai_merge_term' ) );
		$score     = $score ? ( $score > 1 ? $score - 1 : $score ) : '';

		return sprintf(
			// translators: %s: Term name, %d: Term ID.
			__( '<span><strong>%1$s</strong> (ID: %2$s)</span><br/><br/>', 'classifai' ) .
			// translators: %s: Term slug.
			__( '<span><strong>Slug:</strong> %3$s</span><br/>', 'classifai' ) .
			// translators: %s: Term count.
			__( '<span><strong>Used:</strong> %4$s</span><br/>', 'classifai' ) .
			// translators: %s: Term parent name.
			__( '<span><strong>Parent:</strong> %5$s</span><br/>', 'classifai' ) .
			// translators: %s: Similarity score.
			( $score ? __( '<span><strong>Similarity:</strong> %6$s</span><br/>', 'classifai' ) : '%6$s' ) .
			'<a href="%7$s" class="button button-primary term-merge-button">%8$s</a>',
			esc_html( $term->name ),
			'<a href="' . esc_url( get_edit_term_link( $term->term_id, $term->taxonomy ) ) . '" target="_blank">' . esc_html( $term->term_id ) . '</a>',
			esc_html( $term->slug ),
			// translators: %d: Term count.
			'<a href="' . esc_url( admin_url( 'edit.php?tag=' . $term->slug ) ) . '" target="_blank">' . esc_html( sprintf( _n( '%d time', '%d times', $term->count, 'classifai' ), $term->count ) ) . '</a>',
			esc_html( $term->parent > 0 ? get_term( $term->parent )->name : 'None' ),
			$score ? esc_html( round( $score * 100, 2 ) . '%' ) : '',
			esc_url( $merge_url ),
			esc_html__( 'Merge and keep this', 'classifai' )
		);
	}

	/**
	 * Handles the term column output.
	 *
	 * @param array $item The current term item.
	 */
	public function column_term( $item ) {
		$term               = $item['term'];
		$similar_term       = $item['similar_term'];
		$this->last_item_id = $term->term_id;

		return $this->generate_term_html( $term, $similar_term );
	}

	/**
	 * Handles the similar term column output.
	 *
	 * @param array $item The current term item.
	 */
	public function column_similar_term( $item ) {
		$term         = $item['term'];
		$similar_term = $item['similar_term'];

		return $this->generate_term_html( $similar_term, $term, $item['score'] );
	}

	/**
	 * Handles the term actions output.
	 *
	 * @param array $item The current term item.
	 */
	public function column_actions( $item ) {
		$term         = $item['term'];
		$similar_term = $item['similar_term'];

		$args     = array(
			'action'       => 'classifai_skip_similar_term',
			'taxonomy'     => $this->taxonomy,
			'term'         => $term->term_id,
			'similar_term' => $similar_term->term_id,
			'paged'        => $this->get_pagenum(),
			's'            => isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : false, // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		);
		$skip_url = add_query_arg( $args, wp_nonce_url( admin_url( 'admin-post.php' ), 'classifai_skip_similar_term' ) );

		return sprintf(
			"<a href='%s' class='button button-secondary'>%s</a>",
			esc_url( $skip_url ),
			esc_html__( 'Skip', 'classifai' )
		);
	}

	/**
	 * Generates content for a single row of the table
	 *
	 * @param array  $item        The current item.
	 * @param string $column_name The current column name.
	 */
	protected function column_default( $item, $column_name ) {
		return esc_html( $item[ $column_name ] );
	}

	/**
	 * Generates custom table navigation to prevent conflicting nonces.
	 *
	 * @param string $which The location of the bulk actions: Either 'top' or 'bottom'.
	 */
	protected function display_tablenav( $which ) {
		?>
		<div class="tablenav <?php echo esc_attr( $which ); ?>">
			<div class="alignleft actions bulkactions">
				<?php $this->bulk_actions( $which ); ?>
			</div>
			<?php
			$this->extra_tablenav( $which );
			$this->pagination( $which );
			?>
			<br class="clear" />
		</div>
		<?php
	}

	/**
	 * Gets the name of the default primary column.
	 *
	 * @return string Name of the default primary column, in this case, 'term'.
	 */
	protected function get_default_primary_column_name() {
		return 'term';
	}

	/**
	 * Generates content for a single row of the table.
	 *
	 * @param object|array $item The current item
	 */
	public function single_row( $item ) {
		$term  = $item['term'];
		$class = 'border';

		if ( $this->last_item_id === $term->term_id ) {
			$class .= ' skip';
		}

		echo '<tr class="' . esc_attr( $class ) . '">';
		$this->single_row_columns( $item );
		echo '</tr>';
	}
}
