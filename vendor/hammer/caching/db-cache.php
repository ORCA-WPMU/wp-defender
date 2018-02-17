<?php
/**
 * Author: Hoang Ngo
 */

namespace Hammer\Caching;
/**
 * We will store the cache on _options or _sitemeta
 * This lib must be attached by a behavior having ability to determine if current plugin network ativated
 * or just single
 *
 * Class DB_Cache
 * @package Hammer\Caching
 */
class DB_Cache extends Cache {

	/**
	 * @param $key
	 * @param $value
	 * @param null $duration
	 *
	 * @return bool
	 */
	protected function addValue( $key, $value, $duration ) {
		if ( $duration === 0 ) {
			//as long as possible
			$duration = 31104000;
		}
		$expire_key = $this->buildKey( $key . '_expire' );
		if ( $this->isActivatedSingle() ) {
			update_option( $key, $value, false );
			update_option( $expire_key, strtotime( '+' . $duration . ' seconds' ), false );
		} else {
			update_site_option( $key, $value );
			update_site_option( $expire_key, strtotime( '+' . $duration . ' seconds' ) );
		}

		return true;
	}

	/**
	 * @param $key
	 * @param $value
	 */
	protected function updateValue( $key, $value ) {
		if ( $this->isActivatedSingle() ) {
			update_option( $key, $value, false );
		} else {
			update_site_option( $key, $value );
		}

	}

	/**
	 * @param $key
	 * @param $offset
	 */
	protected function decreaseValue( $key, $offset ) {
		$value = $this->getValue( $key );
		if ( is_numeric( $value ) ) {
			$value = $value - $offset;
			$this->updateValue( $key, $value );
		}
	}

	/**
	 * @param $key
	 * @param $offset
	 */
	protected function increaseValue( $key, $offset ) {
		$value = $this->getValue( $key );
		if ( is_numeric( $value ) ) {
			$value = $value + $offset;
			$this->updateValue( $key, $value );
		}
	}

	/**
	 * @param $key
	 *
	 * @return bool
	 */
	protected function deleteValue( $key ) {
		$expire_key = $this->buildKey( $key . '_expire' );
		if ( $this->isActivatedSingle() ) {
			delete_option( $key );
			delete_option( $expire_key );
		} else {
			delete_site_option( $key );
			delete_site_option( $expire_key );
		}

		return true;
	}

	/**
	 * @param $key
	 *
	 * @return bool|mixed|void
	 */
	protected function getValue( $key ) {
		$expire_key = $this->buildKey( $key . '_expire' );
		if ( $this->isActivatedSingle() ) {
			$expiry = get_option( $expire_key, false );
			if ( $expiry == false || $expiry < time() ) {
				//delete this
				$this->deleteValue( $key );

				return false;
			}

			return get_option( $key );
		} else {
			$expiry = get_site_option( $expire_key, false );
			if ( $expiry == false || $expiry < time() ) {
				$this->deleteValue( $key );

				return false;
			}

			return get_site_option( $key );
		}
	}

	/**
	 * Since this not much different
	 *
	 * @param $key
	 * @param $value
	 * @param null $duration
	 *
	 * @return bool
	 */
	protected function setValue( $key, $value, $duration ) {
		return $this->addValue( $key, $value, $duration );
	}

	/**
	 * @param $key
	 *
	 * @return bool
	 */
	protected function valueExist( $key ) {
		if ( $this->getValue( $key ) === false ) {
			return false;
		}

		return true;
	}
}