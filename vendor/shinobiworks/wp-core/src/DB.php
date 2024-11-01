<?php
/**
 * Database Functions
 *
 * @category   Functions for WordPress
 * @package    Shinobi_Works\WP
 * @author     Shinobi Works <support@shinobiworks.com>
 * @license    https://www.gnu.org/licenses/gpl-3.0.html/ GPL v3 or later
 * @link       https://shinobiworks.com
 * @since      0.1.1
 */

namespace Shinobi_Works\WP;

class DB {

	const OPTIONS_TABLE = 'shinobi_options';

	/**
	 * Check Table Exists
	 *
	 * @param string $table table name without prefix.
	 * @return boolean
	 */
	public static function is_table_exists( $table ) {
		$flag = true;
		global $wpdb;
		$table = $wpdb->prefix . $table;
		$table = $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$table
			)
		);
		if ( is_null( $table ) ) {
			$flag = false;
		}
		return $flag;
	}

	/**
	 * Create DB Table
	 *
	 * @param string $version current table version.
	 * @param string $table_name table name without prefix.
	 * @param string $sql sql.
	 * @return boolean
	 */
	public static function create_table( $version = '1.0.0', $table_name = '', $sql = '' ) {
		if ( '' === $table_name || '' === $sql ) {
			return false;
		}
		$installed_ver = \get_option( "{$table_name}_table_ver" );
		$is_table      = self::is_table_exists( $table_name );
		if ( $is_table && $version === $installed_ver ) {
			return true; // This table already exists and same version.
		}
		global $wpdb;
		$table_name      = $wpdb->prefix . $table_name;
		$charset_collate = $wpdb->get_charset_collate();
		include_once \ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( "CREATE TABLE $table_name ( $sql ) $charset_collate;" );
		\update_option( $table_name, $version );
		return true;
	}

	/**
	 * Drop DB Table
	 *
	 * @param string $table table name
	 * @return boolean
	 */
	public static function drop_table( $table ) {
		global $wpdb;
		$table = $wpdb->prefix . $table;
		return $wpdb->query( "DROP TABLE $table" );
	}

	/**
	 * Transient pattern
	 *
	 * @param string $table table name
	 * @param string $format object or array_a or array_n
	 * @return string
	 */
	public static function transient_pattern( $table, $format ) {
		return "{$table}_{$format}";
	}

	/**
	 * Get Results
	 *
	 * @param string $table  table name.
	 * @param int    $format database format. The others are "ARRAY_A", "ARRAY_N"
	 * @link https://wpdocs.osdn.jp/%E9%96%A2%E6%95%B0%E3%83%AA%E3%83%95%E3%82%A1%E3%83%AC%E3%83%B3%E3%82%B9/wpdb_Class
	 *
	 * @return array
	 */
	public static function get_results( $table, $format = OBJECT ) {
		global $wpdb;
		$table     = $wpdb->prefix . $table;
		$transient = self::transient_pattern( $table, mb_strtolower( $format ) );
		$record    = get_transient( $transient );
		if ( false === $record ) {
			$record = $wpdb->get_results(
				// @codingStandardsIgnoreStart
				"SELECT * FROM $table",
				// @codingStandardsIgnoreEnd
				$format
			);
			if ( ! $record ) {
				return false;
			}
			$record = $record;
			set_transient( $transient, $record );
		}
		return $record;
	}

	/**
	 * Get row from database
	 *
	 * @param string $table table name.
	 * @param int    $id    unique id.
	 *
	 * @return object|boolean
	 */
	public static function get_row_by_id( $table, $id ) {
		$results = self::get_results( $table );
		if ( false === $results ) {
			return false;
		}
		foreach ( $results as $index => $result ) {
			if ( (int) $id === (int) $result->ID ) {
				return $result;
			}
		}
		return false;
	}

	/**
	 * Get row with array from wpdb
	 *
	 * @param string $table table name.
	 * @param array  $where search pattern.
	 * @return void|boolean
	 */
	public static function get_row( $table, $where = [] ) {
		if ( ! $where || ! is_array( $where ) ) {
			return false;
		}
		$results = self::get_results( $table );
		if ( false === $results ) {
			return false;
		}
		$key   = array_keys( $where )[0];
		$value = array_values( $where )[0];
		foreach ( $results as $index => $row ) {
			if ( isset( $row->$key ) && $row->$key === $value ) {
				return $row;
			}
		}
		return false;
	}

	/**
	 * Insert
	 *
	 * @param string $table  table name.
	 * @param array  $data   insert data.
	 * @param string $format format style.
	 *
	 * @return boolean
	 */
	public static function insert( $table, $data, $format = null ) {
		global $wpdb;
		$wpdb->hide_errors();
		$table  = $wpdb->prefix . $table;
		$result = $wpdb->insert( $table, $data, $format );
		self::delete_shinobi_transient( $table );
		if ( 1 === $result ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Update
	 *
	 * @param string $table        table name.
	 * @param array  $data         update data.
	 * @param string $where        search pattern.
	 * @param string $format       format style.
	 * @param string $where_format format style of search pattern.
	 *
	 * @return void
	 */
	public static function update( $table, $data, $where, $format = null, $where_format = [ '%d' ] ) {
		global $wpdb;
		$table  = $wpdb->prefix . $table;
		$result = $wpdb->update( $table, $data, $where, $format, $where_format );
		self::delete_shinobi_transient( $table );
		return $result;
	}

	/**
	 * Delete Row
	 *
	 * @param string $table        table name.
	 * @param string $where        search pattern.
	 * @param string $where_format format style of search pattern.
	 *
	 * @return boolean
	 */
	public static function delete( $table, $where, $where_format = null ) {
		global $wpdb;
		$table  = $wpdb->prefix . $table;
		$result = $wpdb->delete( $table, $where, $where_format );
		self::delete_shinobi_transient( $table );
		return 0 !== $result ? true : false;
	}

	/**
	 * Delete Transient
	 *
	 * @param string $table table name without wpdb->prefix.
	 *
	 * @depends get_results
	 * @return void
	 */
	public static function delete_shinobi_transient( $table ) {
		// Using mb_strtolower().
		$format_arr = [
			'array_a', // ARRAY_A
			'array_n', // ARRAY_N
			'object', // OBJECT
		];
		foreach ( $format_arr as $format ) {
			\delete_transient( self::transient_pattern( $table, $format ) );
		}
	}

	/**
	 * Get option value from shinobi options table
	 *
	 * @param string $option_name option name.
	 * @param string $default_value default option value.
	 * @return boolean|array
	 */
	public static function get_option( $option_name, $default_value = false ) {
		$transient = self::shinobi_options_transient_pattern( $option_name );
		$record    = \get_transient( $transient );
		if ( false === $record ) {
			$_flag        = true;
			$_options_arr = self::get_results( self::OPTIONS_TABLE );
			if ( ! $_options_arr || ! is_array( $_options_arr ) ) {
				return false;
			}
			foreach ( $_options_arr as $index => $options ) {
				if ( $option_name === $options->option_name ) {
					$record = $options->option_value;
					$_flag  = true;
					break;
				} else {
					$_flag = false;
				}
			}
			if ( $_flag ) {
				set_transient( $transient, $record );
			} else {
				return $default_value;
			}
		}
		return self::is_json( $record ) ? json_decode( $record, true ) : $record;
	}

	/**
	 * Update option value in shinobi options table
	 *
	 * @param string $option_name option name.
	 * @param string $option_value option value.
	 */
	public static function update_option( $option_name, $option_value ) {
		$option_name  = trim( $option_name );
		$option_value = is_array( $option_value ) ? wp_json_encode( $option_value ) : (string) $option_value;
		$row          = self::get_row( self::OPTIONS_TABLE, [ 'option_name' => $option_name ] );
		if ( $row ) {
			self::update(
				self::OPTIONS_TABLE,
				[ 'option_value' => $option_value ],
				[ 'ID' => $row->ID ]
			);
		} else {
			self::insert(
				self::OPTIONS_TABLE,
				[
					'option_name'  => $option_name,
					'option_value' => $option_value,
				]
			);
		}
		\delete_transient( self::shinobi_options_transient_pattern( $option_name ) );
	}

	/**
	 * Delete option value in shinobi options table
	 *
	 * @param string $option_name option name.
	 * @return boolean|null
	 */
	public static function delete_option( $option_name ) {
		if ( ! self::get_option( $option_name ) ) {
			return null;
		}
		\delete_transient( self::shinobi_options_transient_pattern( $option_name ) );
		return self::delete( self::OPTIONS_TABLE, [ 'option_name' => $option_name ] );
	}

	/**
	 * Transient pattern of "shinobi_options"
	 *
	 * @param string $option_name option name
	 * @return string
	 */
	public static function shinobi_options_transient_pattern( $option_name ) {
		return "shinobi_wp_{$option_name}";
	}

	/**
	 * Check if json data
	 *
	 * @param string $string is option data
	 * @return boolean
	 */
	public static function is_json( $string ) {
		return is_string( $string ) && is_array( json_decode( $string, true ) ) && ( JSON_ERROR_NONE === json_last_error() ) ? true : false;
	}

}
