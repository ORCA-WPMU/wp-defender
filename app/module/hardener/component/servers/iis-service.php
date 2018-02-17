<?php
/**
 * Author: Paul Kevin
 */

namespace WP_Defender\Module\Hardener\Component\Servers;

use WP_Defender\Component\Error_Code;
use WP_Defender\Module\Hardener\IRule_Service;
use WP_Defender\Module\Hardener\Rule_Service;

/**
 * IIS 7 Service
 *
 * Protect th uploads directory
 */
class Iis_Service extends Rule_Service implements IRule_Service {

    /**
	 * @return bool
	 */
	public function check() {
        return true;
    }

    public function process() {
        $path = WP_CONTENT_DIR . '/uploads';
        $filename = 'web.config';
        if ( ! file_exists( $path . '/' . $filename ) ) {
			$fp = fopen( $path . '/' . $filename, 'w' );
			fwrite( $fp, '<configuration/>' );
			fclose( $fp );
		}

        $formatxml = PHP_EOL;
		$formatxml = "  <handlers accessPolicy=\"Read\" />";
		$formatxml .= PHP_EOL;

		$doc = new \DOMDocument();
		$doc->preserveWhiteSpace = true;
		if ( $doc->load( $path . '/' . $filename ) === false ) {
            return new \WP_Error( Error_Code::NOT_WRITEABLE,
					sprintf( __( "The file %s could not be loaded", wp_defender()->domain ), $filename ) );
		}
		$xpath = new \DOMXPath( $doc );
		$read_accesspolicy = $xpath->query( '/configuration/system.webServer/handlers[starts-with(@accessPolicy,\'Read\')]' );
		if ( $read_accesspolicy->length > 0 ) {
		    return true;
		}

		$xmlnodes = $xpath->query( '/configuration/system.webServer/handlers' );
		if ( $xmlnodes->length > 0 ) {
			$handlers_node = $xmlnodes->item(0);
		}
		else {
			$handlers_node = $doc->createElement( 'handlers' );
			$xmlnodes = $xpath->query( '/configuration/system.webServer' );
			if ( $xmlnodes->length > 0 ) {
				$system_webServer_node = $xmlnodes->item(0);
				$handler_fragment = $doc->createDocumentFragment();
				$handler_fragment->appendXML( $formatxml );
				$system_webServer_node->appendChild( $handler_fragment );
			}
			else {
				$system_webServer_node = $doc->createElement( 'system.webServer' );
				$handler_fragment = $doc->createDocumentFragment();
				$handler_fragment->appendXML( $formatxml );
				$system_webServer_node->appendChild( $handler_fragment );

				$xmlnodes = $xpath->query( '/configuration' );
				if ( $xmlnodes->length > 0 ) {
					$config_node = $xmlnodes->item(0);
					$config_node->appendChild( $system_webServer_node );
				}
				else {
					$config_node = $doc->createElement( 'configuration' );
					$doc->appendChild( $config_node );
					$config_node->appendChild( $system_webServer_node );
				}
			}
		}

        $rule_fragment = $doc->createDocumentFragment();
        $rule_fragment->appendXML( $formatxml );
        $handlers_node->appendChild( $rule_fragment );

        $doc->encoding = "UTF-8";
        $doc->formatOutput = true;
        saveDomDocument( $doc, $path .'/'. $filename );
        return true;
    }

    /**
	 * @return bool|\WP_Error
	 */
	public function revert() {
        $path = WP_CONTENT_DIR . '/uploads';
	    $filename = 'web.config';

        if ( ! file_exists( $path . '/' . $filename ) ) {
			return true;
		}

		$doc = new DOMDocument();
		$doc->preserveWhiteSpace = false;
		if ( $doc->load( $path . '/' . $filename ) === false ) {
		  return false;
		}

		$xpath = new DOMXPath( $doc );
		$handlers = $xpath->query( '/configuration/system.webServer/handlers[contains(@accessPolicy,\'Read\')]' );
		if ( $handlers->length > 0 ) {
			$child = $handlers->item(0);
			$parent = $child->parentNode;
			$parent->removeChild( $child );
			$doc->formatOutput = true;
			saveDomDocument( $doc, $path .'/'. $filename );
		}
        return true;
    }
}
?>