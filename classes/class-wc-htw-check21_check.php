<?php
/**
 * WC HighTechWeb Check21 Gateway Class.
 *
 */

class WC_htw_Chek21_check extends WC_Payment_Gateway {
	
	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {
		global $woocommerce;
		
		$this->id = 'htw-check21';
		$this->icon = apply_filters ( 'wc_authorize_net_cim_echeck_icon', '' );
		$this->has_fields = true;
		$this->method_title = __ ( 'HighTechWeb Check21', 'woocommerce-htw-check21' );
		$this->method_description = __ ( 'Accept payments by Check21 ', 'woocommerce-htw-check21' );
		// $this->view_transaction_url = '';
		
		$this->transaction_number = null;
		$this->transaction_status = null;
		
		$this->supports = array (
				'subscriptions',
				'products',
				'subscription_cancellation',
				'subscription_reactivation',
				'subscription_suspension',
				'subscription_amount_changes',
				'subscription_payment_method_change',
				'subscription_date_changes',
				'default_credit_card_form',
				'refunds',
				'pre-orders' 
		);
		
		$this->api_url = 'https://www.backofficemanage.com/API.aspx';
		
		// Load the settings.
		$this->init_form_fields ();
		$this->init_settings ();
		
		// Define user set variables
		$this->debug = $this->get_option ( 'debug' );
		$this->enabled = $this->get_option ( 'enable' );
		$this->merchant = $this->get_option ( 'merchant' );
		$this->account_number = $this->get_option ( 'account_number' );
		$this->username = $this->get_option ( 'username' );
		$this->test_mode = $this->get_option ( 'test_mode' );
		
		foreach ( $this->settings as $setting_key => $setting ) {
			$this->$setting_key = $setting;
		}
		
		// Save Settings
		add_action ( 'woocommerce_update_options_payment_gateways_' . $this->id, array (
				$this,
				'process_admin_options' 
		) );
		
		// Filters.
		add_filter ( 'woocommerce_settings_api_sanitized_fields_' . $this->id, array (
				$this,
				'sanitize_settings' 
		) );
		add_action ( 'woocommerce_order_status_refunded', 'htw_refunded' );
		
		// Active logs.
		if ('yes' == $this->debug) {
			if (class_exists ( 'WC_Logger' )) {
				$this->log = new WC_Logger ();
			} else {
				$this->log = $woocommerce->logger ();
			}
		}
		
		// Display admin notices.
		$this->admin_notices ();
	}
	
	
	
	public function init_form_fields() {
		$this->form_fields = array (
				
				'enabled' => array (
						'title' => __ ( 'Enable / Disable', WC_htw_Chek21::TEXT_DOMAIN ),
						'label' => __ ( 'Enable this gateway.', WC_htw_Chek21::TEXT_DOMAIN ),
						'type' => 'checkbox',
						'default' => 'no' 
				),
				
				'merchant' => array (
						'title' => __ ( 'Merchant Name', 'woocommerce-htw-check21' ),
						'type' => 'text',
						'desc_tip' => true,
						'default' => '' ,
						'description'    => __( ' The API " Name Merchant" for your Credential Check21.com account.  ', WC_htw_Chek21::TEXT_DOMAIN ),
				),
				'account_number' => array (
						'title' => __ ( 'Merchant Account Number', 'woocommerce-htw-check21' ),
						'type' => 'text',
						'desc_tip' => true,
						'default' => '',
						'description'    => __( ' The API " Account Number " for your Credential Check21.com account.  ', WC_htw_Chek21::TEXT_DOMAIN ),
				),
				'username' => array (
						'title' => __ ( 'Merchant Username', 'woocommerce-htw-check21' ),
						'type' => 'text',
						'desc_tip' => true,
						'default' => '' ,
						'description'    => __( ' The API " Username " for your Credential Check21.com account.  ', WC_htw_Chek21::TEXT_DOMAIN ),
				),
				'test_mode' => array(
						'title'       => __( 'Test Mode', WC_htw_Chek21::TEXT_DOMAIN ),
						'label'       => __( 'Enable Test Mode', WC_htw_Chek21::TEXT_DOMAIN ),
						'type'        => 'checkbox',
						'description' => sprintf( __( 'Enable Test Mode ', WC_htw_Chek21::TEXT_DOMAIN )),
						'default'     => 'no'
				),
				'debug' => array (
						'title' => __ ( 'Debug Log', 'woocommerce-htw-check21' ),
						'type' => 'checkbox',
						'label' => __ ( 'Enable logging', 'woocommerce-htw-check21' ),
						'default' => 'no',
						'description' => sprintf ( __ ( 'Log HightTechWeb Check21 events, such as API requests, inside %s', 'woocommerce-htw-check21' ), '<code>woocommerce/logs/' . esc_attr ( $this->id ) . '-' . sanitize_file_name ( wp_hash ( $this->id ) ) . '.txt</code>' ) 
				) 
		);
	}
	
