<?php
/**
 * Author: Hoang Ngo
 */

namespace Hammer\Base;
class DB_Model extends Model {
	const EVENT_BEFORE_INSERT = 'beforeInsert', EVENT_AFTER_INSERT = 'afterInsert',
		EVENT_BEFORE_UPDATE = 'beforeUpdate', EVENT_AFTER_UPDATE = 'afterUpdate',
		EVENT_BEFORE_DELELTE = 'beforeDelete', EVENT_AFTER_DELETE = 'afterDelete';

	public $id;
	protected static $tableName = '';

	public function save() {
		if ( $this->id ) {
			$this->update();
		} else {
			$this->insert();
		}
	}

	/**
	 * @return false|int|\WP_Error
	 */
	private function insert() {
		if ( static::$tableName == null ) {
			return new \WP_Error( 'db_error', 'No table specific' );
		}

		$this->trigger( self::EVENT_BEFORE_INSERT );
		$data = $this->export();
		if ( isset( $data['id'] ) && ! empty( $data['id'] ) ) {
			return new \WP_Error( 'db_error', "This id already exists!" );
		}

		$id = self::getWPDB()->insert( self::getTable(), $data );
		if ( $id == false ) {
			error_log( self::getWPDB()->last_error );

			return new \WP_Error( 'db_error', self::getWPDB()->last_error );
		}

		$this->trigger( self::EVENT_BEFORE_UPDATE );

		return self::getWPDB()->insert_id;
	}

	/**
	 * @return bool|\WP_Error
	 */
	private function update() {
		if ( static::$tableName == null ) {
			return new \WP_Error( 'db_error', 'No table specific' );
		}
		$this->trigger( self::EVENT_BEFORE_UPDATE );
		$data  = $this->export();
		$check = self::findByID( $data['id'] );
		if ( is_null( $check ) ) {
			return new \WP_Error( 'db_error', "This record doesn't exists" );
		}
		unset( $data['id'] );
		$affected = self::getWPDB()->update( self::getTable(), $data, array(
			'id' => $check->id
		) );
		if ( $affected == false ) {
			error_log( self::getWPDB()->last_error );

			return new \WP_Error( 'db_error', self::getWPDB()->last_error );
		}
		$this->trigger( self::EVENT_AFTER_UPDATE );

		return true;
	}

	/**
	 * @param $id
	 *
	 * @return mixed|null
	 */
	public static function findByID( $id ) {
		if ( ! $id ) {
			return null;
		}
		$sql  = self::getWPDB()->prepare( "SELECT * FROM " . self::getTable() . " WHERE id = %d", $id );
		$data = self::getWPDB()->get_row( $sql, ARRAY_A );
		if ( is_array( $data ) && count( $data ) ) {
			return self::bind( $data );
		} else {
			return null;
		}
	}

	/**
	 * @param array $attributes
	 * @param null $orderBy
	 * @param string $order
	 *
	 * @return mixed|null
	 */
	public static function findOne( $attributes = array(), $orderBy = null, $order = 'ASC' ) {
		$fields   = self::buildFields();
		$where    = self::buildWhere( $attributes );
		$sqlOrder = null;
		if ( ! empty( $orderBy ) && in_array( $orderBy, $fields ) ) {
			if ( ! in_array( strtolower( $order ), array( 'asc', 'desc' ) ) ) {
				$order = 'ASC';
			}
			$sqlOrder = 'ORDER BY ' . $orderBy . ' ' . $order;
		}

		$sql  = 'SELECT ' . implode( ', ', $fields ) . ' FROM ' . self::getTable() .
		        ' WHERE ' . implode( 'AND', $where ) . $sqlOrder . ' LIMIT 0,1';
		$data = self::getWPDB()->get_row( $sql, ARRAY_A );
		if ( is_array( $data ) && count( $data ) ) {
			return self::bind( $data );
		}

		return null;
	}

