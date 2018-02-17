<?php
/**
 * Author: Hoang Ngo
 */

namespace Hammer\Helper;

use Hammer\Base\File;

class File_Helper {
	/**
	 * @param $path
	 * @param bool $include_file
	 * @param bool $include_dir
	 * @param array $exclude
	 * @param array $include
	 * @param bool $is_recursive
	 * @param bool $max_size
	 *
	 * @return array
	 */
	public static function findFiles( $path, $include_file = true, $include_dir = true, $exclude = array(), $include = array(), $is_recursive = true, $max_size = false ) {
		$tv = new File( $path, $include_file, $include_dir, $include, $exclude, $is_recursive );
		if ( $max_size != false ) {
			$tv->max_filesize = $max_size;
		}
		$result = $tv->get_dir_tree();
		unset( $v );

		return $result;
	}
}