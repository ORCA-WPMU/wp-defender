<?php
/**
 * Author: Hoang Ngo
 */

namespace WP_Defender\Module\IP_Lockout\Component;

use Hammer\Helper\Log_Helper;
use Hammer\WP\Component;
use WP_Defender\Behavior\Utils;
use WP_Defender\Module\IP_Lockout\Model\IP_Model;
use WP_Defender\Module\IP_Lockout\Model\Log_Model;
use WP_Defender\Module\IP_Lockout\Model\Settings;

class Login_Protection_Api extends Component {
	const COUNT_TOTAL = 'wdCountTotals';

	/**
	 * @param Log_Model $log
	 * @param bool $force
	 */
	public static function maybeLock( Log_Model $log, $force = false, $blacklist = false ) {
		//find record first
		$model = IP_Model::findOne( array(
			'ip' => $log->ip
		) );

		if ( is_object( $model ) && $model->status == IP_Model::STATUS_BLOCKED ) {
			//already locked, just return
			return;
		}

		$settings = Settings::instance();
		//find backward from log date, if there are only log & counter > max attempt, then lock
		$after = strtotime( '-' . $settings->login_protection_lockout_timeframe . ' seconds' );
		if ( is_object( $model ) ) {
			//recal release time, if after time smaller than lock time,then we will use last locktime for check
			if ( $after < $model->lock_time ) {
				$after = $model->lock_time;
			}
		}

		$attempt = Log_Model::count( array(
			'ip'      => $log->ip,
			'type'    => Log_Model::AUTH_FAIL,
			'blog_id' => get_current_blog_id(),
			'date'    => array( 'compare' => '>', 'value' => $after )
		) );

		if ( ! is_object( $model ) ) {
			//no record, create one
			$model         = new IP_Model();
			$model->ip     = $log->ip;
			$model->status = IP_Model::STATUS_NORMAL;
		}
		$model->attempt = $attempt;
		if ( $model->attempt >= $settings->login_protection_login_attempt || $force == true ) {
			$model->status       = IP_Model::STATUS_BLOCKED;
			$model->release_time = strtotime( '+ ' . $settings->login_protection_lockout_duration . ' seconds' );
			if ( $blacklist && $force ) {
				$model->lockout_message = esc_html__( "You have been locked out by the administrator for attempting to login with a banned username", wp_defender()->domain );
			} else {
				$model->lockout_message = $settings->login_protection_lockout_message;
			}
			$model->lock_time = time();
			$model->save();
			//we need to create a log
			$lock_log             = new Log_Model();
			$lock_log->type       = Log_Model::AUTH_LOCK;
			$lock_log->date       = time();
			$lock_log->ip         = $log->ip;
			$lock_log->user_agent = $_SERVER['HTTP_USER_AGENT'];
			if ( $force && $blacklist ) {
				$lock_log->log = esc_html__( "Lockout occurred: Attempting to login with a banned username.", wp_defender()->domain );
			} elseif ( ! empty( $log->tried ) ) {
				$lock_log->log = sprintf( esc_html__( "Lockout occurred: Too many failed login attempts for the username %s", wp_defender()->domain ), $log->tried );
			} else {
				$lock_log->log = esc_html__( "Lockout occurred: Too many failed login attempts", wp_defender()->domain );
			}
			$lock_log->save();
			//if fail2ban, we will add that IP to blacklist
			if ( $settings->login_protection_lockout_ban || $blacklist ) {
				$settings->addIpToList( $model->ip, 'blacklist' );
			}

			//trigger an action
			do_action( 'wd_login_lockout', $model, $force, $blacklist );
		} else {
			$model->save();
		}
	}

