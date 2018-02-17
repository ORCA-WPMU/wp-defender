<?php
/**
 * Author: Hoang Ngo
 */

namespace WP_Defender\Module\Audit\Component;

use WP_Defender\Behavior\Utils;
use WP_Defender\Module\Audit\Event_Abstract;

class Options_Audit extends Event_Abstract {
	const CONTEXT_SETTINGS = 'ct_setting';

	public function get_hooks() {
		return array(
			'update_option' => array(
				'args'        => array( 'option', 'old_value', 'value' ),
				'callback'    => array( '\WP_Defender\Module\Audit\Component\Options_Audit', 'process_options' ),
				'level'       => self::LOG_LEVEL_ERROR,
				'event_type'  => 'settings',
				'action_type' => Audit_API::ACTION_UPDATED,
			),
			/*'update_site_option' => array(
				'args'        => array( 'option', 'old_value', 'value' ),
				'callback'    => array( 'WD_Options_Audit', 'process_network_options' ),
				'level'       => self::LOG_LEVEL_ERROR,
				'event_type'  => 'settings',
				'action_type' => Audit_API::ACTION_UPDATED,
			)*/
		);
	}

	public static function process_network_options() {
		$args   = func_get_args();
		$option = $args[1]['option'];
		$old    = $args[1]['old_value'];
		$new    = $args[1]['value'];

		$option_human_read = self::key_to_human_name( $option );

		if ( $old == $new ) {
			return false;
		}

		if ( is_array( $old ) ) {
			$old = implode( ', ', $old );
		}

		if ( is_array( $new ) ) {
			$new = implode( ', ', $new );
		}

		$text = sprintf( esc_html__( "%s update network option %s from %s to %s", wp_defender()->domain ),
			Utils::instance()->getDisplayName( get_current_user_id() ), $option_human_read, $old, $new );

		return array( $text, self::CONTEXT_SETTINGS );
	}

	/**
	 * @return bool|string
	 */
	public static function process_options() {
		$args              = func_get_args();
		$option            = $args[1]['option'];
		$old               = $args[1]['old_value'];
		$new               = $args[1]['value'];
		$option_human_read = self::key_to_human_name( $option );

		//to avoid the recursing compare if both are nested array, convert all to string
		$check1 = is_array( $old ) ? serialize( $old ) : $old;
		$check2 = is_array( $new ) ? serialize( $new ) : $new;

		if ( $check1 == $check2 ) {
			return false;
		}
		if ( $option_human_read !== false ) {
			//we will need special case for reader
			switch ( $option ) {
				case 'users_can_register':
					if ( $new == 0 ) {
						$text = sprintf( esc_html__( "%s disabled site registration", wp_defender()->domain ), Utils::instance()->getDisplayName( get_current_user_id() ) );
					} else {
						$text = sprintf( esc_html__( "%s opened site registration", wp_defender()->domain ), Utils::instance()->getDisplayName( get_current_user_id() ) );
					}
					break;
				case 'start_of_week':
					global $wp_locale;
					$old_day = $wp_locale->get_weekday( $old );
					$new_day = $wp_locale->get_weekday( $new );
					$text    = sprintf( esc_html__( "%s update option %s from %s to %s", wp_defender()->domain ),
						Utils::instance()->getDisplayName( get_current_user_id() ), $option_human_read, $old_day, $new_day );
					break;
				case 'WPLANG':
					//no old value here
					$text = sprintf( esc_html__( "%s update option %s to %s", wp_defender()->domain ),
						Utils::instance()->getDisplayName( get_current_user_id() ), $option_human_read, $old, $new );
					break;
				default:
					$text = sprintf( esc_html__( "%s update option %s from %s to %s", wp_defender()->domain ),
						Utils::instance()->getDisplayName( get_current_user_id() ), $option_human_read, $old, $new );
					break;
			}

			return array( $text, self::CONTEXT_SETTINGS );
		}

		return false;
	}

