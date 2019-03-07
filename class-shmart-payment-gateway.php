<?php
/**
 * File contains Shmart Payment Gateway class.
 *
 * Object of this class will be created in edd-shamrt.php.
 *
 * @package Shmart_Payment_Gateway
 */

/**
 * Shmart_Payment_Gateway class
 *
 * It will be loaded in the Edd Payment Gateways
 *
 * @package Shmart_Payment_Gateway
 * @since   Shmart_Payment_Gateway 1.0
 */
class Shmart_Payment_Gateway {

	/**
	 * Used for warnings.
	 *
	 * @var $warning Warning
	 * @since Shmart_Payment_Gateway 1.0
	 */
	protected $warnings = [];

	/**
	 * Used for providing icon url.
	 *
	 * @var $icon Url
	 * @since Shmart_Payment_Gateway 1.0.3
	 */
	private $icon = SPG_ASSETS . 'img/logo.jpg';

	/**
	 * Used for filtering output.
	 *
	 * @var $allowed_html Array of allowed html tags.
	 * @since Shmart_Payment_Gateway 1.0.3
	 */
	private $allowed_html = array(
		'a'  => array(
			'href'   => array(),
			'title'  => array(),
			'target' => array(),
		),
		'b'  => array(),
		'br' => array(),
	);

	/**
	 * Shmart_Payment_Gateway constructor
	 *
	 * Bootstraps the class and sets up everything.
	 *
	 * @package Shmart_Payment_Gateway
	 * @since   Shmart_Payment_Gateway 1.0
	 */
	public function __construct() {
		global $edd_options;

		// We use this hook to render our warnings.
		add_action( 'admin_notices', array( $this, 'render_warnings' ) );

		// Check if EDD is active.
		if ( ! class_exists( 'Easy_Digital_Downloads' ) ) {

			$this->warnings[] = sprintf(
				// translators: %s: EDD Link.
				__( '<b>Easy Digital Downloads - Shmart Payment Gateway: </b> The plugin is enabled but not effective. It requires %s in order to work.', 'edd-shmart' ),
				sprintf( '<a target="_blank" href="%s"><b>%s</b></a>', 'https://wordpress.org/plugins/easy-digital-downloads/', 'Easy Digital Downloads' )
			);
			return;

		}

		// Display notice if app id is blank or currency is not INR.
		if ( ! empty( $edd_options['gateways']['shmart'] )
			&& 'INR' !== $edd_options['currency']
			&& empty( $edd_options['shmart_openexchangerates_appid'] ) ) {

			$this->warnings[] = __( '<b>Easy Digital Downloads - Shmart Payment Gateway: </b> Please enter Open Exchange Rates App ID under <b>Downloads > Settings > Payment Gateways > Shmart</b>.', 'edd-shmart' );

		}

		// Add filter to add payment gateway in edd.
		add_filter( 'edd_payment_gateways', array( &$this, 'add_shmart_payment' ) );

		// Add filter to register shmart payment gateway settings in edd.
		add_filter( 'edd_settings_gateways', array( &$this, 'register_shmart_payment_gateway_settings' ) );

		// Add filter to register shmart payment gateway subsection.
		add_filter( 'edd_settings_sections_gateways', array( &$this, 'register_gateway_section' ), 1, 1 );

		// Add filter to register shmart icon for accepted payment gateways.
		add_filter( 'edd_accepted_payment_icons', array( $this, 'register_payment_icon' ), 10, 1 );

		// Add shmart payment form for user.
		add_action( 'edd_shmart_cc_form', array( &$this, 'add_shmart_payment_form' ) );

		// Add `Contact Number` field into shmart payment gateway form.
		add_action( 'edd_purchase_form_user_info_fields', array( &$this, 'shmart_payment_form_fields' ) );

		// Add validation in shmart payment gateway form.
		add_filter( 'edd_purchase_form_required_fields', array( &$this, 'shmart_payment_form_fields_validation' ) );

		// Set validation for billing address by return `true` or `false`.
		add_filter( 'edd_require_billing_address', array( &$this, 'is_billing_address_require' ) );

		// Process Shmart Purchase.
		add_action( 'edd_gateway_shmart', array( &$this, 'process_shmart_purchase' ) );

		// Get Resposne from shmart.
		add_action( 'init', array( &$this, 'get_response_from_shmart' ) );

		// Process shmart response.
		add_action( 'verify_shmart_response', array( &$this, 'process_shmart_response' ) );

		// Process if shmart payment is failed.
		add_action( 'template_redirect', array( &$this, 'shmart_listen_for_failed_payments' ), 20 );

		// Display settings link on plugin page (beside the activate/deactivate links).
		add_filter( 'plugin_action_links_' . SPG_BASENAME, array( &$this, 'shmart_action_links' ) );

		// Clear currency conversion rates if settings updated.
		add_action( 'update_option_edd_settings', array( &$this, 'clear_currency_rates' ), 10, 2 );
	}

