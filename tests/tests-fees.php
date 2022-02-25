<?php


/**
 * @group cs_fees
 */
class Tests_Fee extends CS_UnitTestCase {
	protected $_post = null;

	public function set_up() {

		parent::set_up();
		$post_id = $this->factory->post->create( array( 'post_title' => 'Test Download', 'post_type' => 'download', 'post_status' => 'publish' ) );
		$this->_post = get_post( $post_id );

		cs_add_to_cart( $this->_post->ID );

	}

	public function test_adding_fee_legacy() {

		CS()->session->set( 'cs_cart_fees', null );

		//This is not using the $args array because it's for backwards compatibility.
		CS()->fees->add_fee( 10, 'Shipping Fee', 'shipping_fee' );

		$expected = array(
			'shipping_fee' => array(
				'amount' => '10.00',
				'label' => 'Shipping Fee',
				'type'  => 'fee',
				'no_tax' => false,
				'download_id' => 0,
				'price_id'    => NULL
			),
		);

		$this->assertEquals( $expected, CS()->fees->get_fees( 'all' ) );
	}

	public function test_adding_fee() {

		CS()->session->set( 'cs_cart_fees', null );

		//Arbitrary fee test.
		CS()->fees->add_fee( array(
			'amount' => '20.00',
			'label' => 'Arbitrary Item',
			'download_id' => $this->_post->ID,
			'id' => 'arbitrary_fee',
			'type' => 'item'
		) );

		$expected = array(
			'arbitrary_fee' => array(
				'amount' => '20.00',
				'label' => 'Arbitrary Item',
				'type' => 'item',
				'no_tax' => false
			)
		);

		$this->assertEquals( $expected, CS()->fees->get_fees( 'all' ) );
	}

	public function test_adding_fee_no_cart_item() {

		CS()->session->set( 'cs_cart_fees', null );

		cs_empty_cart();

		//Arbitrary fee test.
		$this->assertFalse( CS()->fees->add_fee( array(
			'amount' => '20.00',
			'label' => 'Arbitrary Item',
			'download_id' => $this->_post->ID,
			'id' => 'arbitrary_fee',
		) ) );

	}

	public function test_adding_fee_for_variable_price() {

		CS()->session->set( 'cs_cart_fees', null );

		//Test with variable price id attached to a fee.
		CS()->fees->add_fee( array(
			'amount' => '10.00',
			'label' => 'Shipping Fee (Small)',
			'download_id' => $this->_post->ID,
			'price_id' => 1,
			'id' => 'shipping_fee_with_variable_price_id',
			'type' => 'fee'
		) );

		$expected = array(
			'shipping_fee_with_variable_price_id' => array(
				'amount' => '10.00',
				'label' => 'Shipping Fee (Small)',
				'type'  => 'fee',
				'no_tax' => false,
				'download_id' => $this->_post->ID,
				'price_id'    => 1
			),
		);

		$this->assertEquals( $expected, CS()->fees->get_fees( 'all' ) );
	}

	public function test_adding_fee_for_variable_price_not_in_cart() {

		CS()->session->set( 'cs_cart_fees', null );

		cs_empty_cart();

		//Test with variable price id attached to a fee.
		$this->assertFalse( CS()->fees->add_fee( array(
			'amount' => '10.00',
			'label' => 'Shipping Fee (Small)',
			'download_id' => $this->_post->ID,
			'price_id' => 1,
			'id' => 'shipping_fee_with_variable_price_id',
			'type' => 'fee'
		) ) );

		cs_add_to_cart( $this->_post->ID, array( 'price_id' => 1 ) );
		$this->assertNotEmpty( CS()->fees->add_fee( array(
			'amount' => '10.00',
			'label' => 'Shipping Fee (Small)',
			'download_id' => $this->_post->ID,
			'price_id' => 1,
			'id' => 'shipping_fee_with_variable_price_id',
			'type' => 'fee'
		) ) );
	}

	public function test_adding_fees() {

		CS()->session->set( 'cs_cart_fees', null );

		//Add Legacy Fee
		CS()->fees->add_fee( 10, 'Shipping Fee', 'shipping_fee' );

		//Add Normal Fee with variable price id for to a fee.
		CS()->fees->add_fee( array(
			'amount' => '10.00',
			'label' => 'Shipping Fee (Small)',
			'download_id' => $this->_post->ID,
			'price_id' => 1,
			'id' => 'shipping_fee_with_variable_price_id',
			'type' => 'fee'
		) );

		//Add Normal fee
		CS()->fees->add_fee( array(
			'amount' => '20.00',
			'label' => 'Arbitrary Item',
			'download_id' => $this->_post->ID,
			'id' => 'arbitrary_fee',
			'type' => 'item'
		) );

		$expected = array(
			//Legacy Fee
			'shipping_fee' => array(
				'amount' => '10.00',
				'label' => 'Shipping Fee',
				'type'  => 'fee',
				'no_tax' => false,
				'download_id' => 0,
				'price_id'    => NULL
			),
			//Normal Fee with Variable Price
			'shipping_fee_with_variable_price_id' => array(
				'amount' => '10.00',
				'label' => 'Shipping Fee (Small)',
				'type'  => 'fee',
				'no_tax' => false,
				'download_id' => $this->_post->ID,
				'price_id'    => 1
			),
			//Normal Fee
			'arbitrary_fee' => array(
				'amount' => '20.00',
				'label' => 'Arbitrary Item',
				'type' => 'item',
				'no_tax' => false
			)
		);

		$this->assertEquals( $expected, CS()->fees->get_fees( 'all' ) );
	}

