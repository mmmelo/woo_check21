<?php
/**
 * Plugin Name: WooCommerce HighTechWeb Check21
 * Plugin URI: http://www.hightechweb.com
 * Description: The Check 21 API allows our merchants to link directly to the Check 21 Service in order to submit check transactions in real- time format and receive information regarding those transactions.
 * Author: HighTechWeb
 * Author URI: http://www.hightechweb.com
 * Version: 1.0
 * Text Domain: woocommerce-htw-check21
 * Domain Path: /i18n/languages/
 *
 * Copyright: (c) HighTechWeb
 *
 * @package     WC_htw-check21
 * @author      HighTechWeb
 * @category    Payment-Gateways
 * @copyright   Copyright (c) HighTechWeb, Inc
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Required library class
if ( ! class_exists( 'SV_WC_Helper' ) ) {
	require_once( 'lib/skyverge/woocommerce/class-sv-wc-helper.php' );
}


class WC_htw_Chek21  {

	
	const VERSION = '1.2.1';
	const TEXT_DOMAIN = 'woocommerce-gateway-htw-check21';
	protected static $instance = null;
	
	
	
	/**
	 * Initialize the plugin public actions.
	 */
	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}
	
	/** 
	 * Add the gateway to WooCommerce.
	 */
	public function init() {
	
		// Checks if WooCommerce is installed.
		if ( class_exists( 'WC_Payment_Gateway' ) ) {
			include_once 'classes/class-wc-htw-check21_check.php';
			/**
			 * Hook to add Woocommerce Gateways
			 */
			add_filter( 'woocommerce_payment_gateways', array( $this, 'htw_gateway' ) );
			
		} else {
			add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
		}
	}
	/**
	* Return an instance of this class.
	*/
	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}
	
	/**
	 * 
	 * WooCommerce fallback notice.
	 */
	public function woocommerce_missing_notice() {
		// TODO fazer messagem de error
		echo '<p> Error ';
	}
	
	/**
	 * 
	 * @param $htw_gateway
	 * @return $htw_gateway
	 */
	public function htw_gateway( $htw_gateway ) {
		$htw_gateway[] = 'WC_htw_Chek21_check';
		return $htw_gateway;
	}
	
}

add_action( 'plugins_loaded', array( 'WC_htw_Chek21', 'get_instance' ), 0 );