	/**
	 * Generate Button HTML.
	 */
	public function generate_button_html($key, $data) {
		$field = $this->plugin_id . $this->id . '_' . $key;
		$defaults = array (
				'class' => 'button-secondary',
				'css' => '',
				'custom_attributes' => array (),
				'desc_tip' => false,
				'description' => '',
				'title' => '' 
		);
		
		$data = wp_parse_args ( $data, $defaults );
		
		ob_start ();
		?>
<tr valign="top">
	<th scope="row" class="titledesc"><label
		for="<?php echo esc_attr( $field ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
					<?php echo $this->get_tooltip_html( $data ); ?>
				</th>
	<td class="forminp">
		<fieldset>
			<legend class="screen-reader-text">
				<span><?php echo wp_kses_post( $data['title'] ); ?></span>
			</legend>
			<button class="<?php echo esc_attr( $data['class'] ); ?>" type="button" name="<?php echo esc_attr( $field ); ?>" id="<?php echo esc_attr( $field ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>" <?php echo $this->get_custom_attribute_html( $data ); ?>><?php echo wp_kses_post( $data['title'] ); ?></button>
						<?php echo $this->get_description_html( $data ); ?>
					</fieldset>
	</td>
</tr>
<?php
		return ob_get_clean ();
	}
	
	/**
	 * Santize our settings
	 *
	 * @see process_admin_options()
	 */
	public function sanitize_settings($settings) {
		// We're just going to make the api key all upper case characters since that's how our imaginary API works
		if (isset ( $settings ) && isset ( $settings ['api_key'] )) {
			$settings ['api_key'] = strtoupper ( $settings ['api_key'] );
		}
		return $settings;
	}
	
	/**
	 * Validate the API key
	 *
	 * @see validate_settings_fields()
	 */
	public function validate_api_key_field($key) {
		// get the posted value
		$value = $_POST [$this->plugin_id . $this->id . '_' . $key];
		
		// check if the API key is longer than 20 characters. Our imaginary API doesn't create keys that large so something must be wrong. Throw an error which will prevent the user from saving.
		if (isset ( $value ) && 20 < strlen ( $value )) {
			$this->errors [] = $key;
		}
		return $value;
	}
	
	/**
	 * Display errors by overriding the display_errors() method
	 *
	 * @see display_errors()
	 */
	public function display_errors() {
		
		// loop through each error and display it
		foreach ( $this->errors as $key => $value ) {
			?>
<div class="error">
	<p><?php _e( 'Looks like you made a mistake with the ' . $value . ' field. Make sure it isn&apos;t longer than 20 characters', 'woocommerce-htw-check21' ); ?></p>
</div>
<?php
		}
	}
	protected function admin_notices() {
		if (is_admin ()) {
			$id = 'woocommerce_' . $this->id . '_';
			
			// Checks if api_key is not empty.
			if (empty ( $this->account_number ) || empty ( $this->merchant )) {
				add_action ( 'admin_notices', array (
						$this,
						'plugin_not_configured_message' 
				) );
			}
		}
	}
	
	/**
	 * Returns a value indicating the the Gateway is available or not. It's called
	 * automatically by WooCommerce before allowing customers to use the gateway
	 * for payment.
	 */
	public function is_available() {
		// Test if is valid for use.
		$available = parent::is_available () && 'yes' == $this->get_option ( 'enabled' ) && ! empty ( $this->account_number ) && ! empty ( $this->merchant );
		
		return $available;
	}
	
	/**
	 * Add selected card icons to payment method label
	 */
	public function get_icon() {
		$icon = "Check21";
		return apply_filters ( 'woocommerce_gateway_icon', $icon, $this->id );
	}
	