	public function test_has_fees() {

		CS()->session->set( 'cs_cart_fees', null );

		CS()->fees->add_fee( array(
			'amount' => '10.00',
			'label' => 'Shipping Fee (Small)',
			'download_id' => $this->_post->ID,
			'price_id' => 1,
			'id' => 'shipping_fee_with_variable_price_id',
			'type' => 'fee'
		) );

		$this->assertTrue( CS()->fees->has_fees() );
	}

	public function test_get_fee() {

		CS()->session->set( 'cs_cart_fees', null );

		CS()->fees->add_fee( array(
			'amount' => '10.00',
			'label' => 'Shipping Fee (Small)',
			'download_id' => $this->_post->ID,
			'price_id' => 1,
			'id' => 'shipping_fee_with_variable_price_id',
			'type' => 'fee'
		) );

		$expected = array(
			'amount' => '10.00',
			'label' => 'Shipping Fee (Small)',
			'type'  => 'fee',
			'no_tax' => false,
			'download_id' => $this->_post->ID,
			'price_id'    => 1
		);

		$this->assertEquals( $expected, CS()->fees->get_fee( 'shipping_fee_with_variable_price_id' ) );

		$fee = CS()->fees->get_fee( 'shipping_fee_with_variable_price_id' );

		$this->assertEquals( '10.00', $fee['amount'] );
		$this->assertEquals( 'Shipping Fee (Small)', $fee['label'] );
		$this->assertEquals( 'fee', $fee['type'] );

	}

	public function test_get_all_fees() {

		CS()->session->set( 'cs_cart_fees', null );

		//Add Legacy Fee
		CS()->fees->add_fee( 10, 'Shipping Fee', 'shipping_fee' );

		//Add Normal Fee with variable price id for to a fee.
		CS()->fees->add_fee( array(
			'amount' => '10.00',
			'label' => 'Shipping Fee (Small)',
			'download_id' => $this->_post->ID,
			'price_id' => 1,
			'id' => 'shipping_fee_with_variable_price_id',
			'type' => 'fee'
		) );

		//Add Normal fee
		CS()->fees->add_fee( array(
			'amount' => '20.00',
			'label' => 'Arbitrary Item',
			'download_id' => $this->_post->ID,
			'id' => 'arbitrary_fee',
			'type' => 'item'
		) );

		$expected = array(
			//Legacy Fee
			'shipping_fee' => array(
				'amount' => '10.00',
				'label' => 'Shipping Fee',
				'type'  => 'fee',
				'no_tax' => false,
				'download_id' => 0,
				'price_id'    => NULL
			),
			//Normal Fee with Variable Price
			'shipping_fee_with_variable_price_id' => array(
				'amount' => '10.00',
				'label' => 'Shipping Fee (Small)',
				'type'  => 'fee',
				'no_tax' => false,
				'download_id' => $this->_post->ID,
				'price_id'    => 1
			),
			//Normal Fee
			'arbitrary_fee' => array(
				'amount' => '20.00',
				'label' => 'Arbitrary Item',
				'type' => 'item',
				'no_tax' => false
			)
		);

		//Test getting all Fees
		$this->assertEquals( $expected, CS()->fees->get_fees( 'all' ) );

		$expected = array(
			//Legacy Fee
			'shipping_fee' => array(
				'amount' => '10.00',
				'label' => 'Shipping Fee',
				'type'  => 'fee',
				'no_tax' => false,
				'download_id' => 0,
				'price_id'    => NULL
			),
			//Normal Fee with Variable Price
			'shipping_fee_with_variable_price_id' => array(
				'amount' => '10.00',
				'label' => 'Shipping Fee (Small)',
				'type'  => 'fee',
				'no_tax' => false,
				'download_id' => $this->_post->ID,
				'price_id'    => 1
			),
		);

		//Test getting all fees with the type set to 'fee'
		$this->assertEquals( $expected, CS()->fees->get_fees( 'fee' ) );

		$expected = array(
			//Normal Fee
			'arbitrary_fee' => array(
				'amount' => '20.00',
				'label' => 'Arbitrary Item',
				'type' => 'item',
				'no_tax' => false
			)
		);

		// Test getting only fees with the type set to 'item'
		$this->assertEquals( $expected, CS()->fees->get_fees( 'item' ) );

		$expected = array(
			//Normal Fee with Variable Price
			'shipping_fee_with_variable_price_id' => array(
				'amount' => '10.00',
				'label' => 'Shipping Fee (Small)',
				'type'  => 'fee',
				'no_tax' => false,
				'download_id' => $this->_post->ID,
				'price_id'    => 1
			),
		);

		// Test getting download-specific fees
		$this->assertEquals( $expected, CS()->fees->get_fees( 'fee', $this->_post->ID ) );

	}

