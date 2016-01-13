<?php
/**
 * Plugin Name: Payment Gateway Easy Digital Downloads Shmart
 * Plugin URI:
 * Description: Extends Easy Digital Downloads plugin, allowing you to take payments through popular Indian payment gateway service Shmart.
 * Version: 1.0
 * Author: rtCamp
 * Author URI: https://rtcamp.com/
 * Text Domain: edd-shmart
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
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

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
    define( 'SPG_ROOT', plugin_dir_path(__FILE__) );
}

/**
 * Base URL of plugin
 */
if ( !defined( 'SPG_BASEURL' ) ) {
    define( 'SPG_BASEURL', plugin_dir_url(__FILE__) );
}

/**
 * Base Name of plugin
 */
if ( !defined( 'SPG_BASENAME' ) ) {
    define( 'SPG_BASENAME', plugin_basename(__FILE__) );
}

/**
 * Directory Name of plugin
 */
if ( !defined( 'SPG_DIRNAME' ) ) {
    define( 'SPG_DIRNAME', dirname( plugin_basename(__FILE__) ) );
}

/**
 * Shmart_Payment_Gateway
 *
 * The starting point of Shmart_Payment_Gateway.
 *
 * @package Shmart_Payment_Gateway
 * @since Shmart_Payment_Gateway 1.0
 */