	/**
	 * Render plugin warning.
	 */
	public function render_warnings() {

		// Display erros only if warnings exists.
		if ( ! empty( $this->warnings ) ) {

			foreach ( $this->warnings as $warning ) {
				?>
					<div class="message error">
						<p><?php echo wp_kses( $warning, $this->allowed_html ); ?></p>
					</div>
				<?php
			}
		}

	}

	/**
	 * Add `Shmart` Payment gateway into EDD.
	 *
	 * @param array $gateways Payment Gateways List.
	 * @return $gateways
	 */
	public function add_shmart_payment( $gateways ) {

		global $edd_options;

		$gateways['shmart'] = array(
			'admin_label'    => __( 'Shmart (recommended for Indian users)', 'edd-shmart' ),
			'checkout_label' => __( 'Shmart', 'edd-shmart' ),
		);

		if ( ! empty( $edd_options['shmart_checkout_label'] ) ) {

			$gateways['shmart']['checkout_label'] = $edd_options['shmart_checkout_label'];

		}

		return $gateways;

	}

	/**
	 * Register `Shmart` Payment gateway settings in EDD Payment Gateway tab.
	 *
	 * @param array $gateways_settings Settings of gateways.
	 * @return $gateways_settings
	 * @since  Shmart_Payment_Gateway 1.0
	 */
	public function register_shmart_payment_gateway_settings( $gateways_settings ) {

		global $edd_options;

		$shmart_settings['shmart'] = array(
			'id'   => 'shmart',
			'name' => sprintf( '<strong>%1$s</strong>', __( 'Shmart Settings', 'edd-shmart' ) ),
			'desc' => __( 'Configure the Shmart settings', 'edd-shmart' ),
			'type' => 'header',
		);

		$shmart_settings['shmart_merchant_id'] = array(
			'id'   => 'shmart_merchant_id',
			'name' => __( 'Shmart Merchant ID', 'edd-shmart' ),
			'desc' => __( '<br />Enter your Merchant ID', 'edd-shmart' ),
			'type' => 'text',
			'size' => 'regular',
		);

		$shmart_settings['shmart_api_key'] = array(
			'id'   => 'shmart_apikey',
			'name' => __( 'Shmart API Key', 'edd-shmart' ),
			'desc' => __( '<br />Enter your Shmart API Key', 'edd-shmart' ),
			'type' => 'text',
			'size' => 'regular',
		);

		$shmart_settings['shmart_secret_key'] = array(
			'id'   => 'shmart_secret_key',
			'name' => __( 'Shmart Secret Key', 'edd-shmart' ),
			'desc' => __( '<br />Enter your Shmart Secret Key', 'edd-shmart' ),
			'type' => 'text',
			'size' => 'regular',
		);

		$shmart_settings['shmart_checkout_label'] = array(
			'id'   => 'shmart_checkout_label',
			'name' => __( 'Checkout Label', 'edd-shmart' ),
			'desc' => __( '<br />Display payment gateway text on checkout page', 'edd-shmart' ),
			'type' => 'text',
			'size' => 'regular',
		);

		// translators: %s: A link to openexchanage signup page.
		$desc = sprintf( wp_kses( __( "<br>For USD, you can use free API to convert USD to INR. <a target='_blank' href='%1\$s'>Click Here</a> for free api. <br>Other than USD currency, you must need to use enterpise API. <a target='_blank' href='%2\$s'>Click Here</a> for enterprise or unlimited api. <br>In both condition, API key is must to enable shmart payment gateway.", 'edd-shmart' ), $this->allowed_html ), esc_url( 'https://openexchangerates.org/signup/free' ), esc_url( 'https://openexchangerates.org/signup' ) );

		$shmart_settings['shmart_openexchangerates_appid'] = array(
			'id'   => 'shmart_openexchangerates_appid',
			'name' => __( 'Open Exchange Rates APP ID', 'edd-shmart' ),
			'desc' => $desc,
			'type' => 'text',
			'size' => 'regular',
		);

		$gateways_settings['shmart_section'] = $shmart_settings;
		return $gateways_settings;

	}