	public function test_total_fees() {

		CS()->session->set( 'cs_cart_fees', null );

		//Add Normal Fee
		CS()->fees->add_fee( array(
			'amount' => '20.00',
			'label' => 'Tax Fee',
			'download_id' => NULL,
			'id' => 'arbitrary_fee_one',
			'type' => 'item'
		) );

		//Add a variable price fee
		CS()->fees->add_fee( array(
			'amount' => '10.00',
			'label' => 'Shipping Fee (Small)',
			'download_id' => $this->_post->ID,
			'price_id' => 1,
			'id' => 'shipping_fee_with_variable_price_id_one',
			'type' => 'fee'
		) );

		//Add another variable price fee
		CS()->fees->add_fee( array(
			'amount' => '10.00',
			'label' => 'Shipping Fee (Medium)',
			'download_id' => $this->_post->ID,
			'price_id' => 2,
			'id' => 'shipping_fee_with_variable_price_id_two',
			'type' => 'fee'
		) );

		//Add another normal Fee
		CS()->fees->add_fee( array(
			'amount' => '20.00',
			'label' => 'Arbitrary Fee',
			'download_id' => NULL,
			'id' => 'arbitrary_fee_two',
			'type' => 'item'
		) );

		//Test adding up all the fees
		$this->assertEquals( 60, CS()->fees->total() );

		//Test getting the total of fees that match the post ID passed
		$this->assertEquals( 20, CS()->fees->total( $this->_post->ID ) );

		//Test the string value of the fees total
		$this->assertEquals( '60.00', CS()->fees->total() );
	}

	public function test_record_fee() {

		CS()->session->set( 'cs_cart_fees', null );

		//Add Legacy Fee
		CS()->fees->add_fee( 10, 'Shipping Fee', 'shipping_fee' );

		//Add Normal Fee with variable price id for to a fee.
		CS()->fees->add_fee( array(
			'amount' => '10.00',
			'label' => 'Shipping Fee (Small)',
			'download_id' => $this->_post->ID,
			'price_id' => 1,
			'id' => 'shipping_fee_with_variable_price_id',
			'type' => 'fee'
		) );

		//Add Normal fee
		CS()->fees->add_fee( array(
			'amount' => '20.00',
			'label' => 'Arbitrary Item',
			'download_id' => $this->_post->ID,
			'id' => 'arbitrary_fee',
			'type' => 'item'
		) );

		$expected = array(
			'fees' => array(
				//Legacy Fee
				'shipping_fee' => array(
					'amount' => '10.00',
					'label' => 'Shipping Fee',
					'type'  => 'fee',
					'no_tax' => false,
					'download_id' => 0,
					'price_id'    => NULL
				),
				//Normal Fee with Variable Price
				'shipping_fee_with_variable_price_id' => array(
					'amount' => '10.00',
					'label' => 'Shipping Fee (Small)',
					'type'  => 'fee',
					'no_tax' => false,
					'download_id' => $this->_post->ID,
					'price_id'    => 1
				),
				//Normal Fee
				'arbitrary_fee' => array(
					'amount' => '20.00',
					'label' => 'Arbitrary Item',
					'type' => 'item',
					'no_tax' => false
				)
			)
		);

		$actual = CS()->fees->record_fees( $payment_meta = array(), $payment_data = array() );

		$this->assertEquals( $expected, $actual );
	}

	public function test_fee_number_format_default() {
		CS()->session->set( 'cs_cart_fees', null );

		CS()->fees->add_fee( array(
			'amount' => '20',
			'label' => 'Arbitrary Item',
			'download_id' => $this->_post->ID,
			'id' => 'arbitrary_fee',
			'type' => 'item'
		) );

		$expected = array(
			'arbitrary_fee' => array(
				'amount' => '20.00',
				'label' => 'Arbitrary Item',
				'type' => 'item',
				'no_tax' => false
			)
		);

		$this->assertEquals( $expected, CS()->fees->get_fees( 'all' ) );
	}

	public function test_fee_number_format_decimal_filter() {
		add_filter( 'cs_currency_decimal_count', array( $this, 'alter_decimal_filter' ), 10, 2 );

		CS()->session->set( 'cs_cart_fees', null );

		CS()->fees->add_fee( array(
			'amount' => '20',
			'label' => 'Arbitrary Item',
			'download_id' => $this->_post->ID,
			'id' => 'arbitrary_fee',
			'type' => 'item'
		) );

		$expected = array(
			'arbitrary_fee' => array(
				'amount' => '20.000000',
				'label' => 'Arbitrary Item',
				'type' => 'item',
				'no_tax' => false
			)
		);

		$this->assertEquals( $expected, CS()->fees->get_fees( 'all' ) );

		remove_filter( 'cs_currency_decimal_count', array( $this, 'alter_decimal_filter' ), 10, 2 );
	}

	public function alter_decimal_filter() {
		return 6;
	}
}
