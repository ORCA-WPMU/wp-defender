<?php
/**
 * Author: Hoang Ngo
 */

namespace WP_Defender;

use Hammer\Helper\HTTP_Helper;
use Hammer\WP\Component;


class Controller extends Component {
	protected $slug;

	/**
	 * @return bool
	 */
	protected function isInPage() {
		return HTTP_Helper::retrieve_get( 'page' ) == $this->slug;
	}

	/**
	 * @param $view
	 *
	 * @return bool
	 */
	public function isView( $view ) {
		return HTTP_Helper::retrieve_get( 'view' ) == $view;
	}

	/**
	 * @return bool
	 */
	public function isDashboard() {
		return HTTP_Helper::retrieve_get( 'page' ) == 'wp-defender';
	}

	/**
	 * @param $viewFile
	 * @param array $params
	 * @param bool $echo
	 *
	 * @return bool|string
	 */
	public function renderPartial( $viewFile, $params = array(), $echo = true ) {
		ob_start();
		$content =  parent::renderPartial( $viewFile, $params, $echo );
		ob_end_clean();

		$content = apply_filters( 'wd_render_partial', $content, $viewFile, $params );
		if ( $echo ) {
			echo $content;
		}

		return $content;
	}
}