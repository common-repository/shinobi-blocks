<?php
/**
 * FAQ
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

/**
 * FAQ Class
 */
class FAQ extends Factory {

	const OPTION_NAME = 'faq_block';

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();
		add_action( 'save_post', [ $this, 'save_post' ], 100, 3 );
		add_action( '4536' === get_template() ? 'wp_head_4536' : 'wp_head', [ $this, 'add_structured_data_to_head' ], 30, 1 );
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
		$master = DB::get_option( self::OPTION_NAME );
		if ( ! isset( $master[ $id ] ) ) {
			$master[ $id ] = [];
		}
		// Get post content.
		$post_content = $post->post_content;
		// Get faq attributes group.
		$faq_data = self::get_data( $post_content );
		if ( ! $faq_data ) {
			// Delete option value.
			$master = parent::delete_option_value( $id, $master );
		} else {
			$faq = self::generate_faq_data( $faq_data );
			// Get some data.
			if ( $faq ) {
				$structured_data = [
					'@context'   => 'https://schema.org',
					'@type'      => 'FAQPage',
					'mainEntity' => $faq,
				];
				$master[ $id ]   = [
					'structured_data' => $structured_data,
				];
			} else {
				$master = parent::delete_option_value( $id, $master, 'structured_data' );
			}
			// Add timestamp.
			$master[ $id ]['last_update'] = current_time( 'timestamp' );
		}
		if ( $master ) {
			ksort( $master );
			DB::update_option( self::OPTION_NAME, $master );
		} else {
			DB::delete_option( self::OPTION_NAME );
		}
	}

	/**
	 * Get FAQ Attributes
	 *
	 * @param string $subject post content
	 * @return array|false
	 */
	public static function get_data( $subject ) {
		$pattern = '/<!-- wp:shinobi-blocks\/faq-item (.+?)-->(.+?)<!-- \/wp:shinobi-blocks\/faq-item -->/s';
		$match   = parent::preg_match_all( $pattern, $subject );
		return $match ? $match : false;
	}

	/**
	 * Generate FAQ Data
	 *
	 * @param array $data data after preg_match_all
	 * @return array|false
	 */
	public static function generate_faq_data( $data ) {
		$faq              = [];
		$attributes_group = $data[1];
		$content_group    = $data[2];
		$count            = count( $attributes_group );
		for ( $i = 0; $i < $count; $i++ ) {
			$attributes = json_decode( trim( $attributes_group[ $i ] ), true );
			$content    = trim( $content_group[ $i ] );
			$question   = self::get_question( $attributes );
			$answer     = self::get_answer( $content );
			if ( $question && $answer ) {
				$faq[] = [
					'@type'          => 'Question',
					'name'           => $question,
					'acceptedAnswer' => [
						'@type' => 'Answer',
						'text'  => $answer,
					],
				];
			}
		}
		return $faq;
	}

	/**
	 * Get Question Text
	 *
	 * @param array $json attributes
	 * @return string|null
	 */
	public static function get_question( $json ) {
		$question = $json['question'];
		return $question ? $question : null;
	}

	/**
	 * Get Answer Text
	 *
	 * @param array $content faq content
	 * @link https://developers.google.com/search/docs/data-types/faqpage?hl=ja#answer
	 * @return string|null|false
	 */
	public static function get_answer( $content ) {
		$content = self::get_answer_regex( $content ); // Strict Mode
		if ( ! $content ) {
			return false;
		}
		$allowable_tags = [
			'<br>',
			'<ol>',
			'<ul>',
			'<li>',
			'<a>',
			'<p>',
			'<b>',
			'<strong>',
			'<i>',
			'<em>',
		];
		$allowable_tags = implode( '', $allowable_tags );
		$answer         = strip_tags( $content, $allowable_tags );
		$answer         = preg_replace( '/(?:\r\n){1,}|\n{1,}|\r{1,}/', '<br>', trim( $answer ) );
		return parent::is_text( $answer ) ? $answer : null;
	}

	public static function get_answer_regex( $subject ) {
		$pattern = '/<div.+?class="([\w\-\_\s]+?)".*?>([\s\S]+?)<\/div>/';
		if ( 2 === preg_match_all( $pattern, $subject, $match ) ) {
			foreach ( $match[1] as $index => $class ) {
				$search = 'shinobi-blocks_faq-answer-text';
				if ( false !== strpos( $class, $search ) ) {
					return $match[2][ $index ];
				}
			}
		}
		return false;
	}

	/**
	 * Add structured data inside head tag
	 *
	 * @return void
	 */
	public function add_structured_data_to_head() {
		if ( ! is_singular() ) {
			return;
		}
		$option_value = DB::get_option( self::OPTION_NAME );
		$id           = get_queried_object_id();
		if ( isset( $option_value[ $id ] ) ) {
			$option_value = $option_value[ $id ];
			if ( isset( $option_value['structured_data'] ) ) {
				echo '<script type="application/ld+json">' . wp_json_encode( $option_value['structured_data'] ) . '</script>';
			}
		}
	}

}
