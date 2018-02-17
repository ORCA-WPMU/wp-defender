<?php
namespace WP_Defender\Module\Audit\Component;
use WP_Defender\Behavior\Utils;
use WP_Defender\Module\Audit\Event_Abstract;

/**
 * Author: Hoang Ngo
 */
class Comment_Audit extends Event_Abstract {
	const ACTION_SPAMMED = 'spammed', ACTION_UNSPAMMED = 'unspammed', ACTION_DUPLICATED = 'duplicated', ACTION_FLOOD = 'flood';
	const CONTEXT_COMMENT = 'ct_comment';
	protected $type = 'comment';

	public function get_hooks() {
		return array(
			'wp_insert_comment'         => array(
				'args'        => array( 'comment_ID' ),
				'level'       => self::LOG_LEVEL_INFO,
				'event_type'  => $this->type,
				'action_type' => Audit_API::ACTION_ADDED,
				'callback'    => array( '\WP_Defender\Module\Audit\Component\Comment_Audit', 'processGenericComment' )
			),
			'comment_flood_trigger'     => array(
				'args'        => array( 'time_lastcomment', 'time_newcomment' ),
				'level'       => self::LOG_LEVEL_NOTICE,
				'event_type'  => $this->type,
				'action_type' => self::ACTION_FLOOD,
				'context'     => self::CONTEXT_COMMENT,
				'text'        => sprintf( esc_html__( "User %s flooded comment", wp_defender()->domain ), '{{wp_user}}' ),
			),
			'deleted_comment'           => array(
				'args'        => array( 'comment_ID' ),
				'level'       => self::LOG_LEVEL_INFO,
				'event_type'  => $this->type,
				'action_type' => Audit_API::ACTION_DELETED,
				'callback'    => array( '\WP_Defender\Module\Audit\Component\Comment_Audit', 'processGenericComment' ),
			),
			'trash_comment'             => array(
				'args'        => array( 'comment_ID' ),
				'level'       => self::LOG_LEVEL_INFO,
				'event_type'  => $this->type,
				'action_type' => Audit_API::ACTION_TRASHED,
				'callback'    => array( '\WP_Defender\Module\Audit\Component\Comment_Audit', 'processGenericComment' ),
			),
			'untrash_comment'           => array(
				'args'        => array( 'comment_ID' ),
				'level'       => self::LOG_LEVEL_INFO,
				'event_type'  => $this->type,
				'action_type' => Audit_API::ACTION_RESTORED,
				'callback'    => array( '\WP_Defender\Module\Audit\Component\Comment_Audit', 'processGenericComment' ),
			),
			'spam_comment'              => array(
				'args'        => array( 'comment_ID' ),
				'level'       => self::LOG_LEVEL_INFO,
				'event_type'  => $this->type,
				'action_type' => self::ACTION_SPAMMED,
				'callback'    => array( '\WP_Defender\Module\Audit\Component\Comment_Audit', 'processGenericComment' ),
			),
			'unspam_comment'            => array(
				'args'        => array( 'comment_ID' ),
				'level'       => self::LOG_LEVEL_INFO,
				'event_type'  => $this->type,
				'action_type' => self::ACTION_UNSPAMMED,
				'callback'    => array( '\WP_Defender\Module\Audit\Component\Comment_Audit', 'processGenericComment' ),
			),
			'transition_comment_status' => array(
				'args'        => array( 'new_status', 'old_status', 'comment' ),
				'level'       => self::LOG_LEVEL_INFO,
				'event_type'  => $this->type,
				'action_type' => Audit_API::ACTION_UPDATED,
				'callback'    => array( '\WP_Defender\Module\Audit\Component\Comment_Audit', 'processCommentStatusChanged' ),
			),
			'edit_comment'              => array(
				'args'        => array( 'comment_ID' ),
				'level'       => self::LOG_LEVEL_INFO,
				'event_type'  => $this->type,
				'action_type' => Audit_API::ACTION_UPDATED,
				'callback'    => array( '\WP_Defender\Module\Audit\Component\Comment_Audit', 'processGenericComment' ),
			),
			'comment_duplicate_trigger' => array(
				'args'        => array( 'commentdata' ),
				'level'       => self::LOG_LEVEL_INFO,
				'event_type'  => $this->type,
				'action_type' => self::ACTION_DUPLICATED,
				'callback'    => array( '\WP_Defender\Module\Audit\Component\Comment_Audit', 'processCommentDuplicateTrigger' ),
			),
		);
	}