	/**
	 * @param Log_Model $log
	 */
	public static function maybe404Lock( Log_Model $log ) {
		//find record first
		$model = IP_Model::findOne( array(
			'ip' => $log->ip
		) );

		if ( is_object( $model ) && $model->status == IP_Model::STATUS_BLOCKED ) {
			//already locked, just return
			return;
		}

		$settings = Settings::instance();
		//find backward from log date, if there are only log & counter > max attempt, then lock
		$after = strtotime( '- ' . $settings->detect_404_timeframe . ' seconds' );

		if ( is_object( $model ) ) {
			//recal release time, if after time smaller than lock time,then we will use last locktime for check
			if ( $after < $model->lock_time_404 ) {
				$after = $model->lock_time_404;
			}
		}
		$logs = Log_Model::findAll( array(
			'ip'      => $log->ip,
			'type'    => Log_Model::ERROR_404,
			'blog_id' => get_current_blog_id(),
			'date'    => array(
				'compare' => '>',
				'value'   => $after
			)
		) );

		if ( ! is_object( $model ) ) {
			//no record, create one
			$model         = new IP_Model();
			$model->ip     = $log->ip;
			$model->status = IP_Model::STATUS_NORMAL;
		}

		//filter out the extension
		$ignoresFileTypes = $settings->get404Ignorelist();
		foreach ( $logs as $k => $log ) {
			$ext = pathinfo( $log->log, PATHINFO_EXTENSION );
			if ( in_array( $ext, $ignoresFileTypes ) ) {
				unset( $logs[ $k ] );
			}
		}

		if ( count( $logs ) >= $settings->detect_404_threshold ) {
			//we need to check the extension
			$model->status          = IP_Model::STATUS_BLOCKED;
			$model->release_time    = strtotime( '+ ' . $settings->detect_404_lockout_duration . ' seconds' );
			$model->lockout_message = $settings->detect_404_lockout_message;
			$model->lock_time_404   = time();
			$model->save();
			$lock_log             = new Log_Model();
			$lock_log->type       = Log_Model::LOCKOUT_404;
			$lock_log->date       = time();
			$lock_log->ip         = $log->ip;
			$lock_log->user_agent = $_SERVER['HTTP_USER_AGENT'];
			$uri                  = esc_url( $_SERVER['REQUEST_URI'] );
			$lock_log->log        = sprintf( esc_html__( "Lockout occurred:  Too many 404 requests for %s", wp_defender()->domain ), $uri );
			$lock_log->save();
			//if fail2ban, we will add that IP to blacklist
			if ( $settings->detect_404_lockout_ban ) {
				$settings->addIpToList( $model->ip, 'blacklist' );
			}
			do_action( 'wd_404_lockout', $model, $uri );
		}
	}

	/**
	 * @param null $time - unix timestamp
	 *
	 * @deprecated
	 * @return int
	 */
	public static function get404Lockouts( $time = null ) {
		$logs = Log_Model::count( array(
			'type' => Log_Model::LOCKOUT_404,
			'date' => array(
				'compare' => '>=',
				'value'   => $time
			)
		) );

		return $logs;
	}

	/**
	 * @param null $time - unix timestamp
	 *
	 * @deprecated
	 * @return int
	 */
	public static function getLoginLockouts( $time = null ) {
		$logs = Log_Model::count( array(
			'type' => Log_Model::AUTH_LOCK,
			'date' => array(
				'compare' => '>=',
				'value'   => $time
			)
		) );

		return $logs;
	}

	/**
	 * @param null $time - unix timestamp
	 *
	 * @deprecated
	 * @return int
	 */
	public static function getAllLockouts( $time = null ) {
		$logs = Log_Model::count( array(
			'type' => array(
				Log_Model::LOCKOUT_404,
				Log_Model::AUTH_LOCK
			),
			'date' => array(
				'compare' => '>=',
				'value'   => $time
			)
		) );

		return $logs;
	}

	/**
	 * @return Log_Model
	 * @deprecated
	 */
	public static function getLastLockout() {
		$log = Log_Model::findAll( array(
			'type' => array(
				Log_Model::LOCKOUT_404,
				Log_Model::AUTH_LOCK
			)
		), 'id', 'DESC', '0,1' );
		$log = array_shift( $log );
		if ( is_object( $log ) ) {
			return $log;
		}

		return null;
	}


