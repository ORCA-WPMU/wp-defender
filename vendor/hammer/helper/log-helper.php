<?php
/**
 * Author: Hoang Ngo
 */

namespace Hammer\Helper;

class Log_Helper {
	public static function logger( $log ) {
		if ( ! is_string( $log ) ) {
			$log = var_export( $log, true );
		}
		error_log( $log );
	}
}