	/**
	 * Display the payment fields on the checkout page
	 */
	public function payment_fields() {
		if ($this->method_title) {
			echo '<p>' . wp_kses_post ( $this->method_title ) . '</p>';
		}
		
		if ($this->test_mode) {
			
			echo '<p>' . __ ( 'TEST MODE ENABLED', WC_htw_Chek21::TEXT_DOMAIN ) . '</p>';
			echo '<p>' . sprintf ( __ ( 'Use test bank account - routing: %s, account number: %s', WC_htw_Chek21::TEXT_DOMAIN ), '031202084', '8675309' ) . '</p>';
		}
		
		?>
<fieldset>
				<?php
		
		if (is_user_logged_in ()) :
			
			$has_accounts = (count ( $accounts = $this->get_saved_bank_accounts ( get_current_user_id () ) ) > 0) ? true : false;
			if ($has_accounts) :
				?>
					  	<p class="form-row form-row-wide">
		<a class="button" style="float: right;"
			href="<?php echo get_permalink( wc_get_page_id( 'myaccount' ) ); ?>#cim-my-cards"><?php echo wp_filter_kses( parent::$instance->manage_payment_methods_text ); ?></a>
						<?php foreach( $accounts as $profile_id => $card ) : ?>
							<input type="radio"
			id="authorize-net-cim-echeck-payment-profile-id-<?php echo esc_attr( $profile_id ); ?>"
			name="authorize-net-cim-echeck-payment-profile-id"
			style="width: auto;" value="<?php echo esc_attr( $profile_id ); ?>"
			<?php checked( $card['active'] ); ?> /> <label
			style="display: inline;"
			for="authorize-net-cim-echeck-payment-profile-id-<?php echo esc_attr( $profile_id ); ?>"><?php printf( __( '%s ending in %s (expires %s)', WC_Authorize_Net_CIM::TEXT_DOMAIN ), esc_html( $card['type'] ), esc_html( $card['last_four'] ), esc_html( $card['exp_date'] ) ); ?></label><br />
							<?php endforeach; ?>
							<input type="radio" id="authorize-net-cim-use-new-bank-account"
			name="authorize-net-cim-echeck-payment-profile-id"
			<?php checked( $has_accounts, false ); ?> style="width: auto;"
			value="" /> <label style="display: inline;" for="new-card"><?php echo __( 'Use a new bank account', WC_htw_Chek21::TEXT_DOMAIN ); ?></label>
	</p>
	<div class="clear"></div>
						<?php endif; ?>
					<?php endif; ?>
					
				<div
		<?php echo ( isset( $has_accounts ) && $has_accounts ) ? ' class="authorize-net-cim-new-saved-bank-account"' : ''; ?>>
		<p class="form-row form-row-first">
			<label for="htw-check21-routing-number"><?php _e( "Bank Routing Number", WC_htw_Chek21::TEXT_DOMAIN); ?> <span
				class="required">*</span></label> <input type="text"
				class="input-text" id="htw-check21-routing-number"
				name="htw-check21-routing-number" maxlength="9" autocomplete="on" />
		</p>
		<p class="form-row form-row-last">
			<label for="htw-check21-account-number"><?php _e( "Bank Account Number", WC_htw_Chek21::TEXT_DOMAIN); ?> <span
				class="required">*</span></label> <input type="text"
				class="input-text" id="htw-check21-account-number"
				name="htw-check21-account-number" maxlength="17" autocomplete="on" />
		</p>
		<p class="form-row form-row-first">
			<label for="htw-check21-check-number"><?php _e( "Ckeck Number", WC_htw_Chek21::TEXT_DOMAIN); ?> <span
				class="required">*</span></label> <input type="text"
				class="input-text" id="htw-check21-check-number"
				name="htw-check21-check-number" maxlength="6" autocomplete="on" />
		</p>
		<div class="clear"></div>
	</div>
</fieldset>

<?php
		
		// wc_enqueue_js( ob_get_clean() );
	}
	
	/**
	 * Validate the payment fields when processing the checkout
	 */
	public function validate_fields() {
		$is_valid = true;
		
		$routing_number = SV_WC_Helper::get_post ( 'htw-check21-routing-number' );
		$account_number = SV_WC_Helper::get_post ( 'htw-check21-account-number' );
		$check_number = SV_WC_Helper::get_post ( 'htw-check21-check-number' );
		
		// routing number digit check
		if (! ctype_digit ( $routing_number )) {
			SV_WC_Helper::wc_add_notice ( __ ( 'Routing Number is invalid (only digits are allowed)', WC_htw_Chek21::TEXT_DOMAIN ), 'error' );
			$is_valid = false;
		}
		
		if (9 != strlen ( $routing_number )) {
			SV_WC_Helper::wc_add_notice ( __ ( 'Routing number is invalid (must be 9 digits)', WC_htw_Chek21::TEXT_DOMAIN ), 'error' );
			$is_valid = false;
		}
		
		if (! ctype_digit ( $account_number )) {
			SV_WC_Helper::wc_add_notice ( __ ( 'Account Number is invalid (only digits are allowed)', WC_htw_Chek21::TEXT_DOMAIN ), 'error' );
			$is_valid = false;
		}
		
		if (strlen ( $account_number ) < 5 || strlen ( $account_number ) > 17) {
			SV_WC_Helper::wc_add_notice ( __ ( 'Account number is invalid (must be between 5 and 17 digits)', WC_htw_Chek21::TEXT_DOMAIN ), 'error' );
			$is_valid = false;
		}
		
		if (! ctype_digit ( $check_number )) {
			SV_WC_Helper::wc_add_notice ( __ ( 'Check Number is invalid (only digits are allowed)', WC_htw_Chek21::TEXT_DOMAIN ), 'error' );
			$is_valid = false;
		}
		
		return $is_valid;
	}
	
