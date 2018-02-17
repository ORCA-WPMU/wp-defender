<?php

/**
 * Author: Hoang Ngo
 */
namespace WP_Defender\Module\Audit\Component;

use WP_Defender\Module\Audit\Event_Abstract;

class Media_Audit extends Event_Abstract {
	const ACTION_UPLOADED = 'uploaded';
	protected $type = 'media';

	public function get_hooks() {
		return array(
			'add_attachment'     => array(
				'args'         => array( 'post_ID' ),
				'level'        => self::LOG_LEVEL_INFO,
				'event_type'   => $this->type,
				'action_type'  => self::ACTION_UPLOADED,
				'text'         => sprintf( esc_html__( "%s uploaded a file: \"%s\" to Media Library", wp_defender()->domain ), '{{wp_user}}', '{{file_path}}' ),
				'program_args' => array(
					'file_path'  => array(
						'callable' => 'get_post_meta',
						'params'   => array(
							'{{post_ID}}',
							'_wp_attached_file',
							true
						),
					),
					'mime_type' => array(
						'callable' => array( '\WP_Defender\Module\Audit\Component\Media_Audit', 'get_mime_type' ),
						'params'   => array(
							'{{post_ID}}'
						)
					)
				),
				'context'      => '{{mime_type}}'
			),
			'attachment_updated' => array(
				'args'         => array( 'post_ID' ),
				'level'        => self::LOG_LEVEL_INFO,
				'action_type'  => Audit_API::ACTION_UPDATED,
				'event_type'   => $this->type,
				'text'         => sprintf( esc_html__( "%s updated a file: \"%s\" from Media Library", wp_defender()->domain ), '{{wp_user}}', '{{file_path}}' ),
				'program_args' => array(
					'file_path' => array(
						'callable' => 'get_post_meta',
						'params'   => array(
							'{{post_ID}}',
							'_wp_attached_file',
							true
						),
					),
					'mime_type' => array(
						'callable' => array( '\WP_Defender\Module\Audit\Component\Media_Audit', 'get_mime_type' ),
						'params'   => array(
							'{{post_ID}}'
						)
					)
				),
				'context'      => '{{mime_type}}'
			),
			'delete_attachment'  => array(
				'args'         => array( 'post_ID' ),
				'level'        => self::LOG_LEVEL_INFO,
				'action_type'  => Audit_API::ACTION_DELETED,
				'event_type'   => $this->type,
				'text'         => sprintf( esc_html__( "%s deleted a file: \"%s\" from Media Library", wp_defender()->domain ), '{{wp_user}}', '{{file_path}}' ),
				'program_args' => array(
					'file_path' => array(
						'callable' => 'get_post_meta',
						'params'   => array(
							'{{post_ID}}',
							'_wp_attached_file',
							true
						),
					),
					'mime_type' => array(
						'callable' => array( '\WP_Defender\Module\Audit\Component\Media_Audit', 'get_mime_type' ),
						'params'   => array(
							'{{post_ID}}'
						)
					)
				),
				'context'      => '{{mime_type}}'
			),
		);
	}

	public function dictionary() {
		return array(
			self::ACTION_UPLOADED => esc_html__( "Uploaded", wp_defender()->domain )
		);
	}

	public static function get_mime_type( $post_ID ) {
		$file_path = get_post_meta( $post_ID, '_wp_attached_file', true );

		return pathinfo( $file_path, PATHINFO_EXTENSION );
	}
}