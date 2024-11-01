<?php
/**
 * How To
 *
 * @category   Plugin
 * @package    WordPress
 * @subpackage Shinobi Blocks
 * @author     Shinobi Works <support@shinobiworks.com>
 * @license    https://www.gnu.org/licenses/gpl-3.0.html/ GPL v3 or later
 * @link       https://shinobiworks.com
 * @since      1.0.0
 */

namespace Shinobi_Blocks\App\Factory;

use Shinobi_Blocks\App\Factory;
use Shinobi_Works\WP\DB;

use function Shinobi_Blocks\shinobi_blocks_config;

/**
 * How To Class
 */
class HowTo extends Factory {

	const OPTION_NAME = 'how_to_block';

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();
		add_action( 'save_post', [ $this, 'save_post' ], 100, 3 );
		add_action( '4536' === get_template() ? 'wp_head_4536' : 'wp_head', [ $this, 'add_structured_data_to_head' ], 30, 1 );
		add_filter( 'inline_style_4536', [ $this, 'add_inline_style_inside_amp_head' ], 30, 1 );
	}

	/**
	 * Save post data with save_post action
	 *
	 * @param int     $id Post id.
	 * @param object  $post Post data object.
	 * @param boolean $update If post data is old, this value is "true".
	 * @return void
	 */
	public function save_post( $id, $post, $update ) {
		// Get real post id.
		$id = parent::get_save_post_id( $id );
		// Set master data.
		$how_to_step_master = self::get_how_to_step_all_option_value();
		if ( ! isset( $how_to_step_master[ $id ] ) ) {
			$how_to_step_master[ $id ] = [
				'css' => self::get_how_to_step_dot_color(),
			];
		}
		// Get data of how to.
		$data = self::get_how_to_data( $post->post_content );
		if ( ! $data ) {
			// Delete option value.
			$how_to_step_master = parent::delete_option_value( $id, $how_to_step_master );
		} else {
			// Prepare how to data.
			$how_to_attributes = json_decode( $data[1], true );
			$how_to_content    = $data[2];
			// Get some data.
			if ( self::is_section( $how_to_attributes ) ) {
				// Get how to section data.
				$how_to_name  = self::get_heading( $how_to_content );
				$section_data = self::get_how_to_section_data( $how_to_content );
				$step         = [];
				foreach ( $section_data[2] as $i => $section ) {
					$how_to_section_data    = self::generate_how_to_data( $section );
					$how_to_section_heading = $how_to_section_data['heading'];
					$how_to_section_step    = $how_to_section_data['step'];
					if ( $how_to_section_heading && $how_to_section_step ) {
						$step[] = [
							'@type'           => 'HowToSection',
							'name'            => $how_to_section_heading,
							'itemListElement' => $how_to_section_step,
						];
					} else {
						$step = false;
					}
					$css[] = $how_to_section_data['css'];
				}
				// Delete duplicate css.
				$css = array_unique( $css );
				$css = array_values( $css );
				// Convert to strings.
				$css = implode( '', $css );
			} else {
				// Get only how to step data.
				$how_to_data = self::generate_how_to_data( $how_to_content );
				$how_to_name = $how_to_data['heading'];
				$step        = $how_to_data['step'];
				$css         = $how_to_data['css'];
			}
			if ( $how_to_name && $step ) {
				// Ready structured data.
				$structured_data = [
					'@context' => 'http://schema.org',
					'@type'    => 'HowTo',
					'name'     => $how_to_name,
					'step'     => $step,
				];
				// If there is a description.
				$description = self::get_description( $how_to_attributes );
				if ( $description ) {
					$structured_data['description'] = $description;
				}
				/**
				 * Ready save data for mysql database.
				 */
				$how_to_step_master[ $id ] = [
					'structured_data' => $structured_data,
					'css'             => $css,
				];
			} else {
				$how_to_step_master = parent::delete_option_value( $id, $how_to_step_master, 'structured_data' );
			}
			// Add timestamp.
			$how_to_step_master[ $id ]['last_update'] = current_time( 'timestamp' );
		}
		if ( $how_to_step_master ) {
			ksort( $how_to_step_master );
			DB::update_option( self::OPTION_NAME, $how_to_step_master );
		} else {
			DB::delete_option( self::OPTION_NAME );
		}
	}

	/**
	 * Get how to step content
	 *
	 * @param string $subject post content.
	 * @return string|false
	 */
	public static function get_how_to_data( $subject ) {
		// Search pattern.
		$pattern = '/<!-- wp:shinobi-blocks\/how-to (.*?)-->(.+?)<!-- \/wp:shinobi-blocks\/how-to -->/s';
		return parent::preg_match( $pattern, $subject );
	}

	/**
	 * Generate how to data
	 *
	 * @param string $content how to content
	 * @return array|false
	 */
	public static function generate_how_to_data( $content ) {
		if ( ! $content ) {
			return false;
		}
		// Get name of how to.
		$heading = self::get_heading( $content );
		if ( ! $heading ) {
			return false;
		}
		// Get data of how to section.
		$step_data = self::get_how_to_step_data( $content );
		// Get content of how to step item.
		$step = self::get_how_to_step_item_content( $step_data[2] );
		if ( ! $step ) {
			return false;
		}
		// Get attributes of how to step item.
		$item_attributes = json_decode( $step_data[1][0], true );
		// Get dot color.
		$css = self::get_how_to_step_dot_color( $item_attributes );
		return compact( 'heading', 'step', 'css' );
	}

	/**
	 * Check if section attribute is used
	 *
	 * @param array $json attributes
	 * @return string
	 */
	public static function is_section( $json ) {
		return $json['useSections'] ? true : false;
	}

	/**
	 * Get description
	 *
	 * @param array $json attributes
	 * @return string
	 */
	public static function get_description( $json ) {
		return $json['description'] ? $json['description'] : null;
	}

	/**
	 * Get how to step heading
	 *
	 * @param string $subject how to step content.
	 * @return string|boolean
	 */
	public static function get_heading( $subject ) {
		$pattern = '/<h[2-5].*?>(.*?)<\/h[2-5]>/';
		if ( 0 !== preg_match( $pattern, $subject, $match ) ) {
			if ( parent::is_text( $match[1] ) ) {
				return wp_strip_all_tags( $match[1] );
			}
		}
		return false;
	}

	/**
	 * Get how to sections
	 *
	 * @param string $subject how to content.
	 * @return array|false
	 */
	public static function get_how_to_section_data( $subject ) {
		$pattern = '/<!-- wp:shinobi-blocks\/how-to-section (.*?)-->([\s\S]+?)<!-- \/wp:shinobi-blocks\/how-to-section -->/';
		return parent::preg_match_all( $pattern, $subject );
	}

	/**
	 * Get how to step item
	 *
	 * @param string $subject how to content.
	 * @return array|false
	 */
	public static function get_how_to_step_data( $subject ) {
		$pattern = '/<!-- wp:shinobi-blocks\/how-to-step (.*?)-->(.+?)<!-- \/wp:shinobi-blocks\/how-to-step -->/s';
		return parent::preg_match_all( $pattern, $subject );
	}

	/**
	 * Get dot color
	 *
	 * @param array $json attributes
	 * @return string
	 */
	public static function get_how_to_step_dot_color( $json = [] ) {
		$config          = shinobi_blocks_config( 'color' );
		$primary_color   = isset( $json['primaryColor'] ) ? $json['primaryColor'] : $config['primary'];
		$secondary_color = isset( $json['secondaryColor'] ) ? $json['secondaryColor'] : $config['secondary'];
		$color_type      = isset( $json['colorType'] ) ? $json['colorType'] : null;
		$class           = '.shinobi-blocks_how-to-step-dot';
		$dot_id          = isset( $json['dotId'] ) ? "[data-dot-id=\"{$json['dotId']}\"]" : '';
		if ( $dot_id && ( $json['primaryColor'] || $json['secondaryColor'] || $color_type ) ) {
			$class .= $dot_id;
		}
		switch ( $color_type ) {
			case 'primary':
				$css = "background:$primary_color";
				break;
			case 'secondary':
				$css = "background:$secondary_color";
				break;

			default:
				$css = "background:linear-gradient(to right, $primary_color, $secondary_color)";
				break;
		}
		$css = "$class{{$css}}";
		return $css;
	}

	/**
	 * Get content of how to step item
	 *
	 * @param array $matches array of preg_match_all
	 * @return array|false
	 */
	public static function get_how_to_step_item_content( $content ) {
		if ( ! is_array( $content ) || 2 > (int) count( $content ) ) {
			return false;
		}
		$item = [];
		foreach ( $content as $i => $new_subject ) {
			// Get text.
			$pattern = '/<p.+?>([\s\S]+?)<\/p>/';
			if ( 0 !== preg_match( $pattern, $new_subject, $match ) ) {
				$text = wp_strip_all_tags( $match[1] );
				// Check if text exists.
				if ( parent::is_text( $text ) ) {
					$item[ $i ]['text'] = $text;
				} else {
					return false;
				}
			} else {
				return false;
			}
			// Get image.
			$pattern = '/<img.+?src="(.+?)".*?>/';
			if ( 0 !== preg_match( $pattern, $new_subject, $match ) ) {
				$item[ $i ]['image'] = $match[1];
			}
			$item[ $i ]['@type'] = 'HowToStep';
		}
		return $item;
	}

	/**
	 * Get all shinobi option value
	 *
	 * @return array|false
	 */
	public static function get_how_to_step_all_option_value() {
		return DB::get_option( self::OPTION_NAME );
	}

	/**
	 * Get option value from shinobi_option table
	 *
	 * @return array|string|false
	 */
	public static function get_how_to_step_option_value( $type = null ) {
		if ( ! is_singular() ) {
			return false;
		}
		$option_value = self::get_how_to_step_all_option_value();
		$id           = get_queried_object_id();
		if ( ! isset( $option_value[ $id ] ) ) {
			return false;
		}
		$option_value = $option_value[ $id ];
		return $type && isset( $option_value[ $type ] ) ? $option_value[ $type ] : $option_value;
	}

	/**
	 * Add structured data inside head tag
	 *
	 * @return void
	 */
	public function add_structured_data_to_head() {
		$structured_data = self::get_how_to_step_option_value( 'structured_data' );
		if ( $structured_data ) {
			echo '<script type="application/ld+json">' . wp_json_encode( $structured_data ) . '</script>';
		}
	}

	/**
	 * Add inline css inside head of amp page
	 *
	 * @param array $css
	 * @return array|false
	 *
	 * @package WordPress Theme 4536
	 * @link https://github.com/shinobiworks/4536/blob/master/resources/css/_init.php
	 */
	public function add_inline_style_inside_amp_head( $css ) {
		$how_to_css = self::get_how_to_step_option_value( 'css' );
		if ( $how_to_css ) {
			$css[] = $how_to_css;
		}
		return $css;
	}

}