	/**
	 * Get saved payment profiles 
	 */
	private function get_saved_bank_accounts($user_id) {
		$payment_profiles = $this->get_payment_profiles ( $user_id );
		
		if (empty ( $payment_profiles )) {
			return array ();
		}
		
		foreach ( $payment_profiles as $payment_profile_id => $payment_profile ) {
			
			// remove all non-bank account payment types
			if ('Bank Account' !== $payment_profile ['type']) {
				unset ( $payment_profiles [$payment_profile_id] );
			}
		}
		
		return $payment_profiles;
	}
	
	/**
	 * Get the available payment profiles for a user 
	 */
	public function get_payment_profiles($user_id) {
		$payment_profiles = get_user_meta ( $user_id, '_wc_authorize_net_cim_payment_profiles', true );
		
		return is_array ( $payment_profiles ) ? $payment_profiles : array ();
	}
	
	/**
	 * Process the payment.
	 */
	public function process_payment($order_id) {
		$order = new WC_Order ( $order_id );
		
		$transaction = $this->do_transaction ( $order, $_POST );
		
		$this->log->add ( $this->id, ' |Process Payment|  Transation: ' . $this->transation_number );
		$this->log->add ( $this->id, ' | Transation: ' . $transaction );
		
		if (! $transaction) {
			
			$this->add_error ( $transaction );
			
			$this->log->add ( $this->id, ' |Error|  Transation: ' . $this->transation_number );
			
			return array (
					'result' => 'fail' 
			);
		} // Save transaction data.
		update_post_meta ( $order->id, '_check21_transaction_id', $this->transation_number );
		
		// For WooCommerce 2.2 or later.
		update_post_meta ( $order->id, '_transaction_id', $this->transation_number );
		
		$this->process_order_status ( $order, $transaction );
		
		// Redirect to thanks page.
		if (defined ( 'WC_VERSION' ) && version_compare ( WC_VERSION, '2.1', '>=' )) {
			WC ()->cart->empty_cart ();
			
			return array (
					'result' => 'success',
					'redirect' => $this->get_return_url ( $order ) 
			);
		} else {
			$woocommerce->cart->empty_cart ();
			
			return array (
					'result' => 'success',
					'redirect' => add_query_arg ( 'key', $order->order_key, add_query_arg ( 'order', $order_id, get_permalink ( woocommerce_get_page_id ( 'thanks' ) ) ) ) 
			);
		}
	}
	
	/**
	 * Process the order status. 
	 */
	public function process_order_status($order, $status) {
		
		// TODO: Fazer o refund.
		if ('yes' == $this->debug) {
			$this->log->add ( $this->id, 'Payment status for order ' . $order->get_order_number () . ' is now: ' . $status );
		}
		
		switch ($status) {
			case 'paid' :
				$order->add_order_note ( __ ( 'HTW Chec21: Transaction paid.', 'woocommerce-htw-check21' ) );
				$order->payment_complete();
				$order->update_status( 'completed' );
				break;
			case 'over' :
				$order->update_status ( 'on-hold', __ ( 'HTW Chec21: The banking ticket was issued but not paid yet.', 'woocommerce-htw-check21' ) );
				
				break;
			case 'refused' :
				$order->update_status ( 'failed', __ ( 'HTW Chec21: The transaction was rejected by the card company or by fraud.', 'woocommerce-htw-check21' ) );
				
				$transaction_id = get_post_meta ( $order->id, '_check21_transaction_id', true );
				
				$this->send_email ( sprintf ( __ ( 'The transaction for order %s was rejected by the card company or by fraud', 'woocommerce-htw-check21' ), $order->get_order_number () ), __ ( 'Transaction failed', 'woocommerce-htw-check21' ), sprintf ( __ ( 'Order %s has been marked as failed, because the transaction was rejected by the card company or by fraud, for more details, see %s.', 'woocommerce-htw-check21' ), $order->get_order_number (), $transaction_url ) );
				break;
			case 'refunded' :
				$order->update_status( 'refunded', sprintf( __( 'Payment Refunded', 'woocommerce-htw-check21' ) ) );
				break;
			default :
				break;
		}
	}
	
