<?php

namespace Shinobi_Works\WP;

use Shinobi_Works\WP\DB;

class Bootstrap {

	public function __construct() {
		add_action( 'init', [ $this, 'create_options_table' ] );
	}

	/**
	 * Create "shinobi_options" table
	 *
	 * @return void
	 */
	public function create_options_table() {
		$version = '1.0.1';
		$table   = DB::OPTIONS_TABLE;
		$sql     = '
		ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		option_name varchar(191) NOT NULL,
		option_value longtext NOT NULL,
		PRIMARY KEY  id (ID)
		';
		DB::create_table( $version, $table, $sql );
	}

}
