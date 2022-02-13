<?php
/**
 * Recurring Payments
 *
 * Manages automatic activation for Recurring Payments.
 *
 * @package     CS
 * @subpackage  Recurring
 * @copyright   Copyright (c) 2021, CommerceStore
 * @license     https://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.11.4
 */
namespace CS\Admin\Settings;

use \CS\Admin\Extensions\Extension;

class Recurring extends Extension {

	/**
	 * The product ID on CS.
	 *
	 * @var integer
	 */
	protected $item_id = 28530;

	/**
	 * The CommerceStore settings tab where this extension should show.
	 *
	 * @since 2.11.4
	 * @var string
	 */
	protected $settings_tab = 'gateways';

	/**
	 * The pass level required to access this extension.
	 */
	const PASS_LEVEL = \CS\Admin\Pass_Manager::EXTENDED_PASS_ID;

	/**
	 * The settings section for this item.
	 *
	 * @since 2.11.5
	 * @var string
	 */
	protected $settings_section = 'recurring';

	public function __construct() {
		add_filter( 'cs_settings_sections_gateways', array( $this, 'add_section' ) );
		add_action( 'cs_settings_tab_top_gateways_recurring', array( $this, 'settings_field' ) );
		add_action( 'cs_settings_tab_top_gateways_recurring', array( $this, 'hide_submit_button' ) );

		parent::__construct();
	}

	/**
	 * Gets the custom configuration for Recurring.
	 *
	 * @since 2.11.4
	 * @param \CS\Admin\Extensions\ProductData $product_data The product data object.
	 * @return array
	 */
	protected function get_configuration( \CS\Admin\Extensions\ProductData $product_data ) {
		return array(
			'style'       => 'detailed-2col',
			'title'       => 'Increase Revenue By Selling Subscriptions!',
			'description' => $this->get_custom_description(),
			'features'    => array(
				'Flexible Recurring Payments',
				'Custom Reminder Emails',
				'Free Trial Support',
				'Signup Fees',
				'Recurring Revenue Reports',
			),
		);
	}

	/**
	 * Gets a custom description for the Recurring extension card.
	 *
	 * @since 2.11.4
	 * @return string
	 */
	private function get_custom_description() {
		$description = array(
			'Grow stable income by selling subscriptions and make renewals hassle free for your customers.',
			'When your customers are automatically billed, you reduce the risk of missed payments and retain more customers.',
		);

		return $this->format_description( $description );
	}

	/**
	 * Adds the Recurring Payments section to the settings.
	 *
	 * @param array $sections
	 * @return array
	 */
	public function add_section( $sections ) {
		if ( ! $this->can_show_product_section() ) {
			return $sections;
		}

		$sections[ $this->settings_section ] = __( 'Recurring Payments', 'commercestore' );

		return $sections;
	}

	/**
	 * Whether CommerceStore Recurring active or not.
	 *
	 * @since 2.11.4
	 *
	 * @return bool True if Recurring is active.
	 */
	protected function is_activated() {
		if ( $this->manager->is_plugin_active( $this->get_product_data() ) ) {
			return true;
		}

		return class_exists( 'CS_Recurring' );
	}
}

new Recurring();
