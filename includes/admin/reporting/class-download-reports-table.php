<?php
/**
 * Download Reports Table Class
 *
 * @package     CS
 * @subpackage  Admin/Reports
 * @copyright   Copyright (c) 2018, Easy Digital Downloads, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.5
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

use CS\Admin\List_Table;

/**
 * CS_Download_Reports_Table Class
 *
 * Renders the Download Reports table
 *
 * @since 1.5
 */
class CS_Download_Reports_Table extends List_Table {

	/**
	 * @var object Query results
	 * @since 1.5.2
	 */
	private $products;

	/**
	 * Get things started
	 *
	 * @since 1.5
	 * @see WP_List_Table::__construct()
	 */
	public function __construct() {
		parent::__construct( array(
			'singular' => 'report-' . cs_get_label_singular(),
			'plural'   => 'report-' . cs_get_label_plural(),
			'ajax'     => false
		) );

		add_action( 'cs_report_view_actions', array( $this, 'category_filter' ) );

		$this->query();
	}

	/**
	 * Gets the name of the primary column.
	 *
	 * @since 2.5
	 * @access protected
	 *
	 * @return string Name of the primary column.
	 */
	protected function get_primary_column_name() {
		return 'title';
	}

	/**
	 * This function renders most of the columns in the list table.
	 *
	 * @since 1.5
	 *
	 * @param array $item Contains all the data of the downloads
	 * @param string $column_name The name of the column
	 *
	 * @return string Column Name
	 */
	public function column_default( $item, $column_name ) {
		switch( $column_name ){
			case 'earnings' :
				return cs_currency_filter( cs_format_amount( $item[ $column_name ] ) );
			case 'average_sales' :
				return round( $item[ $column_name ] );
			case 'average_earnings' :
				return cs_currency_filter( cs_format_amount( $item[ $column_name ] ) );
			case 'details' :
				return '<a href="' . admin_url( 'edit.php?post_type=' . CS_POST_TYPE . '&page=cs-reports&view=downloads&download-id=' . $item['ID'] ) . '">' . __( 'View Detailed Report', 'commercestore' ) . '</a>';
			default:
				return $item[ $column_name ];
		}
	}

	/**
	 * Retrieve the table columns
	 *
	 * @since 1.5
	 * @return array $columns Array of all the list table columns
	 */
	public function get_columns() {
		return array(
			'title'            => cs_get_label_singular(),
			'sales'            => __( 'Sales',                    'commercestore' ),
			'earnings'         => __( 'Earnings',                 'commercestore' ),
			'average_sales'    => __( 'Monthly Average Sales',    'commercestore' ),
			'average_earnings' => __( 'Monthly Average Earnings', 'commercestore' ),
			'details'          => __( 'Detailed Report',          'commercestore' )
		);
	}

	/**
	 * Retrieve the sortable columns
	 *
	 * @since 1.4
	 * @return array Array of all the sortable columns
	 */
	public function get_sortable_columns() {
		return array(
			'title'    => array( 'title',    true  ),
			'sales'    => array( 'sales',    false ),
			'earnings' => array( 'earnings', false )
		);
	}

	/**
	 * Retrieve the category being viewed
	 *
	 * @since 1.5.2
	 * @return int Category ID
	 */
	public function get_category() {
		return absint( $this->get_request_var( 'category', 0 ) );
	}

	/**
	 * Retrieve the total number of downloads
	 *
	 * @since 1.5
	 * @return int $total Total number of downloads
	 */
	public function get_total_downloads() {
		$total  = 0;
		$counts = wp_count_posts( CS_POST_TYPE, 'readable' );

		foreach( $counts as $count ) {
			$total += $count;
		}

		return $total;
	}

	/**
	 * Outputs the reporting views
	 *
	 * These aren't really bulk actions but this outputs the markup in the
	 * right place.
	 *
	 * @since 1.5
	 * @return void
	 */
	public function bulk_actions( $which = '' ) {
		cs_report_views();
	}

	/**
	 * Attaches the category filter to the log views
	 *
	 * @since 1.5.2
	 * @return void
	 */
	public function category_filter() {
		if ( get_terms( CS_CAT_TYPE ) ) {
			echo CS()->html->category_dropdown( 'category', $this->get_category() );
		}
	}

	/**
	 * Performs the products query
	 *
	 * @since 1.5.2
	 * @return void
	 */
	public function query() {

		$orderby  = sanitize_text_field( $this->get_request_var( 'orderby', 'title' ) );
		$order    = sanitize_text_field( $this->get_request_var( 'order',   'DESC'  ) );
		$category = $this->get_category();

		$args = array(
			'post_type'        => CS_POST_TYPE,
			'post_status'      => 'publish',
			'order'            => $order,
			'fields'           => 'ids',
			'posts_per_page'   => $this->per_page,
			'paged'            => $this->get_paged(),
			'suppress_filters' => true
		);

		if ( ! empty( $category ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => CS_CAT_TYPE,
					'terms'    => $category
				)
			);
		}

		switch ( $orderby ) {
			case 'title' :
				$args['orderby'] = 'title';
				break;

			case 'sales' :
				$args['orderby']  = 'meta_value_num';
				$args['meta_key'] = '_cs_download_sales';
				break;

			case 'earnings' :
				$args['orderby']  = 'meta_value_num';
				$args['meta_key'] = '_cs_download_earnings';
				break;
		}

		$r = apply_filters( 'cs_download_reports_prepare_items_args', $args, $this );

		$this->products = new WP_Query( $r );
	}

	/**
	 * Build and retrieves all of the download reports data.
	 *
	 * @since 1.5
	 * @deprecated 3.0 Use get_data()
	 *
	 * @return array All the data for customer reports.
	 */
	public function reports_data() {
		_cs_deprecated_function( __METHOD__, '3.0', 'CS_Download_Reports_Table::get_data()' );

		return $this->get_data();
	}

	/**
	 * Retrieves all of the download reports data.
	 *
	 * @since 3.0
	 *
	 * @return array Download reports table data.
	 */
	public function get_data() {
		$reports_data = array();

		$downloads = $this->products->posts;

		if ( $downloads ) {
			foreach ( $downloads as $download ) {
				$reports_data[] = array(
					'ID'               => $download,
					'title'            => get_the_title( $download ),
					'sales'            => cs_get_download_sales_stats( $download ),
					'earnings'         => cs_get_download_earnings_stats( $download ),
					'average_sales'    => cs_get_average_monthly_download_sales( $download ),
					'average_earnings' => cs_get_average_monthly_download_earnings( $download ),
				);
			}
		}

		return $reports_data;
	}

	/**
	 * Setup the final data for the table
	 *
	 * @since 1.5
	 * @uses CS_Download_Reports_Table::get_columns()
	 * @uses CS_Download_Reports_Table::get_sortable_columns()
	 * @uses CS_Download_Reports_Table::get_total_downloads()
	 * @uses CS_Download_Reports_Table::get_data()
	 * @uses CS_Download_Reports_Table::set_pagination_args()
	 * @return void
	 */
	public function prepare_items() {
		$this->_column_headers = array(
			$this->get_columns(),
			array(),
			$this->get_sortable_columns()
		);

		$total_items = $this->get_total_downloads();
		$this->items = $this->get_data();

		$this->set_pagination_args( array(
			'total_pages' => ceil( $total_items / $this->per_page ),
			'total_items' => $total_items,
			'per_page'    => $this->per_page,
		) );
	}
}