	/**
	 * Register the Shmart gateway subsection
	 *
	 * @param  array $gateway_sections  Current Gateway Tab subsections.
	 * @return array                    Gateway subsections with Shmart Gateway
	 * @since                           Shmart_Payment_Gateway 1.0.3
	 */
	public function register_gateway_section( $gateway_sections ) {

		$gateway_sections['shmart_section'] = __( 'Shmart', 'edd-shmart' );

		return $gateway_sections;

	}

	/**
	 * Register the payment icon for shmart
	 *
	 * @access public
	 * @param  array $payment_icons Array of payment icons.
	 * @return array                The array of icons with shmart Added.
	 * @since                       Shmart_Payment_Gateway 1.0.3
	 */
	public function register_payment_icon( $payment_icons ) {

		$payment_icons[ $this->icon ] = 'Shmart';

		return $payment_icons;

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

		if ( 'shmart' === edd_get_chosen_gateway() ) {

			$contact_number = is_user_logged_in() ? get_user_meta( get_current_user_id(), '_edd_user_contact_info', true ) : '';
			require_once SPG_ROOT . 'templates/payment-form-fields.php';

		}

	}

	/**
	 * Billing address is required for shmart payment gateway.
	 *
	 * @param  bool $is_billing Whether billing details is dispalyed or not.
	 */
	public function is_billing_address_require( $is_billing ) {

		if ( 'shmart' === edd_get_chosen_gateway() && edd_get_cart_total() ) {
			return true;
		} else {
			return false;
		}

	}

	/**
	 * Add validation in shmart payment gateway form.
	 *
	 * @param array $required_fields All Require fields.
	 * @return $required_fields
	 */
	public function shmart_payment_form_fields_validation( $required_fields ) {

		if ( 'shmart' === edd_get_chosen_gateway() ) {

			$required_fields['contact_number'] = array(
				'error_id'      => 'invalid_contact_number',
				'error_message' => __( 'Please enter contact number', 'edd-shmart' ),
			);
			if ( edd_get_cart_total() ) {
				$required_fields['card_address'] = array(
					'error_id'      => 'invalid_address',
					'error_message' => __( 'Please enter billing address', 'edd-shmart' ),
				);
			}
		}

		return $required_fields;

	}

	/**
	 * Get Shmart Redirect
	 *
	 * @global $edd_options Array of all the EDD Options
	 * @param boolean $ssl_check Need url with ssl or without ssl.
	 * @return $shmart_uri
	 */
	public function get_shmart_redirect( $ssl_check = false ) {

		global $edd_options;

		if ( is_ssl() || ! $ssl_check ) {
			$protocal = 'https://';
		} else {
			$protocal = 'http://';
		}

		// Check the current payment mode.
		if ( edd_is_test_mode() ) {
			// Test mode.
			$shmart_uri = $protocal . 'pay.shmart.in/checkout/v2/transactions';
		} else {
			// Live mode.
			$shmart_uri = $protocal . 'pay.shmart.in/checkout/v2/transactions';
		}

		return apply_filters( 'edd_shmart_uri', $shmart_uri );

	}

	/**
	 * Generate Merchant reference ID.
	 *
	 * @param int $length Length of Merchant reference ID.
	 * @return $merchant_ref_id.
	 */
	public function generate_merchant_ref_id( $length = 20 ) {

		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

		$characters_length = strlen( $characters );

		$merchant_ref_id = '';

		for ( $i = 0; $i < $length; $i++ ) {
			$merchant_ref_id .= $characters[ wp_rand( 0, $characters_length - 1 ) ];
		}

		return $merchant_ref_id;

	}