	/**
	 * @return string
	 */
	public static function getLogsActionsText( $log ) {
		$links     = array();
		$settings  = Settings::instance();
		$blacklist = $settings->getIpBlacklist();
		$whitelist = $settings->getIpWhitelist();

		$ip    = Utils::instance()->getUserIp();
		$nonce = wp_create_nonce( 'lockoutIPAction' );
		if ( $ip != $log->ip ) {
			if ( ! in_array( $log->ip, $blacklist ) ) {
				$links[] = '<a data-nonce="' . $nonce . '" class="ip-action" data-type="blacklist" data-id="' . $log->id . '" href="#">' . __( "Ban", wp_defender()->domain ) . '</a>';
			} else {
				$links[] = '<a data-nonce="' . $nonce . '" class="ip-action" data-type="unblacklist" data-id="' . $log->id . '" href="#">' . __( "Unban", wp_defender()->domain ) . '</a>';
			}
		}

		if ( ! in_array( $log->ip, $whitelist ) ) {
			$links[] = '<a data-nonce="' . $nonce . '" class="ip-action" data-type="whitelist" data-id="' . $log->id . '" href="#">' . __( "Whitelist", wp_defender()->domain ) . '</a>';
		} else {
			$links[] = '<a data-nonce="' . $nonce . '" class="ip-action" data-type="unwhitelist" data-id="' . $log->id . '" href="#">' . __( "Unwhitelist", wp_defender()->domain ) . '</a>';
		}

		return implode( ' | ', $links );
	}

	/**
	 * Validate import file is in right format and usable for IP Lockout
	 *
	 * @param $file
	 *
	 * @return array|bool
	 */
	public static function verifyImportFile( $file ) {
		$fp   = fopen( $file, 'r' );
		$data = array();
		while ( ( $line = fgetcsv( $fp ) ) !== false ) {

			if ( count( $line ) != 2 ) {
				return false;
			}

			if ( ! in_array( $line[1], array( 'whitelist', 'blacklist' ) ) ) {
				return false;
			}

			if ( Settings::instance()->validateIp( $line[0] ) == false ) {
				return false;
			}

			$data[] = $line;

		}
		fclose( $fp );

		return $data;
	}

	/**
	 * @return bool
	 */
	public static function isActive() {
		return Settings::instance()->login_protection && Settings::instance()->detect_404;
	}

	/**
	 * @param bool $clearCron
	 *
	 * @return false|int
	 * @deprecated 1.5
	 */
	public static function getReportTime( $clearCron = true, $utc = true ) {
		if ( $clearCron ) {
			wp_clear_scheduled_hook( 'lockoutReportCron' );
		}
		$settings = Settings::instance();
		switch ( $settings->report_frequency ) {
			case '1':
				//check if the time is over or not, then send the date
				$timeString     = date( 'Y-m-d' ) . ' ' . $settings->report_time . ':00';
				$nextTimeString = date( 'Y-m-d', strtotime( 'tomorrow' ) ) . ' ' . $settings->report_time . ':00';
				break;
			case '7':
			default:
				$timeString     = date( 'Y-m-d', strtotime( $settings->report_day . ' this week' ) ) . ' ' . $settings->report_time . ':00';
				$nextTimeString = date( 'Y-m-d', strtotime( $settings->report_day . ' next week' ) ) . ' ' . $settings->report_time . ':00';
				break;
			case '30':
				$timeString     = date( 'Y-m-d', strtotime( $settings->report_day . ' this month' ) ) . ' ' . $settings->report_time . ':00';
				$nextTimeString = date( 'Y-m-d', strtotime( $settings->report_day . ' next month' ) ) . ' ' . $settings->report_time . ':00';
				break;
		}

		$toUTC = Utils::instance()->localToUtc( $timeString );
		if ( $toUTC <= time() ) {
			if ( $utc ) {
				return Utils::instance()->localToUtc( $nextTimeString );
			} else {
				return strtotime( $timeString );
			}
		} else {
			if ( $utc ) {
				return $toUTC;
			} else {
				return strtotime( $timeString );
			}
		}
	}

