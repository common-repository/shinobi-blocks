<?php
/**
 * Assets
 *
 * @category   Plugin
 * @package    WordPress
 * @subpackage Shinobi Blocks
 * @author     Shinobi Works <support@shinobiworks.com>
 * @license    https://www.gnu.org/licenses/gpl-3.0.html/ GPL v3 or later
 * @link       https://shinobiworks.com/
 * @since      1.0.0
 */

namespace Shinobi_Blocks\App;

use Shinobi_Works\WP\DB;

/**
 * Main class
 */
class Assets {

	const STYLE_HANDLE = 'shinobi-blocks';
	const STYLE_URL    = SHINOBI_BLOCKS_URL . 'dist/blocks.style.build.css';
	const STYLE_DIR    = SHINOBI_BLOCKS_DIR . 'dist/blocks.style.build.css';

	const EDITOR_STYLE_HANDLE = 'shinobi-blocks-editor';
	const EDITOR_STYLE_URL    = SHINOBI_BLOCKS_URL . 'dist/blocks.editor.build.css';
	const EDITOR_STYLE_DIR    = SHINOBI_BLOCKS_DIR . 'dist/blocks.editor.build.css';

	const SCRIPT_HANDLE = 'shinobi-blocks';
	const SCRIPT_URL    = SHINOBI_BLOCKS_URL . 'dist/blocks.build.js';
	const SCRIPT_DIR    = SHINOBI_BLOCKS_DIR . 'dist/blocks.build.js';

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', [ $this, 'wp_add_inline_style' ] );
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_block_editor_assets' ] );
		add_filter( 'inline_style_4536', [ $this, 'add_inline_style_inside_amp_head' ], 30, 1 );
	}

	/**
	 * Enqueue block assets for backend
	 */
	public function enqueue_block_editor_assets() {
		self::wp_enqueue_style();
		self::wp_enqueue_editor_style();
		self::wp_enqueue_script_for_backend();
		self::wp_set_script_translations();
		self::wp_localize_script();
	}

	/**
	 * Enqueue block styles for both frontend + backend.
	 */
	public static function wp_enqueue_style() {
		wp_enqueue_style(
			self::STYLE_HANDLE, // Handle.
			self::STYLE_URL, // Block style CSS.
			is_admin() ? [ 'wp-editor' ] : null, // Dependency to include the CSS after it.
			is_admin() ? filemtime( self::STYLE_DIR ) : null // Version: File modification time.
		);
	}

	/**
	 * Enqueue block editor script for backend.
	 */
	public static function wp_enqueue_script_for_backend() {
		wp_enqueue_script(
			self::SCRIPT_HANDLE, // Handle.
			self::SCRIPT_URL, // Block.build.js: We register the block here. Built with Webpack.
			[ 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor' ], // Dependencies, defined above.
			is_admin() ? filemtime( self::SCRIPT_DIR ) : null, // Version: filemtime — Gets file modification time.
			true // Enqueue the script in the footer.
		);
	}

	/**
	 * Enqueue block editor styles for backend.
	 */
	public static function wp_enqueue_editor_style() {
		wp_enqueue_style(
			self::EDITOR_STYLE_HANDLE,
			self::EDITOR_STYLE_URL,
			// Handle.
			// Block editor CSS.
			[ 'wp-edit-blocks' ], // Dependency to include the CSS after it.
			is_admin() ? filemtime( self::EDITOR_STYLE_DIR ) : null // Version: File modification time.
		);
	}

	/**
	 * Translations for JavaScript
	 *
	 * @since 1.2.0
	 * @return void
	 */
	public static function wp_set_script_translations() {
		wp_set_script_translations( self::SCRIPT_HANDLE, 'shinobi-blocks' );
	}

	/**
	 * WP Localized globals
	 */
	public static function wp_localize_script() {
		wp_localize_script(
			self::SCRIPT_HANDLE,
			'shinobiBlocks', // Array containing dynamic data for a JS Global.
			[
				'pluginDirPath' => SHINOBI_BLOCKS_DIR,
				'pluginDirUrl'  => SHINOBI_BLOCKS_URL,
				// Add more data here that you want to access from `shinobiBlocks` object.
			]
		);
	}

	/**
	 * Get Inline Style
	 *
	 * @return string
	 */
	public static function get_inline_style() {
		ob_start();
		require self::STYLE_DIR;
		return ob_get_clean();
	}

	/**
	 * Add inline style inside head
	 *
	 * @return void
	 */
	public function wp_add_inline_style() {
		if ( ! self::is_shinobi_blocks() ) {
			return;
		}
		$version = is_admin() ? filemtime( self::STYLE_DIR ) : null;
		$handle  = self::STYLE_HANDLE;
		wp_register_style( $handle, false, [], $version );
		wp_enqueue_style( $handle );
		wp_add_inline_style( $handle, self::get_inline_style() );
	}

	/**
	 * Add inline css inside head of amp page
	 *
	 * @param array $css
	 * @return array|false
	 *
	 * @package WordPress Theme 4536
	 * @link https://github.com/shinobiworks/4536/blob/master/resources/css/_init.php/
	 */
	public function add_inline_style_inside_amp_head( $css ) {
		$stylesheet = self::STYLE_DIR;
		if ( ! is_file( $stylesheet ) || ! self::is_amp_page() ) {
			return $css;
		}
		ob_start();
		require $stylesheet;
		$css[] = ob_get_clean();
		return $css;
	}

	/**
	 * Check if option value exists
	 *
	 * @return boolean
	 */
	public static function is_shinobi_blocks() {
		if ( ! is_singular() ) {
			return false;
		}
		$option_value = DB::get_option( Factory::IS_SHINOBI_BLOCKS_OPTION_NAME );
		$flag         = false;
		$post_id      = get_queried_object_id();
		if ( $option_value && is_array( $option_value ) && in_array( $post_id, $option_value, true ) ) {
			$flag = true;
		}
		return apply_filters( 'is_shinobi_blocks', $flag );
	}

	/**
	 * Check type of current page
	 *
	 * @return boolean
	 *
	 * @package WordPress Theme 4536
	 * @link https://github.com/shinobiworks/4536/blob/master/resources/functions/amp.php/
	 */
	public static function is_amp_page() {
		$flag = false;
		if ( '4536' === get_template() && filter_input( INPUT_GET, 'amp', FILTER_VALIDATE_BOOLEAN ) ) {
			$flag = true;
		}
		return $flag;
	}

	/**
	 * Enqueue block assets for frontend + backend
	 *
	 * @deprecated 1.0.0
	 */
	public function enqueue_block_assets() {
		if ( ! self::is_shinobi_blocks() ) {
			return;
		}
		self::wp_enqueue_style();
	}

}