	/**
	 * Do the transaction.
	 */
	protected function do_transaction($order, $posted) {
		$data = $this->generate_transaction_data ( $order, $posted );
		
		// Sets the post params.
		$params = array (
				'body' => http_build_query ( $data ),
				'sslverify' => false,
				'timeout' => 60 
		);
		
		if ('yes' == $this->debug) {
			$this->log->add ( $this->id, 'Doing a transaction for order ' . $order->get_order_number () . '...' );
		}
		
		$response = wp_remote_post ( $this->api_url, $params );
		
		
		$status_code = $this->status_codes ( $response ['body'] );
		
		if (is_wp_error ( $status_code )) {
			if ('yes' == $this->debug) {
				$this->log->add ( $this->id, 'WP_Error in doing the transaction: ' . $response->get_error_message () );
			}
			
			return array ();
		} else {
			
			$transaction_data = $status_code;
			
			if (! $transaction_data) {
				if ('yes' == $this->debug) {
					$this->log->add ( $this->id, 'Failed to make the transaction: ' . print_r ( $response, true ) );
				}
				
				return $transaction_data;
			}
			
			if ('yes' == $this->debug) {
				$this->log->add ( $this->id, 'Transaction completed successfully! The transaction response is: ' . print_r ( $transaction_data, true ) );
			}
			
			return $transaction_data;
		}
	}
	
	/**
	 * Get Status Code
	 * 
	 */
	function status_codes($result) {
		$return = true;
		$result_code = substr ( $result, 0, 1 );
		$result_msg = substr ( $result, 2, 1 );
		$result_id = substr ( $result, 4, strlen ( $result ) );
		
		/**
		 * $Result_Code:
		 * 0 = Operation Failed.
		 * 1 = Operation Completed Successfully.
		 * 2 = Daily Transaction Limit Has Been Passed.
		 *
		 *
		 * $Result Msg
		 * Success 0 No error exists.
		 * Internal 1 Internal API Error.
		 * Data Format 2 Fields provided are not in a valid format.
		 * Required Field Missing 3 Required fields are missing
		 * Duplicate Transaction 4 Transaction already exists in system.
		 * Transaction Confirmation Failed 5 Confirmed response cannot be returned.
		 * Over Limit 7 Daily transaction limit has been passed.
		 */
		
		if ($result_code != 1 && $result_msg != 0) {
			
			if ($result_Code == 2) {
				$this->transaction_status = "over";
			} else {
				$this->transaction_status = "refused";
			}
			
			$return = false;
		} else {
			$this->transaction_status = "paid";
		}
		
		$this->transation_number = $result_id;
		$this->transaction_status = "";
		
		$this->log->add ( $this->id, ' Result 0: ' . $return );
		$this->log->add ( $this->id, ' Result 1: ' . $this->transation_number );
		$this->log->add ( $this->id, ' Result 2: ' . $result );
		
		return $return;
	}
	
