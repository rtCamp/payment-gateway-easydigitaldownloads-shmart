<?php
/**
 * Plugin Name: Easy Digital Downloads - Shmart Payment Gateway
 * Plugin URI: https://rtcamp.com/products/easydigitaldownloads-shmart-payment-gateway/
 * Description: Extends Easy Digital Downloads plugin, allowing you to take payments through popular Indian service Shmart.
 * Version: 1.0.3
 * Author: rtCamp
 * Author URI: https://rtcamp.com/
 * Text Domain: edd-shmart
 *
 * @package Shmart_Payment_Gateway
 */

/**
 * Main file, includes plugin classes and registers constants
 *
 * @package Shmart_Payment_Gateway
 *
 * @since Shmart_Payment_Gateway 1.0
 */
/**
 * Don't load this file directly!
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Absolute path to plugin
 */
if ( ! defined( 'SPG_ABSPATH' ) ) {
	define( 'SPG_ABSPATH', __DIR__ );
}

/**
 * Path to plugins root folder
 */
if ( ! defined( 'SPG_ROOT' ) ) {
	define( 'SPG_ROOT', plugin_dir_path( __FILE__ ) );
}

/**
 * Base URL of plugin
 */
if ( ! defined( 'SPG_BASEURL' ) ) {
	define( 'SPG_BASEURL', plugin_dir_url( __FILE__ ) );
}

/**
 * Base Name of plugin
 */
if ( ! defined( 'SPG_BASENAME' ) ) {
	define( 'SPG_BASENAME', plugin_basename( __FILE__ ) );
}

/**
 * Directory Name of plugin
 */
if ( ! defined( 'SPG_DIRNAME' ) ) {
	define( 'SPG_DIRNAME', dirname( plugin_basename( __FILE__ ) ) );
}

/**
 * Assets directory url.
 */
if ( ! defined( 'WCSHM_ASSETS' ) ) {
	define( 'SPG_ASSETS', SPG_BASEURL . 'assets/' );
}

// Load the EDD license handler only if not already loaded. Must be placed in the main plugin file.
if ( ! class_exists( 'EDD_License' ) ) {
	include SPG_ROOT . 'lib/edd-license/EDD_License_Handler.php';
}

// Update this details on version update otherwise update won't work.
$license = new EDD_License( __FILE__, 'Easy Digital Downloads - Shmart Payment Gateway', '1.0.1', 'rtCamp', null, 'https://rtcamp.com/' );

/**
 * Shmart_Payment_Gateway
 *
 * The starting point of Shmart_Payment_Gateway.
 *
 * @package Shmart_Payment_Gateway
 * @since   Shmart_Payment_Gateway 1.0
 */
if ( ! class_exists( 'Shmart_Payment_Gateway' ) ) {

	// Include the file that contains the Shmart Payment Gateway class.
	require_once SPG_ROOT . 'class-shmart-payment-gateway.php';
	/**
	 * Instantiate Main Class
	 */
	global $rtspg;
	$rtspg = new Shmart_Payment_Gateway();
}
