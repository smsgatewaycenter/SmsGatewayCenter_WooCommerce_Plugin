<?php
	if (!defined('ABSPATH'))
		exit;

	include_once (WOOCOM_SGC_SMS_ALERTS_DIR . '/core/smsgatewaycenter.class.php');
	global $wpdb, $woocommerce;

	/**
	 * Append Field to register form
	 */
	add_action('register_form', 'sgc_alerts_regi_form');

	function sgc_alerts_regi_form() {
		$billing_phone = !empty($_POST['billing_phone']) ? $_POST['billing_phone'] : '';
		?>
		<p>
			<label for="billing_phone"><?php _e('Mobile No', 'sgc_alerts_textdomain') ?><br />
				<input type="text" name="billing_phone" id="billing_phone" class="input" value="<?php echo esc_attr(stripslashes($billing_phone)); ?>" size="10" /></label>
		</p>
		<?php
	}

	/**
	 * Validate register form customer billing phone
	 */
	add_filter('registration_errors', 'sgc_alerts_regi_errors', 10, 3);

	function sgc_alerts_regi_errors($errors, $sanitized_user_login, $user_email) {
		if (empty($_POST['billing_phone'])) {
			$errors->add('billing_phone_error', __('<strong>ERROR</strong>: Please enter valid mobile number', 'sms'));
		}
		return $errors;
	}

	/**
	 * Update user billing phone
	 */
	add_action('user_register', 'sgc_alerts_customer_register');
	function sgc_alerts_customer_register($userd) {
		$options = get_option('sgc_sms_alerts_option_name');
		$woocom_shop_phone = $_POST['billing_phone'];
		$woocom_first_name = $_POST['billing_first_name'];
		$woocom_last_name = $_POST['billing_last_name'];
		if (!empty($_POST['billing_phone'])) {
			update_user_meta($user_id, 'billing_phone', sanitize_text_field($_POST['billing_phone']));
			$smsgatewaycenter = new smsgatewaycenter($options['sgc_userId'], $options['sgc_password'], false);
			$textMsg = sgc_sms_alerts_regi_variable($options['sgc_alerts_regi_status'], $woocom_first_name, $woocom_last_name);
			$smsgatewaycenter->sendSmsGatewayCenterSmsPost($textMsg, $woocom_shop_phone);
		}
	}
	
	/**
	 * register fields Validating.
	 */
	function sgc_wooc_validate_extra_register_fields($username, $email, $validation_errors) {
		if (isset($_POST['billing_first_name']) && empty($_POST['billing_first_name'])) {
			$validation_errors->add('billing_first_name_error', __('<strong>Error</strong>: First name is required!', 'woocommerce'));
		}
		if (isset($_POST['billing_last_name']) && empty($_POST['billing_last_name'])) {
			$validation_errors->add('billing_last_name_error', __('<strong>Error</strong>: Last name is required!.', 'woocommerce'));
		}
		return $validation_errors;
	}
	add_action('woocommerce_register_post', 'sgc_wooc_validate_extra_register_fields', 10, 3);

	/**
	 * Below code save extra fields.
	 */
	function sgc_wooc_save_extra_register_fields($customer_id) {
		if (isset($_POST['billing_phone'])) {
			// Phone input filed which is used in WooCommerce
			update_user_meta($customer_id, 'billing_phone', sanitize_text_field($_POST['billing_phone']));
		}
		if (isset($_POST['billing_first_name'])) {
			//First name field which is by default
			update_user_meta($customer_id, 'first_name', sanitize_text_field($_POST['billing_first_name']));
			// First name field which is used in WooCommerce
			update_user_meta($customer_id, 'billing_first_name', sanitize_text_field($_POST['billing_first_name']));
		}
		if (isset($_POST['billing_last_name'])) {
			// Last name field which is by default
			update_user_meta($customer_id, 'last_name', sanitize_text_field($_POST['billing_last_name']));
			// Last name field which is used in WooCommerce
			update_user_meta($customer_id, 'billing_last_name', sanitize_text_field($_POST['billing_last_name']));
		}
	}
	add_action('woocommerce_created_customer', 'sgc_wooc_save_extra_register_fields');

	/**
	 * If admin registering new user then trigger the field
	 */
	add_action('user_new_form', 'sgc_alerts_admin_regi_form');

	function sgc_alerts_admin_regi_form($operation) {
		if ('add-new-user' !== $operation) {
			return;
		}
		$billing_phone = !empty($_POST['billing_phone']) ? $_POST['billing_phone'] : '';
		?>
		<h3><?php esc_html_e('Personal Information', 'sms'); ?></h3>
		<table class="form-table">
			<tr>
				<th><label for="billing_phone"><?php esc_html_e('Mobile No', 'sms'); ?></label> <span class="description"><?php esc_html_e('(required)', 'sms'); ?></span></th>
				<td>
					<input type="text" name="billing_phone" id="billing_phone" class="input" value="<?php echo esc_attr(stripslashes($billing_phone)); ?>" size="50" /></label>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Show billing phone field on profile and edit profile page
	 */
	add_action('show_user_profile', 'sgc_alerts_show_extra_profile_fields');
	add_action('edit_user_profile', 'sgc_alerts_show_extra_profile_fields');

	function sgc_alerts_show_extra_profile_fields($user) {
		?>
		<h3><?php esc_html_e('Personal Information', 'sms'); ?></h3>
		<table class="form-table">
			<tr>
				<th><label for="billing_phone"><?php esc_html_e('Mobile No', 'sms'); ?></label></th>
				<td><?php echo esc_html(get_the_author_meta('billing_phone', $user->ID)); ?></td>
			</tr>
		</table>
		<?php
	}

	add_action('woocommerce_edit_account_form', 'sgc_alers_show_mobile_field_my_account');
	function sgc_alers_show_mobile_field_my_account() {
		 $user = wp_get_current_user();
		 if (!empty($_POST['billing_phone'])) {
			 $billPhone = esc_attr_e($_POST['billing_phone']);
		 } else {
			 $billPhone = esc_attr( $user->billing_phone );
		 }
		?>
		<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
			<label for="billing_phone">Billing Phone&nbsp;<span class="required">*</span></label>
			<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="billing_phone" id="billing_phone" autocomplete="off" value="<?php echo $billPhone; ?>">
		</p>
		<?php
	}
	
	add_action('woocommerce_save_account_details', 'save_billing_phone_account_details', 12, 1);
	function save_billing_phone_account_details($user_id) {
		// For Favorite color
		if (isset($_POST['billing_phone'])){
			update_user_meta($user_id, 'billing_phone', sanitize_text_field($_POST['billing_phone']));
		}
	}

	/**
	 * Profile Update SMS Alert
	 */
	add_action('profile_update', 'sgc_alerts_update_profile', 10, 1);
	function sgc_alerts_update_profile($userid) {
		$options = get_option('sgc_sms_alerts_option_name');
		$isAdminNotifyEnabled = $options['sgc_notify_admin'];
		$wpMeta = get_user_meta($userid);
		$woocom_shop_phone = $wpMeta['billing_phone'][0];
		$woocom_first_name = $wpMeta['first_name'][0];
		$woocom_last_name = $wpMeta['last_name'][0];
		if ($isAdminNotifyEnabled == 'on') {
			$customerPhones = array();
			$customerPhones[] = $options['sgc_wp_admin_mobile'];
			array_push($customerPhones, $woocom_shop_phone);
		} else {
			$customerPhones = $woocom_shop_phone;
		}
		$textMsg = sgc_sms_alerts_regi_variable($options['sgc_alerts_update_profile'], $woocom_first_name, $woocom_last_name);
		$smsgatewaycenter = new smsgatewaycenter($options['sgc_userId'], $options['sgc_password'], false);
		$smsgatewaycenter->sendSmsGatewayCenterSmsPost($textMsg, $customerPhones);
	}

	//hook with woocommerce to save address
	if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
		add_action('woocommerce_customer_save_address', 'sgc_alerts_update_profile');
	}


	/**
	 * Reset Password SMS Alert
	 */
	add_action('password_reset', 'sgc_alerts_password_reset', 10, 1);
	function sgc_alerts_password_reset($user) {
		$options = get_option('sgc_sms_alerts_option_name');

		$isAdminNotifyEnabled = $options['sgc_notify_admin'];
		$userid = $user->ID;
		$wpMeta = get_user_meta($userid);
		$woocom_shop_phone = $wpMeta['billing_phone'][0];
		$woocom_first_name = $wpMeta['first_name'][0];
		$woocom_last_name = $wpMeta['last_name'][0];
		if ($isAdminNotifyEnabled == 'on') {
			$customerPhones = array();
			$customerPhones[] = $options['sgc_wp_admin_mobile'];
			array_push($customerPhones, $woocom_shop_phone);
		} else {
			$customerPhones = $woocom_shop_phone;
		}
		$textMsg = sgc_sms_alerts_regi_variable($options['sgc_alerts_pass_reset'], $woocom_first_name, $woocom_last_name);
		$smsgatewaycenter = new smsgatewaycenter($options['sgc_userId'], $options['sgc_password'], false);
		$result = $smsgatewaycenter->sendSmsGatewayCenterSmsPost($textMsg, $customerPhones);
	}

	function sgc_wooc_extra_register_fields() {
		?>
		<p class="form-row form-row-wide">
			<label for="reg_billing_phone"><?php _e('Phone', 'woocommerce'); ?></label>
			<input type="text" class="input-text" name="billing_phone" id="reg_billing_phone" value="<?php esc_attr_e($_POST['billing_phone']); ?>" />
		</p>
		<p class="form-row form-row-first">
			<label for="reg_billing_first_name"><?php _e('First name', 'woocommerce'); ?><span class="required">*</span></label>
			<input type="text" class="input-text" name="billing_first_name" id="reg_billing_first_name" value="<?php if (!empty($_POST['billing_first_name'])) esc_attr_e($_POST['billing_first_name']); ?>" />
		</p>
		<p class="form-row form-row-last">
			<label for="reg_billing_last_name"><?php _e('Last name', 'woocommerce'); ?><span class="required">*</span></label>
			<input type="text" class="input-text" name="billing_last_name" id="reg_billing_last_name" value="<?php if (!empty($_POST['billing_last_name'])) esc_attr_e($_POST['billing_last_name']); ?>" />
		</p>
		<div class="clear"></div>
		<?php
	}
	add_action('woocommerce_register_form_start', 'sgc_wooc_extra_register_fields');