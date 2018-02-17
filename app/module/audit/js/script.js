jQuery(function ($) {
    //bind form handler for every form inside scan section
    WDAudit.formHandler();
    WDAudit.listenFilter();
    $('div.auditing').on('form-submitted', function (e, data, form) {
        if (form.attr('id') != 'active-audit') {
            return;
        }
        if (data.success == true) {
            location.reload();
        } else {
            Defender.showNotification('error', data.data.message);
        }
    });

    $('div.auditing').on('form-submitted', function (e, data, form) {
        if (!form.hasClass('banIP')) {
            return;
        }
        if (data.success == true) {
            form.closest('.well').prev().remove();
            form.closest('.well').remove();
            Defender.showNotification('success', data.data.message);
        } else {
            Defender.showNotification('error', data.data.message);
        }
    });

    $('div.auditing').on('form-submitted', function (e, data, form) {
        if (!form.hasClass('audit-settings')) {
            return;
        }
        if (data.success == true) {
            Defender.showNotification('success', data.data.message);
        } else {
            Defender.showNotification('error', data.data.message);
        }
    });

    $('select[name="frequency"]').change(function () {
        if ($(this).val() == '1') {
            $(this).closest('.schedule-box').find('div.days-container').hide();
        } else {
            $(this).closest('.schedule-box').find('div.days-container').show();
        }
    }).change();
    $('body').on('click', '.show-info', function (e) {
        e.preventDefault();
        var target = $($(this).data('target'));
        if (target.is(':visible')) {
            target.addClass('wd-hide');
            $(this).removeClass('opened');
            $(this).find('td:nth-child(4) a').html('<i class="dev-icon dev-icon-caret_down"></i>');
        } else {
            target.removeClass('wd-hide');
            $(this).addClass('opened');
            $(this).find('td:nth-child(4) a').html('<i class="dev-icon dev-icon-caret_up"></i>');
        }
    })

    //calendar
    if ($('.wd-calendar').size() > 0) {
        var start = moment().subtract(7, 'days');
        var end = moment();
        var maxDate = end;
        var minDate = moment().subtract(1, 'years');
        $('#wd_range_from').daterangepicker({
            //startDate: start,
            //endDate: end,
            autoApply: true,
            maxDate: maxDate,
            minDate: minDate,
            "linkedCalendars": false,
            showDropdowns: false,
            applyClass: 'wd-hide',
            cancelClass: 'wd-hide',
            alwaysShowCalendars: true,
            opens: 'center',
            dateLimit: {
                days: 90
            },
            locale: {
                "format": "MM/DD/YYYY",
                "separator": " - "
            },
            template: '<div class="daterangepicker wd-calendar wp-defender dropdown-menu"> ' +
            '<div class="ranges"> ' +
            '<div class="range_inputs"> ' +
            '<button class="applyBtn" disabled="disabled" type="button"></button> ' +
            '<button class="cancelBtn" type="button"></button> ' +
            '</div> ' +
            '</div> ' +
            '<div class="calendar left"> ' +
            '<div class="calendar-table"></div> ' +
            '</div> ' +
            '<div class="calendar right"> ' +
            '<div class="calendar-table"></div> ' +
            '</div> ' +
            '</div>',
            showCustomRangeLabel: false,
            ranges: {
                'Today': [moment(), moment()],
                '7 Days': [moment().subtract(6, 'days'), moment()],
                '30 Days': [moment().subtract(29, 'days'), moment()]
            }
        });
    }
    if ($('.new-event-count').size() > 0) {
        //setTimeout(WDAudit.listenForEvents(), 10000)
    }

    $('body').on('click', '.nav a', function (e) {
        e.preventDefault();
        if ($(this).attr('disabled') == 'disabled') {
            return;
        }
        var query = WDAudit.buildFilterQuery();
        WDAudit.ajaxPull(query + '&paged=' + $(this).data('paged'), function () {

        });
    });
    $('body').on('click', 'a.afilter', function (e) {
        e.preventDefault();
        var query = $(this).attr('href').replace('#', '');
        WDAudit.ajaxPull(query, function () {

        });
    })
    $('div.auditing').on('form-submitted', function (e, data, form) {
        if (!form.hasClass('audit-widget')) {
            return;
        }
        if (data.success == true) {
            form.closest('.dev-box').replaceWith($(data.data.html))
        } else {
            Defender.showNotification('error', data.data.message);
        }
    });
    $('div.auditing').on('form-submitted', function (e, data, form) {
        if (!form.hasClass('count-7-days')) {
            return;
        }
        if (data.success == true) {
            if (data.data.eventWeek > 0) {
                $('.issues-count h5').html(data.data.eventWeek);
            }
        } else {
            Defender.showNotification('error', data.data.message);
        }
    });

    if ($('.audit-widget').size() > 0) {
        $('.audit-widget').submit();
    }

    if ($('.count-7-days').size() > 0) {
        $('.count-7-days').submit();
    }

    $('#toggle_audit_logging').change(function () {
        if ($(this).prop('checked') == false) {
            $('.active-audit').submit();
        }
    })
    if ($('#audit-table-container').size() > 0) {
        if ($('#audit-table-container').find('table').size() == 0) {
            var query = WDAudit.buildFilterQuery();
            WDAudit.ajaxPull(query, function () {
                jQuery("#audit-table-container select").each(function () {
                    WDP.wpmuSelect(this);
                });
            });
        }
    }
    ;

    $('body').on('click', '.audit-csv', function () {
        var query = WDAudit.buildFilterQuery();
        query = query + '&action=exportAsCvs';
        location.href = ajaxurl + '?' + query;
        // var that = $(this);
        // $.ajax({
        //     type: 'POST',
        //     url: ajaxurl,
        //     data: query,
        //     beforeSend: function () {
        //         that.attr('disabled', 'disabled');
        //     },
        //     success: function (data) {
        //         if (data.success == 1) {
        //             that.removeAttr('disabled');
        //         }
        //     }
        // })
    })
});
var count;

