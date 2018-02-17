<?php
/**
 * Author: Hoang Ngo
 */

namespace WP_Defender\Module\IP_Lockout\Component;

use Hammer\WP\Component;

class IP_API extends Component {
	public static function compareCIDR( $ip, $block ) {
		list ( $subnet, $bits ) = explode( '/', $block );
		if ( self::isV4( $ip ) && self::isV4( $subnet ) ) {
			return self::_compareCIDRV4( $ip, $block );
		} elseif ( self::isV6( $ip ) && self::isV6( $subnet ) && self::isV6Support() ) {
			return self::_compareCIDRV6( $ip, $block );
		}

		return false;
	}

	/**
	 * @param $ip
	 * @param $block
	 *
	 * @src http://stackoverflow.com/a/594134
	 * @return bool
	 */
	private static function _compareCIDRV4( $ip, $block ) {
		list ( $subnet, $bits ) = explode( '/', $block );
		$ip     = ip2long( $ip );
		$subnet = ip2long( $subnet );
		$mask   = - 1 << ( 32 - $bits );
		$subnet &= $mask; # nb: in case the supplied subnet wasn't correctly aligned

		return ( $ip & $mask ) == $subnet;
	}

	/**
	 * @param $ip
	 * @param $block
	 *
	 * @return bool
	 */
	private static function _compareCIDRV6( $ip, $block ) {
		$ip  = self::expandIPv6( $ip );
		$ip  = inet_pton( $ip );
		$bIP = self::ineToBits( $ip );
		list ( $subnet, $bits ) = explode( '/', $block );
		$subnet  = self::expandIPv6( $subnet );
		$subnet  = inet_pton( $subnet );
		$bSubnet = self::ineToBits( $subnet );

		$ipNetBits  = substr( $bIP, 0, $bits );
		$subnetBits = substr( $bSubnet, 0, $bits );

		return $ipNetBits === $subnetBits;
	}

	/**
	 * @param $inet
	 *
	 * @src https://stackoverflow.com/a/7951507
	 * @return string
	 */
	private static function ineToBits( $inet ) {
		$unpacked = unpack( 'a16', $inet );
		$unpacked = str_split( $unpacked[1] );
		$binaryip = '';
		foreach ( $unpacked as $char ) {
			$binaryip .= str_pad( decbin( ord( $char ) ), 8, '0', STR_PAD_LEFT );
		}

		return $binaryip;
	}

	/**
	 * @param $ip
	 * @param $firstInRange
	 * @param $lastInRange
	 *
	 * @return bool
	 */
	public static function compareInRange( $ip, $firstInRange, $lastInRange ) {
		if ( self::isV4( $firstInRange ) && self::isV4( $lastInRange ) ) {
			return self::_compareV4InRange( $ip, $firstInRange, $lastInRange );
		} elseif ( self::isV6( $firstInRange ) && self::isV6( $lastInRange ) && self::isV6Support() ) {
			self::_compareV6InRange( $ip, $firstInRange, $lastInRange );
		}

		return false;
	}

	/**
	 * @param $ip
	 * @param $fistInRange
	 * @param $lastInRange
	 *
	 * @return bool
	 */
	private static function _compareV4InRange( $ip, $fistInRange, $lastInRange ) {
		$low  = sprintf( "%u", ip2long( $fistInRange ) );
		$high = sprintf( "%u", ip2long( $lastInRange ) );

		$cip = sprintf( "%u", ip2long( $ip ) );
		if ( $high >= $cip && $cip >= $low ) {
			return true;
		}

		return false;
	}

	/**
	 * @param $ip
	 * @param $firstInRange
	 * @param $lastInRange
	 *
	 * @return bool
	 */
	private static function _compareV6InRange( $ip, $firstInRange, $lastInRange ) {
		$firstInRange = inet_pton( self::expandIPv6( $firstInRange ) );
		$lastInRange  = inet_pton( self::expandIPv6( $lastInRange ) );
		$ip           = inet_pton( self::expandIPv6( $ip ) );

		if ( ( strlen( $ip ) == strlen( $firstInRange ) )
		     && ( $ip >= $firstInRange && $ip <= $lastInRange ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Compare ip2 to ip1, true if ip2>ip1, false if not
	 *
	 * @param $ip1
	 * @param $ip2
	 *
	 * @return bool
	 */
	public static function compareIP( $ip1, $ip2 ) {
		if ( self::isV4( $ip1 ) && self::isV4( $ip2 ) ) {
			if ( sprintf( "%u", ip2long( $ip2 ) ) - sprintf( "%u", ip2long( $ip1 ) ) > 0 ) {
				return true;
			}
		} elseif ( self::isV6( $ip1 ) && self::isV6( $ip2 ) && self::isV6Support() ) {
			$ip1 = inet_pton( self::expandIPv6( $ip1 ) );
			$ip2 = inet_pton( self::expandIPv6( $ip2 ) );

			return $ip2 > $ip1;
		}

		return false;
	}

	/**
	 * @param $ip
	 *
	 * @return mixed
	 */
	private static function isV4( $ip ) {
		return filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 );
	}

	/**
	 * @param $ip
	 *
	 * @return mixed
	 */
	private static function isV6( $ip ) {
		return filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 );
	}

	/**
	 * @param $ip
	 *
	 * @return bool|string
	 */
	public static function expandIPv6( $ip ) {
		$hex = unpack( "H*hex", inet_pton( $ip ) );
		$ip  = substr( preg_replace( "/([A-f0-9]{4})/", "$1:", $hex['hex'] ), 0, - 1 );

		return $ip;
	}

	/**
	 * @return bool
	 */
	public static function isV6Support() {
		return defined( 'AF_INET6' );
	}
}