if ( !class_exists( "Shmart_Payment_Gateway" ) ) {

    class Shmart_Payment_Gateway {

        // plugin warning
        protected $warning;

        public function __construct() {
            global $edd_options;

            // we use this hook to render our warnings
            add_action( 'admin_notices', array( $this, 'render_warnings' ) );

            // check if EDD is active
            if ( !class_exists( 'Easy_Digital_Downloads' ) ) {
                $this->warning = sprintf(
                    __( 'The plugin Easy Digital Downloads - Shmart Payment Gateway is enabled but not effective. It requires %s in order to work.', 'edd-shmart' ),
                    sprintf( '<a target="_blank" href="%s">%s</a>', 'https://wordpress.org/plugins/easy-digital-downloads/', 'Easy Digital Downloads' )
                );
                return;
            }

            //Display notice if app id is blank.
            if( isset( $edd_options['gateways']['shmart'] ) && ( !isset( $edd_options['shmart_openexchangerates_appid'] ) || empty( $edd_options['shmart_openexchangerates_appid'] ) ) ) {
                $this->warning = __( 'Please enter Open Exchange Rates app id under shmart settings.', 'edd-shmart' );
            }

            // Add filter to add payment gateway in edd.
            add_filter( 'edd_payment_gateways', array( &$this, 'add_shmart_payment' ) );

            // Add filter to register shmart payment gateway settings in edd.
            add_filter( 'edd_settings_gateways', array( &$this, 'register_shmart_payment_gateway_settings' ) );

            // Add shmart payment form for user.
            add_action( 'edd_shmart_cc_form', array( &$this, 'add_shmart_payment_form' ) );

            // Add `Contact Number` field into shmart payment gateway form.
            add_action( 'edd_purchase_form_user_info_fields', array( &$this, 'shmart_payment_form_fields' ) );

            // Add validation in shmart payment gateway form.
            add_filter( 'edd_purchase_form_required_fields', array( &$this, 'shmart_payment_form_fields_validation' ) );

            // Set validation for billing address by return `true` or `false`;
            add_filter( 'edd_require_billing_address', array( &$this, 'is_billing_address_require' ) );

            // Process Shmart Purchase.
            add_action( 'edd_gateway_shmart', array( &$this, 'process_shmart_purchase' ) );

            // Get Resposne from shmart.
            add_action( 'init', array( &$this, 'get_response_from_shmart' ) );

            // Process shmart response.
            add_action( 'verify_shmart_response', array( &$this, 'process_shmart_response' ) );

            // Process if shmart payment is failed.
            add_action( 'template_redirect', array( &$this, 'shmart_listen_for_failed_payments' ), 20 );

            //Display settings link on plugin page (beside the activate/deactivate links).
            add_filter( 'plugin_action_links_' . SPG_BASENAME, array( &$this, 'shmart_action_links' ) );

            //Clear currency conversion rates if settings updated.
            add_action( 'update_option_edd_settings', array( &$this, 'clear_currency_rates' ), 10, 2 );
        }

        /**
         * Render plugin warning.
         */
        public function render_warnings() {
            if( !empty( $this->warning ) ) :
        ?>
            <div class="message error">
                <p><?php echo $this->warning; ?></p>
            </div>
        <?php
            endif;
        }

        /**
         * Add `Shmart` Payment gateway into EDD.
         * @param array   $gateways Payment Gateways List
         * @return $gateways
         */
        public function add_shmart_payment( $gateways ) {
            global $edd_options;

            $gateways['shmart'] = array(
                'admin_label'       => __( 'Shmart (recommended for Indian users)', 'edd-shmart' ),
                'checkout_label'    => __( 'Shmart', 'edd-shmart' ),
            );

            if( isset( $edd_options['shmart_checkout_label'] ) && !empty( $edd_options['shmart_checkout_label'] ) ) {
                $gateways['shmart']['checkout_label'] = __( $edd_options['shmart_checkout_label'], 'edd' );
            }

            return $gateways;
        }

        /**
         * Register `Shmart` Payment gateway settings in EDD Payment Gateway tab.
         * @param array   $gateways_settings Settings of gateways
         * @return $gateways_settings
         */
        public function register_shmart_payment_gateway_settings( $gateways_settings ) {
            global $edd_options;

            $gateways_settings['shmart'] = array(
                'id'    => 'shmart',
                'name'  => '<strong>' . __( 'Shmart Settings', 'edd-shmart' ) . '</strong>',
                'desc'  => __( 'Configure the Shmart settings', 'edd-shmart' ),
                'type'  => 'header'
            );

            $gateways_settings['shmart_merchant_id'] = array(
                'id'    => 'shmart_merchant_id',
                'name'  => __( 'Shmart Merchant ID', 'edd-shmart' ),
                'desc'  => __( 'Enter your Merchant ID', 'edd-shmart' ),
                'type'  => 'text',
                'size'  => 'regular'
            );

            $gateways_settings['shmart_api_key'] = array(
                'id'    => 'shmart_apikey',
                'name'  => __( 'Shmart API Key', 'edd-shmart' ),
                'desc'  => __( 'Enter your Shmart API Key', 'edd-shmart' ),
                'type'  => 'text',
                'size'  => 'regular'
            );

            $gateways_settings['shmart_secret_key'] = array(
                'id'    => 'shmart_secret_key',
                'name'  => __( 'Shmart Secret Key', 'edd-shmart' ),
                'desc'  => __( 'Enter your Shmart Secret Key', 'edd-shmart' ),
                'type'  => 'text',
                'size'  => 'regular'
            );

            $gateways_settings['shmart_checkout_label'] = array(
                'id'    => 'shmart_checkout_label',
                'name'  => __( 'Checkout Label', 'edd-shmart' ),
                'desc'  => __( 'Display payment gateway text on checkout page', 'edd-shmart' ),
                'type'  => 'text',
                'size'  => 'regular',
            );

            if( isset( $edd_options['currency'] ) && 'INR' != $edd_options['currency'] ) {
                $desc = "For USD, you can use free API to convert USD to INR. <a target='_blank' href='https://openexchangerates.org/signup/free'>Click Here</a> for free api." .
                             "<br>Other than USD currency, you must need to use enterpise API. <a target='_blank' href='https://openexchangerates.org/signup'>Click Here</a> for enterprise or unlimited api." .
                             "<br>In both condition, API key is must to enable shmart payment gateway.";
                $desc = __( $desc, 'edd-shmart' );

                $gateways_settings['shmart_openexchangerates_appid'] = array(
                    'id'    => 'shmart_openexchangerates_appid',
                    'name'  => __( 'Open Exchange Rates APP ID', 'edd-shmart' ),
                    'desc'  => $desc,
                    'type'  => 'text',
                    'size'  => 'regular',
                );
            }

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
            if ( 'shmart' == edd_get_chosen_gateway() ) {
                $contact_number = is_user_logged_in() ? get_user_meta( get_current_user_id(), '_edd_user_contact_info', true ) : '';
                ?>
                <p id="edd-contact-wrap">
                    <label for="contact_number" class="edd-label">
                <?php _e( 'Contact Number', 'edd-shmart' ); ?>
                        <?php if ( edd_field_is_required( 'contact_number' ) ) { ?>
                            <span class="edd-required-indicator">*</span>
                        <?php } ?>
                    </label>
                    <span class="edd-description"><?php _e( 'Your contact number.', 'edd-shmart' ); ?></span>
                    <input id="contact_number" type="text" size="10" name="contact_number" class="contact-number edd-input<?php if ( edd_field_is_required('contact_number') ) {
                    echo ' required';
                } ?>" placeholder="<?php _e( 'Contact Number', 'edd-shmart' ); ?>" value="<?php echo $contact_number; ?>"/>
                </p>
                <?php
            }
        }

        /**
         * Billing address is required for shmart payment gateway.
         * @param  bool $is_billing Whether billing details is dispalyed or not.
         */
        public function is_billing_address_require( $is_billing ) {
            if ( 'shmart' == edd_get_chosen_gateway() && edd_get_cart_total() ) {
                return true;
            } else {
                return false;
            }
        }

        /**
         * Add validation in shmart payment gateway form.
         * @param array   $required_fields All Require fields
         * @return $required_fields
         */
        public function shmart_payment_form_fields_validation( $required_fields ) {

            if ( 'shmart' == edd_get_chosen_gateway() ) {
                $required_fields['contact_number'] = array(
                    'error_id' => 'invalid_contact_number',
                    'error_message' => __( 'Please enter contact number', 'edd-shmart' )
                );
                if( edd_get_cart_total() ) {
                    $required_fields['card_address'] = array(
                        'error_id' => 'invalid_address',
                        'error_message' => __( 'Please enter billing address', 'edd-shmart' )
                    );
                }
            }

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

            if ( is_ssl() || !$ssl_check ) {
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

            $charactersLength = strlen( $characters );

            $merchant_refID = '';

            for ($i = 0; $i < $length; $i++) {
                $merchant_refID .= $characters[ rand( 0, $charactersLength - 1 ) ];
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

            if ( !wp_verify_nonce( $purchase_data['gateway_nonce'], 'edd-gateway' ) ) {
                wp_die(__( 'Nonce verification has failed', 'edd-shmart' ), __( 'Error', 'edd-shmart' ), array( 'response' => 403 ) );
            }

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
                'gateway'       => 'shmart',
                'status'        => 'pending'
            );

            // Add contact number if user is logged in.
            if ( is_user_logged_in() ) {
                $user_ID = get_current_user_id();
                // Add contact number in user meta.
                update_user_meta( $user_ID, '_edd_user_contact_info', $purchase_data['post_data']['contact_number'] );
            }

            // Record the pending payment
            $payment = edd_insert_payment( $payment_data );

            // Check payment
            if ( !$payment ) {
                // Record the error
                edd_record_gateway_error( __( 'Payment Error', 'edd-shmart' ), sprintf( __( 'Payment creation failed before sending buyer to Shmart. Payment data: %s', 'edd-shmart' ), json_encode( $payment_data ) ), $payment );
                // Problems? send back
                edd_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['edd-gateway'] );
            } else {
                // Only send to Shmart if the pending payment is created successfully
                //$listener_url = add_query_arg( 'edd-listener', 'SHMART_RESPONSE', home_url( 'index.php' ) );
                // Get the success url
                $listener_url = add_query_arg(
                    array(
                        'edd-listener'  => 'SHMART_RESPONSE',
                        'payment-id'    => $payment
                    ),
                    home_url( 'index.php' )
                );

                // Get the Shmart redirect uri
                $shmart_redirect = trailingslashit( $this->get_shmart_redirect() );

                // Merchant ID.
                $merchant_id = $edd_options['shmart_merchant_id'];

                // Generate merchant ref ID.
                $merchant_refID = $this->generate_merchant_ref_ID();

                // Checksum Method.
                $checksum_method = 'MD5';

                /* Do currency conversion. */
                $amount = $this->do_currency_conversion( $purchase_data['price'] );

                // Round up final amount and convert amount into paisa.
                $amount = ( ceil( $amount ) * 100 );

                // String to generate checksum.
                $checksum_string = $edd_options['shmart_secret_key'] . $merchant_id . '|' . $edd_options['shmart_apikey'] . '|' . $_SERVER['SERVER_ADDR'] . '|' . $merchant_refID . '|' . 'INR' . '|' . $amount . '|' . $checksum_method . '|' . 1;

                // Generate checksum.
                $checksum = md5( $checksum_string );

                // Setup Shamrt arguments
                $shamrt_args = array(
                    'apikey'                => $edd_options['shmart_apikey'],
                    'currency_code'         => 'INR',
                    'amount'                => $amount,
                    'merchant_refID'        => $merchant_refID,
                    'merchant_id'           => $merchant_id,
                    'checksum_method'       => $checksum_method,
                    'checksum'              => $checksum,
                    'ip_address'            => $_SERVER['SERVER_ADDR'],
                    'email'                 => $purchase_data['user_email'],
                    'mobileNo'              => $purchase_data['post_data']['contact_number'],
                    'f_name'                => $purchase_data['user_info']['first_name'],
                    'addr'                  => $purchase_data['user_info']['address']['line1'] . ', ' . $purchase_data['user_info']['address']['line2'],
                    'city'                  => $purchase_data['user_info']['address']['city'],
                    'state'                 => $purchase_data['user_info']['address']['state'],
                    'zipcode'               => $purchase_data['user_info']['address']['zip'],
                    'country'               => $purchase_data['user_info']['address']['country'],
                    'show_shipping_addr'    => 0,
                    'rurl'                  => get_permalink( $edd_options['success_page'] ),
                    'furl'                  => edd_get_failed_transaction_uri( '?payment-id=' . $payment ),
                    'surl'                  => $listener_url,
                    'authorize_user'        => 1,
                );

                $shamrt_args = apply_filters( 'edd_shmart_redirect_args', $shamrt_args, $purchase_data );

                echo '<form action="' . $shmart_redirect . '" method="POST" name="shmartForm">';

                foreach ( $shamrt_args as $arg => $arg_value ) {
                    echo '<input type="hidden" name="' . $arg . '" value="' . $arg_value . '">';
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
            if ( isset( $_GET['edd-listener'] ) && 'SHMART_RESPONSE' == $_GET['edd-listener'] ) {
                do_action( 'verify_shmart_response' );
            }
        }

        /**
         * process Shmart response and then redirect to purchase confirmation page.
         * @global $edd_options Array of all the EDD Options
         * @return void
         */
        public function process_shmart_response() {
            global $edd_options;

            // Get all response data coming from shamrt.
            $post_data = $_POST;

            // Get payment id.
            $payment_id = $_GET['payment-id'];

            // Get Payment status code.
            $payment_status_code = intval( $post_data['status'] );

            if ( empty( $payment_id ) ) {
                return;
            }

            if ( 'shmart' != edd_get_payment_gateway( $payment_id ) ) {
                return; // this isn't a shmart response.
            }

            // create payment note.
            $payment_note = sprintf( __( 'Shmart Reference ID: %s <br> Merchant Reference ID: %s', 'edd-shmart' ), $post_data['shmart_refID'], $post_data['merchant_refID'] );
            $payment_note .= '<br> Message: ' . $post_data['status_msg'];

            if ( 0 == $payment_status_code ) {

                edd_insert_payment_note( $payment_id, $payment_note );
                edd_set_payment_transaction_id( $payment_id, $post_data['shmart_refID'] );
                edd_update_payment_status( $payment_id, 'publish' );

                $confirm_url = add_query_arg(
                    array(
                        'payment-confirmation' => 'shmart',
                        'payment-id'            => $payment_id
                    ),
                    get_permalink( $edd_options['success_page'] )
                );

                wp_redirect( $confirm_url );
            } else {

                edd_insert_payment_note( $payment_id, $payment_note );
                edd_set_payment_transaction_id( $payment_id, $post_data['shmart_refID'] );
                edd_update_payment_status( $payment_id, 'failed' );

                wp_redirect( edd_get_failed_transaction_uri( '?payment-id=' . $payment_id ) );
            }

            die();
        }

        /**
         * Mark payments as Failed when returning to the Failed Transaction page
         * @return      void
         */
        public function shmart_listen_for_failed_payments() {

            $failed_page = edd_get_option( 'failure_page', 0 );

            if ( !empty( $failed_page ) && is_page( $failed_page ) && !empty( $_GET['payment-id'] ) ) {

                $payment_id = absint( $_GET['payment-id'] );

                if ( !empty( $_POST ) ) {
                    // create payment note for failed transaction.
                    $payment_note = sprintf( __( 'Shmart Reference ID: %s <br> Merchant Reference ID: %s', 'edd-shmart' ), $_POST['shmart_refID'], $_POST['merchant_refID'] );
                    $payment_note .= '<br> Message: ' . $_POST['status_msg'];

                    edd_insert_payment_note( $payment_id, $payment_note );
                    edd_set_payment_transaction_id( $payment_id, $post_data['shmart_refID'] );
                }
            }
        }

        /**
         * Check Open Exchange Rates APP ID is valid or not.
         * If valid then store and return currency rates.
         * Currency rates store in transient for 1 hour.
         * If not valid then return error message.
         * @global $edd_options
         * @return array
         */
        public function get_currency_rate() {
            global $edd_options;

            $exchangeRates = $appId = ''; $return = array();

            // Check for app id.
            if( isset( $edd_options['shmart_openexchangerates_appid'] ) && !empty( $edd_options['shmart_openexchangerates_appid'] ) ) {
                $appId = $edd_options['shmart_openexchangerates_appid'];
            } else {
                return $return = array(
                    "error"     => true,
                    "message"   => __( 'Need app id for currency conversion', 'edd-shmart' ),
                );
            }

            // Get currency rates if exist.
            if ( false === ( $exchangeRates = get_transient( '_rtp_currency_rates' ) ) ) {
                // It wasn't there, so get latest currency rates.

                $file = 'latest.json';
                $base = $edd_options['currency'];

                // Open CURL session:
                $ch = curl_init( "http://openexchangerates.org/api/{$file}?base={$base}&app_id={$appId}" );
                curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );

                // Get the data:
                $json = curl_exec( $ch );
                curl_close( $ch );

                // Decode JSON response:
                $exchangeRates = json_decode( $json );

                if( isset( $exchangeRates->error ) ) {
                    return $return = array(
                        "error"     => true,
                        "message"   => $exchangeRates->description,
                    );
                } else {
                    set_transient( '_rtp_currency_rates', $exchangeRates->rates, 1 * HOUR_IN_SECONDS );
                    $exchangeRates = $exchangeRates->rates;
                }
            }

            return $return = array(
                "success"     => true,
                "rates"   => $exchangeRates,
            );
        }
        /**
         * Doing currency conversion. For example convert USD amount to INR.
         * @param int   $amount Amount in.
         * @param string $currency Defualt is INR
         * @return $converted_amount Converted amount.
         */
        public function do_currency_conversion( $amount, $currency = 'INR' ) {
            global $edd_options;

            $converted_amount = $amount;
            if( isset( $edd_options['currency'] ) && 'INR' != $edd_options['currency'] ) {
                $exchangeRates = $this->get_currency_rate();
                if( isset( $exchangeRates['success'] ) ) {
                    $converted_amount = ( $amount * $exchangeRates['rates']->$currency );
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
         * @param array $action_links
         * @return array $action_links
         *
         */
        public function shmart_action_links( $action_links ) {
            $settings = array(
                'settings' => '<a href="' . admin_url( 'edit.php?post_type=download&page=edd-settings&tab=gateways' ) . '">' . __( 'Settings', 'edd-shmart' ) . '</a>'
            );
            $action_links = array_merge( $settings, $action_links );
            return $action_links;
        }

        /**
         * Clear currency conversion rates from transient.
         * @param type $old_value
         * @param type $value
         */
        public function clear_currency_rates( $old_value, $value ) {
            delete_transient( '_rtp_currency_rates' );
        }
    }

    /**
     * Instantiate Main Class
     */
    global $rtspg;
    $rtspg = new Shmart_Payment_Gateway();
}