	/**
	 * Check if useragent is looks like from google
	 *
	 * @param string $userAgent
	 *
	 * @return bool
	 */
	public static function isGoogleUA( $userAgent = '' ) {
		if ( empty( $userAgent ) ) {
			$userAgent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : null;
		}
		if ( function_exists( 'mb_strtolower' ) ) {
			$userAgent = mb_strtolower( $userAgent, 'UTF-8' );
		} else {
			$userAgent = strtolower( $userAgent );
		}

		if ( stristr( $userAgent, 'googlebot' ) !== false ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if IP is from google, base on https://support.google.com/webmasters/answer/80553?hl=en
	 *
	 * @param $ip
	 *
	 * @return bool
	 */
	public static function isGoogleIP( $ip ) {
		$hostname = gethostbyaddr( $ip );
		//check if this hostname has googlebot or google.com
		if ( preg_match( '/\.googlebot|google\.com$/i', $hostname ) ) {
			$hosts = gethostbynamel( $hostname );
			//check if this match the oringal ip
			foreach ( $hosts as $host ) {
				if ( $ip == $host ) {
					return true;
				}
			}
		}

		return false;
	}

	public static function maybeSendNotification( $type, $model, $settings ) {
		$lastSentKey = $type == 'login' ? 'lastSentLockout' : 'lastSent404';
		$stopTimeKey = $type == 'login' ? 'stopTimeLockout' : 'stopTime404';
		if ( $settings->cooldown_enabled ) {
			//check the last time,and check the status
			$lastSent    = $model->getMeta( $lastSentKey );
			$stopTime    = $model->getMeta( $stopTimeKey, false );
			$currentTime = apply_filters( 'wd_lockout_notification_current_time', time() );
			if ( $stopTime && $currentTime < $stopTime ) {
				//no further email
				return false;
			}
			//we need to check if we can lock
			if ( $lastSent == false ) {
				//no info, we need to init
				$lastSent = time();
				$model->updateMeta( $lastSentKey, $lastSent );
			}
			//we have last sent value here, now need to check the amount from now to last sent
			if ( $stopTime && $lastSent < $stopTime ) {
				$lastSent = $stopTime;
			}

			$count = Log_Model::count( array(
				'type'    => $type == 'login' ? Log_Model::AUTH_LOCK : Log_Model::LOCKOUT_404,
				'blog_id' => get_current_blog_id(),
				'date'    => array(
					'compare' => '>',
					'value'   => $lastSent
				)
			) );
			if ( $count >= $settings->cooldown_number_lockout ) {
				$model->updateMeta( $stopTimeKey, strtotime( '+' . $settings->cooldown_period . ' hours' ) );
				$model->updateMeta( $lastSentKey, time() );
			}
		}

		return true;
	}

	/**
	 *
	 */
	public static function createTables() {
		global $wpdb;

		$charsetCollate = $wpdb->get_charset_collate();
		$tableName1     = $wpdb->base_prefix . 'defender_lockout';
		$tableName2     = $wpdb->base_prefix . 'defender_lockout_log';
		$sql            = "CREATE TABLE IF NOT EXISTS `{$tableName1}` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ip` varchar(255) DEFAULT NULL,
  `status` varchar(16) DEFAULT NULL,
  `lockout_message` text,
  `release_time` int(11) DEFAULT NULL,
  `lock_time` int(11) DEFAULT NULL,
  `lock_time_404` int(11) DEFAULT NULL,
  `attempt` int(11) DEFAULT NULL,
  `attempt_404` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) $charsetCollate;
CREATE TABLE `{$tableName2}` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `log` text,
  `ip` varchar(255) DEFAULT NULL,
  `date` int(11) DEFAULT NULL,
  `type` varchar(16) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `blog_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) $charsetCollate;
";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	public static function alterTableFor171() {
		global $wpdb;
		$tableName1 = $wpdb->base_prefix . 'defender_lockout_log';
		$tableName2 = $wpdb->base_prefix . 'defender_lockout';
		$check      = "SHOW COLUMNS FROM {$tableName1} LIKE 'tried';";
		$check      = $wpdb->get_col( $check );
		if ( count( $check ) == 0 ) {
			$sql = "ALTER TABLE " . $tableName1 . " ADD COLUMN `tried` VARCHAR(255);";
			$wpdb->query( $sql );
		}
		$check = "SHOW COLUMNS FROM {$tableName2} LIKE 'meta';";
		$check = $wpdb->get_col( $check );
		if ( count( $check ) == 0 ) {
			$sql = "ALTER TABLE " . $tableName2 . " ADD COLUMN `meta` text;";
			$wpdb->query( $sql );
		}
	}

	/**
	 * @return bool
	 */
	public static function checkIfTableExists() {
		global $wpdb;
		$tableName1 = $wpdb->base_prefix . 'defender_lockout';
		$tableName2 = $wpdb->base_prefix . 'defender_lockout_log';
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$tableName1'" ) != $tableName1 ||
		     $wpdb->get_var( "SHOW TABLES LIKE '$tableName2'" ) != $tableName2 ) {
			return false;
		}

		return true;
	}
}