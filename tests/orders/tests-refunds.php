<?php
namespace CS\Orders;

use Carbon\Carbon;
use CS\Utils\Exceptions\Invalid_Argument;

/**
 * Refund Tests.
 *
 * @group cs_orders
 * @group cs_refunds
 * @group database
 *
 * @coversDefaultClass \CS\Orders\Order
 */
class Refunds_Tests extends \CS_UnitTestCase {

	/**
	 * Orders fixture.
	 *
	 * @var array
	 * @static
	 */
	protected static $orders = array();

	/**
	 * Set up fixtures once.
	 */
	public static function wpsetUpBeforeClass() : void  {
		self::$orders = parent::cs()->order->create_many( 5 );

		foreach ( self::$orders as $order ) {
			cs_add_order_adjustment( array(
				'object_type' => 'order',
				'object_id'   => $order,
				'type'        => 'discount',
				'description' => '5OFF',
				'subtotal'    => 0,
				'total'       => 5,
			) );
		}
	}

	/**
	 * @covers ::cs_is_order_refundable
	 */
	public function test_is_order_refundable_should_return_true() {
		$this->assertTrue( cs_is_order_refundable( self::$orders[0] ) );
	}

	/**
	 * @covers ::cs_is_order_refund_window_passed
	 */
	public function test_is_order_refund_window_passed_return_true() {
		$order = parent::cs()->order->create_and_get( array(
			'date_refundable' => '2000-01-01 00:00:00',
		) );

		$this->assertTrue( cs_is_order_refund_window_passed( $order->id ) );
	}

	/**
	 * @covers ::cs_is_order_refundable_by_override
	 */
	public function test_is_order_refundable_by_override_return_true() {
		$order = parent::cs()->order->create_and_get( array(
			'date_refundable' => '2000-01-01 00:00:00',
		) );

		add_filter( 'cs_is_order_refundable_by_override', '__return_true' );

		$this->assertTrue( cs_is_order_refundable_by_override( $order->id ) );
	}

	/**
	 * @covers ::cs_get_order_total
	 */
	public function test_get_order_total_should_be_120() {
		$this->assertSame( 120.0, cs_get_order_total( self::$orders[0] ) );
	}

	/**
	 * @covers ::cs_get_order_item_total
	 */
	public function test_get_order_item_total_should_be_120() {
		$this->assertSame( 120.0, cs_get_order_item_total( array( self::$orders[0] ), 1 ) );
	}

	/**
	 * @covers ::cs_refund_order
	 */
	public function test_refund_order() {

		// Refund order entirely.
		$refunded_order = cs_refund_order( self::$orders[0] );

		// Check that a new order ID was returned.
		$this->assertNotInstanceOf( 'WP_Error', $refunded_order );
		$this->assertGreaterThan( 0, $refunded_order );

		// Fetch original order.
		$o = cs_get_order( self::$orders[0] );

		// Check a valid Order object was returned.
		$this->assertInstanceOf( 'CS\Orders\Order', $o );

		// Verify status.
		$this->assertSame( 'refunded', $o->status );

		// Verify type.
		$this->assertSame( 'sale', $o->type );

		// Verify total.
		$this->assertSame( 120.0, floatval( $o->total ) );

		// Fetch refunded order.
		$r = cs_get_order( $refunded_order );

		// Check a valid Order object was returned.
		$this->assertInstanceOf( 'CS\Orders\Order', $r );

		// Verify status.
		$this->assertSame( 'complete', $r->status );

		// Verify type.
		$this->assertSame( 'refund', $r->type );

		// Verify total.
		$this->assertSame( -120.0, floatval( $r->total ) );
	}

	/**
	 * @covers ::cs_refund_order
	 */
	public function test_refund_order_returns_wp_error_if_refund_amount_exceeds_max() {
		$order = cs_get_order( self::$orders[1] );

		$to_refund = array();

		foreach( $order->items as $order_item ) {
			$to_refund[] = array(
				'order_item_id' => $order_item->id,
				'subtotal'      => $order_item->subtotal * 2,
				'tax'           => $order_item->tax,
				'total'         => $order_item->total * 2
			);
		}

		$refund_id = cs_refund_order( $order->id, $to_refund );

		$this->assertInstanceOf( 'WP_Error', $refund_id );

		$this->assertEquals( 'refund_validation_error', $refund_id->get_error_code() );
		$this->assertStringContainsString( 'The maximum refund subtotal', $refund_id->get_error_message() );
	}

