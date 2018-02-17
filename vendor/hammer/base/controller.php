<?php
/**
 * Author: Hoang Ngo
 */

namespace Hammer\Base;

class Controller extends Component {

	/**
	 * @var string
	 */
	public static $id;

	public $layout = null;
	public $module = null;

	/**
	 * @param $viewFile
	 * @param array $params
	 * @param bool $echo
	 *
	 * @return bool|string
	 */
	public function render( $viewFile, $params = array(), $echo = true ) {
		$base_path = $this->getBasePath();

		$view = new View( $base_path . 'view' );
		//assign controller to this
		if ( ! isset( $params['controller'] ) ) {
			$params['controller'] = $this;
		}
		$content = $view->render( $viewFile, $params );

		if ( ! empty( $this->layout ) ) {
			$template = new View( $base_path . 'view' . DIRECTORY_SEPARATOR . 'layouts' );
			$content  = $template->render( $this->layout, array_merge( $params, array(
				'controller' => $this,
				'contents'   => $content
			) ) );
		}
		if ( $echo == false ) {
			return $content;
		}

		echo $content;
	}

	/**
	 * This will guess the called class path, and return the base
	 *
	 * @return bool|string
	 */
	private function getBasePath() {
		$reflector = new \ReflectionClass( get_called_class() );
		$base_path = dirname( dirname( $reflector->getFileName() ) );

		if ( is_dir( $base_path . DIRECTORY_SEPARATOR . 'controller' )
		     && is_dir( $base_path . DIRECTORY_SEPARATOR . 'view' )
		) {
			return $base_path . DIRECTORY_SEPARATOR;
		}

		return false;
	}

	/**
	 * @param $viewFile
	 * @param array $params
	 * @param bool $echo
	 *
	 * @return bool|string
	 */
	public function renderPartial( $viewFile, $params = array(), $echo = true ) {
		$base_path = $this->getBasePath();
		if ( ! isset( $params['controller'] ) ) {
			$params['controller'] = $this;
		}
		$view    = new View( $base_path . 'view' );
		$content = $view->render( $viewFile, $params );
		if ( $echo == true ) {
			echo $content;
		}

		return $content;
	}
}