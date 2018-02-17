<div class="wrap">
    <h2><?php _e( "Security", wp_defender()->domain ) ?></h2>
    <table class="form-table">
        <tbody>
        <tr class="user-sessions-wrap hide-if-no-js">
            <th><?php _e( "Two Factor Authentication", wp_defender()->domain ) ?></th>
            <td aria-live="assertive">
                <div class="def-notification">
					<?php _e( "Two factor authentication is active.", wp_defender()->domain ) ?>
                </div>
                <button type="button" class="button" id="disableOTP">
					<?php _e( "Disable", wp_defender()->domain ) ?>
                </button>
            </td>
        </tr>
        <tr class="user-sessions-wrap hide-if-no-js">
            <th><?php _e( "Fallback email address", wp_defender()->domain ) ?></th>
            <td aria-live="assertive">
                <input type="text" class="regular-text" name="def_backup_email" value="<?php echo $email ?>"/>
                <p class="description">
					<?php _e( "If you ever lose your device, you can send a fallback passcode to this email address.", wp_defender()->domain ) ?>
                </p>
            </td>
        </tr>
        </tbody>
    </table>
</div>
<script type="text/javascript">
    jQuery(function ($) {
        $('#disableOTP').click(function () {
            var data = {
                action: 'defDisableOTP'
            }
            var that = $(this);
            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: data,
                beforeSend: function () {
                    that.attr('disabled', 'disabled');
                },
                success: function (data) {
                    if (data.success == true) {
                        location.reload();
                    } else {
                        that.removeAttr('disabled');
                    }
                }
            })
        })
    })
</script>