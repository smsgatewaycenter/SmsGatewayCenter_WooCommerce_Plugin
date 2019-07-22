<?php
	/*
	  Plugin Name: WooCommerce SMSGatewayCenter SMS Alerts
	  Plugin URI: https://www.smsgateway.center
	  Description: Sends SMS Alerts for WooCommerce Order statuses and send bulk SMS to all subscribers groups via SMSGatewayCenter API.
	  Version: 1.0.1
	  Author: SMS Gateway Center
	  Author URI: https://www.smsgateway.center/docs/api/
	  Text Domain: SMSGatewayCenter
	 */
	if (!defined('ABSPATH'))exit;  // if direct access

	define("SGC_SMS_ALERTS_DOMAIN", "SGC-SMS-ALERTS");
	$plugin_dir_name = dirname(plugin_basename(__FILE__));
	define("WOOCOM_SGC_SMS_ALERTS_DIR", WP_PLUGIN_DIR . "/" . $plugin_dir_name);
	define("SMS_ALERTS_GATEWAY_URL", WP_PLUGIN_URL . "/" . $plugin_dir_name);

	global $wc_settings_sms,
	$smsid,
	$smslabel,
	$smsforwooplnm,
	$wpdb,
	$woocommerce,
	$product;

	class SGCSMSAlerts {

		/**
		 * Admin Page Settings
		 */
		private $options;

		/**
		 * Construct
		 */
		public function __construct() {
			add_action('admin_menu', array($this, 'sgc_sms_alerts_menu'));
			add_action('admin_init', array($this, 'sgc_sms_alerts_page_init'));
			add_action('admin_enqueue_scripts', array($this, 'sgc_sms_alerts_enqueue_script'));

			include_once WOOCOM_SGC_SMS_ALERTS_DIR . '/includes/class-sgc-alerts-subscribe.php';
			$this->subscribe = new SGC_Alerts_Subscriptions();
		}

		/**
		 * SMS Gateway Center Menu
		 */
		public function sgc_sms_alerts_menu() {
			add_menu_page('SGC SMS Alerts', 'SGC SMS Alerts', 'manage_options', 'sgc_sms_alerts', array($this, 'sgc_sms_alerts_page'), 'dashicons-email-alt');
			add_submenu_page('sgc_sms_alerts', 'SGC DLR Logs', 'SGC DLR Logs', 'manage_options', 'sgc_sms_alerts_dlr_page', array($this, 'sgc_sms_alerts_dlr_page'));
			add_submenu_page('sgc_sms_alerts', 'SGC Subscribers', 'SGC Subscribers', 'manage_options', 'sgc_sms_alerts_subscribers', array($this, 'sgc_sms_alerts_subscribers'));
			add_submenu_page('sgc_sms_alerts', 'SGC Subscriber Groups', 'SGC Subscriber Groups', 'manage_options', 'sgc_sms_alerts_subscriber_groups', array($this, 'sgc_sms_alerts_subscriber_groups'));
		}

		/**
		 * Enqueue Script for Wordpress
		 */
		public function sgc_sms_alerts_enqueue_script() {
			wp_enqueue_style('sgc_sms_alerts', SMS_ALERTS_GATEWAY_URL . '/assets/css/sgc_alerts_style.css');
			wp_enqueue_style('sgc_sms_alerts_bootstrap_min', SMS_ALERTS_GATEWAY_URL . '/assets/css/bootstrap.min.css');
			wp_enqueue_script('sgc_sms_alerts_twbsPagination', SMS_ALERTS_GATEWAY_URL . '/assets/js/jquery.twbsPagination.min.js');
		}

		/**
		 * Page Configuration
		 */
		public function sgc_sms_alerts_page_init() {
			//register
			register_setting(
				'sgc_sms_alerts_option_group', // Option group
				'sgc_sms_alerts_option_name', // Option name
				array($this, 'sgc_sms_alerts_sanitize') // Sanitize
			);

			//API heading section settings
			add_settings_section(
				'sgc_alerts_api_config', // ID
				'SMS API Credentials', // Title
				'', // Callback
				'sgc_sms_alerts' // Page
			);

			//API userId section settings
			add_settings_field(
				'sgc_userId', 'Username', array($this, 'sgc_userId_callback'), 'sgc_sms_alerts', 'sgc_alerts_api_config'
			);

			//API password section settings
			add_settings_field(
				'sgc_password', 'Password', array($this, 'sgc_password_callback'), 'sgc_sms_alerts', 'sgc_alerts_api_config'
			);

			//API sender id section settings
			add_settings_field(
				'sgc_senderid', 'Sender ID', array($this, 'sgc_senderid_callback'), 'sgc_sms_alerts', // Page
				'sgc_alerts_api_config'
			);

			//Admin notification section heading
			add_settings_section(
				'sgc_alerts_order_admin_notify_config', 'SMS Alert Settings for Admins', '', 'sgc_sms_alerts'
			);

			//Admin notify enable/disable section settings
			add_settings_field(
				'sgc_notify_admin', 'Enable / Disable Admin Notification', array($this, 'sgc_wp_admin_notify_callback'), 'sgc_sms_alerts', "sgc_alerts_order_admin_notify_config"
			);

			//Admin Mobile Number section settings
			add_settings_field(
				'sgc_wp_admin_mobile', 'Admin\'s Mobile Number', array($this, 'sgc_wp_admin_mobile_callback'), 'sgc_sms_alerts', 'sgc_alerts_order_admin_notify_config'
			);
			
			//product review notification
			add_settings_field(
				'product_review_notification', 'Enable / Disable Product Review Notification', array($this, 'sgc_alerts_order_review_callback'), 'sgc_sms_alerts', 'sgc_alerts_order_admin_notify_config'
			);
			
			//heading
			add_settings_section(
				'sgc_alerts_order_notify_config', 'Order Notification Configuration', '', 'sgc_sms_alerts'
			);

			//default variables
			add_settings_section(
				'sgc_alerts_order_notify_config_1', 'Default Variables Allowed', array($this, 'sgc_default_variables_callback'), 'sgc_sms_alerts'
			);

			//checkboxes to select specific notification
			add_settings_field(
				'sms_order_status_notification', 'Select status to send notification', array($this, 'sms_order_status_alert_callback'), 'sgc_sms_alerts', 'sgc_alerts_order_notify_config'
			);
			
			//order complete
			add_settings_field(
				'sgc_alerts_order_complete', 'Text Message Content for Order <span class="color-blue">Complete</span> Status', array($this, 'sgc_alerts_order_completed_callback'), 'sgc_sms_alerts', 'sgc_alerts_order_notify_config'
			);
			
			//order processing 
			add_settings_field(
				'sgc_alerts_order_processing', 'Text Message Content for Order <span class="color-blue">Processing</span> Status', array($this, 'sgc_alerts_order_processing_callback'), 'sgc_sms_alerts', 'sgc_alerts_order_notify_config'
			);
			
			//order pending payment
			add_settings_field(
				'sgc_alerts_order_pending_payment', 'Text Message Content for Order <span class="color-blue">Pending Payment</span> Status', array($this, 'sgc_alerts_order_pending_callback'), 'sgc_sms_alerts', 'sgc_alerts_order_notify_config'
			);
			
			//order on hold
			add_settings_field(
				'sgc_alerts_order_onhold', 'Text Message Content for Order <span class="color-blue">On-Hold</span> Status', array($this, 'sgc_alerts_order_onhold_callback'), 'sgc_sms_alerts', 'sgc_alerts_order_notify_config'
			);
			
			//order cancelled
			add_settings_field(
				'sgc_alerts_order_cancelled', 'Text Message Content for Order <span class="color-blue">Cancelled</span> Status', array($this, 'sgc_alerts_order_cancelled_callback'), 'sgc_sms_alerts', 'sgc_alerts_order_notify_config'
			);
			
			//order refunded
			add_settings_field(
				'sgc_alerts_order_refunded', 'Text Message Content for Order <span class="color-blue">Refunded</span> Status', array($this, 'sgc_alerts_order_refunded_callback'), 'sgc_sms_alerts', 'sgc_alerts_order_notify_config'
			);

			//order failed
			add_settings_field(
				'sgc_alerts_order_failed', 'Text Message Content for Order <span class="color-blue">Failed</span> Status', array($this, 'sgc_alerts_order_failed_callback'), 'sgc_sms_alerts', 'sgc_alerts_order_notify_config'
			);
			
			//heading
			add_settings_section(
				'sms_user_notification_config', 'Customer Notification Configuration', '', 'sgc_sms_alerts'
			);
			
			//new registrations
			add_settings_field(
				'sgc_alerts_regi_status', 'Text Message Content for Customer\'s <span class="color-blue">New Registration</span> Status', array($this, 'sgc_user_regi_status_callback'), 'sgc_sms_alerts', 'sms_user_notification_config'
			);
			
			//update profile
			add_settings_field(
				'sgc_alerts_update_profile', 'Text Message Content for Customer\'s <span class="color-blue">Profile Update</span> Status', array($this, 'sgc_user_update_profile_callback'), 'sgc_sms_alerts', 'sms_user_notification_config'
			);
			
			//password reset
			add_settings_field(
				'sgc_alerts_pass_reset', 'Text Message Content for Customer\'s <span class="color-blue">Forget Password</span> Status', array($this, 'sgc_alerts_password_reset_callback'), 'sgc_sms_alerts', 'sms_user_notification_config'
			);
			
			//coupon announcement
			add_settings_field(
				'sgc_alerts_coupon_announcement', 'Text Message Content for Customer\'s <span class="color-blue">Coupon Announcement</span>', array($this, 'sgc_alerts_coupon_announcement_callback'), 'sgc_sms_alerts', 'sms_user_notification_config'
			);
		}

		/**
		 * Sanitize inputs
		 * @param mixed $input
		 * @return mixed
		 */
		public function sgc_sms_alerts_sanitize($input) {
			$new_input = array();
			if (isset($input['sgc_userId'])) {
				$new_input['sgc_userId'] = sanitize_text_field($input['sgc_userId']);
			}
			if (isset($input['sgc_password'])) {
				$new_input['sgc_password'] = sanitize_text_field($input['sgc_password']);
			}
			if (isset($input['sgc_senderid'])) {
				$new_input['sgc_senderid'] = sanitize_text_field($input['sgc_senderid']);
			}
			if (isset($input['sgc_wp_admin_mobile'])) {
				$new_input['sgc_wp_admin_mobile'] = sanitize_text_field($input['sgc_wp_admin_mobile']);
			}
			//admin notify enable
			if (isset($input['sgc_notify_admin'])) {
				$new_input['sgc_notify_admin'] = $input['sgc_notify_admin'];
			}
			//complete
			if (isset($input['sgc_alerts_order_complete'])) {
				$new_input['sgc_alerts_order_complete'] = sanitize_textarea_field($input['sgc_alerts_order_complete']);
			}
			if (isset($input['sgc_alerts_order_completed_status'])) {
				$new_input['sgc_alerts_order_completed_status'] = $input['sgc_alerts_order_completed_status'];
			}
			//processing
			if (isset($input['sgc_alerts_order_processing'])) {
				$new_input['sgc_alerts_order_processing'] = sanitize_textarea_field($input['sgc_alerts_order_processing']);
			}
			if (isset($input['sgc_alerts_order_status_processing'])) {
				$new_input['sgc_alerts_order_status_processing'] = $input['sgc_alerts_order_status_processing'];
			}
			//pending payment
			if (isset($input['sgc_alerts_order_pending_payment'])) {
				$new_input['sgc_alerts_order_pending_payment'] = sanitize_textarea_field($input['sgc_alerts_order_pending_payment']);
			}
			if (isset($input['sgc_alerts_order_status_pending_payment'])) {
				$new_input['sgc_alerts_order_status_pending_payment'] = $input['sgc_alerts_order_status_pending_payment'];
			}
			//on hold
			if (isset($input['sgc_alerts_order_onhold'])) {
				$new_input['sgc_alerts_order_onhold'] = sanitize_textarea_field($input['sgc_alerts_order_onhold']);
			}
			if (isset($input['sgc_alerts_order_status_onhold'])) {
				$new_input['sgc_alerts_order_status_onhold'] = $input['sgc_alerts_order_status_onhold'];
			}
			//cancelled
			if (isset($input['sgc_alerts_order_cancelled'])) {
				$new_input['sgc_alerts_order_cancelled'] = sanitize_textarea_field($input['sgc_alerts_order_cancelled']);
			}
			if (isset($input['sgc_alerts_order_status_cancelled'])) {
				$new_input['sgc_alerts_order_status_cancelled'] = $input['sgc_alerts_order_status_cancelled'];
			}
			//refund
			if (isset($input['sgc_alerts_order_refunded'])) {
				$new_input['sgc_alerts_order_refunded'] = sanitize_textarea_field($input['sgc_alerts_order_refunded']);
			}
			if (isset($input['sgc_alerts_order_status_refunded'])) {
				$new_input['sgc_alerts_order_status_refunded'] = $input['sgc_alerts_order_status_refunded'];
			}
			//failed
			if (isset($input['sgc_alerts_order_failed'])) {
				$new_input['sgc_alerts_order_failed'] = sanitize_textarea_field($input['sgc_alerts_order_failed']);
			}
			if (isset($input['sgc_alerts_order_status_failed'])) {
				$new_input['sgc_alerts_order_status_failed'] = $input['sgc_alerts_order_status_failed'];
			}
			//registration
			if (isset($input['sgc_alerts_regi_status'])) {
				$new_input['sgc_alerts_regi_status'] = sanitize_textarea_field($input['sgc_alerts_regi_status']);
			}
			//profile
			if (isset($input['sgc_alerts_update_profile'])) {
				$new_input['sgc_alerts_update_profile'] = sanitize_textarea_field($input['sgc_alerts_update_profile']);
			}
			//forget pass
			if (isset($input['sgc_alerts_pass_reset'])) {
				$new_input['sgc_alerts_pass_reset'] = sanitize_textarea_field($input['sgc_alerts_pass_reset']);
			}
			//review
			if (isset($input['product_review_notification'])) {
				$new_input['product_review_notification'] = $input['product_review_notification'];
			}
			//coupon
			if (isset($input['sgc_alerts_coupon_announcement'])) {
				$new_input['sgc_alerts_coupon_announcement'] = sanitize_textarea_field($input['sgc_alerts_coupon_announcement']);
			}
			return $new_input;
		}

		/**
		 * Username callback
		 */
		public function sgc_userId_callback() {
			printf(
				'<input type="text" id="sgc_userId" name="sgc_sms_alerts_option_name[sgc_userId]" size="50" value="%s" />', isset($this->options['sgc_userId']) ? esc_attr($this->options['sgc_userId']) : ''
			);
			printf('<span class="sgc_userId"><a href="https://www.smsgateway.center" target="_blank">Click Here</a> to register if you do not have your login credentials.</span>');
		}

		/**
		 * Password callback
		 */
		public function sgc_password_callback() {
			printf(
				'<input type="text" id="sgc_password" name="sgc_sms_alerts_option_name[sgc_password]" size="50" value="%s" />', isset($this->options['sgc_password']) ? esc_attr($this->options['sgc_password']) : ''
			);
		}

		/**
		 * Get List of approved sender ids
		 */
		public function sgc_senderid_callback() {
			$options = get_option('sgc_sms_alerts_option_name');
			$settings['sgc_userId'] = $options['sgc_userId'];
			$settings['sgc_password'] = $options['sgc_password'];
			$smsgatewaycenterCls = new smsgatewaycenter($settings['sgc_userId'], $settings['sgc_password'], false);
			$sgcRawResponse = $smsgatewaycenterCls->sgcListSenderNames();
			if (json_decode($sgcRawResponse)->status == 'success') {
				$senderNamesList = json_decode($sgcRawResponse)->SenderNames;

				echo "<select id='sgc_senderid' name='sgc_sms_alerts_option_name[sgc_senderid]'>";
				foreach ($senderNamesList as $sender) {
					$selected = ($options['sgc_senderid'] == $sender->senderName) ? 'selected="selected"' : '';
					echo "<option value='$sender->senderName' $selected>$sender->senderName</option>";
				}
				echo "</select>";
			} else {
				echo "<span class=\"text-red\">Please input valid username and password to fetch your approved sender ids.</span>";
			}
		}
		
		/**
		 * Enable Disable Admin Notifications
		 */
		public function sgc_wp_admin_notify_callback() {
			printf(
				'<input id="%1$s" name="sgc_sms_alerts_option_name[sgc_notify_admin]" type="checkbox" %2$s /> ', 'sgc_notify_admin', checked(isset($this->options['sgc_notify_admin']), true, false)
			);
		}
		
		/**
		 * Admin Mobile numbers
		 */
		public function sgc_wp_admin_mobile_callback() {
			printf(
				'<input class="regular-text color" type="text" id="sgc_wp_admin_mobile" name="sgc_sms_alerts_option_name[sgc_wp_admin_mobile]" size="50" value="%s" />', isset($this->options['sgc_wp_admin_mobile']) ? esc_attr($this->options['sgc_wp_admin_mobile']) : ''
			);
			printf('<span class="sgc_userId"> You can specify multiple mobiles numbers seperated by comma.</span>');
		}

		/**
		 * Order Review
		 */
		public function sgc_alerts_order_review_callback() {
			printf(
				'<input id="%1$s" name="sgc_sms_alerts_option_name[product_review_notification]" type="checkbox" %2$s /> ', 'product_review_notification', checked(isset($this->options['product_review_notification']), true, false)
			);
		}
		
		/**
		 * Checkbox options for Orders Callback events Enable/Disable
		 */
		public function sms_order_status_alert_callback() {
			//completed
			printf('<input id="%1$s" name="sgc_sms_alerts_option_name[sgc_alerts_order_completed_status]" type="checkbox" %2$s /> ', 'sgc_alerts_order_completed_status', checked(isset($this->options['sgc_alerts_order_completed_status']), true, false));
			printf('<label>Completed</label><br/>');
			//processing
			printf('<input id="%1$s" name="sgc_sms_alerts_option_name[sgc_alerts_order_status_processing]" type="checkbox" %2$s /> ', 'sgc_alerts_order_status_processing', checked(isset($this->options['sgc_alerts_order_status_processing']), true, false));
			printf('<label>Processing</label><br/>');
			//pending payment
			printf('<input id="%1$s" name="sgc_sms_alerts_option_name[sgc_alerts_order_status_pending_payment]" type="checkbox" %2$s /> ', 'sgc_alerts_order_status_pending_payment', checked(isset($this->options['sgc_alerts_order_status_pending_payment']), true, false));
			printf('<label>Pending payment</label> <br/>');
			//on hold
			printf('<input id="%1$s" name="sgc_sms_alerts_option_name[sgc_alerts_order_status_onhold]" type="checkbox" %2$s /> ', 'sgc_alerts_order_status_onhold', checked(isset($this->options['sgc_alerts_order_status_onhold']), true, false));
			printf('<label>On hold</label><br/>');
			//cancelled
			printf('<input id="%1$s" name="sgc_sms_alerts_option_name[sgc_alerts_order_status_cancelled]" type="checkbox" %2$s /> ', 'sgc_alerts_order_status_cancelled', checked(isset($this->options['sgc_alerts_order_status_cancelled']), true, false));
			printf('<label>Cancelled</label><br/>');
			//refunded
			printf('<input id="%1$s" name="sgc_sms_alerts_option_name[sgc_alerts_order_status_refunded]" type="checkbox" %2$s /> ', 'sgc_alerts_order_status_refunded', checked(isset($this->options['sgc_alerts_order_status_refunded']), true, false));
			printf('<label>Refunded</label><br/>');
			//failed
			printf('<input id="%1$s" name="sgc_sms_alerts_option_name[sgc_alerts_order_status_failed]" type="checkbox" %2$s /> ', 'sgc_alerts_order_status_failed', checked(isset($this->options['sgc_alerts_order_status_failed']), true, false));
			printf('<label>Failed</label><br/>');
		}

		/**
		 * Variable Texts
		 * @return type
		 */
		public function sgc_message_content_text_tip() {
			return '<p class="mtip">'
				. '<span>Default Replace Variable params for all Order Notification Configuration:</span></p>'
				. '<table class="table table-bordered table table-hover" cellspacing="0"><tr><td>{WOOCOM_SHOP_NAME}<br />'
				. '{WOOCOM_ORDER_NUMBER}<br />'
				. '{WOOCOM_ORDER_STATUS}<br />'
				. '{WOOCOM_ORDER_AMOUNT}<br /></td><td>'
				. '{WOOCOM_ORDER_DATE}<br />'
				. '{WOOCOM_ORDER_ITEMS}<br />'
				. '{WOOCOM_BILLING_FNAME}<br />'
				. '{WOOCOM_BILLING_LNAME}<br /></td><td>'
				. '{WOOCOM_BILLING_EMAIL}<br />'
				. '{WOOCOM_CURRENT_DATE}<br />'
				. '{WOOCOM_CURRENT_TIME}</td></tr></table>';
		}
		
		/**
		 * Default available variable for message templates
		 */
		public function sgc_default_variables_callback() {
			printf($this->sgc_message_content_text_tip());
		}
		
		/**
		 * Order Completed
		 */
		public function sgc_alerts_order_completed_callback() {
			printf(
				'<textarea id="sgc_alerts_order_complete" name="sgc_sms_alerts_option_name[sgc_alerts_order_complete]" cols="52" rows="3">%s</textarea>', isset($this->options['sgc_alerts_order_complete']) ? esc_attr($this->options['sgc_alerts_order_complete']) : ''
			);
			printf('<td><b>Sample:</b> Thank you for the purchase from {WOOCOM_SHOP_NAME}.<br />Order #{WOOCOM_ORDER_NUMBER}<br />Order Date:{WOOCOM_ORDER_DATE}<br />We thank you for the purchase and we will deliver your merchandise at the earliest.</td>');
		}
		
		/**
		 * Order Processing
		 */
		public function sgc_alerts_order_processing_callback() {
			printf(
				'<textarea id="sgc_alerts_order_processing" name="sgc_sms_alerts_option_name[sgc_alerts_order_processing]" cols="52" rows="3">%s</textarea>', isset($this->options['sgc_alerts_order_processing']) ? esc_attr($this->options['sgc_alerts_order_processing']) : ''
			);
			printf('<td><b>Sample:</b> Thank you for the purchase from {WOOCOM_SHOP_NAME}.<br />Order #{WOOCOM_ORDER_NUMBER}<br />Order Date:{WOOCOM_ORDER_DATE}<br />We thank you for the purchase and we are processing your order for delivery.</td>');
		}

		public function sgc_alerts_order_pending_callback() {
			printf(
				'<textarea id="sgc_alerts_order_pending_payment" name="sgc_sms_alerts_option_name[sgc_alerts_order_pending_payment]" cols="52" rows="3">%s</textarea>', isset($this->options['sgc_alerts_order_pending_payment']) ? esc_attr($this->options['sgc_alerts_order_pending_payment']) : ''
			);
			printf('<td><b>Sample:</b> Dear {WOOCOM_BILLING_FNAME}, Your payment is in pending status.<br />Order #{WOOCOM_ORDER_NUMBER}<br />Order Date:{WOOCOM_ORDER_DATE}<br />We hope you would process the payment to get your order delivered.</td>');
		}
		
		/**
		 * Order On Hold
		 */
		public function sgc_alerts_order_onhold_callback() {
			printf(
				'<textarea id="sgc_alerts_order_onhold" name="sgc_sms_alerts_option_name[sgc_alerts_order_onhold]" cols="52" rows="3">%s</textarea>', isset($this->options['sgc_alerts_order_onhold']) ? esc_attr($this->options['sgc_alerts_order_onhold']) : ''
			);
			printf('<td><b>Sample:</b> Dear {WOOCOM_BILLING_FNAME}, Your order has been put on {WOOCOM_ORDER_STATUS} status.<br />Order #{WOOCOM_ORDER_NUMBER}<br />Order Date:{WOOCOM_ORDER_DATE}<br />We will be in touch with you soon for more details.</td>');
		}
		
		/**
		 * Order Cancelled
		 */
		public function sgc_alerts_order_cancelled_callback() {
			printf(
				'<textarea id="sgc_alerts_order_cancelled" name="sgc_sms_alerts_option_name[sgc_alerts_order_cancelled]" cols="52" rows="3">%s</textarea>', isset($this->options['sgc_alerts_order_cancelled']) ? esc_attr($this->options['sgc_alerts_order_cancelled']) : ''
			);
			printf('<td><b>Sample:</b> Dear {WOOCOM_BILLING_FNAME}, we regret to inform you that your order has been {WOOCOM_ORDER_STATUS}.<br />Order #{WOOCOM_ORDER_NUMBER}<br />Order Date:{WOOCOM_ORDER_DATE}<br />Please get back to us for more details.</td>');
		}
		
		/**
		 * Order Refunded
		 */
		public function sgc_alerts_order_refunded_callback() {
			printf(
				'<textarea id="sgc_alerts_order_refunded" name="sgc_sms_alerts_option_name[sgc_alerts_order_refunded]" cols="52" rows="3">%s</textarea>', isset($this->options['sgc_alerts_order_refunded']) ? esc_attr($this->options['sgc_alerts_order_refunded']) : ''
			);
			printf('<td><b>Sample:</b> Dear {WOOCOM_BILLING_FNAME}, we have processed your refund of {WOOCOM_ORDER_AMOUNT}.<br />Order #{WOOCOM_ORDER_NUMBER}<br />Order Date:{WOOCOM_ORDER_DATE}<br />If you have any clarification, please get back to us.</td>');
		}
		
		/**
		 * Order Failed
		 */
		public function sgc_alerts_order_failed_callback() {
			printf(
				'<textarea id="sgc_alerts_order_failed" name="sgc_sms_alerts_option_name[sgc_alerts_order_failed]" cols="52" rows="3">%s</textarea>', isset($this->options['sgc_alerts_order_failed']) ? esc_attr($this->options['sgc_alerts_order_failed']) : ''
			);
			printf('<td><b>Sample:</b> Dear {WOOCOM_BILLING_FNAME}, Your order has been Failed for some technical reasons.<br />Order #{WOOCOM_ORDER_NUMBER}<br />Order Date:{WOOCOM_ORDER_DATE}<br />We request you to re-order again to get the order delivered.</td>');
		}
		
		/**
		 * New Registrations
		 */
		public function sgc_user_regi_status_callback() {
			printf(
				'<textarea id="sgc_alerts_regi_status" name="sgc_sms_alerts_option_name[sgc_alerts_regi_status]" cols="52" rows="3">%s</textarea><p class="mtip"><span>Available Variable:</span> {WOOCOM_FIRST_NAME}, {WOOCOM_LAST_NAME}</p>', isset($this->options['sgc_alerts_regi_status']) ? esc_attr($this->options['sgc_alerts_regi_status']) : ''
			);
			printf('<td><b>Sample:</b> Dear {WOOCOM_BILLING_FNAME}, Welcome to {WOOCOM_SHOP_NAME} Club. Shop the best of the products and get the best experience. If you face any issue, we are just a call away. Call us on +91999XXXXXXX</td>');
		}
		
		/**
		 * Update Profile
		 */
		public function sgc_user_update_profile_callback() {
			printf(
				'<textarea id="sgc_alerts_update_profile" name="sgc_sms_alerts_option_name[sgc_alerts_update_profile]" cols="52" rows="3">%s</textarea><p class="mtip"><span>Available Variable:</span> {WOOCOM_FIRST_NAME}, {WOOCOM_LAST_NAME}</p>', isset($this->options['sgc_alerts_update_profile']) ? esc_attr($this->options['sgc_alerts_update_profile']) : ''
			);
			printf('<td><b>Sample:</b> Dear {WOOCOM_BILLING_FNAME}, Your profile has been updated successfully at {WOOCOM_SHOP_NAME} site.</td>');
		}
		
		/**
		 * Password Reset
		 */
		public function sgc_alerts_password_reset_callback() {
			printf(
				'<textarea id="sgc_alerts_pass_reset" name="sgc_sms_alerts_option_name[sgc_alerts_pass_reset]" cols="52" rows="3">%s</textarea><p class="mtip"><span>Available Variable:</span> {WOOCOM_FIRST_NAME}, {WOOCOM_LAST_NAME}</p>', isset($this->options['sgc_alerts_pass_reset']) ? esc_attr($this->options['sgc_alerts_pass_reset']) : ''
			);
			printf('<td><b>Sample:</b> Dear {WOOCOM_BILLING_FNAME}, Your password has been reset successfully at {WOOCOM_SHOP_NAME} site.</td>');
		}
		
		/**
		 * Coupon Announcement
		 */
		public function sgc_alerts_coupon_announcement_callback() {
			printf(
				'<textarea id="sgc_alerts_coupon_announcement" name="sgc_sms_alerts_option_name[sgc_alerts_coupon_announcement]" cols="52" rows="3">%s</textarea><p class="mtip">No Available Variables for this content.</p>', isset($this->options['sgc_alerts_coupon_announcement']) ? esc_attr($this->options['sgc_alerts_coupon_announcement']) : ''
			);
			printf('<td><b>Sample:</b> {WOOCOM_SHOP_NAME}: Dear {WOOCOM_BILLING_FNAME},<br />Biggest Sale. Get 10&#37; Discount on all our products. Use Coupon Code ABC10. Text STOP to unsubscribe to 91999XXXXXXX.</td>');
		}
		
		/**
		 * Admin settings page
		 */
		public function sgc_sms_alerts_page() {
			$sgcSmsid = 'sgc_sms_alerts';
			$this->options = get_option('sgc_sms_alerts_option_name');
			?>
			<div class="wrap">
				<h2>SGC SMS Settings</h2>
				<form method="post" action="options.php">
					<?php
					settings_fields('sgc_sms_alerts_option_group');
					do_settings_sections('sgc_sms_alerts');
					submit_button();
					?>
				</form>
			</div>
			<?php
		}

		/**
		 * Get DLR
		 */
		public function sgc_sms_alerts_dlr_page() {
			$options = get_option('sgc_sms_alerts_option_name');
			$settings['sgc_userId'] = $options['sgc_userId'];
			$settings['sgc_password'] = $options['sgc_password'];
			$smsgatewaycenter = new smsgatewaycenter($settings['sgc_userId'], $settings['sgc_password'], false);
			$response = $smsgatewaycenter->sgcListReports();
			$jsonDecode = json_decode($response);
			if($jsonDecode->noofRecords > 0){
				$messages = $jsonDecode->DLRReport;
			} else {
				$messages = [0 => 'none'];
			}
			?>
			<div class="wrap">
				<h1>Delivery Report Logs</h1>
				<p class="color-333">Delivery Report can be fetched for the current day only. You can login to SMS Gateway Center Panel and download historical data in zip format.</p>
				<form action="<?php echo SMS_ALERTS_GATEWAY_URL . '/admin/download.php'; ?>" method="post" id="form_export" target="_blank">
				<?php wp_nonce_field('sgc_csv_exporter'); ?>
					<p class="exportcsv pull-right">
						<input type="submit" class="button-primary" value="<?php echo 'Export To CSV'; ?>"/>
					</p>
				</form>
				<table id="messages" class="table table-bordered table table-hover" cellspacing="0" width="100%">
					<colgroup><col><col><col></colgroup>
					<thead>
						<tr>
							<th>TransactionId</th>
							<th>Mobile</th>
							<th>SenderId</th>
							<th class="txtMsg">TextMsg</th>
							<th>Status</th>
							<th>Cause</th>
							<th>DTime</th>
						</tr>
					</thead>
					<tbody id="dlrData">
					</tbody>
				</table>
				<div id="pager">
					<ul id="pagination" class="pagination-sm"></ul>
				</div>
				<script type="text/javascript">
					<?php 
						if($messages[0] == 'none'){
					?>
						jQuery('#dlrData').html('');
						jQuery('#dlrData').append("<tr><td colspan=\"7\" align=\"center\">No records found!</td></tr>");
						<?php 	} else { ?>
						var data = <?php  echo json_encode($messages); ?>;
						console.log('no');
						var PerPagerec = 10;
						var RecordsTotal = data.length;
						var Pages = Math.ceil(RecordsTotal / PerPagerec);
						totalRecords = 0,
							recPerPage = 10,
							page = 1,
							jQuery('#pagination').twbsPagination({
							totalPages: Pages,
							visiblePages: 20,
							next: 'Next',
							prev: 'Prev',
							onPageClick: function (event, page, recored) {
								records = data;
								totalRecords = records.length;
								totalPages = Math.ceil(totalRecords / recPerPage);
								displayRecordsIndex = Math.max(page - 1, 0) * recPerPage;
								endRec = (displayRecordsIndex) + recPerPage;
								displayRecords = records.slice(displayRecordsIndex, endRec);
								var tr;
								jQuery('#dlrData').html('');
								for (var i = 0; i < displayRecords.length; i++) {
									tr = jQuery('<tr/>');
									tr.append("<td>" + displayRecords[i].TransactionId + "</td>");
									tr.append("<td>" + displayRecords[i].Phone + "</td>");
									tr.append("<td>" + displayRecords[i].SenderId + "</td>");
									tr.append("<td class=\"txtMsg\">" + displayRecords[i].Message + "</td>");
									tr.append("<td>" + displayRecords[i].Status + "</td>");
									tr.append("<td>" + displayRecords[i].Cause + "</td>");
									tr.append("<td>" + displayRecords[i].DeliveryTime + "</td>");
									jQuery('#dlrData').append(tr);
								}
							}
						});
						<?php } ?>
				</script>
			</body>
			</div>
			<?php
		}

		/**
		 * CRUD functionality for Subscribers
		 * @return type
		 */
		public function sgc_sms_alerts_subscribers() {
			if (isset($_GET['action'])) {
				// Add subscriber page
				if ($_GET['action'] == 'add') {
					include_once dirname(__FILE__) . "/includes/templates/subscribe/add-subscriber.php";

					if (isset($_POST['wp_add_subscribe'])) {
						$result = $this->subscribe->add_subscriber($_POST['sms_notify_subscribe_name'], $_POST['sms_notify_subscribe_mobile'], $_POST['sms_notify_group_name']);
						echo $this->sgc_show_notice($result['result'], $result['message']);
					}
					return;
				}

				// Edit subscriber page
				if ($_GET['action'] == 'edit') {
					if (isset($_POST['wp_update_subscribe'])) {
						$result = $this->subscribe->update_subscriber($_GET['ID'], $_POST['sms_notify_subscribe_name'], $_POST['sms_notify_subscribe_mobile'], $_POST['sms_notify_group_name'] /* $_POST['sgc_sms_alerts_subscribe_status'] */);
						echo $this->sgc_show_notice($result['result'], $result['message']);
					}
					$get_subscribe = $this->subscribe->get_subscriber($_GET['ID']);
					include_once dirname(__FILE__) . "/includes/templates/subscribe/edit-subscriber.php";
					return;
				}
				
				// Import subscriber CSV
				if ($_GET['action'] == 'import') {
					//suggest users to use WP CSV TO DB Pluign
					include_once dirname(__FILE__) . "/includes/templates/subscribe/import-subscriber.php";
					return;
				}
			}
			include_once dirname(__FILE__) . '/includes/class-sgc_alerts-subscribers-table.php';
			//Create an instance of our package class...
			$list_table = new SGC_ALERTS_Subscribers_List_Table();
			//Fetch, prepare, sort, and filter our data...
			$list_table->prepare_items();
			include_once dirname(__FILE__) . "/includes/templates/subscribe/subscribers.php";
		}

		/**
		 * Group Management
		 * @return type
		 */
		public function sgc_sms_alerts_subscriber_groups() {
			if (isset($_GET['action'])) {
				// Add group page
				if ($_GET['action'] == 'add') {
					include_once dirname(__FILE__) . "/includes/templates/subscribe/add-group.php";
					if (isset($_POST['wp_add_group'])) {
						$result = $this->subscribe->add_group($_POST['sms_notify_group_name']);
						echo $this->sgc_show_notice($result['result'], $result['message']);
					}
					return;
				}

				// Manage group page
				if ($_GET['action'] == 'edit') {
					if (isset($_POST['wp_update_group'])) {
						$result = $this->subscribe->update_group($_GET['ID'], $_POST['sms_notify_group_name']);
						echo $this->sgc_show_notice($result['result'], $result['message']);
					}
					$get_group = $this->subscribe->get_group($_GET['ID']);
					include_once dirname(__FILE__) . "/includes/templates/subscribe/edit-group.php";
					return;
				}
			}

			include_once dirname(__FILE__) . '/includes/class-sgc-alerts-groups-table.php';
			//Create an instance of our package class...
			$list_table = new SGC_ALERTS_Subscribers_Groups_List_Table();
			//Fetch, prepare, sort, and filter our data...
			$list_table->prepare_items();
			include_once dirname(__FILE__) . "/includes/templates/subscribe/groups.php";
		}
		
		/**
		 * Show Notice of responses
		 * @param string $result
		 * @param string $message
		 * @return string
		 */
		public function sgc_show_notice($result, $message) {
			if (empty($result)) {
				return;
			}
			if ($result == 'error') {
				return '<div class="updated settings-error notice error is-dismissible"><p><strong>' . $message . '</strong></p><button class="notice-dismiss" type="button"><span class="screen-reader-text">' . __('Close', 'sgc_alerts') . '</span></button></div>';
			}
			if ($result == 'update') {
				return '<div class="updated settings-update notice is-dismissible"><p><strong>' . $message . '</strong></p><button class="notice-dismiss" type="button"><span class="screen-reader-text">' . __('Close', 'sgc_alerts') . '</span></button></div>';
			}
		}

	}

	if (is_admin()) {
		$my_settings_page = new SGCSMSAlerts();
	}

	require_once( WOOCOM_SGC_SMS_ALERTS_DIR . '/core/user.class.php' );
	require_once( WOOCOM_SGC_SMS_ALERTS_DIR . '/core/order.class.php' );
	require_once( WOOCOM_SGC_SMS_ALERTS_DIR . '/core/smsgatewaycenter.class.php' );

	/**
	 * Create tables
	 * @global type $wpdb
	 */
	function sgc_sms_alerts_install() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$table_prefix = $wpdb->prefix;

		$sql_subscribe = "CREATE TABLE IF NOT EXISTS {$table_prefix}sgc_sms_alerts_subscribers(
			ID int(10) NOT NULL auto_increment,
			date DATETIME,
			name VARCHAR(20),
			mobile VARCHAR(20) NOT NULL,
			/*status tinyint(1),*/
			group_ID int(5),
			PRIMARY KEY(ID)) CHARSET=utf8";

		$sql_group = "CREATE TABLE IF NOT EXISTS {$table_prefix}sgc_sms_alerts_subscribers_group(
			ID int(10) NOT NULL auto_increment,
			name VARCHAR(250),
			PRIMARY KEY(ID)) CHARSET=utf8";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta($sql_subscribe);
		dbDelta($sql_group);
	}
	register_activation_hook(__FILE__, 'sgc_sms_alerts_install');

	/**
	 * remove tables from DB 
	 * @global type $wpdb
	 */
	function sgc_sms_alerts_uninstall() {
		global $wpdb;
		$wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'sgc_sms_alerts_subscribers');
		$wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'sgc_sms_alerts_subscribers_group');
		delete_option("sgc_sms_alerts_db_version");
	}
	register_uninstall_hook(__FILE__, 'sgc_sms_alerts_uninstall');