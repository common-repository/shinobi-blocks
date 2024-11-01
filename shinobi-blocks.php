<?php
/**
 * Plugin Name: Shinobi Blocks
 * Description: Gutenberg block editor for be friend with search engines.
 * Author: Shinobi Works
 * Author URI: https://shinobiworks.com/
 * Version: 1.0.5
 * License: GPLv3 or later
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html/
 */

namespace Shinobi_Blocks;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Autoload.
require_once __DIR__ . '/vendor/autoload.php';

/*
 * Define Constant.
 */
if ( ! defined( 'SHINOBI_BLOCKS_DIR' ) ) {
	define( 'SHINOBI_BLOCKS_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'SHINOBI_BLOCKS_URL' ) ) {
	define( 'SHINOBI_BLOCKS_URL', plugin_dir_url( __FILE__ ) );
}

/**
 * Define Function.
 */
if ( ! function_exists( 'shinobi_blocks_config' ) ) {
	/**
	 * Get global config
	 *
	 * @param string $type config type
	 * @return false|array
	 */
	function shinobi_blocks_config( $type = null ) {
		$config_file = __DIR__ . '/shinobi-blocks.json';
		if ( ! is_file( $config_file ) ) {
			return false;
		}
		ob_start();
		require $config_file;
		$raw_config = ob_get_clean();
		$config     = json_decode( $raw_config, true );
		if ( $type && is_array( $config ) && array_key_exists( $type, $config ) ) {
			$config = $config[ $type ];
		}
		return $config;
	}
}

/**
 * Bootstrap
 */
class Bootstrap {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'plugins_loaded', [ $this, 'bootstrap' ] );
	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	public function bootstrap() {
		new \Shinobi_Works\WP\Bootstrap();
		new App\Assets();
		new App\RestAPI();
		new App\Factory\HowTo();
		new App\Factory\FAQ();
		self::register_block_type();

		add_filter( 'block_categories', [ $this, 'block_categories' ] );
	}

	/**
	 * Register block type
	 *
	 * @link https://wordpress.org/gutenberg/handbook/blocks/writing-your-first-block-type#enqueuing-block-scripts
	 * @since 1.16.0
	 *
	 * @return void
	 */
	private static function register_block_type() {
		// Get block list.
		$block_list = shinobi_blocks_config( 'blocks' );
		if ( ! $block_list ) {
			return false;
		}
		// Register blocks.
		foreach ( $block_list as $parent => $args ) {
			register_block_type(
				"shinobi-blocks/$parent",
				[
					// Enqueue blocks.style.build.css on both frontend & backend.
					'style'         => App\Assets::STYLE_HANDLE,
					// Enqueue blocks.build.js in the editor only.
					'editor_script' => App\Assets::SCRIPT_HANDLE,
					// Enqueue blocks.editor.build.css in the editor only.
					'editor_style'  => App\Assets::EDITOR_STYLE_HANDLE,
				]
			);
		}
	}

	/**
	 * Add block category
	 *
	 * @param array $categories blocks category names
	 * @return array
	 */
	public function block_categories( $categories ) {
		$slugs = array_column( $categories, 'slug' );
		$slug  = shinobi_blocks_config( 'name' );
		if ( ! in_array( $slug, $slugs, true ) ) {
			$categories[] = [
				'slug'  => $slug,
				'title' => __( 'Shinobi Blocks', 'shinobi-blocks' ),
			];
		}
		return $categories;
	}

}

new Bootstrap();
