<div class="wrap">
	<h2><?php _e('Subscribers', 'sgc_alerts'); ?></h2>

	<div class="sms_notifications-button-group">
		<a href="admin.php?page=sgc_sms_alerts_subscribers&action=add" class="button"><span
				class="dashicons dashicons-admin-users"></span> <?php _e('Add Subscriber', 'sgc_alerts'); ?>
		</a>
		<a href="admin.php?page=sgc_sms_alerts_subscriber_groups" class="button"><span
				class="dashicons dashicons-category"></span> <?php _e('Manage Groups', 'sgc_alerts'); ?>
		</a>
		<a href="admin.php?page=sgc_sms_alerts_subscribers&action=import" class="button"><span
				class="dashicons dashicons-yes"></span> <?php _e('Import Subscribers', 'sgc_alerts'); ?>
		</a>
	</div>

	<form id="subscribers-filter" method="get">
		<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>"/>
		<?php $list_table->search_box(__('Search', 'sgc_alerts'), 'search_id'); ?>
		<?php $list_table->display(); ?>
	</form>
</div>