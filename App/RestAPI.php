<?php
/**
 * WP Rest API
 *
 * @category   Plugin
 * @package    WordPress
 * @subpackage Shinobi Blocks
 * @author     Shinobi Works <support@shinobiworks.com>
 * @license    https://www.gnu.org/licenses/gpl-3.0.html/ GPL v3 or later
 * @link       https://shinobiworks.com
 * @since      1.0.5
 */

namespace Shinobi_Blocks\App;

use Shinobi_Blocks\App\Assets;
use Shinobi_Blocks\App\Factory\FAQ;
use Shinobi_Blocks\App\Factory\HowTo;
use Shinobi_Works\WP\DB;

class RestAPI {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_rest_route' ] );
	}

	/**
	 * Register Rest Route
	 *
	 * @since 1.0.5
	 * @return void
	 */
	public function register_rest_route() {
		$namespace = 'shinobi-blocks/v2';

		register_rest_route(
			$namespace,
			'/css',
			[
				'methods'  => 'GET',
				'callback' => function() {
					$css          = [];
					$css['style'] = Assets::get_inline_style();
					return $css;
				},
			]
		);

		register_rest_route(
			$namespace,
			'/structured-data',
			[
				'methods'  => 'GET',
				'callback' => function() {
					$options = [
						HowTo::OPTION_NAME,
						FAQ::OPTION_NAME,
					];
					$data    = [];
					foreach ( $options as $option ) {
						if ( DB::get_option( $option ) ) {
							foreach ( DB::get_option( $option ) as $id => $value ) {
								$structured_data[ $id ] = $value['structured_data'];
							}
							$data[ $option ] = $structured_data;
						}
					}
					return $data;
				},
			]
		);
	}
}
