<div class="wrap">
	<h2><?php _e('Groups', 'sgc-alerts'); ?></h2>
	<p class="color-333">You can only send maximum 1000 mobile numbers from a group. Do not select multiple groups and kindly restric per group to maximum 1000 numbers.</p>
	<div class="sms_notifications-button-group">
		<a href="admin.php?page=sgc_sms_alerts_subscriber_groups&action=add" class="button"><span class="dashicons dashicons-groups"></span> <?php _e('Add Group', 'sgc-alerts'); ?></a>
	</div>

	<form id="subscribers-filter" method="get">
		<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>"/>
		<?php $list_table->search_box(__('Search', 'sgc_alerts'), 'search_id'); ?>
		<?php $list_table->display(); ?>
	</form>
</div>