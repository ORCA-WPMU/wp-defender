jQuery(function ($) {
    //bind form handler for every form inside scan section
    WDScan.formHandler();

    //bind handler for new scan form
    $('div.wdf-scanning').on('form-submitted', function (e, data, form) {
        if (form.attr('id') != 'start-a-scan') {
            return;
        }

        if (data.success == true) {
            location.reload();
        } else {
            Defender.showNotification('error', data.data.message);
        }
    });
    //processing scan
    if ($('#scanning').size() > 0) {
        $('body').addClass('wpmud');
        WDP.showOverlay("#scanning", {
            title: scan.scanning_title,
            class: 'no-close wp-defender scanning'
        });
    }
    if ($('#process-scan').size() > 0) {
        $('#process-scan').submit();
        $('div.wdf-scanning').on('form-submitted', function (e, data, form) {
            if (form.attr('id') != 'process-scan') {
                return;
            }
            if (data.success == true) {
                location.reload();
            } else {
                $('.status-text.scan-status').text(data.data.statusText);
                $('.scan-progress-text span').text(data.data.percent + '%');
                $('.scan-progress-bar span').css('width', data.data.percent + '%');
                setTimeout(function () {
                    $('#process-scan').submit();
                }, 1500);
            }
        })
        $('div.wdf-scanning').on('form-submitted-error', function (e, data, form, xhr) {
            if (form.attr('id') != 'process-scan') {
                return;
            }
            //try to reup
            setTimeout(function () {
                $('#process-scan').submit();
            }, 1500);
        })
    }

    //ignore form
    $('div.wdf-scanning').on('form-submitted', function (e, data, form) {
        if (!form.hasClass('ignore-item')) {
            return;
        }

        if (data.success == true) {
            //show notification
            Defender.showNotification('success', data.data.message);
            //close the modal form
            WDP.closeOverlay();
            //remove the line
            $('#' + data.data.mid).fadeOut('200', function () {
                $('#' + data.data.mid).remove();
                WDScan.handleFileIssues(data);
            })
        } else {
            Defender.showNotification('error', data.data.message);
        }
    });
    //restore an ignore
    $('div.wdf-scanning').on('form-submitted', function (e, data, form) {
        if (!form.hasClass('ignore-restore')) {
            return;
        }

        if (data.success == true) {
            //show notification
            Defender.showNotification('success', data.data.message);
            $('#' + data.data.mid).fadeOut('200', function () {
                $('#' + data.data.mid).remove();
                WDScan.handleFileIssues(data);
            })
        } else {
            Defender.showNotification('error', data.data.message);
        }
    });
    //delete mitem
    $('body').on('click', '.delete-mitem', function () {
        var parent = $(this).closest('form');
        var confirm_box = parent.find('.confirm-box');
        $(this).addClass('wd-hide');
        confirm_box.removeClass('wd-hide');
        confirm_box.find('.button-secondary').unbind('click').bind('click', function () {
            confirm_box.addClass('wd-hide');
            parent.find('.delete-mitem').removeClass('wd-hide');
        })
    });
    $('div.wdf-scanning').on('form-submitted', function (e, data, form) {
        if (!form.hasClass('delete-item')) {
            return;
        }
        if (data.success == true) {
            //show notification
            Defender.showNotification('success', data.data.message);
            //close the modal form
            WDP.closeOverlay();
            $('#' + data.data.mid).fadeOut('200', function () {
                $('#' + data.data.mid).remove();
                WDScan.handleFileIssues(data);
            })
        } else {
            Defender.showNotification('error', data.data.message);
        }
    });
    $('div.wdf-scanning').on('form-submitted', function (e, data, form) {
        if (!form.hasClass('pull-src')) {
            return;
        }

        if (data.success == true) {
            var parent = form.closest('.source-code');
            parent.html(data.data.html);

            // hljs.highlightBlock(parent.find('pre code'));
            $('pre code').each(function (i, block) {
                hljs.highlightBlock(block);
                hljs.lineNumbersBlock(block);
            });
        } else {
            Defender.showNotification('error', data.data.message);
        }
    })
    //resolve item
    $('div.wdf-scanning').on('form-submitted', function (e, data, form) {
        if (!form.hasClass('resolve-item')) {
            return;
        }

        if (data.success == true) {
            //show notification
            Defender.showNotification('success', data.data.message);
            //close the modal form
            WDP.closeOverlay();
            $('#' + data.data.mid).fadeOut('200', function () {
                $('#' + data.data.mid).remove();
                WDScan.handleFileIssues(data);
            })
        } else {
            Defender.showNotification('error', data.data.message);
        }
    });
    $('div.wdf-scanning').on('form-submitted', function (e, data, form) {
        if (!form.hasClass('scan-settings')) {
            return;
        }

        if (data.success == true) {
            WDP.closeOverlay();
            //show notification
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

    //bulk
    $('#apply-all').click(function () {
        $('.scan-chk').prop('checked', $(this).prop('checked'));
    });
    $('.scan-bulk-frm').submit(function () {
        var data = $(this).serialize();
        $('.scan-chk').each(function () {
            if ($(this).prop('checked') == true) {
                data += '&items[]=' + $(this).val();
            }
        })
        var that = $(this);
        $.ajax({
            type: 'POST',
            data: data,
            url: ajaxurl,
            beforeSend: function () {
                that.find('button').attr('disabled', 'disabled');
            },
            success: function (data) {
                if (data.success) {
                    setTimeout(function () {
                        location.reload();
                    }, 1000)
                    Defender.showNotification('success', data.data.message);
                } else {
                    that.find('button').removeAttr('disabled');
                    Defender.showNotification('error', data.data.message);
                }
            }
        })
        return false;
    });

    $('.column-col_action a').click(function () {
        setTimeout(function () {
            if ($('.source-code:visible').size() > 0) {
                $('.source-code:visible').find('form').submit();
            }
        }, 500)
    })
})

window.WDScan = window.WDScan || {};
WDScan.formHandler = function () {
    var jq = jQuery;
    jq('body').on('submit', '.scan-frm', function () {
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
                if (data.data != undefined && data.data.url != undefined) {
                    location.href = data.data.url;
                } else {
                    that.find('.button').removeAttr('disabled');
                    jq('div.wdf-scanning').trigger('form-submitted', [data, that])
                }
            },
            error: function (xhr) {
                jq('div.wdf-scanning').trigger('form-submitted-error', [data, that, xhr])
            }
        })
        return false;
    })
}

