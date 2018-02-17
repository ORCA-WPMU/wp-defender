<?php
/**
 * Author: Hoang Ngo
 */

namespace WP_Defender\Module\Scan\Model;

use Hammer\Helper\Log_Helper;
use Hammer\WP\Model;
use WP_Defender\Module\Scan\Behavior\Core_Result;

/**
 * Scan model will have attributes
 *
 * id
 * status
 * count files
 * error message
 * date start
 * date finished
 * logs
 *
 * Data will require in cache
 * Core files
 * Content files
 * Files not belong to core - we will scan those file for detecting suspicious
 * Md5 tree
 *
 * Class Scan
 * @package WP_Defender\Module\Scan\Model
 */
class Scan extends Model {
	const STATUS_INIT = 'init', STATUS_PROCESS = 'process', STATUS_ERROR = 'error', STATUS_FINISH = 'finish';

	static $post_type = 'wdf_scan';

	/**
	 * Id
	 * @var int
	 */
	public $id;
	/**
	 * Current status
	 * @var string
	 */
	public $status;
	/**
	 * @var string
	 */
	public $statusText;
	/**
	 * Error message, in string
	 * @var string
	 */
	public $error;
	/**
	 * Total files count for scanning
	 * @var int
	 */
	public $filesCount;
	/**
	 * @var string
	 */
	public $logs;
	/**
	 * Unix timestamp
	 * @var int
	 */
	public $dateStart;
	/**
	 * Unix timestamp
	 * @var int
	 */
	public $dateFinished;

	/**
	 * @return array
	 */
	protected static function maps() {
		return array(
			'id'           => array(
				'type' => 'wp',
				'map'  => 'ID'
			),
			'status'       => array(
				'type' => 'wp',
				'map'  => 'post_status'
			),
			'statusText'   => array(
				'type' => 'wp',
				'map'  => 'post_content'
			),
			'error'        => array(
				'type' => 'meta',
				'map'  => 'error'
			),
			'filesCount'   => array(
				'type' => 'meta',
				'map'  => 'files_count'
			),
			'logs'         => array(
				'type' => 'meta',
				'map'  => 'logs'
			),
			'dateStart'    => array(
				'type' => 'wp',
				'map'  => 'post_date_gmt'
			),
			'dateFinished' => array(
				'type' => 'wp',
				'map'  => 'post_modified_gmt'
			)
		);
	}

	/**
	 * @param $offset int
	 * @param $type string issues|cleaned|ignored
	 *
	 * @return Result_Item[]
	 */
	public function getItems( $offset = 0, $type = Result_Item::STATUS_ISSUE ) {
		$items = Result_Item::findAll( array(
			'parentId' => $this->id,
			'status'   => $type
		), null, null, $offset );

		return $items;
	}

	/**
	 * @param $type
	 *
	 * @return null|string
	 */
	public function countAll( $type ) {
		$scanTypes = Settings::instance()->getScansAvailable();
		if ( empty( $scanTypes ) ) {
			$scanTypes[] = 'core';
		}
		$count = Result_Item::count( array(
			'parentId' => $this->id,
			'status'   => $type,
			'type'     => $scanTypes
		) );

		return $count;
	}

	/**
	 * This is deifferent from all countAllxxx function, only for counting issues category
	 *
	 * @param $type
	 *
	 * @return int
	 */
	public function getCount( $type ) {
		switch ( $type ) {
			case 'core':
				return Result_Item::count( array(
					'parentId' => $this->id,
					'status'   => Result_Item::STATUS_ISSUE,
					'type'     => 'core'
				) );
			case 'content':
				return Result_Item::count( array(
					'parentId' => $this->id,
					'status'   => Result_Item::STATUS_ISSUE,
					'type'     => 'content'
				) );
			case 'vuln':
				return Result_Item::count( array(
					'parentId' => $this->id,
					'status'   => Result_Item::STATUS_ISSUE,
					'type'     => 'vuln'
				) );
		}
	}

	/**
	 * @return array
	 */
	public function events() {
		$that = $this;

		return array(
			self::EVENT_AFTER_DELETE => array(
				array(
					//clean all items relate to this
					function () use ( $that ) {
						$models = Result_Item::findAll( array(
							'parentId' => $that->id
						) );
						foreach ( $models as $model ) {
							$model->delete();
						}
					}
				)
			)
		);
	}
}