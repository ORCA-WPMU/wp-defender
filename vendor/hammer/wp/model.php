<?php
/**
 * Author: Hoang Ngo
 */

namespace Hammer\WP;

use Hammer\Helper\Log_Helper;


/**
 * We will use raw sql for quering _posts table instead of rely on WP_Query for faster performance.
 * Attributes will need tobe mapped to _posts & _postmeta
 *
 * Class Model
 * @package Hammer\WP
 */
abstract class Model extends \Hammer\Base\Model {
	const EVENT_BEFORE_INSERT = 'beforeInsert', EVENT_AFTER_INSERT = 'afterInsert',
		EVENT_BEFORE_UPDATE = 'beforeUpdate', EVENT_AFTER_UPDATE = 'afterUpdate',
		EVENT_BEFORE_DELELTE = 'beforeDelete', EVENT_AFTER_DELETE = 'afterDelete';
	/**
	 * Post type of this model, must be override
	 * @var string
	 */
	protected static $post_type = '';

	/**
	 * @return Model|\WP_Error
	 */
	public function save() {
		if ( $this->id == null ) {
			return $this->insert();
		} else {
			return $this->update();
		}
	}

	/**
	 * @return $this|\WP_Error
	 */
	private function update() {
		$this->trigger( self::EVENT_BEFORE_UPDATE );
		$data  = $this->export();
		$check = static::findByID( $data['id'] );
		if ( ! is_object( $check ) ) {
			return new \WP_Error( 'db_error', "This record doesn't exists" );
		}

		list( $wp, $metas ) = static::prepareData( $data );

		list( $wp1, $metas1 ) = static::prepareData( $check->export() );
		//we have tp check if this really need to update post data
		if ( count( self::arrayRecursiveDiff( $wp, $wp1 ) ) ) {
			wp_update_post( $wp );
		}
		//check metas
		if ( count( self::arrayRecursiveDiff( $metas, $metas1 ) ) ) {
			$changes = self::arrayRecursiveDiff( $metas, $metas1 );
			//we only update changes value
			foreach ( $changes as $changed ) {
				//we need to find the original
				foreach ( $metas1 as $original ) {
					if ( $changed['meta_key'] == $original['meta_key'] ) {
						update_post_meta( $data['id'], $changed['meta_key'], $changed['meta_value'] );
						break;
					}
				}
			}
		}
		$this->trigger( self::EVENT_AFTER_UPDATE );

		return $this->id;
	}

	/**
	 * @return $this|\WP_Error
	 */
	private function insert() {
		$this->trigger( self::EVENT_BEFORE_INSERT );
		$data = $this->export();
		if ( isset( $data['id'] ) && ! empty( $data['id'] ) ) {
			return new \WP_Error( 'db_error', "This id already exists!" );
		}

		list( $wp, $metas ) = static::prepareData( $data );

		$last_id = wp_insert_post( $wp );
		if ( $last_id === 0 || is_wp_error( $last_id ) ) {
			return false;
		}

		foreach ( $metas as $meta ) {
			update_post_meta( $last_id, $meta['meta_key'], $meta['meta_value'] );
		}

		$this->id = $last_id;

		return $last_id;
	}

	/**
	 * @return \WP_Error
	 */
	public function delete() {
		$this->trigger( self::EVENT_BEFORE_DELELTE );
		$data = $this->export();
		if ( ! isset( $data['id'] ) || empty( $data['id'] ) ) {
			return new \WP_Error( 'db_error', "Your record doesn't exists" );
		}

		list( $wp, $metas ) = static::prepareData( $data );
		wp_delete_post( $data['id'] );
		//now the metas
		foreach ( $metas as $meta ) {
			delete_post_meta( $data['id'], $meta['meta_key'] );
		}
		$this->trigger( self::EVENT_AFTER_DELETE );
	}

	/**
	 * @param array $attributes
	 * @param null $orderBy
	 * @param null $order
	 * @param null $limit
	 */
	public static function deleteAll( $attributes = array(), $orderBy = null, $order = null, $limit = null ) {
		if ( ! empty( $attributes ) ) {
			$join = static::buildJoins();
		} else {
			$join = array();
		}
		$where = static::buildWhere( $attributes );
		$sql   = "SELECT ID FROM " . self::getWPDB()->posts . ' AS t0 ' . implode( ' ', $join ) . ' ' . implode( ' AND ', $where );
		if ( ! empty( $orderBy ) && static::buildOrderBy( $orderBy ) != false ) {
			$sql = $sql . ' ORDER BY ' . static::buildOrderBy( $orderBy );
			if ( ! empty( $order ) && in_array( strtolower( $order ), array( 'desc', 'asc' ) ) ) {
				$sql = $sql . ' ' . $order;
			}
		}
		if ( ! empty( $limit ) ) {
			$sql .= ' LIMIT ' . $limit;
		}

		$ids = self::getWPDB()->get_col( $sql );

		foreach ( $ids as $id ) {
			wp_delete_post( $id );
		}
	}

