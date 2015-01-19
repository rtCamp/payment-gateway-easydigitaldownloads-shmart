<?php
/**
 * Plugin Name: Shmart Payment Gateway for EDD
 * Plugin URI: 
 * Description: This plugin give you payment gateway for easy digital downloads.
 * Version: 1.0
 * Author: rtCamp
 * Author URI: https://rtcamp.com/
 * Text Domain: shmart
 */

/**
 * Main file, includes plugin classes and registers constants
 * 
 * @package Shmart_Payment_Gateway
 * 
 * @since Shmart_Payment_Gateway 1.0
 */

/**
 * Absolute path to plugin
 */
if ( !defined( 'SPG_ABSPATH' ) ) {
    define( 'SPG_ABSPATH', __DIR__ );
}

/**
 * Path to plugins root folder
 */
if ( !defined( 'SPG_ROOT' ) ) {
    define( 'SPG_ROOT', plugin_dir_path( __FILE__ ) );
}

/**
 * Base URL of plugin
 */
if ( !defined( 'SPG_BASEURL' ) ) {
    define( 'SPG_BASEURL', plugin_dir_url( __FILE__ ) );
}

/**
 * Base Name of plugin
 */
if ( !defined( 'SPG_BASENAME' ) ) {
    define( 'SPG_BASENAME', plugin_basename( __FILE__ ) );
}

/**
 * Directory Name of plugin
 */
if ( !defined( 'SPG_DIRNAME' ) ) {
    define( 'SPG_DIRNAME', dirname( plugin_basename( __FILE__ ) ) );
}

/**
 * Shmart_Payment_Gateway
 * 
 * The starting point of Shmart_Payment_Gateway.
 * 
 * @package Shmart_Payment_Gateway
 * @since Shmart_Payment_Gateway 1.0
 */