	private static function key_to_human_name( $key ) {
		$human_read = apply_filters( 'wd_audit_settings_keys', array(
			'blogname'                      => esc_html__( "Site Title", wp_defender()->domain ),
			'blogdescription'               => esc_html__( "Tagline", wp_defender()->domain ),
			'gmt_offset'                    => esc_html__( "Timezone", wp_defender()->domain ),
			'date_format'                   => esc_html__( "Date Format", wp_defender()->domain ),
			'time_format'                   => esc_html__( "Time Format", wp_defender()->domain ),
			'start_of_week'                 => esc_html__( "Week Starts On", wp_defender()->domain ),
			'timezone_string'               => esc_html__( "Timezone", wp_defender()->domain ),
			'WPLANG'                        => esc_html__( "Site Language", wp_defender()->domain ),
			'siteurl'                       => esc_html__( "WordPress Address (URL)", wp_defender()->domain ),
			'home'                          => esc_html__( "Site Address (URL)", wp_defender()->domain ),
			'admin_email'                   => esc_html__( "Email Address", wp_defender()->domain ),
			'users_can_register'            => esc_html__( "Membership", wp_defender()->domain ),
			'default_role'                  => esc_html__( "New User Default Role", wp_defender()->domain ),
			'default_pingback_flag'         => esc_html__( "Default article settings", wp_defender()->domain ),
			'default_ping_status'           => esc_html__( "Default article settings", wp_defender()->domain ),
			'default_comment_status'        => esc_html__( "Default article settings", wp_defender()->domain ),
			'comments_notify'               => esc_html__( "Email me whenever", wp_defender()->domain ),
			'moderation_notify'             => esc_html__( "Email me whenever", wp_defender()->domain ),
			'comment_moderation'            => esc_html__( "Before a comment appears", wp_defender()->domain ),
			'require_name_email'            => esc_html__( "Other comment settings", wp_defender()->domain ),
			'comment_whitelist'             => esc_html__( "Before a comment appears", wp_defender()->domain ),
			'comment_max_links'             => esc_html__( "Comment Moderation", wp_defender()->domain ),
			'moderation_keys'               => esc_html__( "Comment Moderation", wp_defender()->domain ),
			'blacklist_keys'                => esc_html__( "Comment Blacklist", wp_defender()->domain ),
			'show_avatars'                  => esc_html__( "Avatar Display", wp_defender()->domain ),
			'avatar_rating'                 => esc_html__( "Maximum Rating", wp_defender()->domain ),
			'avatar_default'                => esc_html__( "Default Avatar", wp_defender()->domain ),
			'close_comments_for_old_posts'  => esc_html__( "Other comment settings", wp_defender()->domain ),
			'close_comments_days_old'       => esc_html__( "Other comment settings", wp_defender()->domain ),
			'thread_comments'               => esc_html__( "Other comment settings", wp_defender()->domain ),
			'thread_comments_depth'         => esc_html__( "Other comment settings", wp_defender()->domain ),
			'page_comments'                 => esc_html__( "Other comment settings", wp_defender()->domain ),
			'comments_per_page'             => esc_html__( "Other comment settings", wp_defender()->domain ),
			'default_comments_page'         => esc_html__( "Other comment settings", wp_defender()->domain ),
			'comment_order'                 => esc_html__( "Other comment settings", wp_defender()->domain ),
			'comment_registration'          => esc_html__( "Other comment settings", wp_defender()->domain ),
			'thumbnail_size_w'              => esc_html__( "Thumbnail size", wp_defender()->domain ),
			'thumbnail_size_h'              => esc_html__( "Thumbnail size", wp_defender()->domain ),
			'thumbnail_crop'                => esc_html__( "Thumbnail size", wp_defender()->domain ),
			'medium_size_w'                 => esc_html__( "Medium size", wp_defender()->domain ),
			'medium_size_h'                 => esc_html__( "Medium size", wp_defender()->domain ),
			'medium_large_size_w'           => esc_html__( "Medium size", wp_defender()->domain ),
			'medium_large_size_h'           => esc_html__( "Medium size", wp_defender()->domain ),
			'large_size_w'                  => esc_html__( "Large size", wp_defender()->domain ),
			'large_size_h'                  => esc_html__( "Large size", wp_defender()->domain ),
			'image_default_size'            => esc_html__( "", wp_defender()->domain ),
			'image_default_align'           => esc_html__( "", wp_defender()->domain ),
			'image_default_link_type'       => esc_html__( "", wp_defender()->domain ),
			'uploads_use_yearmonth_folders' => esc_html__( "Uploading Files", wp_defender()->domain ),
			'posts_per_page'                => esc_html__( "Blog pages show at most", wp_defender()->domain ),
			'posts_per_rss'                 => esc_html__( "Syndication feeds show the most recent", wp_defender()->domain ),
			'rss_use_excerpt'               => esc_html__( "For each article in a feed, show", wp_defender()->domain ),
			'show_on_front'                 => esc_html__( "Front page displays", wp_defender()->domain ),
			'page_on_front'                 => esc_html__( "Front page", wp_defender()->domain ),
			'page_for_posts'                => esc_html__( "Posts page", wp_defender()->domain ),
			'blog_public'                   => esc_html__( "Search Engine Visibility", wp_defender()->domain ),
			'default_category'              => esc_html__( "Default Post Category", wp_defender()->domain ),
			'default_email_category'        => esc_html__( "Default Mail Category", wp_defender()->domain ),
			'default_link_category'         => esc_html__( "", wp_defender()->domain ),
			'default_post_format'           => esc_html__( "Default Post Format", wp_defender()->domain ),
			'mailserver_url'                => esc_html__( "Mail Server", wp_defender()->domain ),
			'mailserver_port'               => esc_html__( "Port", wp_defender()->domain ),
			'mailserver_login'              => esc_html__( "Login Name", wp_defender()->domain ),
			'mailserver_pass'               => esc_html__( "Password", wp_defender()->domain ),
			'ping_sites'                    => esc_html__( "", wp_defender()->domain ),
			'permalink_structure'           => esc_html__( "Permalink Setting", wp_defender()->domain ),
			'category_base'                 => esc_html__( "Category base", wp_defender()->domain ),
			'tag_base'                      => esc_html__( "Tag base", wp_defender()->domain ),
			'registrationnotification'      => esc_html__( "Registration notification", wp_defender()->domain ),
			'registration'                  => esc_html__( "Allow new registrations", wp_defender()->domain ),
			'add_new_users'                 => esc_html__( "Add New Users", wp_defender()->domain ),
			'menu_items'                    => esc_html__( "Enable administration menus", wp_defender()->domain ),
			'upload_space_check_disabled'   => esc_html__( "Site upload space", wp_defender()->domain ),
			'blog_upload_space'             => esc_html__( "Site upload space", wp_defender()->domain ),
			'upload_filetypes'              => esc_html__( "Upload file types", wp_defender()->domain ),
			'site_name'                     => esc_html__( "Network Title", wp_defender()->domain ),
			'first_post'                    => esc_html__( "First Post", wp_defender()->domain ),
			'first_page'                    => esc_html__( "First Page", wp_defender()->domain ),
			'first_comment'                 => esc_html__( "First Comment", wp_defender()->domain ),
			'first_comment_url'             => esc_html__( "First Comment URL", wp_defender()->domain ),
			'first_comment_author'          => esc_html__( "First Comment Author", wp_defender()->domain ),
			'welcome_email'                 => esc_html__( "Welcome Email", wp_defender()->domain ),
			'welcome_user_email'            => esc_html__( "Welcome User Email", wp_defender()->domain ),
			'fileupload_maxk'               => esc_html__( "Max upload file size", wp_defender()->domain ),
			//'global_terms_enabled'          => esc_html__( "", wp_defender()->domain ),
			'illegal_names'                 => esc_html__( "Banned Names", wp_defender()->domain ),
			'limited_email_domains'         => esc_html__( "Limited Email Registrations", wp_defender()->domain ),
			'banned_email_domains'          => esc_html__( "Banned Email Domains", wp_defender()->domain ),
		) );

		if ( isset( $human_read[ $key ] ) ) {
			if ( empty( $human_read[ $key ] ) ) {
				return $key;
			}

			return $human_read[ $key ];
		}

		return false;
	}

	public function dictionary() {
		return array(
			self::CONTEXT_SETTINGS => esc_html__( "Settings", wp_defender()->domain )
		);
	}
}