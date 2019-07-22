<?php

	if (!defined('ABSPATH'))
		exit;

	require_once (WOOCOM_SGC_SMS_ALERTS_DIR . '/core/smsgatewaycenter.class.php');

	global $wpdb, $woocommerce, $product;
	//check wooecommerce plugin
	if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
		//Register action
		add_action("woocommerce_order_status_changed", "sgc_alerts_order_status");

		/**
		 *
		 * @global type $woocommerce
		 * @param type $order_id
		 */
		function sgc_alerts_order_status($order_id) {
			global $woocommerce;
			$order = new WC_Order($order_id);
			$options = get_option('sgc_sms_alerts_option_name');
			$isAdminNotifyEnabled = $options['sgc_notify_admin'];
			$smsgatewaycenter = new smsgatewaycenter($options['sgc_userId'], $options['sgc_password'], FALSE);

			//error_log(print_r($order, true));
			//default phones
			$woocom_shop_phone = $order->get_billing_phone();
			if ($isAdminNotifyEnabled == 'on') {
				$customerPhones = array();
				$customerPhones[] = $options['sgc_wp_admin_mobile'];
				array_push($customerPhones, $woocom_shop_phone);
			} else {
				$customerPhones = $woocom_shop_phone;
			}
			//completed
			if ($order->get_status() === 'completed' && $options['sgc_alerts_order_completed_status'] == 'on') {
				$textMsg = sgc_sms_alerts_shortcode_variable($options['sgc_alerts_order_complete'], $order);
				$result = $smsgatewaycenter->sendSmsGatewayCenterSmsPost($textMsg, $customerPhones);
			}
			//processing
			if ($order->get_status() === 'processing' && $options['sgc_alerts_order_status_processing'] == 'on') {
				$textMsg = sgc_sms_alerts_shortcode_variable($options['sgc_alerts_order_processing'], $order);
				$result = $smsgatewaycenter->sendSmsGatewayCenterSmsPost($textMsg, $customerPhones);
			}
			//pending
			if ($order->get_status() === 'pending' && $options['sgc_alerts_order_status_pending_payment'] == 'on') {
				$textMsg = sgc_sms_alerts_shortcode_variable($options['sgc_alerts_order_pending_payment'], $order);
				$result = $smsgatewaycenter->sendSmsGatewayCenterSmsPost($textMsg, $customerPhones);
			}
			//on-hold
			if ($order->get_status() === 'on-hold' && $options['sgc_alerts_order_status_onhold'] == 'on') {
				$textMsg = sgc_sms_alerts_shortcode_variable($options['sgc_alerts_order_onhold'], $order);
				$result = $smsgatewaycenter->sendSmsGatewayCenterSmsPost($textMsg, $customerPhones);
			}
			//cancelled
			if ($order->get_status() === 'cancelled' && $options['sgc_alerts_order_status_cancelled'] == 'on') {
				$textMsg = sgc_sms_alerts_shortcode_variable($options['sgc_alerts_order_cancelled'], $order);
				$result = $smsgatewaycenter->sendSmsGatewayCenterSmsPost($textMsg, $customerPhones);
			}
			//refunded
			if ($order->get_status() === 'refunded' && $options['sgc_alerts_order_status_refunded'] == 'on') {
				$textMsg = sgc_sms_alerts_shortcode_variable($options['sgc_alerts_order_refunded'], $order);
				$result = $smsgatewaycenter->sendSmsGatewayCenterSmsPost($textMsg, $customerPhones);
			}
			//failed
			if ($order->get_status() === 'failed' && $options['sgc_alerts_order_status_failed'] == 'on') {
				$textMsg = sgc_sms_alerts_shortcode_variable($options['sgc_alerts_order_failed'], $order);
				$result = $smsgatewaycenter->sendSmsGatewayCenterSmsPost($textMsg, $customerPhones);
			}
		}

		/**
		 * Post Comment for moderation
		 */
		add_action('comment_post', 'sgc_alerts_post_comment');
		/**
		 * Comment Post SMS Alert
		 * @global type $product
		 * @global type $current_user
		 * @param type $comment_id
		 */
		function sgc_alerts_post_comment($commentId) {
			global $product, $current_user;
			$options = get_option('sgc_sms_alerts_option_name');
			get_currentuserinfo();
			$user_id = get_current_user_id();
			$name = $current_user->user_firstname;
			$customerPhones = get_user_meta($user_id, 'billing_phone', true);
			$post_id = isset($_POST['comment_post_ID']) ? (int) $_POST['comment_post_ID'] : 0;
			$product = wc_get_product($post_id);
			$title = $product->post->post_title;
			$textMsg = "Thank You! " . $name . ", \nYour review on " . $title . " is awaiting for approval. Your feedback will help millions of other customers, we really appreciate the time and effort you spent in sharing your personal experience with us.";
			$smsgatewaycenter = new smsgatewaycenter($options['sgc_userId'], $options['sgc_password'], FALSE);
			if ($options['product_review_notification'] == 'on') {
				$smsgatewaycenter->sendSmsGatewayCenterSmsPost($textMsg, $customerPhones);
			}
		}

		/**
		 * Review Approved
		 */
		add_action('transition_comment_status', 'sgc_alerts_comment_approved', 10, 3);
		/**
		 * Approved Comments SMS Alert
		 * @global type $product
		 * @param type $new_status
		 * @param type $old_status
		 * @param type $comment
		 */
		function sgc_alerts_comment_approved($new_status, $old_status, $comment) {
			if ($old_status != $new_status) {
				if ($new_status == 'approved') {
					global $product;
					$options = get_option('sgc_sms_alerts_option_name');
					$userid = $comment->user_id;
					$user_data = get_userdata($userid);
					$name = $user_data->display_name;
					$post_id = $comment->comment_post_ID;
					$customerPhones = get_user_meta($userid, 'billing_phone', true);
					$product = wc_get_product($post_id);
					$title = $product->post->post_title;
					$textMsg = "Thank You " . $name . ", \nYour review on " . $title . " has been published. Your feedback will help millions of other customers, we really appreciate the time and effort you spent in sharing your personal experience with us.";
					$smsgatewaycenter = new smsgatewaycenter($options['sgc_userId'], $options['sgc_password'], FALSE);
					if ($options['product_review_notification'] == 'on') {
						$smsgatewaycenter->sendSmsGatewayCenterSmsPost($textMsg, $customerPhones);
					}
				}
			}
		}

	}