if( ! class_exists( "Shmart_Payment_Gateway" ) ) {

	class Shmart_Payment_Gateway {
		
		public function __construct() {
			
			// Add filter to add payment gateway in edd.
			add_filter( 'edd_payment_gateways', array( &$this, 'add_shmart_payment' ) );
			
			// Add filter to register shmart payment gateway settings in edd.
			add_filter( 'edd_settings_gateways', array( &$this, 'register_shmart_payment_gateway_settings' ) );
			
			// Add shmart payment form for user.
			add_action( 'edd_shmart_cc_form', array( &$this, 'add_shmart_payment_form' ) );
			
			// Add `Contact Number` field into shmart payment gateway form.
			add_action( 'edd_cc_billing_bottom',  array( &$this, 'shmart_payment_form_fields' ) );
			
			// Add validation in shmart payment gateway form.
			add_filter( 'edd_purchase_form_required_fields', array( &$this, 'shmart_payment_form_fields_validation' ) );
			// Set validation for billing address by return `true` or `false`;
			add_filter( 'edd_require_billing_address', function() { return true; } );
			
			// Process Shmart Purchase.
			add_action( 'edd_gateway_shmart', array( &$this, 'process_shmart_purchase' ) );
			
			// Get Resposne from shmart.
			add_action( 'init', array( &$this, 'get_response_from_shmart' ) );
			
		}
		
		/**
		 * Add `Shmart` Payment gateway into EDD.
		 * @param array   $gateways Payment Gateways List
		 * @return $gateways
		 */
		public function add_shmart_payment( $gateways ) {
			
			$gateways['shmart'] = array(
				'admin_label'    => __( 'Shmart', 'edd' ),
				'checkout_label' => __( 'Shmart', 'edd' ),
			);
			
			return $gateways;
		}
		
		/**
		 * Register `Shmart` Payment gateway settings in EDD Payment Gateway tab.
		 * @param array   $gateways_settings Settings of gateways
		 * @return $gateways_settings
		 */
		public function register_shmart_payment_gateway_settings( $gateways_settings ) {
				
			$gateways_settings['shmart'] = array(
				'id' => 'shmart',
				'name' => '<strong>' . __( 'Shmart Settings', 'edd' ) . '</strong>',
				'desc' => __( 'Configure the Shmart settings', 'edd' ),
				'type' => 'header'
			);

			$gateways_settings['shmart_merchant_id'] = array(
					'id' => 'shmart_merchant_id',
					'name' => __( 'Shmart Merchant ID', 'edd' ),
					'desc' => __( 'Enter your Merchant ID', 'edd' ),
					'type' => 'text',
					'size' => 'regular'
			);
			
			$gateways_settings['shmart_api_key'] = array(
				'id' => 'shmart_apikey',
				'name' => __( 'Shmart API Key', 'edd' ),
				'desc' => __( 'Enter your Shmart API Key', 'edd' ),
				'type' => 'text',
				'size' => 'regular'
			);
			
			$gateways_settings['shmart_secret_key'] = array(
					'id' => 'shmart_secret_key',
					'name' => __( 'Shmart Secret Key', 'edd' ),
					'desc' => __( 'Enter your Shmart Secret Key', 'edd' ),
					'type' => 'text',
					'size' => 'regular'
			);
			
			return $gateways_settings;
		}
		
		/**
		 * Add `Shmart` Payment gateway form for user where users fill up personal details.
		 */
		public function add_shmart_payment_form() {
				
			do_action( 'edd_after_cc_fields' );
		}
		
		/**
		 * Add `Contact Number` field into `Shmart` payment gateway form.
		 */
		public function shmart_payment_form_fields() {
			
			$contact_number   = is_user_logged_in() && ! empty( $user_address['contact_number']   ) ? $user_address['contact_number']   : '';
?>
			<p id="edd-contact-wrap">
				<label for="contact_number" class="edd-label">
					<?php _e( 'Contact Number', 'edd' ); ?>
					<?php if( edd_field_is_required( 'contact_number' ) ) { ?>
						<span class="edd-required-indicator">*</span>
					<?php } ?>
				</label>
				<span class="edd-description"><?php _e( 'Your contact number.', 'edd' ); ?></span>
			<input id="contact_number" type="text" size="10" name="contact_number" class="contact-number edd-input<?php if( edd_field_is_required( 'contact_number' ) ) { echo ' required'; } ?>" placeholder="<?php _e( 'Contact Number', 'edd' ); ?>" value="<?php echo $contact_number; ?>"/>
		</p>
<?php 
		}
		
		/**
		 * Add validation in shmart payment gateway form.
		 * @param array   $required_fields All Require fields
		 * @return $required_fields
		 */
		public function shmart_payment_form_fields_validation( $required_fields ) {
			
			$required_fields['contact_number'] = array(
				'error_id' => 'invalid_contact_number',
				'error_message' => __( 'Please enter contact number', 'edd' )
			);

			$required_fields['card_address'] = array(
				'error_id' => 'invalid_address',
				'error_message' => __( 'Please enter address', 'edd' )
			);

			return $required_fields;
		}
		
		/**
		 * Get Shmart Redirect
		 * @global $edd_options Array of all the EDD Options
		 * @param boolean   $ssl_check Need url with ssl or without ssl
		 * @return $shmart_uri
		 */
		public function get_shmart_redirect( $ssl_check = false ) {
			global $edd_options;
		
			if ( is_ssl() || ! $ssl_check ) {
				$protocal = 'https://';
			} else {
				$protocal = 'http://';
			}
		
			// Check the current payment mode
			if ( edd_is_test_mode() ) {
				// Test mode
				$shmart_uri = $protocal . 'pay.shmart.in/checkout/v1/transactions';
			} else {
				// Live mode
				$shmart_uri = $protocal . 'pay.shmart.in/checkout/v1/transactions';
			}
		
			return apply_filters( 'edd_shmart_uri', $shmart_uri );
		}
		
		/**
		 * Generate Merchant reference ID.
		 * @param int   $length Length of Merchant reference ID.
		 * @return $merchant_refID
		 */
		public function generate_merchant_ref_ID( $length = 20 ) {
			
			$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
			
			$charactersLength = strlen($characters);
			
			$merchant_refID = '';
			
			for ($i = 0; $i < $length; $i++) {
				$merchant_refID .= $characters[rand(0, $charactersLength - 1)];
			}
			
			return $merchant_refID;
		}
		
		/**
		 * Process Shmart Purchase
		 * @global $edd_options Array of all the EDD Options
		 * @param array   $purchase_data Purchase Data
		 * @return void
		 */
		function process_shmart_purchase( $purchase_data ) {
			global $edd_options;
		
			if( ! wp_verify_nonce( $purchase_data['gateway_nonce'], 'edd-gateway' ) ) {
				wp_die( __( 'Nonce verification has failed', 'edd' ), __( 'Error', 'edd' ), array( 'response' => 403 ) );
			}
			
			// Add contact number in user_info array.
			$purchase_data['user_info']['contact_number'] = $purchase_data['post_data']['contact_number'];
			
			// Collect payment data
			$payment_data = array(
				'price'         => $purchase_data['price'],
				'date'          => $purchase_data['date'],
				'user_email'    => $purchase_data['user_email'],
				'purchase_key'  => $purchase_data['purchase_key'],
				'currency'      => edd_get_currency(),
				'downloads'     => $purchase_data['downloads'],
				'user_info'     => $purchase_data['user_info'],
				'cart_details'  => $purchase_data['cart_details'],
				'gateway'       => 'paypal',
				'status'        => 'pending'
			);
			
			// Record the pending payment
			$payment = edd_insert_payment( $payment_data );
			
			// Check payment
			if ( ! $payment ) {
				// Record the error
				edd_record_gateway_error( __( 'Payment Error', 'edd' ), sprintf( __( 'Payment creation failed before sending buyer to Shmart. Payment data: %s', 'edd' ), json_encode( $payment_data ) ), $payment );
				// Problems? send back
				edd_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['edd-gateway'] );
			} else {
				// Only send to Shmart if the pending payment is created successfully
				$listener_url = add_query_arg( 'edd-listener', 'SHMART_RESPONSE', home_url( 'index.php' ) );
			
				// Get the success url
				$return_url = add_query_arg( array(
						'payment-confirmation' => 'shmart',
						'payment-id' => $payment
			
				), get_permalink( $edd_options['success_page'] ) );
			
				// Get the Shmart redirect uri
				$shmart_redirect = trailingslashit( $this->get_shmart_redirect() );
				
				// Merchant ID.
				$merchant_id = $edd_options['shmart_merchant_id'];
				
				// Generate merchant ref ID.
				$merchant_refID = $this->generate_merchant_ref_ID();
				
				// Checksum Method.
				$checksum_method = 'SHA256';
				
				// Convert amount into paisa.
				$amount = ( intval( $purchase_data['price'] ) * 100 );

				// String to generate checksum.
				$checksum_string = $merchant_id. '|'. $edd_options['shmart_apikey']. '|'. $_SERVER['SERVER_ADDR']. '|'. $merchant_refID . '|'. edd_get_currency() .'|'. $amount. '|'. $checksum_method. '|'. 1;
				
				// Generate checksum.
				$checksum = hash_hmac('sha256', $checksum_string, $edd_options['shmart_secret_key'] );
				
				// Setup Shamrt arguments
				$shamrt_args = array(
					'apikey'        		=> $edd_options['shmart_apikey'],
					'currency_code' 		=> edd_get_currency(),
					'amount'        		=> $amount,
					'merchant_refID' 		=> $merchant_refID,
					'merchant_id'  			=> $merchant_id,
					'checksum_method'		=> $checksum_method,
					'checksum'  			=> $checksum,
					'ip_address' 			=> $_SERVER['SERVER_ADDR'],
					'email'         		=> $purchase_data['user_email'],
					'mobileNo'				=> $purchase_data['user_info']['contact_number'],
					'f_name'   				=> $purchase_data['user_info']['first_name'],
					'addr'      			=> $purchase_data['user_info']['address']['line1']. ', '. $purchase_data['user_info']['address']['line2'],
					'city'       			=> $purchase_data['user_info']['address']['city'],
					'state'       			=> $purchase_data['user_info']['address']['state'],
					'zipcode'       		=> $purchase_data['user_info']['address']['zip'],
					'country'       		=> $purchase_data['user_info']['address']['country'],
					'show_shipping_addr'    => 0,
					'rurl'        			=> $return_url,
					'furl' 					=> edd_get_failed_transaction_uri( '?payment-id=' . $payment ),
					'surl'    				=> $listener_url,
					'authorize_user'		=> 1,
				);

				$shamrt_args = apply_filters( 'edd_shmart_redirect_args', $shamrt_args, $purchase_data );
				
				echo '<form action="'.$shmart_redirect.'" method="POST" name="shmartForm">';
				
				foreach ($shamrt_args as $arg => $arg_value) {
					echo '<input type="hidden" name="'.$arg.'" value="'.$arg_value.'">';
				}
				
				echo '</form>
					  <script language="JavaScript">
				           document.shmartForm.submit();
				      </script>';
				die();
			}
		}
		
		/**
		 * Get response from Shmart and then sends to the processing function.
		 * @global $edd_options Array of all the EDD Options
		 * @return void
		 */
		public function get_response_from_shmart() {
			global $edd_options;
		
			// Regular Shmart Response
			if ( isset( $_GET['edd-listener'] ) && $_GET['edd-listener'] == 'SHMART_RESPONSE' ) {
				$myfile = fopen("response.txt", "w") or die("Unable to open file!");
				fwrite($myfile, serialize($_POST));
				fclose($myfile);
			}
		}
	}
	
	/**
 	  * Instantiate Main Class
 	  */
	global $rtspg;
	$rtSpg = new Shmart_Payment_Gateway();
}
