jQuery(function ($) {
    WDHardener.formHandler();
    WDHardener.rules();

    //On key up or is a user decides to paste
    $('.hardener-instructions textarea.hardener-php-excuted-ignore').on('keyup keypress paste',function(e){
        var text_val = $(this).val();
        //We cant allow index.php
        if( text_val.includes('index.php')){
            text_val = text_val.replace(/index.php/g,'');
            $(this).val(text_val);
        }

        //no fancy scripts or html code. We also validate server side
        if( /<[a-z][\s\S]*>/i.test(text_val)){
            text_val = text_val.replace(/<\/?[^>]+(>|$)/g, "");
            $(this).val(text_val);
        }

        if ($('.hardener-instructions-apache').is(':visible')) {
            //Apache
            $('.hardener-apache-frm [name="file_paths"]').val(text_val);
        } else if ($('.hardener-instructions-litespeed').is(':visible')) {
            //Litespeed
            $('.hardener-litespeed-frm [name="file_paths"]').val(text_val);
        } else if ($('.hardener-instructions-nginx').is(':visible')) {
            //Nginx
            var excludedFiles = text_val.split('\n');
            var newRule = "";
            var $wp_content = $('.hardener-wp-content-dir').val();
            $.each(excludedFiles, function(index, file) {
                if(file){
                    newRule += "\n location ~* ^"+$wp_content+"/.*&#92;"+file+"$ {"+
                                " \n  allow all;"+
                                "\n}";
                }
            });
            $('span.hardener-nginx-extra-instructions').html(newRule);
        }
        if ( $('.hardener-instructions-apache-litespeed').length ) {
            $('.hardener-update-frm [name="file_paths"]').val(text_val);
        }
	});
	
	/**
	 * Validate that the number put is greater than 0 and is actually a number
	 */
	$(document).on('keyup keypress paste','.defender-login-duration', function(){
		var text_val = $(this).val();
		if( /^-?[0-9]+$/i.test(text_val)){
			//is integer
			if(text_val <= 0){
				$(this).val('');
			}
		} else{
            $(this).val('');
		}
	});

    /**
     * Pevent PHP update posts toggle
     */
    $(document).on('change', 'input.trackback-toggle-update-posts', function(){
        if(this.checked) {
            $('.hardener-frm-process-trackback [name="updatePosts"]').val('yes');
        }else{
            $('.hardener-frm-process-trackback [name="updatePosts"]').val('no');
        }
    });

    /**
     * Toggle text area
     */
    $(document).on('click','button.hardener-php-excuted-execption', function(){
        $('.hardener-instructions textarea.hardener-php-excuted-ignore').toggle('fast');
    });

    /**
     * Server select
     */
    $(document).on('change', 'select.hardener-server-list', function(){
        var selected = $(this).val();
        if($(this).hasClass('information')){
            $('.hardener-information').each(function(){
                $(this).addClass('wd-hide');
            });
            $('.hardener-information-'+selected).removeClass('wd-hide');
        }else{
            $('.hardener-instructions').each(function(){
                $(this).addClass('wd-hide');
            });
            $('.hardener-instructions-'+selected).removeClass('wd-hide');
        }
        if( selected == 'apache' || selected == 'litespeed' || selected == 'nginx'){
            $('.hardener-instructions-extra-exceptions').removeClass('wd-hide');
        }else{
            $('.hardener-instructions-extra-exceptions').addClass('wd-hide');
        }
        
    });

    $('div.hardener').on('form-submitted', function (e, data, form) {
        if (form.hasClass('rule-process') == false) {
            return;
        }
        if (data.success == true) {
            Defender.showNotification('success', data.data.message);
            $('.count-issues').text(data.data.issues);
            $('.count-ignored').text(data.data.ignore);
            $('.count-resolved').text(data.data.fixed);
            $('.issues-actioned').text(10 - data.data.issues);
            if (data.data.issues > 0) {
                $('.issues-count i').removeClass('def-icon icon-tick').addClass('def-icon icon-warning');
                $('.count-issues').removeClass('wd-hide');
            } else {
                $('.count-issues').addClass('wd-hide');
                $('.issues-count i').removeClass('def-icon icon-warning').addClass('def-icon icon-tick');
            }
            if (data.data.ignore > 0) {
                $('.count-ignored').removeClass('wd-hide');
            } else {
                $('.count-ignored').addClass('wd-hide');
            }
            if (data.data.fixed > 0) {
                $('.count-resolved').removeClass('wd-hide');
            } else {
                $('.count-resolved').addClass('wd-hide');
            }
            var update_rules = true;
            if ( typeof data.data.update !== "undefined" ) {
                update_rules = false;
            }
            if ( update_rules ) {
                form.closest('.rule').slideUp(500, function () {
                    $(this).remove();
                    if ($('.rule').size() == 0) {
                        setTimeout(function () {
                            location.reload();
                        }, 500)
                    }
                });
            }
        } else {
            Defender.showNotification('error', data.data.message);
        }
    });
});
function debounce(fn, delay) {
    var timer = null;
    return function () {
        var context = this, args = arguments;
        clearTimeout(timer);
        timer = setTimeout(function () {
            fn.apply(context, args);
        }, delay);
    };
}
window.WDHardener = window.WDHardener || {};

WDHardener.formHandler = function () {
    var jq = jQuery;
    jq('body').on('submit', '.hardener-frm', function () {
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
                if (data.data != undefined && data.data.reload != undefined) {
                    if (data.data.message != undefined) {

                        //Modal should not close
                        Defender.showNotification('success', data.data.message, false);

                        //Count down timer
                        if(jq('.hardener-timer').length){
                            var duration = data.data.reload;
                            var refreshTimer = setInterval(function () {
                                seconds = parseInt(duration % 60, 10);
                                seconds = seconds < 10 ? "0" + seconds : seconds;
                                jq('.hardener-timer').html(seconds);

                                if (--duration < 0) {
                                    clearInterval(refreshTimer);
                                    location.reload()
                                }
                            }, 1000);
                        }

                    } else {
                        setTimeout(function () {
                            location.reload()
                        }, 1500)
                    }
                } else if (data.data != undefined && data.data.url != undefined) {
                    location.href = data.data.url;
                } else {
                    that.find('.button').removeAttr('disabled');
                    jq('div.hardener').trigger('form-submitted', [data, that])
                }
            }
        })
        return false;
    })
}

WDHardener.rules = function () {
    var jq = jQuery;
    if (jq('.rules.ignored').size() > 0) {
        //no animation for ignored
        return;
    }
    var id = window.location.hash.substr(1);
    if (id == undefined) {
        jq('.rule').first().removeClass('closed');
    } else {
        jq('#' + id).removeClass('closed');
    }
    jq('.rule .rule-title').click(function () {
        var parent = jq(this).closest('.rule');
        var otherRules = jq('.rule').not(parent);
        otherRules.each(function () {
            var that = jq(this);
            jq(this).find('.rule-content').first().slideUp(function () {
                that.addClass('closed');
            })
        })
        if (parent.hasClass('closed')) {
            //jq(this).switchClass('closed', '', 1000, 'swing');
            parent.find('.rule-content').first().slideDown();
            parent.removeClass('closed');
        } else {
            //jq(this).switchClass('', 'closed', 1000, 'swing');
            parent.find('.rule-content').first().slideUp(function () {
                parent.addClass('closed');
            })
        }
    })
}