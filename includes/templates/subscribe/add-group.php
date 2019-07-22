<div class="wrap">
    <h2><?php _e( 'Add Group', 'sgc_alerts' ); ?></h2>
    <p class="color-333">You can only send maximum 1000 mobile numbers from a group. Do not select multiple groups and kindly restric per group to maximum 1000 numbers.</p>
    <form action="" method="post">
        <table>
            <tr>
                <td colspan="2"><h3><?php _e( 'Add New Group:', 'sgc_alerts' ); ?></h3></td>
            </tr>
            <tr>
                <td><span class="label_td" for="sms_notify_group_name"><?php _e( 'Name', 'sgc_alerts' ); ?>:</span></td>
                <td><input type="text" id="sms_notify_group_name" name="sms_notify_group_name"/></td>
            </tr>

            <tr>
                <td colspan="2">
                    <a href="admin.php?page=sgc_sms_alerts_subscriber_groups" class="button"><?php _e( 'Back', 'sgc_alerts' ); ?></a>
                    <input type="submit" class="button-primary" name="wp_add_group"
                           value="<?php _e( 'Add', 'sgc_alerts' ); ?>"/>
                </td>
            </tr>
        </table>
    </form>
</div>  