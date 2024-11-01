<?php
/**
 * Core Factory File
 *
 * @category   Plugin
 * @package    WordPress
 * @subpackage Shinobi Blocks
 * @author     Shinobi Works <support@shinobiworks.com>
 * @license    https://www.gnu.org/licenses/gpl-3.0.html/ GPL v3 or later
 * @link       https://shinobiworks.com
 * @since      1.0.0
 */

namespace Shinobi_Blocks\App;

use Shinobi_Works\WP\DB;

/**
 * Factory Class
 */
class Factory {

	const IS_SHINOBI_BLOCKS_OPTION_NAME = 'is_shinobi_blocks';

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'save_post', [ $this, '_save_post' ], 10, 3 );
	}

	/**
	 * Check if using Shinobi Blocks in post
	 *
	 * @param int $id post id including revision
	 * @param object $post post object
	 * @param boolean $update
	 * @return void
	 */
	public function _save_post( $id, $post, $update ) {
		$option_value = DB::get_option( self::IS_SHINOBI_BLOCKS_OPTION_NAME );
		$id           = self::get_save_post_id( $id );
		$ids          = $option_value ? $option_value : [];
		if ( self::is_shinobi_blocks( $post->post_content ) ) {
			if ( ! in_array( $id, $ids, true ) ) {
				$ids[] = $id;
			}
		} else {
			$index = array_search( $id, $ids, true );
			if ( false !== $index ) {
				unset( $ids[ $index ] );
			}
		}
		if ( $ids ) {
			sort( $ids );
			DB::update_option( self::IS_SHINOBI_BLOCKS_OPTION_NAME, $ids );
		} else {
			DB::delete_option( self::IS_SHINOBI_BLOCKS_OPTION_NAME );
		}
	}

	/**
	 * Get Real Post ID
	 *
	 * @param int $id
	 * @return int
	 */
	protected static function get_save_post_id( $id ) {
		$parent_id = wp_is_post_revision( $id );
		if ( $parent_id ) {
			$id = $parent_id;
		}
		return $id;
	}

	/**
	 * Check if shinobi blocks exists in post
	 *
	 * @param string $subject post content
	 * @return boolean
	 */
	protected static function is_shinobi_blocks( $subject ) {
		return false !== strpos( $subject, '<!-- wp:shinobi-blocks' ) ? true : false;
	}

	/**
	 * Preg Match
	 *
	 * @param string $subject post content.
	 * @return string|false
	 */
	protected static function preg_match( $pattern, $subject ) {
		// First Check.
		if ( ! self::is_shinobi_blocks( $subject ) ) {
			return false;
		}
		/**
		 * @param string $match[1] attributes
		 * @param string $match[2] raw post content
		 */
		if ( 0 !== preg_match( $pattern, $subject, $match ) ) {
			return $match;
		}
		return false;
	}

	/**
	 * Preg Match All
	 *
	 * @param string $subject how to step content.
	 * @return array|false
	 */
	protected static function preg_match_all( $pattern, $subject ) {
		return preg_match_all( $pattern, $subject, $matches ) ? $matches : false;
	}

	/**
	 * Delete option value in shinobi_option
	 *
	 * @return boolean|null
	 */
	protected static function delete_option_value( $id, $option_value, $type = null ) {
		if ( isset( $option_value[ $id ] ) ) {
			if ( $type ) {
				if ( isset( $option_value[ $id ][ $type ] ) ) {
					unset( $option_value[ $id ][ $type ] );
				}
			} else {
				unset( $option_value[ $id ] );
			}
		}
		return $option_value;
	}

	public static function is_text( $string ) {
		return self::mb_trim( wp_strip_all_tags( $string ) );
	}

	/**
	 * Trim for multi byte string
	 *
	 * @param string $string
	 * @return string
	 */
	public static function mb_trim( $string ) {
		return preg_replace( '/\A[\p{C}\p{Z}]++|[\p{C}\p{Z}]++\z/u', '', $string );
	}

}
