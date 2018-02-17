<?php

namespace WP_Defender\Module\Scan\Component;

use Hammer\Base\Component;
use Hammer\Base\Container;
use Hammer\Helper\File_Helper;
use Hammer\Helper\Log_Helper;
use Hammer\Helper\WP_Helper;
use WP_Defender\Behavior\Utils;
use WP_Defender\Component\Error_Code;
use WP_Defender\Module\Scan\Behavior\Pro\Content_Scan;
use WP_Defender\Module\Scan\Model\Result_Item;
use WP_Defender\Module\Scan\Model\Scan;
use WP_Defender\Module\Scan\Model\Settings;

/**
 * Here contains every function need for scanning module
 * Class Scan_Api
 * @package WP_Defender\Module\Scan\Component
 */
class Scan_Api extends Component {
	const CACHE_CORE = 'wdfcore', CACHE_CONTENT = 'wdfcontent', CACHE_CHECKSUMS = 'wdfchecksum';
	const IGNORE_LIST = 'wdfscanignore', SCAN_PATTERN = 'wdfscanparttern';

	private static $ignoreList = false;

	/**
	 * @return Scan|\WP_Error
	 */
	public static function createScan() {
		if ( is_null( self::getActiveScan() ) ) {
			self::flushCache( false );

			$model             = new Scan();
			$model->status     = Scan::STATUS_INIT;
			$model->statusText = __( "Initializing...", wp_defender()->domain );
			$model->save();

			return $model;
		} else {
			return new \WP_Error( Error_Code::INVALID, __( "A scan is already in progress", wp_defender()->domain ) );
		}
	}

	/**
	 * Check if this module is active
	 */
	public static function isActive() {
		return Settings::instance()->notification;
	}

	/**
	 * @return null|Scan
	 */
	public static function getActiveScan() {
		$cache = WP_Helper::getArrayCache();
		if ( $cache->exists( 'activeScan' ) ) {
			return $cache->get( 'activeScan' );
		}
		$model = Scan::findOne( array(
			'status' => array(
				Scan::STATUS_INIT,
				Scan::STATUS_ERROR,
				Scan::STATUS_PROCESS
			)
		) );

		$cache->set( 'activeScan', $model );

		return $model;
	}

	/**
	 * @return null|Scan
	 */
	public static function getLastScan() {
		$cache = WP_Helper::getArrayCache();
		if ( $cache->exists( 'lastScan' ) ) {
			return $cache->get( 'lastScan' );
		}
		$model = Scan::findOne( array(
			'status' => array(
				Scan::STATUS_FINISH
			)
		), 'ID', 'DESC' );

		$cache->set( 'lastScan', $model );


		return $model;
	}

	/**
	 * @return array
	 */
	public static function getCoreFiles() {
		/**
		 * We we will get one level files & folder inside root, all files inside
		 */
		$cache  = Container::instance()->get( 'cache' );
		$cached = $cache->get( self::CACHE_CORE, false );
		if ( is_array( $cached ) && ! empty( $cached ) ) {
			return $cached;
		}

		$settings        = Settings::instance();
		$firstLevelFiles = File_Helper::findFiles( ABSPATH, true, true, array(
			'dir'  => array(
				ABSPATH . 'wp-content',
				ABSPATH . 'wp-admin',
				ABSPATH . 'wp-includes'
			),
			'path' => array(
				ABSPATH . 'wp-config.php'
			)
		), array(), false, $settings->max_filesize );
		$coreFiles       = File_Helper::findFiles( ABSPATH, true, false, array(), array(
			'dir' => array(
				ABSPATH . 'wp-admin',
				ABSPATH . 'wp-includes',
			)
		), true, $settings->max_filesize );

		$cache->set( self::CACHE_CORE, array_merge( $firstLevelFiles, $coreFiles ), 0 );

		return array_merge( $firstLevelFiles, $coreFiles );
	}

	/**
	 * @return array
	 */
	public static function getContentFiles() {
		$cache  = Container::instance()->get( 'cache' );
		$cached = $cache->get( self::CACHE_CONTENT, false );
		if ( is_array( $cached ) && ! empty( $cached ) ) {
			return $cached;
		}
		$settings = Settings::instance();
		$files    = File_Helper::findFiles( WP_CONTENT_DIR, true, false, array(), array(
			'ext' => array( 'php' )
		), true, $settings->max_filesize );
		//include wp-config.php here
		$files[] = ABSPATH . 'wp-config.php';

		$cache->set( self::CACHE_CONTENT, $files );

		return $files;
	}