	/**
	 *
	 * @param $id
	 *
	 * @return static|null
	 */
	public static function findByID( $id ) {
		list( $posts, $metas ) = self::getMaps();
		$post = get_post( $id );
		if ( ! is_object( $post ) ) {
			return null;
		}
		$class = get_called_class();
		$obj   = new $class;
		foreach ( $posts as $k => $v ) {
			$obj->$k = $post->$v;
		}

		foreach ( $metas as $k => $v ) {
			$value   = get_post_meta( $id, $k, true );
			$obj->$k = $value;
		}

		return $obj;
	}

	/**
	 * @param array $attributes
	 * @param null $orderBy
	 * @param null $order
	 *
	 * @return static|null
	 */
	public static function findOne( $attributes = array(), $orderBy = null, $order = null ) {
		$fields = static::buildFields();
		$join   = static::buildJoins();
		$where  = static::buildWhere( $attributes );
		$sql    = "SELECT " . implode( ',', $fields ) . " FROM " . static::getWPDB()->posts . ' AS t0';
		$sql    = $sql . ' ' . implode( ' ', $join ) . ' ' . implode( ' AND ', $where );
		if ( ! empty( $orderBy ) && static::buildOrderBy( $orderBy ) != false ) {
			$sql = $sql . ' ORDER BY ' . static::buildOrderBy( $orderBy );
			if ( ! empty( $order ) && in_array( strtolower( $order ), array( 'desc', 'asc' ) ) ) {
				$sql = $sql . ' ' . $order;
			}
		}
		$sql = $sql . ' LIMIT 1';
		$row = static::getWPDB()->get_row( $sql, ARRAY_A );
		if ( $row === false ) {
			echo static::getWPDB()->last_error;
		} else if ( empty( $row ) ) {
			return null;
		}
		$model = self::bind( $row );

		return $model;
	}

	/**
	 * @param array $attributes
	 * @param null $orderBy
	 * @param null $order
	 * @param bool $limit
	 *
	 * @return static[]
	 */
	public static function findAll( $attributes = array(), $orderBy = null, $order = null, $limit = false ) {
		$fields = static::buildFields();
		$join   = static::buildJoins();
		$where  = static::buildWhere( $attributes );
		$sql    = "SELECT " . implode( ',', $fields ) . " FROM " . static::getWPDB()->posts . ' AS t0';
		$sql    = $sql . ' ' . implode( ' ', $join ) . ' ' . implode( ' AND ', $where );
		if ( ! empty( $orderBy ) && static::buildOrderBy( $orderBy ) != false ) {
			$sql = $sql . ' ORDER BY ' . static::buildOrderBy( $orderBy );
			if ( ! empty( $order ) && in_array( strtolower( $order ), array( 'desc', 'asc' ) ) ) {
				$sql = $sql . ' ' . $order;
			}
		}

		if ( ! empty( $limit ) ) {
			$sql .= ' LIMIT ' . $limit;
		}
		$rows    = static::getWPDB()->get_results( $sql, ARRAY_A );
		$results = array();
		foreach ( $rows as $row ) {
			$results[] = self::bind( $row );
		}

		return $results;
	}

	/**
	 * @param array $attributes
	 *
	 * @return null|string
	 */
	public static function count( $attributes = array() ) {
		if ( ! empty( $attributes ) ) {
			$join = static::buildJoins();
		} else {
			$join = array();
		}
		$where = static::buildWhere( $attributes );
		$sql   = "SELECT count(ID) FROM " . self::getWPDB()->posts . ' AS t0 ' . implode( ' ', $join ) . ' ' . implode( ' AND ', $where );

		return self::getWPDB()->get_var( $sql );
	}

	/**
	 * @return \wpdb
	 */
	private static function getWPDB() {
		global $wpdb;

		return $wpdb;
	}

	/**
	 * Child class should define the mapping from object to _posts & _postmeta here
	 * @return array
	 */
	protected static function maps() {
		return array();
	}

	/**
	 * Parse the mapping and return tmap type
	 * @return array
	 */
	private static function getMaps() {
		$maps   = static::maps();
		$_posts = array();
		$_metas = array();
		foreach ( $maps as $k => $val ) {
			if ( $val['type'] == 'meta' ) {
				$_metas[ $k ] = $val['map'];
			} elseif ( $val['type'] == 'wp' ) {
				$_posts[ $k ] = $val['map'];
			}
		}

		return array( $_posts, $_metas );
	}

	/**
	 * Build the fields we need to query,with alias
	 * Alias will be _posts t0, and each meta will increase by 1
	 * @return array
	 */
	private static function buildFields() {
		list( $_posts, $_metas ) = static::getMaps();
		$fields = array();
		foreach ( $_posts as $k => $field ) {
			$fields[] = 't0.' . $field . ' AS ' . $k;
		}
		$i = 1;
		foreach ( $_metas as $k => $field ) {
			$fields[] = 't' . $i . '.meta_value AS ' . $k;
			$i ++;
		}

		return $fields;
	}