	/**
	 * Process Shmart Purchase
	 *
	 * @global $edd_options Array of all the EDD Options.
	 * @param array $purchase_data Purchase Data.
	 * @return void
	 */
	public function process_shmart_purchase( $purchase_data ) {

		global $edd_options;

		if ( ! wp_verify_nonce( $purchase_data['gateway_nonce'], 'edd-gateway' ) ) {
			wp_die( esc_html__( 'Nonce verification has failed', 'edd-shmart' ), esc_html__( 'Error', 'edd-shmart' ), array( 'response' => 403 ) );
		}

		// Collect payment data.
		$payment_data = array(
			'price'        => $purchase_data['price'],
			'date'         => $purchase_data['date'],
			'user_email'   => $purchase_data['user_email'],
			'purchase_key' => $purchase_data['purchase_key'],
			'currency'     => edd_get_currency(),
			'downloads'    => $purchase_data['downloads'],
			'user_info'    => $purchase_data['user_info'],
			'cart_details' => $purchase_data['cart_details'],
			'gateway'      => 'shmart',
			'status'       => 'pending',
		);

		// Add contact number if user is logged in.
		if ( is_user_logged_in() ) {
			$user_ID = get_current_user_id();
			// Add contact number in user meta.
			update_user_meta( $user_ID, '_edd_user_contact_info', $purchase_data['post_data']['contact_number'] );
		}

		// Record the pending payment.
		$payment = edd_insert_payment( $payment_data );

		// Check payment.
		if ( ! $payment ) {

			// Record the error.
			// translators: %s: Users data.
			edd_record_gateway_error( __( 'Payment Error', 'edd-shmart' ), sprintf( __( 'Payment creation failed before sending buyer to Shmart. Payment data: %s', 'edd-shmart' ), wp_json_encode( $payment_data ) ), $payment );

			// Problems? send back.
			edd_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['edd-gateway'] );

		} else {

			// Only send to Shmart if the pending payment is created successfully.
			// $listener_url = add_query_arg( 'edd-listener', 'SHMART_RESPONSE', home_url( 'index.php' ) );.
			// Get the success url.
			$listener_url = add_query_arg(
				array(
					'edd-listener' => 'SHMART_RESPONSE',
					'payment-id'   => $payment,
				),
				home_url( 'index.php' )
			);

			// Get the Shmart redirect uri.
			$shmart_redirect = trailingslashit( $this->get_shmart_redirect() );

			// Merchant ID.
			$merchant_id = $edd_options['shmart_merchant_id'];

			// Generate merchant ref ID.
			$merchant_ref_id = $this->generate_merchant_ref_id();

			// Checksum Method.
			$checksum_method = 'MD5';

			/* Do currency conversion. */
			$amount = $this->do_currency_conversion( $purchase_data['price'] );

			// Round up final amount and convert amount into paisa.
			$amount = ( ceil( $amount ) * 100 );

			// Get server IP address.
			$ip_address = gethostbyname( filter_input( INPUT_SEVER, 'SERVER_NAME', FILTER_SANITIZE_STRING ) );

			// String to generate checksum.
			$checksum_string = $edd_options['shmart_secret_key'] . $merchant_id . '|' . $edd_options['shmart_apikey'] . '|' . $ip_address . '|' . $merchant_ref_id . '|INR|' . $amount . '|' . $checksum_method . '|' . 1;

			// Generate checksum.
			$checksum = md5( $checksum_string );

			// Setup Shamrt arguments.
			$shamrt_args = array(
				'apikey'             => $edd_options['shmart_apikey'],
				'currency_code'      => 'INR',
				'amount'             => $amount,
				'merchant_refID'     => $merchant_ref_id,
				'merchant_id'        => $merchant_id,
				'checksum_method'    => $checksum_method,
				'checksum'           => $checksum,
				'ip_address'         => $ip_address,
				'email'              => $purchase_data['user_email'],
				'mobileNo'           => $purchase_data['post_data']['contact_number'],
				'f_name'             => $purchase_data['user_info']['first_name'],
				'addr'               => $purchase_data['user_info']['address']['line1'] . ', ' . $purchase_data['user_info']['address']['line2'],
				'city'               => $purchase_data['user_info']['address']['city'],
				'state'              => $purchase_data['user_info']['address']['state'],
				'zipcode'            => $purchase_data['user_info']['address']['zip'],
				'country'            => $purchase_data['user_info']['address']['country'],
				'show_shipping_addr' => 0,
				'rurl'               => get_permalink( $edd_options['success_page'] ),
				'furl'               => edd_get_failed_transaction_uri( '?payment-id=' . $payment ),
				'surl'               => $listener_url,
				'authorize_user'     => 1,
			);

			$shamrt_args = apply_filters( 'edd_shmart_redirect_args', $shamrt_args, $purchase_data );

			printf( '<form action="%1$s" method="POST" name="shmartForm">', esc_attr( $shmart_redirect ) );

			foreach ( $shamrt_args as $arg => $arg_value ) {

				printf( '<input type="hidden" name="%1$s" value="%2$s">', esc_attr( $arg ), esc_attr( $arg_value ) );

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
	 *
	 * @global $edd_options Array of all the EDD Options
	 * @return void
	 */
	public function get_response_from_shmart() {

		global $edd_options;

		$edd_listener = filter_input( INPUT_GET, 'edd-listener', FILTER_SANITIZE_STRING );

		// Regular Shmart Response.
		if ( ! empty( $edd_listener ) && 'SHMART_RESPONSE' === $edd_listener ) {
			do_action( 'verify_shmart_response' );
		}

	}

	/**
	 * Process Shmart response and then redirect to purchase confirmation page.
	 *
	 * @global $edd_options Array of all the EDD Options
	 * @return void
	 */
	public function process_shmart_response() {

		global $edd_options;

		// Get all response data coming from shmart.
		$status             = filter_input( INPUT_POST, 'status', FILTER_SANITIZE_STRING );
		$status_message     = filter_input( INPUT_POST, 'status_msg', FILTER_SANITIZE_STRING );
		$shmart_ref_id      = filter_input( INPUT_POST, 'shmart_refID', FILTER_SANITIZE_STRING );
		$shmart_merchant_id = filter_input( INPUT_POST, 'merchant_refID', FILTER_SANITIZE_STRING );

		// Get payment id.
		$payment_id = filter_input( INPUT_GET, 'payment-id', FILTER_SANITIZE_STRING );

		// Get Payment status code.
		$payment_status_code = intval( $status );

		if ( empty( $payment_id ) ) {
			return;
		}

		if ( 'shmart' !== edd_get_payment_gateway( $payment_id ) ) {
			return; // this isn't a shmart response.
		}

		// create payment note.
		// translators: %1$s: Reference ID, %2$s: Merchant Reference ID.
		$payment_note  = sprintf( __( 'Shmart Reference ID: %1$s <br> Merchant Reference ID: %2$s', 'edd-shmart' ), $shmart_ref_id, $shmart_merchant_id );
		$payment_note .= sprintf( '<br> Message: %1$s', $status_message );

		if ( 0 === $payment_status_code ) {

			edd_insert_payment_note( $payment_id, $payment_note );
			edd_set_payment_transaction_id( $payment_id, $shmart_ref_id );
			edd_update_payment_status( $payment_id, 'publish' );

			$confirm_url = add_query_arg(
				array(
					'payment-confirmation' => 'shmart',
					'payment-id'           => $payment_id,
				),
				get_permalink( $edd_options['success_page'] )
			);

			// Wp redirect safe.
			wp_redirect( $confirm_url ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
			exit;

		} else {

			edd_insert_payment_note( $payment_id, $payment_note );
			edd_set_payment_transaction_id( $payment_id, $post_data['shmart_refID'] );
			edd_update_payment_status( $payment_id, 'failed' );

			// Wp redirect safe.
			wp_redirect( edd_get_failed_transaction_uri( '?payment-id=' . $payment_id ) ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
			exit;

		}

		die();

	}

	/**
	 * Mark payments as Failed when returning to the Failed Transaction page
	 *
	 * @return      void
	 */
	public function shmart_listen_for_failed_payments() {

		$failed_page = edd_get_option( 'failure_page', 0 );

		$payment_id = filter_input( INPUT_GET, 'payment-id', FILTER_SANITIZE_NUMBER_INT );

		if ( ! empty( $failed_page ) && is_page( $failed_page ) && ! empty( $payement_id ) ) {

			$payment_id = absint( $payment_id );

			if ( ! empty( filter_input_array( INPUT_POST ) ) ) {

				// create payment note for failed transaction.
				// translators: %1$s: Shmart Reference ID, %2$s: Merchant Reference ID.
				$payment_note  = sprintf( __( 'Shmart Reference ID: %1$s <br> Merchant Reference ID: %2$s', 'edd-shmart' ), filter_input( INPUT_GET, 'shmart_refID', FILTER_SANITIZE_STRING ), filter_input( INPUT_GET, 'merchant_refID', FILTER_SANITIZE_STRING ) );
				$payment_note .= sprintf( __( '<br> Message: ', 'edd-shmart' ), filter_input( INPUT_POST, 'status_msg', FILTER_SANITIZE_STRING ) );

				edd_insert_payment_note( $payment_id, $payment_note );
				edd_set_payment_transaction_id( $payment_id, filter_get( INPUT_POST, 'shmart_refID', FILTER_SANITIZE_STRING ) );
			}
		}

	}

	/**
	 * Check Open Exchange Rates APP ID is valid or not.
	 * If valid then store and return currency rates.
	 * Currency rates store in transient for 1 hour.
	 * If not valid then return error message.
	 *
	 * @global $edd_options
	 * @return array
	 */
	public function get_currency_rate() {

		global $edd_options;

		$exchange_rates = '';
		$app_id         = '';
		$return         = array();

		// Check for app id.
		if ( ! empty( $edd_options['shmart_openexchangerates_appid'] ) ) {

			$app_id = $edd_options['shmart_openexchangerates_appid'];

		} else {

			return array(
				'error'   => true,
				'message' => __( 'Need app id for currency conversion', 'edd-shmart' ),
			);

		}

		$exchange_rates = get_transient( '_rtp_currency_rates' );

		// Get currency rates if exist.
		if ( false === $exchange_rates ) {

			// It wasn't there, so get latest currency rates.
			$file = 'latest.json';
			$base = $edd_options['currency'];
			$url  = "http://openexchangerates.org/api/{$file}?base={$base}&app_id={$app_id}";

			// Get the rates from API.
			$res = wp_remote_get( $url );

			// Target the body.
			$body = $res['body'];

			// Decode JSON response.
			$exchange_rates = json_decode( $body );

			if ( ! empty( $exchange_rates->error ) ) {

				return array(
					'error'   => true,
					'message' => $exchange_rates->description,
				);

			} else {

				set_transient( '_rtp_currency_rates', $exchange_rates->rates, 1 * HOUR_IN_SECONDS );
				$exchange_rates = $exchange_rates->rates;

			}
		}

		return array(
			'success' => true,
			'rates'   => $exchange_rates,
		);
	}

	/**
	 * Doing currency conversion. For example convert USD amount to INR.
	 *
	 * @param int    $amount Amount in.
	 * @param string $currency Defualt is INR.
	 * @return       $converted_amount Converted amount.
	 */
	public function do_currency_conversion( $amount, $currency = 'INR' ) {

		global $edd_options;

		$converted_amount = $amount;
		if ( ! empty( $edd_options['currency'] ) && 'INR' !== $edd_options['currency'] ) {

			$exchange_rates = $this->get_currency_rate();

			if ( ! empty( $exchange_rates['success'] ) ) {
				$converted_amount = ( $amount * $exchange_rates['rates']->$currency );
			}

			/**
				 * `edd_shmart_currency_conversion` filter.
				 * Allow users to use different currency conversion api if store currency is not INR.
				 * $converted_amount amount after currency conversion
				 * $amount actual amount
				 * $base_currency is the store currency.
				 * $currency amount is converted into this currency.
				 */
			$base_currency = $edd_options['currency'];

			$converted_amount = apply_filters( 'edd_shmart_currency_conversion', $converted_amount, $amount, $base_currency, $currency );

		}

		return $converted_amount;

	}

	/**
	 * Display `Settings` link on plugin page (beside the activate/deactivate links).
	 *
	 * @param  array $action_links Action links.
	 * @return array $action_links
	 */
	public function shmart_action_links( $action_links ) {

		$settings = array(
			'settings' => sprintf( '<a href="%1$s">%2$s</a>', admin_url( 'edit.php?post_type=download&page=edd-settings&tab=gateways' ), __( 'Settings', 'edd-shmart' ) ),
		);

		$action_links = array_merge( $settings, $action_links );

		return $action_links;

	}

	/**
	 * Clear currency conversion rates from transient.
	 *
	 * @param type $old_value Old value.
	 * @param type $value     New value.
	 */
	public function clear_currency_rates( $old_value, $value ) {

		delete_transient( '_rtp_currency_rates' );

	}

}