	/**
	 * Get checksums
	 * @return array|bool
	 */
	public static function getCoreChecksums() {
		$cache  = Container::instance()->get( 'cache' );
		$cached = $cache->get( self::CACHE_CHECKSUMS, false );
		if ( is_array( $cached ) && ! empty( $cached ) ) {
			return $cached;
		}

		global $wp_version, $wp_local_package;
		$locale = 'en_US';
		if ( ! is_null( $wp_local_package ) && count( explode( '_', $wp_local_package ) ) == 2 ) {
			$locale = $wp_local_package;
		}
		if ( ! function_exists( 'get_core_checksums' ) ) {
			include_once ABSPATH . 'wp-admin/includes/update.php';
		}
		$checksum = get_core_checksums( $wp_version, $locale );
		if ( $checksum == false ) {
			return $checksum;
		}

		if ( isset( $checksum[ $wp_version ] ) ) {
			return $checksum = $checksum[ $wp_version ];
		}

		$cache->set( self::CACHE_CHECKSUMS, $checksum, 86400 );

		return $checksum;
	}

	/**
	 * Processing a scan, return bool if done or not
	 * @return bool|\WP_Error
	 */
	public static function processActiveScan() {
		$model = self::getActiveScan();
		$start = microtime( true );
		if ( ! is_object( $model ) ) {
			return new \WP_Error( Error_Code::INVALID, __( "No scan record exists", wp_defender()->domain ) );
		}

		if ( $model->status == Scan::STATUS_ERROR ) {
			//stop scan
			self::releaseLock();

			return new \WP_Error( Error_Code::SCAN_ERROR, $model->statusText );
		}

		$settings = Settings::instance();
		$steps    = $settings->getScansAvailable();
		$done     = 0;
		if ( self::isLock() ) {
			//locking
			return false;
		} else {
			//create a safe lock
			self::createLock();
		}

		/**
		 * loop through scanning steps, instance scan step as queue and process
		 */
		foreach ( $steps as $step ) {
			$queue = Settings::queueFactory( $step, array(
				'model'      => $model,
				'ignoreList' => self::getIgnoreList()
			) );

			if ( ! is_object( $queue ) || $queue->isEnd() ) {
				$done ++;
				continue;
			}

			$lastPost = $queue->key();
			if ( $lastPost == 0 ) {
				//this is newly, we will update the status text here
				switch ( $step ) {
					case 'core':
						$model->statusText = __( "Analyzing WordPress Core...", wp_defender()->domain );
						break;
					case 'content':
						$model->statusText = __( "Analyzing WordPress Content...", wp_defender()->domain );
						break;
					case 'vuln':
						$model->statusText = __( "Checking for any published vulnerabilities your plugins & themes...", wp_defender()->domain );
						break;
				}
				$model->save();
			}
			while ( ! $queue->isEnd() ) {
				//while in the loop, the model can be set as ERROR, check and return of Error
				if ( $queue->processItem() == false ) {
					//we will by pass this if the process is fail
					//todo we will output the error and let user know
					$queue->next();
					$queue->saveProcess();
					self::releaseLock();

					return false;
				} else {
					//each request onlly allow 10s, or when reached to 64MB ram
					$est      = microtime( true ) - $start;
					$currMem  = ( memory_get_peak_usage( true ) / 1024 / 1024 );
					$memLimit = apply_filters( 'defender_scan_memory_alloc', 128 );
					if ( $est >= 15 || $currMem >= $memLimit || $queue->isEnd() || $queue->key() == 1 ) {
						//save current process and pause
						$queue->saveProcess();

						//unlock before return
						self::releaseLock();
						//we have to cache the checksum of content here
						if ( $step == 'content' ) {
							$altCache    = WP_Helper::getArrayCache();
							$oldChecksum = $altCache->get( Content_Scan::CONTENT_CHECKSUM, null );
							$tries       = $altCache->get( Content_Scan::FILES_TRIED, null );
							$cache       = WP_Helper::getCache();
							if ( is_array( $oldChecksum ) ) {
								$cache->set( Content_Scan::CONTENT_CHECKSUM, $oldChecksum );
							}
							if ( is_array( $tries ) ) {
								$cache->set( Content_Scan::FILES_TRIED, $tries );
							}
						}

						return false;
					}
				}
			}
			//break at the end to prevent it stuck so long when init, also the heavy part is in the while loop
			break;
		}

		if ( $done == count( $steps ) ) {
			//all done
			//remove all old records
			$lastScan = self::getLastScan();
			if ( is_object( $lastScan ) ) {
				$lastScan->delete();
			}
			//mark the current as complted
			$model->status = Scan::STATUS_FINISH;
			$model->save();
			if ( $model->logs == 'report' ) {
				$settings->lastReportSent = time();
				$settings->save();
			}
			self::flushCache();
			self::releaseLock();

			return true;
		}
		self::releaseLock();

		return false;
	}