window.WDAudit = window.WDAudit || {};
WDAudit.formHandler = function () {
    var jq = jQuery;
    jq('body').on('submit', '.audit-frm', function () {
        var data = jq(this).serialize();
        var that = jq(this);
        jq.ajax({
            type: 'POST',
            url: ajaxurl,
            data: data,
            beforeSend: function () {
                that.find('.button').attr('disabled', 'disabled');
            },
            success: function (data) {
				if (data.data != undefined && data.data.notification != undefined){
					if(data.data.notification == 0){
						jq('.defender-audit-frequency').html(data.data.text);
						jq('.defender-audit-schedule').html('');
					} else {
						jq('.defender-audit-frequency').html(data.data.frequency);
						jq('.defender-audit-schedule').html(data.data.schedule);
					}
				}
                if (data.data != undefined && data.data.reload != undefined) {
                    Defender.showNotification('success', data.data.message);
                    location.reload();
                } else if (data.data != undefined && data.data.url != undefined) {
                    location.href = data.data.url;
                } else {
                    that.find('.button').removeAttr('disabled');
                    jq('div.auditing').trigger('form-submitted', [data, that])
                }
            }
        })
        return false;
    })
}

WDAudit.listenForEvents = function () {
    var jq = jQuery;
    var query = WDAudit.buildFilterQuery();
    if (count == null) {
        count = jq('.bulk-nav').first().data('total');
    }
    query += '&lite=1&count=' + count;
    WDAudit.ajaxPull(query, function () {
        setTimeout(WDAudit.listenForEvents, 15000);
    })
}

WDAudit.listenFilter = function () {
    var jq = jQuery;
    var form = jq('.audit-filter form');
    var inputs = form.find(':input');
    var typingTimer;                //timer identifier
    var doneTypingInterval = 800;  //time in ms, 5 second for example
    var state = 0;
    var old_query = '';
    //on keyup, start the countdown
    var currentInput = null;
    inputs.on('change', function () {
        currentInput = jq(this);
        clearTimeout(typingTimer);
        typingTimer = setTimeout(doneTyping, doneTypingInterval);
    });

    //on keydown, clear the countdown
    inputs.on('click', function () {
        state = 1;
        clearTimeout(typingTimer);
    });
    //user is "finished typing," do something
    function doneTyping() {
        //build query
        var query = WDAudit.buildFilterQuery(currentInput);
        if (query == old_query) {
            //no need
            return;
        }
        if (state == 0 && currentInput.is('select') == false) {
            return;
        }
        WDAudit.ajaxPull(query, function () {
            old_query = query;
        })
    }
};

WDAudit.buildFilterQuery = function (currentInput) {
    var jq = jQuery;
    var form = jq('.audit-filter form');
    var inputs = form.find(':input');
    var query = [];
    inputs.each(function () {
        if (jq(this).attr('type') == 'checkbox') {
            if (jq(this).prop('checked') == true) {
                query.push(jq(this).attr('name') + '=' + jq(this).val());
            }
        } else if (jq(this).attr('name') != undefined) {
            if (jq(this).attr('name') == 'date_from') {
                var date = jq(this).val().split('-');
                query.push('date_from=' + jq.trim(date[0]));
                query.push('date_to=' + jq.trim(date[1]));
            } else {
                query.push(jq(this).attr('name') + '=' + jq(this).val());
            }
        }
    });
    return query.join('&');
}
var isFirst = true;
var urlOrigin = location.href;
WDAudit.ajaxPull = function (query, callback) {
    var overlay = Defender.createOverlay();
    var jq = jQuery;
    jq.ajax({
        type: 'GET',
        url: ajaxurl,
        data: query + '&action=auditLoadLogs',
        beforeSend: function () {
            if (query.indexOf('lite') == -1) {
                jq('#audit-table-container').prepend(overlay);
            }
        },
        success: function (data) {
            if (data.success == 1) {
                if (data.data.html != undefined) {
                    var html = jq(data.data.html);
                    if (html.find('#audit-table') > 0 && jq('#audit-table').size() > 0) {
                        jq('#audit-table').replaceWith(html.filter('#audit-table').first());
                        jq('.nav').replaceWith(html.filter('.bulk-nav').first().find('.nav').first());
                        callback();
                    } else {
                        jq('#audit-table-container').html(html);
                        jQuery(".wpmud select").each(function () {
                            WDP.wpmuSelect(this);
                        });
                        callback();
                    }
                    count = data.data.count;
                    overlay.remove();
                    if (isFirst == false) {
                        window.history.pushState(null, document.title, urlOrigin + '&' + query);
                    } else {
                        isFirst = false;
                    }
                } else {
                    jq('.new-event-count').html(data.data.message).removeClass('wd-hide');
                    count = data.data.count;
                    callback();
                }
            }
        }
    })
}