<?php
/**
 * Batch API Request Logs Export Class
 *
 * This class handles API request logs export
 *
 * @package     CS
 * @subpackage  Admin/Reporting/Export
 * @copyright   Copyright (c) 2018, Easy Digital Downloads, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.7
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * CS_Batch_API_Requests_Export Class
 *
 * @since 2.7
 */
class CS_Batch_API_Requests_Export extends CS_Batch_Export {
	/**
	 * Our export type. Used for export-type specific filters/actions
	 *
	 * @var string
	 * @since 2.7
	 */
	public $export_type = 'api_requests';

	/**
	 * Set the CSV columns.
	 *
	 * @since 2.7
	 * @return array $cols All the columns
	 */
	public function csv_cols() {
		$cols = array(
			'ID'      => __( 'Log ID',   'commercestore' ),
			'request' => __( 'API Request', 'commercestore' ),
			'ip'      => __( 'IP Address', 'commercestore' ),
			'user'    => __( 'API User', 'commercestore' ),
			'key'     => __( 'API Key', 'commercestore' ),
			'version' => __( 'API Version', 'commercestore' ),
			'speed'   => __( 'Request Speed', 'commercestore' ),
			'date'    => __( 'Date', 'commercestore' )
		);

		return $cols;
	}

	/**
	 * Get the export data.
	 *
	 * @since 2.7
	 * @since 3.0 Updated to use new query methods.
	 *
	 * @return array $data The data for the CSV file.
	 */
	public function get_data() {
		$data = array();

		$args = array(
			'number' => 30,
			'offset' => ( $this->step * 30 ) - 30
		);

		if ( ! empty( $this->start ) || ! empty( $this->end ) ) {
			$args['date_query'] = $this->get_date_query();
		}

		$logs = cs_get_api_request_logs( $args );

		foreach ( $logs as $log ) {
			/** @var CS\Logs\Api_Request_Log $log */

			$data[] = array(
				'ID'      => $log->id,
				'request' => $log->request,
				'ip'      => $log->ip,
				'user'    => $log->user_id,
				'key'     => $log->api_key,
				'version' => $log->version,
				'speed'   => $log->time,
				'date'    => $log->date_created
			);
		}

		$data = apply_filters( 'cs_export_get_data', $data );
		$data = apply_filters( 'cs_export_get_data_' . $this->export_type, $data );

		return ! empty( $data )
			? $data
			: false;
	}

	/**
	 * Return the calculated completion percentage.
	 *
	 * @since 2.7
	 * @since 3.0 Updated to use new query methods.
	 *
	 * @return int Percentage complete.
	 */
	public function get_percentage_complete() {
		$args = array(
			'fields' => 'ids',
		);

		if ( ! empty( $this->start ) || ! empty( $this->end ) ) {
			$args['date_query'] = $this->get_date_query();
		}

		$total = cs_count_api_request_logs( $args );
		$percentage = 100;

		if ( $total > 0 ) {
			$percentage = ( ( 30 * $this->step ) / $total ) * 100;
		}

		if ( $percentage > 100 ) {
			$percentage = 100;
		}

		return $percentage;
	}

	public function set_properties( $request ) {
		$this->start = isset( $request['api-requests-export-start'] ) ? sanitize_text_field( $request['api-requests-export-start'] ) : '';
		$this->end   = isset( $request['api-requests-export-end'] ) ? sanitize_text_field( $request['api-requests-export-end'] ) : '';
	}
}
