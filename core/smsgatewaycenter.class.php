<?php

	if (!defined('ABSPATH'))exit;

	global $wpdb, $woocommerce, $product;

	if (!function_exists('sgc_sms_alerts_shortcode_variable')) {
		/**
		 * Replace Text Message Variable
		 * @param type $textMsg
		 * @param type $order
		 * @return type
		 */
		function sgc_sms_alerts_shortcode_variable($textMsg, $order) {
			if (!$textMsg || !is_object($order)){
				return;
			}
			$woocom_orderId = $order->get_id();
			$order_custom_fields = get_post_custom($woocom_orderId);
			$current_date_time = current_time('timestamp');

			if (preg_match("/{WOOCOM_SHOP_NAME}/i", $textMsg)) {
				$WOOCOM_SHOP_NAME = get_option("blogname");
				$textMsg = @str_replace("{WOOCOM_SHOP_NAME}", $WOOCOM_SHOP_NAME, $textMsg);
			}
			if (preg_match("/{WOOCOM_ORDER_NUMBER}/i", $textMsg)) {
				$WOOCOM_ORDER_NUMBER = isset($woocom_orderId) ? $woocom_orderId : "";
				$textMsg = @str_replace("{WOOCOM_ORDER_NUMBER}", $WOOCOM_ORDER_NUMBER, $textMsg);
			}
			if (preg_match("/{WOOCOM_ORDER_STATUS}/i", $textMsg)) {
				$WOOCOM_ORDER_STATUS = @ucfirst($order->get_status());
				$textMsg = @str_replace("{WOOCOM_ORDER_STATUS}", $WOOCOM_ORDER_STATUS, $textMsg);
			}
			if (preg_match("/{WOOCOM_ORDER_AMOUNT}/i", $textMsg)) {
				$WOOCOM_ORDER_AMOUNT = $order_custom_fields["_order_total"][0];
				$textMsg = @str_replace("{WOOCOM_ORDER_AMOUNT}", $WOOCOM_ORDER_AMOUNT, $textMsg);
			}
			if (preg_match("/{WOOCOM_ORDER_DATE}/i", $textMsg)) {
				$order_date_format = get_option("date_format");
				$WOOCOM_ORDER_DATE = date_i18n($order_date_format, strtotime($order->get_date_created()));
				$textMsg = @str_replace("{WOOCOM_ORDER_DATE}", $WOOCOM_ORDER_DATE, $textMsg);
			}
			if (preg_match("/{WOOCOM_ORDER_ITEMS}/i", $textMsg)) {
				$order_items = $order->get_items(apply_filters("woocommerce_admin_order_item_types", array("line_item")));
				$WOOCOM_ORDER_ITEMS = "";
				if (count($order_items)) {
					$item_cntr = 0;
					foreach ($order_items as $order_item) {
						if ($order_item["type"] == "line_item") {
							if ($item_cntr == 0)
								$WOOCOM_ORDER_ITEMS = $order_item["name"];
							else
								$WOOCOM_ORDER_ITEMS .= ", " . $order_item["name"];
							$item_cntr++;
						}
					}
				}
				$textMsg = @str_replace("{WOOCOM_ORDER_ITEMS}", $WOOCOM_ORDER_ITEMS, $textMsg);
			}
			if (preg_match("/{WOOCOM_BILLING_FNAME}/i", $textMsg)) {
				$WOOCOM_BILLING_FNAME = $order_custom_fields["_billing_first_name"][0];
				$textMsg = @str_replace("{WOOCOM_BILLING_FNAME}", $WOOCOM_BILLING_FNAME, $textMsg);
			}

			if (preg_match("/{WOOCOM_BILLING_LNAME}/i", $textMsg)) {
				$WOOCOM_BILLING_LNAME = $order_custom_fields["_billing_last_name"][0];
				$textMsg = @str_replace("{WOOCOM_BILLING_LNAME}", $WOOCOM_BILLING_LNAME, $textMsg);
			}
			if (preg_match("/{WOOCOM_BILLING_EMAIL}/i", $textMsg)) {
				$WOOCOM_BILLING_EMAIL = $order_custom_fields["_billing_email"][0];
				$textMsg = @str_replace("{WOOCOM_BILLING_EMAIL}", $WOOCOM_BILLING_EMAIL, $textMsg);
			}
			if (preg_match("/{WOOCOM_CURRENT_DATE}/i", $textMsg)) {
				$wp_date_format = get_option("date_format");
				$WOOCOM_CURRENT_DATE = date_i18n($wp_date_format, $current_date_time);
				$textMsg = @str_replace("{WOOCOM_CURRENT_DATE}", $WOOCOM_CURRENT_DATE, $textMsg);
			}
			if (preg_match("/{WOOCOM_CURRENT_TIME}/i", $textMsg)) {
				$wp_time_format = get_option("time_format");
				$WOOCOM_CURRENT_TIME = date_i18n($wp_time_format, $current_date_time);
				$textMsg = @str_replace("{WOOCOM_CURRENT_TIME}", $WOOCOM_CURRENT_TIME, $textMsg);
			}
			return $textMsg;
		}

	}
	
	/**
	 * Replace Registration variable
	 * @param type $textMsg
	 * @param type $firstname
	 * @return type
	 */
	function sgc_sms_alerts_regi_variable($textMsg, $firstname, $lastname){
		if (!$textMsg || !($firstname)){
			return;
		}
		if (preg_match("/{WOOCOM_FIRST_NAME}/i", $textMsg)) {
			$textMsg = @str_replace("{WOOCOM_FIRST_NAME}", $firstname, $textMsg);
		}
		if (preg_match("/{WOOCOM_LAST_NAME}/i", $textMsg)) {
			$textMsg = @str_replace("{WOOCOM_LAST_NAME}", $lastname, $textMsg);
		}
		return $textMsg;
	}

	class smsgatewaycenter {

		const REQUEST_URL = 'https://www.smsgateway.center/';
		const REQUEST_TIMEOUT = 60;
		const REQUEST_HANDLER = 'curl';
		private $username;
		private $password;
		private $apiKey;
		public $errors = array();
		public $warnings = array();
		public $lastRequest = array();

		/**
		 * Instantiate the object
		 * @param $usernameg
		 * @param $hash
		 */
		function __construct($username, $password, $apiKey = false) {
			$this->username = $username;
			$this->password = $password;
			if ($apiKey) {
				$this->apiKey = $apiKey;
			}
		}

		/**
		 * Get Default parameters
		 * @return string
		 */
		function sgcGetDefaultParams() {
			$params['userId'] = $this->username;
			$params['password'] = $this->password;
			$params['format'] = 'json';
			return $params;
		}

		/**
		 * Curl request handler
		 * @param $endpoint
		 * @param $params
		 * @return mixed
		 * @throws Exception
		 */
		private function sgcCurlRequest($endpoint, $params) {
			$url = self::REQUEST_URL . $endpoint;
			//error_log(print_r($params, true));
			// Initialize handle
			$ch = curl_init($url);
			curl_setopt_array($ch, array(
				CURLOPT_POST => true,
				CURLOPT_POSTFIELDS => $params,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_TIMEOUT => self::REQUEST_TIMEOUT
			));

			$rawResponse = curl_exec($ch);
			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			$error = curl_error($ch);
			curl_close($ch);

			if ($rawResponse === false) {
				throw new Exception('Failed to connect to the SMS Gateway Center Server: ' . $error);
			} elseif ($httpCode != 200) {
				throw new Exception('Bad response from the SMS Gateway Center Server: HTTP code ' . $httpCode);
			}
			//echo $rawResponse;exit;
			return $rawResponse;
		}
		
		/**
		 * List Sender names
		 * @return array|mixed
		 */
		public function sgcListSenderNames() {
			$params = $this->sgcGetDefaultParams();
			$params['do'] = 'list';
			return $this->sgcCurlRequest('library/api/self/SenderName/', $params);
		}
		
		/**
		 * Send Simple Message in POST method
		 * @param type $textMsg
		 * @param type $recipients
		 * @return boolean
		 */
		public function sendSmsGatewayCenterSmsPost($textMsg, $phones = array()) {
			@ini_set('allow_url_fopen', 1);
			$options = get_option('sgc_sms_alerts_option_name');
			$baseURL = self::REQUEST_URL;
			$params['userId'] = $options['sgc_userId'];
			$params['password'] = $options['sgc_password'];
			$params['senderId'] = $options['sgc_senderid'];
			$params['sendMethod'] = 'simpleMsg';
			$params['msgType'] = 'dynamic';
			$params['msg'] = $textMsg;
			$params['format'] = 'json';
			
			$isAdminNotifyEnabled = $options['sgc_notify_admin'];
			
			$sendSMSUrl = $baseURL . "SMSApi/rest/send";
			if ($isAdminNotifyEnabled == 'on' && !empty($options['sgc_wp_admin_mobile']) && is_array($phones)) {
				$params['mobile'] = implode(',', $phones);
			} else {
				$params['mobile'] = $phones;
			}
			
			$response = wp_remote_post($sendSMSUrl, array('body' => $params));
			$jsonResponse = wp_remote_retrieve_body( $response );
			//error_log(print_r($params, TRUE));
			$jsonResponse = json_decode($jsonResponse);
			if ($jsonResponse->status == 'error') {
				return $jsonResponse->reason;
			} elseif ($jsonResponse->status == 'success') {
				return true;
			}
		}
		
		/**
		 * List DLR
		 * @return array|mixed
		 */
		public function sgcListReports() {
			date_default_timezone_set('Asia/Kolkata');
			$params = $this->sgcGetDefaultParams();
			$params['FromDate'] = date('Y-m-d');
			return $this->sgcCurlRequest('library/api/self/SMSDlr/', $params);
		}

	}