	/**
	 * @param array $attributes
	 * @param null $orderBy
	 * @param string $order
	 * @param null $limit
	 *
	 * @return array
	 */
	public static function findAll( $attributes = array(), $orderBy = null, $order = 'ASC', $limit = null ) {
		$fields   = self::buildFields();
		$where    = self::buildWhere( $attributes );
		$sqlOrder = null;
		if ( ! empty( $orderBy ) && in_array( $orderBy, $fields ) ) {
			if ( ! in_array( strtolower( $order ), array( 'asc', 'desc' ) ) ) {
				$order = 'ASC';
			}
			$sqlOrder = ' ORDER BY ' . $orderBy . ' ' . $order;
		}

		$sql = 'SELECT ' . implode( ', ', $fields ) . ' FROM ' . self::getTable() .
		       ' WHERE ' . implode( ' AND ', $where ) . $sqlOrder;
		if ( $limit ) {
			$sql = $sql . ' LIMIT ' . $limit;
		}
		$data    = self::getWPDB()->get_results( $sql, ARRAY_A );
		$results = array();
		if ( is_array( $data ) && count( $data ) ) {
			foreach ( $data as $row ) {
				$results[] = self::bind( $row );
			}
		}

		return $results;
	}

	/**
	 *
	 */
	public function delete() {
		global $wpdb;
		$wpdb->delete( self::getTable(), array(
			'id' => $this->id
		) );
	}

	/**
	 * @param array $attributes
	 * @param bool $limit
	 *
	 * @return int
	 */
	public static function deleteAll( $attributes = array(), $limit = false ) {
		$where = self::buildWhere( $attributes );
		$sql   = "SELECT id from " . self::getTable() . " WHERE " . implode( ' AND ', $where );
		if ( $limit != false ) {
			$sql = $sql . ' LIMIT ' . $limit;
		}

		global $wpdb;
		$ids = $wpdb->get_col( $sql );
		if ( ! empty( $ids ) ) {
			$sql = "DELETE from " . self::getTable() . " WHERE id";
			$sql .= " IN (" . implode( ', ', array_fill( 0, count( $ids ), '%s' ) ) . ")";
			$sql = call_user_func_array( array(
				$wpdb,
				'prepare'
			), array_merge( array( $sql ), $ids ) );
			$wpdb->query( $sql );
		}

		return count( $ids );
	}

	/**
	 * @param array $attributes
	 *
	 * @return null|string
	 */
	public static function count( $attributes = array() ) {
		$where = self::buildWhere( $attributes );
		$sql   = "SELECT count(id) FROM " . self::getTable() . " WHERE " . implode( ' AND ', $where );

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

	private static function getTable() {
		return self::getWPDB()->base_prefix . static::$tableName;
	}

	/**
	 * @return array
	 */
	private static function buildFields() {
		$class  = get_called_class();
		$obj    = new $class;
		$data   = $obj->export();
		$fields = array_keys( $data );

		return $fields;
	}

	/**
	 * @param array $attributes
	 *
	 * @return array
	 */
	private static function buildWhere( $attributes = array() ) {
		if ( empty( $attributes ) ) {
			return array( '1=1' );
		}
		$condition = array();
		$fields    = self::buildFields();
		global $wpdb;
		foreach ( $attributes as $key => $attribute ) {
			if ( ! in_array( $key, $fields ) ) {
				//this condition is not supported
				continue;
			}
			if ( is_array( $attribute ) ) {
				if ( isset( $attribute['compare'] ) ) {
					$sql         = $key . ' ' . $attribute['compare'] . ' %s';
					$sql         = $wpdb->prepare( $sql, $attribute['value'] );
					$condition[] = $sql;
				} elseif ( ! empty( $attribute ) ) {
					$sql = $key . " IN (" . implode( ', ', array_fill( 0, count( $attribute ), '%s' ) ) . ")";

					$sql         = call_user_func_array( array(
						$wpdb,
						'prepare'
					), array_merge( array( $sql ), $attribute ) );
					$condition[] = $sql;
				}
			} elseif ( strpos( '%', $attribute ) === 0 ) {
				$condition[] = $wpdb->prepare( $key . ' LIKE %s', $attribute );
			} else {
				$condition[] = $wpdb->prepare( $key . ' = %s', $attribute );
			}
		}

		$condition = array_filter( $condition );

		return $condition;
	}
}