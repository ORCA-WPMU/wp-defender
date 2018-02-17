<?php

namespace WP_Defender\Module\Scan\Model;

use Hammer\WP\Model;
use WP_Defender\Module\Scan\Component\Scan_Api;

/**
 * This is the scan result item. Each item will be a post.
 *
 * Base on item type, we will attach the behavior to process
 *
 * Class Result_Item
 * @package WP_Defender\Module\Scan\Model
 */
class Result_Item extends Model {
	const STATUS_ISSUE = 'issue', STATUS_FIXED = 'fixed', STATUS_IGNORED = 'ignored';
	protected static $post_type = 'wdf_scan_item';
	/**
	 * @var int
	 */
	public $id;
	/**
	 * This should be the behavior slug
	 * @var string
	 */
	public $type;
	/**
	 * status of this item, can be fixed, ignore or issue
	 *
	 * @var string
	 */
	public $status;
	/**
	 * ID of the scan
	 * @var
	 */
	public $parentId;

	/**
	 * @var int
	 */
	public $dateFixed;
	/**
	 * @var int
	 */
	public $dateIgnored;

	/**
	 * @var array
	 */
	public $raw;

	protected static function maps() {
		return array(
			'id'          => array(
				'type' => 'wp',
				'map'  => 'ID'
			),
			'type'        => array(
				'type' => 'meta',
				'map'  => 'type'
			),
			'status'      => array(
				'type' => 'wp',
				'map'  => 'post_status'
			),
			'parentId'    => array(
				'type' => 'wp',
				'map'  => 'post_parent'
			),
			'dateFixed'   => array(
				'type' => 'meta',
				'map'  => 'dateFixed'
			),
			'dateIgnored' => array(
				'type' => 'meta',
				'map'  => 'dateIgnored'
			),
			'raw'         => array(
				'type' => 'meta',
				'map'  => 'raw'
			),
		);
	}

	/**
	 * Add this status to ignore, also we will need to cache the ignore as globally
	 */
	public function ignore() {
		$this->status      = self::STATUS_IGNORED;
		$this->dateIgnored = date( 'Y-m-d H:i:s' );
		$this->save();

		//upadte to global ignore cache
		Scan_Api::indexIgnore( $this->id );
	}

	/**
	 * mark this as resolved
	 */
	public function markAsResolved() {
		$this->status    = self::STATUS_FIXED;
		$this->dateFixed = date( 'Y-m-d H:i:s' );
		$this->save();
	}

	/**
	 * Un ignore this
	 */
	public function unignore() {
		$this->status = self::STATUS_ISSUE;
		$this->save();

		Scan_Api::unIndexIgnore( $this->id );
	}

	public function behaviors() {
		switch ( $this->type ) {
			case 'core':
				return array(
					'coreResult' => '\WP_Defender\Module\Scan\Behavior\Core_Result',
					'utils'      => '\WP_Defender\Behavior\Utils'
				);
				break;
			case 'vuln':
				return array(
					'vulnResult' => '\WP_Defender\Module\Scan\Behavior\Pro\Vuln_Result',
					'utils'      => '\WP_Defender\Behavior\Utils'
				);
				break;
			case 'content':
				return array(
					'contentResult' => '\WP_Defender\Module\Scan\Behavior\Pro\Content_Result',
					'utils'         => '\WP_Defender\Behavior\Utils'
				);
				break;
		}
	}
}