	/**
	 * @covers ::cs_refund_order
	 * @covers ::cs_get_order_total
	 */
	public function test_partially_refund_order() {
		$order = cs_get_order( self::$orders[1] );

		$to_refund = array();

		foreach( $order->items as $order_item ) {
			// Only refund half the subtotal / tax for the order item. This creates a partial refund.
			$to_refund[] = array(
				'order_item_id' => $order_item->id,
				'subtotal'      => ( $order_item->subtotal - $order_item->discount ) / 2,
				'tax'           => $order_item->tax / 2,
				'total'         => $order_item->total / 2
			);
		}

		$refund_id = cs_refund_order( $order->id, $to_refund );

		$this->assertGreaterThan( 0, $refund_id );

		// Fetch original order.
		$o = cs_get_order( $order->id );

		// Check a valid Order object was returned.
		$this->assertInstanceOf( 'CS\Orders\Order', $o );

		// Verify status.
		$this->assertSame( 'partially_refunded', $o->status );

		// Verify type.
		$this->assertSame( 'sale', $o->type );

		// Verify original total.
		$this->assertEquals( 120.0, floatval( $o->total ) );

		// Verify total minus refunded amount.
		$this->assertEquals( 60.0, cs_get_order_total( $o->id ) );

		// Fetch refunded order.
		$r = cs_get_order( $refund_id );

		// Check a valid Order object was returned.
		$this->assertInstanceOf( 'CS\Orders\Order', $r );

		// Verify status.
		$this->assertSame( 'complete', $r->status );

		// Verify type.
		$this->assertSame( 'refund', $r->type );

		// Verify total.
		$this->assertEquals( -60.0, floatval( $r->total ) );
	}

	public function test_partial_refund_with_free_download_remaining() {
		$order_id = self::$orders[2];
		$oid      = cs_add_order_item( array(
			'order_id'     => $order_id,
			'product_id'   => 17,
			'product_name' => 'Free Download',
			'status'       => 'inherit',
			'amount'       => 0,
			'subtotal'     => 0,
			'discount'     => 0,
			'tax'          => 0,
			'total'        => 0,
			'quantity'     => 1,
		) );

		$to_refund = array();
		$order     = cs_get_order( $order_id );
		foreach ( $order->items as $order_item ) {
			if ( $order_item->total > 0 ) {
				$to_refund[] = array(
					'order_item_id' => $order_item->id,
					'subtotal'      => ( $order_item->subtotal - $order_item->discount ),
					'tax'           => $order_item->tax,
					'total'         => $order_item->total,
				);
			}
		}

		$refund_id = cs_refund_order( $order->id, $to_refund );

		// Fetch original order.
		$o = cs_get_order( $order->id );

		$this->assertSame( 'partially_refunded', $o->status );
	}

	/**
	 * @covers ::cs_get_refundability_types
	 */
	public function test_get_refundability_types() {
		$expected = array(
			'refundable'    => __( 'Refundable', 'commercestore' ),
			'nonrefundable' => __( 'Non-Refundable', 'commercestore' ),
		);

		$this->assertEqualSetsWithIndex( $expected, cs_get_refundability_types() );
	}

	/**
	 * @covers ::cs_get_refund_date()
	 */
	public function test_get_refund_date() {

		// Static date to ensure unit tests don't fail if this test runs for longer than 1 second.
		$date = '2010-01-01 00:00:00';

		$this->assertSame( Carbon::parse( $date )->addDays( 30 )->toDateTimeString(), cs_get_refund_date( $date ) );
	}

	/**
	 * @covers \CS\Orders\Refund_Validator::validate_and_calculate_totals
	 * @covers \CS\Orders\Refund_Validator::get_refunded_order_items
	 * @throws \Exception
	 */
	public function test_refund_validator_all_returns_original_amounts() {
		$order     = cs_get_order( self::$orders[1] );
		$validator = new Refund_Validator( $order, 'all', 'all' );
		$validator->validate_and_calculate_totals();

		$this->assertEquals( ( $order->subtotal - $order->discount ), $validator->subtotal );
		$this->assertEquals( $order->tax, $validator->tax );
		$this->assertEquals( $order->total, $validator->total );

		$order_item_ids  = wp_list_pluck( $order->items, 'id' );
		$refund_item_ids = wp_list_pluck( $validator->get_refunded_order_items(), 'parent' );

		sort( $order_item_ids );
		sort( $refund_item_ids );

		$this->assertEquals( $order_item_ids, $refund_item_ids );
	}

	/**
	 * An Invalid_Argument exception is thrown if the `order_item_id` argument is missing.
	 *
	 * @covers \CS\Orders\Refund_Validator::validate_and_format_order_items
	 */
	public function test_refund_validator_throws_exception_missing_order_item_id() {
		$order = cs_get_order( self::$orders[1] );

		$this->expectException( Invalid_Argument::class );

		$validator = new Refund_Validator( $order, array(
			array(
				'subtotal' => 100,
				'tax'      => 20,
				'total'    => 120
			)
		), 'all' );

		$exception = $this->getExpectedException();
		$this->assertStringContainsString( 'order_item_id', $exception->getMessage() );
	}

	/**
	 * An Invalid_Argument exception is thrown if the `subtotal` argument is missing.
	 *
	 * @covers \CS\Orders\Refund_Validator::validate_and_format_order_items
	 * @covers \CS\Orders\Refund_Validator::validate_required_fields
	 */
	public function test_refund_validator_throws_exception_missing_subtotal() {
		$order = cs_get_order( self::$orders[1] );

		$this->expectException( Invalid_Argument::class );

		$validator = new Refund_Validator( $order, array(
			array(
				'order_item_id' => $order->items[0]->id,
				'tax'           => $order->items[0]->tax,
				'total'         => $order->items[0]->total
			)
		), 'all' );

		$exception = $this->getExpectedException();
		$this->assertStringContainsString( 'subtotal', $exception->getMessage() );
	}
}
