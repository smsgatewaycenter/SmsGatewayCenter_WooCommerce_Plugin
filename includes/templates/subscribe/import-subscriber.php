<div class="wrap color-333">
	<h2><?php _e('Import Subscibers', 'sgc-alerts'); ?></h2>
	<p class="color-333">To Import subscribers, you can install a free word plugin, <a href="https://wordpress.org/plugins/wp-csv-to-database/" target="_blank">here</a>.</p>
	<p>Or go to Add New Plugins, search for <b>WP CSV TO DB Pluign</b> then install and activate the plugin.</p>
	<p>Upon activating, go to Sidebar Menu -> Settings -> <b>WP CSV/DB.</b></p>
	<p><b>Step 1</b>: On the settings tab, Select Database table, Select our table <b>TABLE_PREFIX_sgc_sms_alerts_subscribers</b> from the dropdown.</p>
	<p><b>Step 2</b>: Now, <b>Select Input File</b> and upload your CSV file. You can see the sample screenshot of CSV file below. Fig. 1.1</p>
	<figure class="wp-block-image"><img src="<?php echo SMS_ALERTS_GATEWAY_URL; ?>/assets/images/csv_screenshot.png" alt="CSV Screenshot" class="wp-image-73" sizes="(max-width: 1024px) 100vw, 1024px"><figcaption>Fig. 1.1</figcaption></figure>
	<p><b>Step 3</b>: Here, <b>Select Starting Row</b>, Enter <b>2</b> in the text input, so that our 1st row heading wont get inserted into database.</p>
	<p><b>Step 4</b>: Check <b>Disable "auto_increment" Column</b> as we dont want to use this column.</p>
	<p><b>Step 5</b>: Check <b>Update Database Rows</b> too as if you are inserting any duplicate let it overwrite in the database table.</p>
	<p><b>Final Step:</b> Click on <b>Import to DB</b> button and you are all done!</p>
	<p>&nbsp;</p>
	<p>&nbsp;</p>
	<p><strong>Important Note:</strong> Restrict to maximum 1000 Subscribers per group to send Coupon Announcements SMS to listed Subscribers. Import Plugin does not validate mobile numbers, hence before uploading just uploaded the proper mobile numbers.</p>
	<p>&nbsp;</p>
	<p>&nbsp;</p>
	<p><strong>Tips:</strong></p>
	<p>When ever you want to send other than Coupons Announcement SMS, just change the SMS content from SGC SMS Alerts Settings page.</p>
</div>