	/**
	 * remove all scan models
	 */
	public static function removeAllScanRecords() {
		$models = Scan::findAll();
		foreach ( $models as $model ) {
			$model->delete();
		}
	}

	/**
	 * Ignoe list will be a global array, so it can share from each scan
	 * @return Result_Item[]
	 */
	public static function getIgnoreList() {
		if ( is_array( self::$ignoreList ) ) {
			return self::$ignoreList;
		}

		$ids = get_site_option( self::IGNORE_LIST );
		if ( $ids == false ) {
			$cache = Container::instance()->get( 'cache' );
			$ids   = $cache->get( self::IGNORE_LIST, array() );
			update_site_option( self::IGNORE_LIST, $ids );
		} elseif ( ! is_array( $ids ) ) {
			$ids = unserialize( $ids );
		}

		if ( empty( $ids ) ) {
			self::$ignoreList = array();

			return array();
		}

		$ignoreList = Result_Item::findAll( array(
			'id' => $ids
		) );

		self::$ignoreList = $ignoreList;


		return $ignoreList;
	}

	/**
	 * Check if a file get ignored
	 *
	 * @param $id int the ID of resultScan get ignored
	 *
	 * @return bool
	 */
	public static function isIgnored( $slug ) {
		$ignoreList = Scan_Api::getIgnoreList();
		foreach ( $ignoreList as $model ) {
			if ( $model->hasMethod( 'getSlug' ) && $model->getSlug() == $slug ) {
				return $model->id;
			}
		}

		return false;
	}

	/**
	 * Add an item to ignore list
	 *
	 * @param $id
	 */
	public static function indexIgnore( $id ) {
		$ids = get_site_option( self::IGNORE_LIST );
		if ( $ids == false ) {
			$cache = Container::instance()->get( 'cache' );
			$ids   = $cache->get( self::IGNORE_LIST, array() );
		} elseif ( ! is_array( $ids ) ) {
			$ids = unserialize( $ids );
		}
		if ( ! is_array( $ids ) ) {
			$ids = array();
		}
		$ids[] = $id;
		update_site_option( self::IGNORE_LIST, $ids );
	}

	/**
	 * Remove an item from ignore list
	 *
	 * @param $id
	 */
	public static function unIndexIgnore( $id ) {
		$ids = get_site_option( self::IGNORE_LIST );
		if ( $ids == false ) {
			$cache = Container::instance()->get( 'cache' );
			$ids   = $cache->get( self::IGNORE_LIST, array() );
		} elseif ( ! is_array( $ids ) ) {
			$ids = unserialize( $ids );
		}
		if ( ! is_array( $ids ) ) {
			$ids = array();
		}
		unset( $ids[ array_search( $id, $ids ) ] );
		update_site_option( self::IGNORE_LIST, $ids );
	}

	/**
	 * Get current percent of scan in decimal
	 * @return float
	 */
	public static function getScanProgress() {
		$settings     = Settings::instance();
		$steps        = $settings->getScansAvailable();
		$total        = 0;
		$currentIndex = 0;
		foreach ( $steps as $step ) {
			$queue = Settings::queueFactory( $step, array() );
			if ( is_object( $queue ) ) {
				$total += $queue->count();
				if ( $queue->isEnd() ) {
					$currentIndex += $queue->count();
				} else {
					$currentIndex += $queue->key();
				}
			}
		}

		if ( $total > 0 ) {
			return round( ( $currentIndex / $total ) * 100, 2 );
		} else {
			return ( 0 );
		}
	}

	/**
	 * flush all cache generated during scan process
	 */
	public static function flushCache( $flushQueue = true ) {
		$cache = WP_Helper::getCache();
		if ( $flushQueue == true ) {
			$settings = Settings::instance();
			$steps    = $settings->getScansAvailable();
			foreach ( $steps as $step ) {
				$queue = Settings::queueFactory( $step, array() );
				if ( is_object( $queue ) ) {
					$queue->clearStatusData();
				}
			}
		}
		//todo still update
		$cache->delete( self::CACHE_CORE );
		$cache->delete( self::CACHE_CONTENT );
		$cache->delete( self::SCAN_PATTERN );
		delete_site_option( self::SCAN_PATTERN );
		$cache->delete( 'filestried' );
		$cache->delete( self::CACHE_CHECKSUMS );
		$altCache = WP_Helper::getArrayCache();
		$altCache->delete( 'lastScan' );
	}

