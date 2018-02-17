<?php
/**
 * Author: Hoang Ngo
 */

namespace WP_Defender\Vendor;

use Hammer\Helper\HTTP_Helper;
use Hammer\Helper\Log_Helper;
use Hammer\WP\Component;

class Email_Search extends Component {
	public $eId = '';
	public $lite = false;
	public $settings;
	public $attribute = 'receipts';
	public $empty_msg = '';
	public $placeholder = '';
	public $noExclude = false;

	public function add_hooks() {
		//this should add in init
		$this->add_action( 'wp_ajax_wd_username_search_' . $this->eId, 'ajax_search_user' );
		$this->add_action( 'wp_ajax_add_receipt_' . $this->eId, 'add_receipt' );
		$this->add_action( 'wp_ajax_remove_receipt_' . $this->eId, 'remove_receipt' );
	}

	public function add_script() {
		$this->add_action( 'admin_footer', 'scripts' );
	}

	public function remove_receipt() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$user_id = HTTP_Helper::retrieve_post( 'user' );
		$user    = get_user_by( 'id', $user_id );
		if ( is_object( $user ) ) {
			$index = array_search( $user_id, $this->settings->{$this->attribute} );
			if ( $index !== false ) {
				unset( $this->settings->{$this->attribute}[ $index ] );
				$this->settings->save();
			}
		}
	}

	public function add_receipt() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$user_name = HTTP_Helper::retrieve_post( 'user' );
		$user      = get_user_by( 'login', $user_name );
		if ( is_object( $user ) ) {
			$this->settings->{$this->attribute}[] = $user->ID;
			$this->settings->save();
			wp_send_json( array(
				'status'     => 1,
				'avatar'     => $this->getAvatarUrl( get_avatar( $user->ID, 30 ) ),
				'name'       => $this->getDisplayName( $user->ID ),
				'is_current' => get_current_user_id() == $user->ID,
				'user_id'    => $user->ID
			) );
		} else {
			wp_send_json( array(
				'status' => 0
			) );
		}
	}

	public function ajax_search_user() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$args = array(
			'search'         => '*' . HTTP_Helper::retrieve_post( 'term' ) . '*',
			'search_columns' => array( 'user_login' ),
			'number'         => 10,
			'exclude'        => $this->settings->{$this->attribute},
			'orderby'        => 'user_login',
			'order'          => 'ASC'
		);
		if ( $this->noExclude == true ) {
			unset( $args['exclude'] );
		}

		$query   = new \WP_User_Query( $args );
		$results = array();
		foreach ( $query->get_results() as $row ) {
			$results[] = array(
				'id'    => $row->user_login,
				'label' => '<span class="name title">' . esc_html( $this->getDisplayName( $row->ID ) ) . '</span> <span class="email">' . esc_html( $row->user_email ) . '</span>',
				'thumb' => $this->getAvatarURL( get_avatar( $row->user_email ) )
			);
		}
		echo json_encode( $results );
		exit;
	}

	protected function getAvatarURL( $get_avatar ) {
		preg_match( "/src='(.*?)'/i", $get_avatar, $matches );

		return $matches[1];
	}

	public function renderInput() {
		if ( empty( $this->placeholder ) ) {
			$this->placeholder = __( "Enter a username", wp_defender()->domain );
		}
		?>
		<?php if ( $this->lite == false ): ?>
            <div class="receipt">
                <ul>
					<?php foreach ( $this->settings->{$this->attribute} as $id ): ?>
						<?php $user = get_user_by( 'id', $id ) ?>
						<?php if ( is_object( $user ) ): ?>
                            <li><?php echo get_avatar( $user->ID, 30 ) ?>
                                <span class="name"><?php echo esc_html( $this->getDisplayName( $user->ID ) ) ?></span>
								<?php if ( get_current_user_id() == $user->ID ): ?>
                                    <span class="def-tag tag-generic"><?php esc_html_e( "You", wp_defender()->domain ) ?></span>
								<?php endif; ?>
                                <a data-id="<?php echo esc_attr( $user->ID ) ?>"
                                   class="remove wd-remove-recipient float-r"
                                   href="#"><?php esc_html_e( "Remove", wp_defender()->domain ) ?></a>
                            </li>
						<?php endif; ?>
					<?php endforeach; ?>
                </ul>
                <div>
                    <span><input data-empty-msg="<?php echo esc_attr( $this->empty_msg ) ?>"
                                 placeholder="<?php echo esc_attr( $this->placeholder ) ?>" name="term"
                                 id="wd-username-search"
                                 type="search"/></span>
                    <button type="submit" disabled id="add-receipt"
                            class="button button-grey"><?php _e( "Add", wp_defender()->domain ) ?></button>
                </div>
            </div>
		<?php else: ?>
            <input name="term" data-empty-msg="<?php echo esc_attr( $this->empty_msg ) ?>"
                   placeholder="<?php echo esc_attr( $this->placeholder ) ?>" id="wd-username-search" type="search"/>
		<?php endif; ?>
		<?php
	}

	/**
	 * @param $id
	 *
	 * @return null|string
	 */
	protected function getDisplayName( $id ) {
		$user = get_user_by( 'id', $id );
		if ( ! is_object( $user ) ) {
			return null;
		}
		if ( ! empty( $user->user_nicename ) ) {
			return $user->user_nicename;
		} else {
			return $user->user_firstname . ' ' . $user->user_lastname;
		}
	}

	public function scripts() {
		?>
        <script type="text/javascript">
            jQuery(function ($) {
                var typingTimer;                //timer identifier
                var doneTypingInterval = 1000;  //time in ms, 5 second for example
                var $input = $("#wd-username-search");

                //on keyup, start the countdown
                $input.on('keyup', function () {
                    clearTimeout(typingTimer);
                    typingTimer = setTimeout(doneTyping, doneTypingInterval);
                });

                //on keydown, clear the countdown
                $input.on('keydown', function () {
                    clearTimeout(typingTimer);
                });

                //user is "finished typing," do something
                function doneTyping() {
                    //do something
                    var that = $input;
                    var value = that.val();
                    if (value.length > 2) {
                        $.ajax({
                            type: 'POST',
                            url: ajaxurl,
                            data: {
                                'action': 'wd_username_search_<?php echo $this->eId ?>',
                                'id': '<?php echo $this->eId ?>',
                                'term': value
                            },
                            beforeSend: function () {
                                that.trigger('progress:start');
                            },
                            success: function (data) {
                                data = $.parseJSON(data);
                                that.trigger('progress:stop');
                                that.trigger('results:show', [data]);
                            }
                        })
                    }

                    $("#wd-username-search").on('item:select', function () {
                        $(this).closest('.receipt').find('button').removeAttr('disabled')
                    })
                }

                $('#add-receipt').click(function () {
                    var that = $(this);
                    $.ajax({
                        type: 'POST',
                        url: ajaxurl,
                        data: {
                            action: 'add_receipt_<?php echo $this->eId ?>',
                            'id': '<?php echo $this->eId ?>',
                            user: $("#wd-username-search").val()
                        },
                        beforeSend: function () {
                            that.attr('disabled', 'disabled')
                        },
                        success: function (data) {
                            var user_row = $('<li/>');
                            user_row.append($('<img/>').attr({
                                src: data.avatar,
                                width: '30'
                            }));
                            user_row.append($('<span class="name"/>').html(data.name));
                            if (data.is_current) {
                                user_row.append($('<span/>').addClass('def-tag tag-generic').html('<?php esc_html_e( "You", wp_defender()->domain ) ?>'))
                            }
                            user_row.append($('<a/>').attr({
                                'data-id': data.user_id,
                                'class': 'remove float-r wd-remove-recipient',
                                'href': '#'
                            }).html('<?php esc_html_e( "Remove", wp_defender()->domain ) ?>'))

                            $('.receipt ul').append(user_row);
                            $("#wd-username-search").trigger('results:clear');
                            $("#wd-username-search").val('');
                        }
                    })
                    return false;
                })
                $('body').on('click', '.wd-remove-recipient', function (e) {
                    e.preventDefault();
                    var that = $(this);
                    $.ajax({
                        type: 'POST',
                        url: ajaxurl,
                        data: {
                            action: 'remove_receipt_<?php echo $this->eId ?>',
                            'id': '<?php echo $this->eId ?>',
                            user: that.data('id')
                        },
                        beforeSend: function () {
                            that.attr('disabled', 'disabled')
                        },
                        success: function (data) {
                            that.closest('li').remove();
                        }
                    })
                })
            })
        </script>
		<?php
	}
}