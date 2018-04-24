<?php
namespace EDD\Reports\Data\Charts\v2;

if ( ! class_exists( 'EDD\\Reports\\Init' ) ) {
	require_once( EDD_PLUGIN_DIR . 'includes/reports/class-init.php' );
}

new \EDD\Reports\Init();

/**
 * Tests for the Pie_Dataset class
 *
 * @group edd_reports
 * @group edd_reports_charts
 *
 * @coversDefaultClass \EDD\Reports\Data\Charts\v2\Pie_Dataset
 */
class Pie_Dataset_Tests extends \EDD_UnitTestCase {

	/**
	 * @covers ::$fields
	 */
	public function test_default_fields() {
		$expected = array(
			'hoverBackgroundColor', 'hoverBorderColor',
			'hoverBorderWidth'
		);

		if ( version_compare( PHP_VERSION, '5.5', '<' ) ) {
			$class = 'EDD\\Reports\\Data\\Charts\\v2\\Pie_Dataset';
		} else {
			$class = Pie_Dataset::class;
		}

		$pie_dataset = $this->getMockBuilder( $class )
			->setMethods( null )
			->disableOriginalConstructor()
			->getMock();

		$this->assertEqualSets( $expected, $pie_dataset->get_fields() );
	}

}