	/**
	 * Build the join, base on each _wpmeta field mapped
	 * @return array
	 */
	private static function buildJoins() {
		list( $_posts, $_metas ) = static::getMaps();
		$joins = array();
		$i     = 1;
		foreach ( $_metas as $k => $field ) {
			$joins[] = static::getWPDB()->prepare( 'LEFT JOIN ' . static::getWPDB()->postmeta . ' as t' . $i . ' ON t' . $i . '.post_id=ID AND t' . $i . '.meta_key=%s', $field );
			$i ++;
		}

		return $joins;
	}

	/**
	 * @param array $params
	 *
	 * @return array
	 */
	private static function buildWhere( $params = array() ) {
		if ( empty( $params ) ) {
			return array( static::getWPDB()->prepare( 'WHERE 1=1 and t0.post_type=%s', static::$post_type ) );
		}
		list( $_posts, $_metas ) = static::getMaps();
		$where = array( static::getWPDB()->prepare( 'WHERE t0.post_type=%s', static::$post_type ) );
		foreach ( $_posts as $key => $field ) {
			if ( isset( $params[ $key ] ) ) {
				$where[] = self::_buildWhere( 0, $field, $params[ $key ] );
			}
		}

		$i = 1;
		foreach ( $_metas as $key => $field ) {
			if ( isset( $params[ $key ] ) ) {
				$where[] = self::_buildWhere( $i, $field, $params[ $key ] );
			}
			$i ++;
		}

		$where = array_filter( $where );

		return $where;
	}

	private static function _buildWhere( $pos, $field, $value ) {
		$wpdb = self::getWPDB();
		if ( $pos != 0 ) {
			$field = 'meta_value';
		}
		if ( is_array( $value ) ) {
			if ( isset( $value['compare'] ) ) {
				$sql = 't' . $pos . '.' . $field . ' ' . $value['compare'] . ' %s';
				$sql = $wpdb->prepare( $sql, $value['value'] );
			} elseif ( ! empty( $value ) ) {
				$sql = 't' . $pos . "." . $field . " IN (" . implode( ', ', array_fill( 0, count( $value ), '%s' ) ) . ")";

				$sql = call_user_func_array( array( $wpdb, 'prepare' ), array_merge( array( $sql ), $value ) );
			} else {
				//this case the in array is empty,
				$sql = "";
			}

			return $sql;
		} elseif ( strpos( '%', $value ) === 0 ) {
			return $wpdb->prepare( 't' . $pos . '.' . $field . ' LIKE %s', $value );
		} else {
			return $wpdb->prepare( 't' . $pos . '.' . $field . ' = %s', $value );
		}
	}

	/**
	 * @param $attribute
	 *
	 * @return string|bool
	 */
	private static function buildOrderBy( $attribute ) {
		$fields = self::getMaps();
		//check for meta value first
		$i = array_search( $attribute, array_keys( $fields[1] ) );
		if ( $i === false ) {
			//we need to check wp value
			if ( isset( $fields[0][ $attribute ] ) ) {
				return 't0.' . $fields[0][ $attribute ];
			}
		} else {
			return 't' . ( $i + 1 ) . '.meta_value';
		}

		return false;
	}

	/**
	 * @param $data
	 *
	 * @return array
	 */
	private static function prepareData( $data ) {
		list( $_wp, $_metas ) = self::getMaps();
		$wp    = array();
		$metas = array();

		foreach ( $_wp as $attribute => $meta ) {
			if ( isset( $data[ $attribute ] ) ) {
				$wp[ $meta ] = $data[ $attribute ];
			}
		}
		$wp['post_type'] = static::$post_type;

		if ( ! isset( $wp['post_status'] ) ) {
			$wp['post_status'] = 'publish';
		}

		foreach ( $_metas as $attribute => $meta ) {
			if ( isset( $data[ $attribute ] ) ) {

				$metas[] = array(
					'meta_value' => $data[ $attribute ],
					'meta_key'   => $meta
				);
			}
		}

		return array( $wp, $metas );
	}

	/**
	 * @param $data
	 *
	 * @return mixed
	 */
	private static function bind( $data ) {
		$class = get_called_class();
		$obj   = new $class;

		foreach ( $data as $key => $val ) {
			$data[ $key ] = maybe_unserialize( $val );
		}

		$obj->import( $data );

		return $obj;
	}

	/**
	 * @param $aArray1
	 * @param $aArray2
	 *
	 * @return array
	 * http://stackoverflow.com/questions/3876435/recursive-array-diff
	 */
	private static function arrayRecursiveDiff( $aArray1, $aArray2 ) {
		$aReturn = array();

		foreach ( $aArray1 as $mKey => $mValue ) {
			if ( array_key_exists( $mKey, $aArray2 ) ) {
				if ( is_array( $mValue ) ) {
					$aRecursiveDiff = self::arrayRecursiveDiff( $mValue, $aArray2[ $mKey ] );
					if ( count( $aRecursiveDiff ) ) {
						$aReturn[ $mKey ] = $mValue;
					}
				} else {
					if ( $mValue != $aArray2[ $mKey ] ) {
						$aReturn[ $mKey ] = $mValue;
					}
				}
			} else {
				$aReturn[ $mKey ] = $mValue;
			}
		}

		return $aReturn;
	}
}