	/**
	 * @return array
	 */
	public function dictionary() {
		return array(
			self::ACTION_DUPLICATED => esc_html__( "Duplicated", wp_defender()->domain ),
			self::ACTION_SPAMMED    => esc_html__( "Spammed", wp_defender()->domain ),
			self::ACTION_UNSPAMMED  => esc_html__( "Unspammed", wp_defender()->domain ),
			self::CONTEXT_COMMENT   => esc_html__( "Comment", wp_defender()->domain )
		);
	}

	/**
	 * @return array
	 */
	public static function processCommentDuplicateTrigger() {
		$args         = func_get_args();
		$comment_data = $args[1]['commentdata'];

		$post            = get_post( $comment_data['comment_post_ID'] );
		$post_type       = get_post_type_object( $post->post_type );
		$post_type_label = strtolower( $post_type->labels->singular_name );
		$text            = sprintf( esc_html__( "User %s submitted a duplicate comment on %s \"%s\"", wp_defender()->domain ), is_user_logged_in() ? Utils::instance()->getDisplayName( get_current_user_id() ) : $comment_data['comment_author'], $post_type_label, $post->post_title );

		return array( $text, $post_type_label );
	}

	/**
	 * @return bool|string
	 */
	public static function processCommentStatusChanged() {
		$args            = func_get_args();
		$new_stat        = $args[1]['new_status'];
		$old_stat        = $args[1]['old_status'];
		$comment         = $args[1]['comment'];
		$post            = get_post( $comment->comment_post_ID );
		$post_type       = get_post_type_object( $post->post_type );
		$post_type_label = strtolower( $post_type->labels->singular_name );
		$text            = false;
		if ( $old_stat == 'unapproved' && $new_stat == 'approved' ) {
			$text = sprintf( esc_html__( "%s approved comment ID %s from %s, on %s \"%s\"", wp_defender()->domain ), Utils::instance()->getDisplayName( get_current_user_id() ), $comment->comment_ID, $comment->comment_author, $post_type_label, $post->post_title );
		} elseif ( $new_stat == 'unapproved' && $old_stat == 'approved' ) {
			$text = sprintf( esc_html__( "%s unapproved comment ID %s from %s, on %s \"%s\"", wp_defender()->domain ), Utils::instance()->getDisplayName( get_current_user_id() ), $comment->comment_ID, $comment->comment_author, $post_type_label, $post->post_title );
		}

		return array( $text, $post_type_label );
	}

