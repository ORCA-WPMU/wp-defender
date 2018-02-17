<?php
/**
 * Author: Hoang Ngo
 */


spl_autoload_register( function ( $class ) {
	$base_path = __DIR__ . DIRECTORY_SEPARATOR;
	$pools     = explode( '\\', $class );

	if ( $pools[0] != 'Hammer' ) {
		return;
	}

	//build the path
	unset( $pools[0] );
	$path = implode( DIRECTORY_SEPARATOR, $pools );
	$path = $base_path . strtolower( str_replace( '_', '-', $path ) ) . '.php';
	if ( file_exists( $path ) ) {
		include $path;
	}
} );

//autoload dependencies
//require_once __DIR__ . '/vendor/autoload.php';

//loading the dependency
\Hammer\Base\Container::instance()->set( 'cache', initCacheEngine() );
\Hammer\Base\Container::instance()->set( 'cache_alt', new \Hammer\Caching\Array_Cache() );

/**
 * Init cache engine base on availability of memcached or not
 * @return \Hammer\Caching\DB_Cache|\Hammer\Caching\Memcached_Cache
 */
function initCacheEngine() {
	if ( function_exists( 'wp_using_ext_object_cache' )
	     && wp_using_ext_object_cache() && ! defined( 'W3TC' ) ) {
		return new \Hammer\Caching\Memcached_Cache();
	} else {
		return new \Hammer\Caching\DB_Cache();
	}
}