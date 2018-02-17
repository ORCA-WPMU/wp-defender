jQuery(function ($) {
    Adtools.formHandler();

    $('div.advanced-tools').on('form-submitted', function (e, data, form) {
        if (form.attr('id') != 'advanced-settings-frm') {
            return;
        }
        if (data.success == true) {
            Defender.showNotification('success', data.data.message);
        } else {
            Defender.showNotification('error', data.data.message);
        }
    })
    $('.deactivate-2factor').click(function () {
        $('#advanced-settings-frm').append('<input type="hidden" name="enabled" value="0"/>');
        $(this).attr('disabled', 'disabled');
        $('#advanced-settings-frm').submit();
    })
});
window.Adtools = window.Adtools || {};
Adtools.formHandler = function () {
    var jq = jQuery;
    jq('body').on('submit', '.advanced-settings-frm', function () {
        var data = jq(this).serialize();
        var that = jq(this);
        jq.ajax({
            type: 'POST',
            url: ajaxurl,
            data: data,
            beforeSend: function () {
                that.find('button[type="submit"]').attr('disabled', 'disabled');
            },
            success: function (data) {
                if (data.data.reload != undefined) {
                    Defender.showNotification('success', data.data.message);
                    location.reload();
                } else if (data.data != undefined && data.data.url != undefined) {
                    location.href = data.data.url;
                } else {
                    that.find('button[type="submit"]').removeAttr('disabled');
                    jq('div.advanced-tools').trigger('form-submitted', [data, that])
                }
            }
        })
        return false;
    })
}