	/**
	 * @return bool|string
	 */
	public static function processGenericComment() {
		$args       = func_get_args();
		$hookname   = $args[0];
		$comment_id = $args[1]['comment_ID'];
		if ( ! isset( $args[1]['commentdata'] ) ) {
			$comment = get_comment( $comment_id );
			$comment = $comment->to_array();
		} else {
			$comment = $args[1]['commentdata'];
		}
		if ( ! isset( $args[1]['comment_approved'] ) ) {
			$comment_approved = '';
		} else {
			$comment_approved = $args[1]['comment_approved'];
		}
		$post            = get_post( $comment['comment_post_ID'] );
		$post_type       = get_post_type_object( $post->post_type );
		$post_type_label = strtolower( $post_type->labels->singular_name );
		switch ( $hookname ) {
			case 'comment_post':
				if ( $comment_approved === 'spam' ) {
					$comment_status = 'spam';
				} elseif ( $comment_approved === 1 ) {
					$comment_status = esc_html__( "approved", wp_defender()->domain );
				} else {
					$comment_status = esc_html__( "pending approval", wp_defender()->domain );
				}
				if ( $comment['comment_parent'] == 0 ) {
					$text = sprintf( esc_html__( "%s commented on %s \"%s\" - comment status: %s", wp_defender()->domain ),
						$comment['comment_author'], $post_type_label, $post->post_title, $comment_status );
				} else {
					$parent_comment = get_comment( $comment['comment_parent'] );
					$text           = sprintf( esc_html__( "%s replied to %s's comment on %s \"%s\" - comment status: %s", wp_defender()->domain ),
						$comment['comment_author'], $parent_comment->comment_author, $post_type_label, $post->post_title, $comment_status );
				}
				break;
			case 'wp_insert_comment':
				if ( $comment_approved === 'spam' ) {
					$comment_status = 'spam';
				} elseif ( $comment_approved === 1 ) {
					$comment_status = esc_html__( "approved", wp_defender()->domain );
				} else {
					$comment_status = esc_html__( "pending approval", wp_defender()->domain );
				}
				if ( $comment['comment_parent'] == 0 ) {
					$text = sprintf( esc_html__( "%s commented on %s \"%s\" - comment status: %s", wp_defender()->domain ),
						$comment['comment_author'], $post_type_label, $post->post_title, $comment_status );
				} else {
					$parent_comment = get_comment( $comment['comment_parent'] );
					$text           = sprintf( esc_html__( "%s replied to %s's comment on %s \"%s\" - comment status: %s", wp_defender()->domain ),
						$comment['comment_author'], $parent_comment->comment_author, $post_type_label, $post->post_title, $comment_status );
				}
				break;
			case 'deleted_comment':
				$text = sprintf( esc_html__( "%s deleted comment ID %s, comment author: %s on %s \"%s\"", wp_defender()->domain ),
					Utils::instance()->getDisplayName( get_current_user_id() ), $comment_id, $comment['comment_author'], $post_type_label, $post->post_title );
				break;
			case 'trash_comment':
				$text = sprintf( esc_html__( "%s trashed comment ID %s, comment author: %s on %s \"%s\"", wp_defender()->domain ),
					Utils::instance()->getDisplayName( get_current_user_id() ), $comment_id, $comment['comment_author'], $post_type_label, $post->post_title );
				break;
			case 'untrash_comment':
				$text = sprintf( esc_html__( "%s untrashed comment ID %s, comment author: %s on %s \"%s\"", wp_defender()->domain ),
					Utils::instance()->getDisplayName( get_current_user_id() ), $comment_id, $comment['comment_author'], $post_type_label, $post->post_title );
				break;
			case 'spam_comment':
				$text = sprintf( esc_html__( "%s marked comment ID %s, comment author: %s on %s \"%s\" as spam", wp_defender()->domain ),
					Utils::instance()->getDisplayName( get_current_user_id() ), $comment_id, $comment['comment_author'], $post_type_label, $post->post_title );
				break;
			case 'unspam_comment':
				$text = sprintf( esc_html__( "%s unmarked comment ID %s, comment author: %s on %s \"%s\" as spam", wp_defender()->domain ),
					Utils::instance()->getDisplayName( get_current_user_id() ), $comment_id, $comment['comment_author'], $post_type_label, $post->post_title );
				break;
			case 'edit_comment':
				$text = sprintf( esc_html__( "%s edited comment ID %s, comment author: %s on %s \"%s\"", wp_defender()->domain ),
					Utils::instance()->getDisplayName( get_current_user_id() ), $comment_id, $comment['comment_author'], $post_type_label, $post->post_title );
				break;
			default:
				return false;
		}

		return array( $text, $post_type_label );
	}
}