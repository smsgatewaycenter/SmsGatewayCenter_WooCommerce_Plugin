<?php

	require_once '../../../../wp-load.php';
	if (is_user_logged_in() && isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'sgc_csv_exporter')) {

		check_admin_referer('sgc_csv_exporter');

		$options = get_option('sgc_sms_alerts_option_name');
		$settings['sgc_userId'] = $options['sgc_userId'];
		$settings['sgc_password'] = $options['sgc_password'];
		$smsgatewaycenter = new smsgatewaycenter($settings['sgc_userId'], $settings['sgc_password'], false);
		$response = $smsgatewaycenter->sgcListReports();
		$messages = json_decode($response)->DLRReport;

		$messages_export = array();
		foreach ($messages as $key => $dlr) {
			$messages_export[] = array(
				'Phone' => $dlr->Phone,
				'TransactionId' => $dlr->TransactionId,
				'MessageId' => $dlr->MessageId,
				'SenderId' => $dlr->SenderId,
				'Message' => $dlr->Message,
				'MessageType' => $dlr->Type,
				'MessageLength' => $dlr->MessageLength,
				'MessageCost' => $dlr->MessageCost,
				'Status' => $dlr->Status,
				'Cause' => $dlr->Cause,
				'Region' => $dlr->Circle,
				'Operator' => $dlr->Operator,
				'ReceivedTime' => $dlr->ReceivedTime,
				'DeliveryTime' => $dlr->DeliveryTime
			);
		}
		$records = [];
		foreach ($messages_export as $rs) {
			$arraykeys = array_keys($rs);
			$records[] = $rs;
		}
		$headers = $arraykeys;
		$filename = 'export-dlr-' . $settings['sgc_userId'] . date_i18n("Y-m-d_H-i-s") . '.zip';
		smsgatewaycenter_write_zip_file($filename, $headers, $records);
	}
	
	/**
	 * Write a zip file on the fly 
	 * @param string $zipname give a file name with .zip extension 
	 * @param array $headers array of file header
	 * @param arrray $records array of file data
	 */
	function smsgatewaycenter_write_zip_file($zipname, $headers, $records) {
		$zip = new ZipArchive;
		$zip->open(WOOCOM_SGC_SMS_ALERTS_DIR . '/download/' . $zipname, ZipArchive::CREATE);

		// loop to create multiple csv files
		for ($i = 0; $i < 1; $i++) {
			// create a temporary file
			$fd = fopen('php://temp/maxmemory:268435456', 'w');
			fputs($fd, $bom = ( chr(0xEF) . chr(0xBB) . chr(0xBF) ));
			if (false === $fd) {
				die('Failed to create temporary file');
			}

			// write the data to csv
			fputcsv($fd, $headers);
			foreach ($records as $record) {
				fputcsv($fd, $record);
			}

			// return to the start of the stream
			rewind($fd);
			$sfile = str_replace('.zip', '', $zipname);
			// add the in-memory file to the archive, giving a name
			$zip->addFromString($sfile . '.csv', stream_get_contents($fd));
			//close the file
			fclose($fd);
		}
		// close the archive
		$zip->close();
		header('Content-Encoding: UTF-8');
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Type: application/zip');
		header('Content-disposition: attachment; filename=' . $zipname);
		header('Content-Length: ' . filesize(WOOCOM_SGC_SMS_ALERTS_DIR . '/download/' . $zipname));
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		readfile(WOOCOM_SGC_SMS_ALERTS_DIR . '/download/' . $zipname);

		// remove the zip archive
		// you could also use the temp file method above for this.
		unlink(WOOCOM_SGC_SMS_ALERTS_DIR . '/download/' . $zipname);
	}