//Refresh file issues counts
WDScan.handleFileIssues = function (data) {
    var jq = jQuery;
    if (data.data.counts != undefined) {
        if (data.data.counts.issues) {
            //If the issues are more than 0, update or create elements
            if (data.data.counts.issues > 0) {
                if (jq('.def-issues-top-left-icon i:not(.icon-warning)')) {
                    jq('.def-issues-top-left-icon').html('<i class="def-icon icon-warning fill-red"></i>');
                }
                if (!jq('.def-issues-below').length) {
                    if (jq('li.issues-nav a').length) {
                        jq('li.issues-nav a').append('<span class="def-tag tag-error def-issues-below">' + data.data.counts.issues + '</span>');
                    }
                } else {
                    jq('.def-issues-below').html(data.data.counts.issues);
                }
                if (!jq('.def-issues-summary').length) {
                    if (jq('.def-issues-title').length) {
                        jq('.def-issues-title').append('<span class="def-tag tag-error def-issues def-issues-summary">' + data.data.counts.issues + '</span>');
                    }
                } else {
                    jq('.def-issues-summary').html(data.data.counts.issues);
                }
                jq('.def-issues-below').show();
                jq('.def-issues').html(data.data.counts.issues);
                if (jq('.def-issues-top-right-wp i:not(.tag-error)') && data.data.counts.issues_wp > 0) {
                    jq('.def-issues-top-right-wp').html('<span class="def-tag tag-error">' + data.data.counts.issues_wp + '</span>');
                } else {
                    if (data.data.counts.issues_wp > 0) {
                        jq('.def-issues-top-right-wp .tag-error').html(data.data.counts.issues_wp);
                    } else {
                        if (jq('.def-issues-top-right-wp span:not(.icon-tick)')) {
                            jq('.def-issues-top-right-wp').html('<i class="def-icon icon-tick"></i>');
                        }
                    }

                }
                if (data.data.counts.vuln_issues != undefined) {
                    if (jq('.def-issues-top-right-pt i:not(.tag-error)') && data.data.counts.vuln_issues > 0) {
                        jq('.def-issues-top-right-pt').html('<span class="def-tag tag-error">' + data.data.counts.vuln_issues + '</span>');
                    } else {
                        if (data.data.counts.vuln_issues > 0) {
                            jq('.def-issues-top-right-pt .tag-error').html(data.data.counts.vuln_issues);
                        } else {
                            if (jq('.def-issues-top-right-pt span:not(.icon-tick)')) {
                                jq('.def-issues-top-right-pt').html('<i class="def-icon icon-tick"></i>');
                            }
                        }
                    }
                }
                if (data.data.counts.content_issues != undefined) {
                    if (jq('.def-issues-top-right-sc i:not(.tag-error)') && data.data.counts.content_issues > 0) {
                        jq('.def-issues-top-right-sc').html('<span class="def-tag tag-error">' + data.data.counts.content_issues + '</span>');
                    } else {
                        if (data.data.counts.content_issues > 0) {
                            jq('.def-issues-top-right-sc .tag-error').html(data.data.counts.content_issues);
                        } else {
                            if (jq('.def-issues-top-right-sc span:not(.icon-tick)')) {
                                jq('.def-issues-top-right-sc').html('<i class="def-icon icon-tick"></i>');
                            }
                        }
                    }
                }
            } else {
                //Show success messages
                jq('.def-issues-top-left').html(0);
                if (jq('.def-issues-top-left-icon i:not(.icon-tick)')) {
                    jq('.def-issues-top-left-icon').html('<i class="def-icon icon-tick"></i>');
                }
                if (jq('.def-issues-top-right-wp span:not(.icon-tick)')) {
                    jq('.def-issues-top-right-wp').html('<i class="def-icon icon-tick"></i>');
                }
                if (data.data.counts.vuln_issues != undefined) {
                    if (jq('.def-issues-top-right-pt span:not(.icon-tick)')) {
                        jq('.def-issues-top-right-pt').html('<i class="def-icon icon-tick"></i>');
                    }
                    if (jq('.def-issues-top-right-sc span:not(.icon-tick)')) {
                        jq('.def-issues-top-right-sc').html('<i class="def-icon icon-tick"></i>');
                    }
                }
                jq('.def-issues-summary').hide();
                jq('.def-issues-below').hide();
                if (jq('.issues-box-content').length) {
                    jq('.issues-box-content').html('<div class="well well-green with-cap"><i class="def-icon icon-tick"></i>' + scan.no_issues + '</div>');
                }
            }

        }
        //Ignored counts
        if (data.data.counts.ignored) {
            if (data.data.counts.ignored > 0) {
                jq('.def-ignored').html(data.data.counts.ignored);
            } else {
                jq('.def-ignored').html("");
            }
        }
    }
}