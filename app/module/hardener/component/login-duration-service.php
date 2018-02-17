<?php
/**
 * @author Paul Kevin
 */

namespace WP_Defender\Module\Hardener\Component;

use WP_Defender\Module\Hardener\IRule_Service;
use WP_Defender\Module\Hardener\Rule_Service;
use WP_Defender\Module\Hardener\Model\Settings;

class Login_Duration_Service extends Rule_Service implements IRule_Service {

	const CACHE_KEY = 'login_duration';
	const DURATION_CACHE_KEY = 'login_duration_days';
	const DEFAULT_DAYS = 14;

	protected $duration;

	/**
	 * @param mixed $duration
	 */
	public function setDuration( $duration ) {
		$this->duration = $duration;
	}

    /**
	 * @return bool
	 */
	public function check() {
		$key = Settings::instance()->getDValues( self::CACHE_KEY );

		return ( $key == 1 );
    }

    public function process() {
		Settings::instance()->setDValues( self::CACHE_KEY, 1 );
		Settings::instance()->setDValues( self::DURATION_CACHE_KEY, $this->duration );
		return true;
	}

	public function revert() {
		Settings::instance()->setDValues( self::CACHE_KEY, 0 );
		Settings::instance()->setDValues( self::DURATION_CACHE_KEY, self::DEFAULT_DAYS );
		return true;
	}

	public function getDuration( $in_seconds = false ) {
		$duration = Settings::instance()->getDValues( self::DURATION_CACHE_KEY );
		if ( !empty( $duration ) ) {
			$duration = intval( $duration );
			return ( $in_seconds ) ? $duration * DAY_IN_SECONDS : $duration;
		} else {
			return ( $in_seconds ) ? self::DEFAULT_DAYS * DAY_IN_SECONDS : self::DEFAULT_DAYS;
		}
	}
}
?>