	/**
	 * Generate the transaction data.
	 */
	protected function generate_transaction_data($order, $posted, $refund = false, $amount = null) {
		global $woocommerce;
		
		if ('yes' == $this->debug) {
			$this->log->add ( $this->id, ' Gererate Transaction: ' . $order->id . ' Refund: ' . $refund );
		}
		
		
		$routing_number = SV_WC_Helper::get_post ( 'htw-check21-routing-number' );
		$account_number = SV_WC_Helper::get_post ( 'htw-check21-account-number' );
		$check_number = SV_WC_Helper::get_post ( 'htw-check21-check-number' );
		
		$ipaddress = 0;
		
		if (getenv ( 'HTTP_CLIENT_IP' )) {
			$ipaddress = getenv ( 'HTTP_CLIENT_IP' );
		}
		
		if (!$refund) {
			$data = array (
					'pfname' => $order->billing_first_name,
					'plname' => $order->billing_last_name,
					'paddress1' => $order->billing_address_1,
					'paddress2' => $order->billing_address_2,
					'pcity' => $order->billing_city,
					'pstate' => $order->billing_state,
					'pzip' => $order->billing_postcode,
					'pemail' => $order->billing_email,
					'pphone' => $order->billing_phone,
					'brouting' => $routing_number,
					'baccount' => $account_number,
					'bcheck' => str_pad ( $check_number, 6, 0, STR_PAD_LEFT ),
					'orderid' => $order->id,
					'memo' => ' TEST MMM ',
					'pdate' => date ( "m/d/Y" ),
					'accountid' => $this->account_number,
					'amount' => $order->order_total,
					'recurring' => 0,
					'timeoft' => '23:59 PM EST',
					'uname' => $this->username,
					'clientip' => $ipaddress 
			);
		} else {
			
			$data = array (
					'isrefund' => 'True',
					'tnum' => $this->get_transaction_id($order->id),
					'amount' => $amount,
					'accountid' => $this->account_number,
					'uname' => $this->username 
			);
			
			$this->log->add ( $this->id, ' Data: ' . print_r($data,true) );
			
		}
		
		return $data;
	}
	
	/**
	 *  Displays the error messages.
	 *        
	 */
	protected function add_error($messages) {
		global $woocommerce;
		
		if (defined ( 'WC_VERSION' ) && version_compare ( WC_VERSION, '2.1', '>=' )) {
			foreach ( $messages as $message ) {
				wc_add_notice ( $message ['message'], 'error' );
			}
		} else {
			foreach ( $messages as $message ) {
				$woocommerce->add_error ( $message ['message'] );
			}
		}
	}
	
	/**
	 * Process a refund if supported
	 */
	public function process_refund($order_id, $amount = null, $reason = '') {
		
		if ('yes' == $this->debug) {
			$this->log->add ( $this->id, ' Refund: ' . $order_id  . ' | Amount: ' . $amount . ' | Reason: ' . $reason);
		}
		
		$order = wc_get_order( $order_id );
		
		$this->log->add ( $this->id, ' Order Refund: ' . $order->id);
		
		$result = $this->refund_order( $order, $amount, $reason );
		
		if ($result) {
			
			$order->add_order_note ( sprintf ( __ ( 'Refunded %s - Refund ID: %s', 'woocommerce-htw-check21' ), $order_id, $this->transation_number ) );
			$this->process_order_status($order,'refunded');
			return true;
		}
		
		return false;
	}
	
	/**
	 * Get transaction id for the order
	 *
	 * @return string
	 */
	function get_transaction_id($order_id) {
		$this->log->add ( $this->id, ' Refund Order: ' . $order_id);
		return get_post_meta( $order_id, '_transaction_id', true );
		
	}
	
	/**
	 * Refund an order
	 */
	function refund_order($order, $amount = null, $reason = '') {
		
		if ('yes' == $this->debug) {
			$this->log->add ( $this->id, ' Refund Order: ' . $order->id . ' - Amount: ' . $amount );
		}
		
		$this->log->add ( $this->id, ' Refund Order: ' . $order_id  . ' | Amount: ' . $amount . ' | Reason: ' . $reason);
		
		$data = $this->generate_transaction_data ( $order, $posted, true, $amount );
		
		// Sets the post params.
		$params = array (
				'body' => http_build_query ( $data ),
				'sslverify' => false,
				'timeout' => 60 
		);
		
		if ('yes' == $this->debug) {
			$this->log->add ( $this->id, 'Doing a refund transaction for order ' . $order->get_order_number () . '...' );
		}
		
		$response = wp_remote_post ( $this->api_url, $params );
		
		
		$this->log->add ( $this->id, ' **** MMM : ' . $this->api_url . print_r($params,true) );
		
		$status_code = $this->status_codes ( $response ['body'] );
		
		if (is_wp_error ( $status_code )) {
			if ('yes' == $this->debug) {
				$this->log->add ( $this->id, 'WP_Error in doing the refund transaction: ' . $response->get_error_message () );
			}
			
			return array ();
		} else {
			
			$transaction_data = $status_code;
			
			if (! $transaction_data) {
				if ('yes' == $this->debug) {
					$this->log->add ( $this->id, 'Failed to make the refund transaction: ' . print_r ( $response, true ) );
				}
				
				return $transaction_data;
			}
			
			if ('yes' == $this->debug) {
				$this->log->add ( $this->id, 'Transaction completed successfully! The Refund transaction response is: ' . print_r ( $transaction_data, true ) );
			}
			
			
			
			return $transaction_data;
		}
	}
}


