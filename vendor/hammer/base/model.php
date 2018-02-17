<?php
/**
 * Author: Hoang Ngo
 */

namespace Hammer\Base;

class Model extends Component {
	const EVENT_BEFORE_VALIDATE = 'beforeValidate', EVENT_AFTER_VALIDATE = 'afterValidate';
	/**
	 * Validators applied to this
	 * @var array
	 */
	protected $validators = array();
	/**
	 * Validate errors
	 * @var array
	 */
	protected $errors = array();
	/**
	 * scenario for validation
	 * @var null
	 */
	protected $scenario = null;

	/**
	 * @return null
	 */
	public function getScenario() {
		return $this->scenario;
	}

	/**
	 * @param null $scenario
	 */
	public function setScenario( $scenario ) {
		$this->scenario = $scenario;
	}

	/**
	 * Validate current model data based on provided rules
	 * @return bool
	 */
	public function validate() {
		$this->beforeValidate();
		//our rules
		$data = array_merge( $this->rules(), $this->validators );
		//we need to parse it to GUMP format
		$validators = array();
		foreach ( $data as $value ) {
			$attributes = $value[0];
			$rule       = $value[1];
			$scenario   = isset( $value[2] ) ? $value[2] : null;
			if ( $scenario != null && $scenario != $this->scenario ) {
				//not this scenario
				continue;
			}

			foreach ( $attributes as $attribute ) {
				if ( ! isset( $validators[ $attribute ] ) ) {
					$validators[ $attribute ] = null;
				}

				$crules                   = explode( '|', $validators[ $attribute ] );
				$crules[]                 = $rule;
				$crules                   = array_unique( $crules );
				$crules                   = array_filter( $crules );
				$validators[ $attribute ] = implode( '|', $crules );
			}
		}

		if ( ! empty( $validators ) ) {
			if ( ! class_exists( 'GUMP', false ) ) {
				require_once dirname( __DIR__ ) . '/vendor/wixel/gump/gump.class.php';
			}
			\GUMP::set_field_names( $this->getAttributeLabels() );
			$gump = new \GUMP();
			foreach ( $validators as $key => $rule ) {
				$errors = $gump->validate( array( $key => $this->$key ), array( $key => $rule ) );
				if ( $errors !== true ) {
					$this->errors[ $key ] = array_shift( $gump->get_errors_array() );
				}
			}
		}

		$this->afterValidate();

		if ( count( $this->errors ) ) {
			return false;
		}

		return true;
	}

	/**
	 *
	 * @return array
	 */
	public function rules() {
		return array();
	}

	/**
	 * Trigger even before validate
	 */
	protected function beforeValidate() {
		$this->trigger( self::EVENT_BEFORE_VALIDATE );
	}

	/**
	 * Trigger event after validate
	 */
	protected function afterValidate() {
		$this->trigger( self::EVENT_AFTER_VALIDATE );
	}

	/**
	 * @param $attribute
	 *
	 * @return mixed
	 */
	public function getAttributeLabel( $attribute ) {
		$labels = $this->getAttributeLabels();

		return isset( $labels[ $attribute ] ) ? $labels[ $attribute ] : $attribute;
	}

	/**
	 * @return array
	 */
	public function getAttributeLabels() {
		$attributes = $this->export();
		$labels     = array();
		foreach ( $attributes as $attribute => $value ) {
			$labels[ $attribute ] = ucwords( str_replace( '_', ' ', $attribute ) );
		}

		return $labels;
	}

	/**
	 * @return array
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * @param $error
	 *
	 * @return mixed|null
	 */
	public function getError( $error ) {
		return isset( $this->errors[ $error ] ) ? $this->errors[ $error ] : null;
	}

	/**
	 * Export all public attribute of this model
	 * @return array
	 */
	public function export() {
		$reflection = new \ReflectionClass( $this );
		$props      = $reflection->getProperties( \ReflectionProperty::IS_PUBLIC );
		$values     = array();
		foreach ( $props as $prop ) {
			$value = $prop->getValue( $this );
			if ( is_null( $value ) ) {
				$value = '';
			}
			$values[ $prop->getName() ] = $value;
		}

		return $values;
	}

	/**
	 * Massive assign attributes
	 *
	 * @param $data
	 */
	public function import( $data ) {
		foreach ( $data as $key => $val ) {
			if ( $this->hasProperty( $key ) ) {
				$this->$key = $val;
			}
		}
	}

	/**
	 * @param $key
	 * @param string $message
	 */
	public function addError( $key, $message = '' ) {
		$this->errors[ $key ] = $message;
	}

	/**
	 * @param $key
	 */
	public function removeError( $key ) {
		unset( $this->errors[ $key ] );
	}

	/**
	 * Clear model errors
	 */
	public function clearErrors() {
		$this->errors = array();
	}

	/**
	 * Clear custom validators
	 */
	public function clearValidators() {
		$this->validators = array();
	}

	/**
	 * Add a custom validator
	 *
	 * @param $validator
	 * @param $attribute
	 * @param null $scenario
	 */
	public function addValidator( $validator, $attribute, $scenario = null ) {
		$this->validators[] = array(
			array( $attribute ),
			$validator,
			$scenario
		);
	}
}