	/**
	 * A function for dealing with windows host, as wordpress checksums path all in UNIX format
	 *
	 * @param $file
	 *
	 * @return mixed
	 */
	public static function convertToUnixPath( $file ) {
		//check if this is windows OS, if so convert the ABSPATH
		//Removed : Adds unecessay slashes in windows
		/*if ( DIRECTORY_SEPARATOR == '\\' ) {
			$abs_path = rtrim( ABSPATH, '/' );
			$abs_path = $abs_path . '\\';
		} else {
			$abs_path = ABSPATH;
		}*/
		//now getting the relative path
		$relative_path = str_replace( ABSPATH, '', $file );
		if ( DIRECTORY_SEPARATOR == '\\' ) {
			$relative_path = str_replace( '\\', '', $relative_path ); //Make sure the files do not have a /filename.etension or checksum fails
		}

		return $relative_path;
	}


	/**
	 * A function for dealing with windows host, Fixes the URL path on Windows
	 *
	 * @param $file
	 *
	 * @return mixed
	 */
	public static function convertToWindowsAbsPath( $file ) {
		//check if this is windows OS, if so convert the ABSPATH
		if ( DIRECTORY_SEPARATOR == '\\' ) {
			$abs_path = rtrim( ABSPATH, '/' );
			$abs_path = $abs_path . '\\';

			//now getting the relative path
			$abs_path = str_replace( $abs_path, '', $file );
			$abs_path = str_replace( '\\', '/', $abs_path );
			$abs_path = str_replace( '//', '/', $abs_path );

			return $abs_path;
		}

		return $file;
	}

	/**
	 * Get the schedule time for a scan
	 *
	 * @param $clearCron bool - force to clear scanning cron
	 *
	 * @return false|int
	 * @deprecated 1.4.2
	 */
	public static function getScheduledScanTime( $clearCron = true ) {
		if ( $clearCron ) {
			wp_clear_scheduled_hook( 'processScanCron' );
		}
		$settings = Settings::instance();
		switch ( $settings->frequency ) {
			case '1':
				//check if the time is over or not, then send the date
				$timeString     = date( 'Y-m-d' ) . ' ' . $settings->time . ':00';
				$nextTimeString = date( 'Y-m-d', strtotime( 'tomorrow' ) ) . ' ' . $settings->time . ':00';
				break;
			case '7':
			default:
				$timeString     = date( 'Y-m-d', strtotime( $settings->day . ' this week' ) ) . ' ' . $settings->time . ':00';
				$nextTimeString = date( 'Y-m-d', strtotime( $settings->day . ' next week' ) ) . ' ' . $settings->time . ':00';
				break;
			case '30':
				$timeString     = date( 'Y-m-d', strtotime( $settings->day . ' this month' ) ) . ' ' . $settings->time . ':00';
				$nextTimeString = date( 'Y-m-d', strtotime( $settings->day . ' next month' ) ) . ' ' . $settings->time . ':00';
				break;
		}
		$toUTC = Utils::instance()->localToUtc( $timeString );
		if ( $toUTC < time() ) {
			return Utils::instance()->localToUtc( $nextTimeString );
		} else {
			return $toUTC;
		}
	}

	/**
	 * Create a lock
	 * @return int
	 */
	public static function createLock() {
		$lockPath = WP_Helper::getUploadDir() . '/wp-defender/';
		if ( ! is_dir( $lockPath ) ) {
			wp_mkdir_p( $lockPath );
		}

		$lockFile = $lockPath . 'scan-lock';

		return file_put_contents( $lockFile, time(), LOCK_EX );
	}

	/**
	 * @return bool
	 */
	public static function isLock() {
		$lockPath = WP_Helper::getUploadDir() . '/wp-defender/';
		$lockFile = $lockPath . 'scan-lock';
		if ( ! is_file( $lockFile ) ) {
			return false;
		}

		$time = file_get_contents( $lockFile );
		if ( strtotime( '+1 minutes', $time ) < time() ) {
			//this lock locked for too long, unlock it
			@unlink( $lockFile );

			return false;
		}

		return true;
	}

	/**
	 * release a lock
	 */
	public static function releaseLock() {
		$lockPath = WP_Helper::getUploadDir() . '/wp-defender/';
		$lockFile = $lockPath . 'scan-lock';
		@unlink( $lockFile );
	}

	/**
	 * @return array|mixed|object|\WP_Error
	 */
	public static function getPatterns() {
		$activeScan = self::getActiveScan();
		if ( ! is_object( $activeScan ) ) {
			return array();
		}

		$patterns = get_site_option( Scan_Api::SCAN_PATTERN, null );
		if ( is_array( $patterns ) ) {
			//return pattern if that exists, no matter the content
			return $patterns;
		}

		$api_endpoint = "https://premium.wpmudev.org/api/defender/v1/signatures";
		$patterns     = Utils::instance()->devCall( $api_endpoint, array(), array(
			'method' => 'GET'
		) );
		if ( is_wp_error( $patterns ) || $patterns == false ) {
			$patterns = array();
		}

		update_site_option( Scan_Api::SCAN_PATTERN, $patterns );

		return $patterns;
	}
}