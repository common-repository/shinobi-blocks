<?php
/**
 * Class DB_Test
 */

use Shinobi_Works\WP\Bootstrap;
use Shinobi_Works\WP\DB;

/**
 * DB test case.
 */
class DB_Test extends WP_UnitTestCase {

	const TABLE_NAME = 'my_awesome_table';

	/**
	 * Default global variable
	 *
	 * @var string
	 */
	private $test_user_name = 'Shinobi Works';
	private $test_comment   = 'My First Comment';

	/**
	 * New global variable
	 *
	 * @var string
	 */
	private $new_test_user_name = 'Shinobi Works 2.0';
	private $new_test_comment   = 'My Second Comment';

	/**
	 * Before class runnning
	 *
	 * @return void
	 */
	public static function setUpBeforeClass() {
		new Bootstrap();
		do_action( 'init' );
	}

	/**
	 * Setup
	 */
	public function setUp() {
	}

	/**
	 * Teardown
	 */
	public function tearDown() {
	}

	/**
	 * Test creat_table()
	 */
	public function test_create_table() {
		$version = '1.0.0';
		$sql     = '
		ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		user_name varchar(255) NOT NULL,
		comment varchar(1000) NOT NULL,
		PRIMARY KEY  id (ID)
		';
		$result  = DB::create_table( $version, self::TABLE_NAME, $sql );
		$this->assertTrue( $result );
	}

	/**
	 * Test is_table_exists()
	 *
	 * @depends test_create_table
	 */
	public function test_is_table_exists() {
		$result = DB::is_table_exists( self::TABLE_NAME );
		$this->assertTrue( $result );
	}

	/**
	 * Test insert()
	 *
	 * @depends test_create_table
	 */
	public function test_insert() {
		$data = [
			'user_name' => $this->test_user_name,
			'comment'   => $this->test_comment,
		];
		$this->assertTrue( DB::insert( self::TABLE_NAME, $data ) );
	}

	/**
	 * Test get_results()
	 *
	 * @depends test_insert
	 */
	public function test_get_results() {
		/**
		 * Get results
		 */
		$get_results = DB::get_results( self::TABLE_NAME )[0];
		$this->assertSame(
			$this->test_user_name,
			$get_results->user_name
		);
	}

	/**
	 * Test get_results() for default database
	 */
	public function test_get_results_default() {
		/**
		 * Object
		 */
		$results = DB::get_results( 'users' )[0];
		// ID
		$expected = '1';
		$actual   = $results->ID;
		$this->assertSame( $expected, $actual );
		// Email
		$expected = 'admin@example.org';
		$actual   = $results->user_email;
		$this->assertSame( $expected, $actual );
		/**
		 * Array of usermeta table
		 */
		$results = DB::get_results( 'usermeta', ARRAY_A )[0];
		// Meta key
		$expected = 'nickname';
		$actual   = $results['meta_key'];
		$this->assertSame( $expected, $actual );
		// Meta value
		$expected = 'admin';
		$actual   = $results['meta_value'];
		$this->assertSame( $expected, $actual );
	}

	/**
	 * Test get_row_by_id()
	 *
	 * @depends test_insert
	 */
	public function test_get_row_by_id() {
		$get_row_by_id = DB::get_row_by_id( self::TABLE_NAME, 1 );
		$this->assertSame(
			$this->test_comment,
			$get_row_by_id->comment
		);
	}

	/**
	 * Test get_row()
	 *
	 * @depends test_insert
	 */
	public function test_get_row() {
		$where   = [
			'user_name' => $this->test_user_name,
		];
		$results = DB::get_row( self::TABLE_NAME, $where );
		$this->assertSame( $this->test_user_name, $results->user_name );
	}


	/**
	 * Test get_row() for default database
	 */
	public function test_get_row_default() {
		/**
		 * Case.1 by "users table"
		 */
		$user_nicename  = 'admin';
		$results_case_1 = DB::get_row(
			'users',
			[
				'user_nicename' => $user_nicename,
			]
		);
		$this->assertSame( $user_nicename, $results_case_1->user_nicename );
		/**
		 * Case.2 by "options table"
		 */
		$results_case_2 = DB::get_row(
			'options',
			[
				'option_name' => 'admin_email',
			]
		);
		$this->assertSame( $results_case_2->option_value, 'admin@example.org' );
	}

	/**
	 * Test update()
	 *
	 * @depends test_create_table
	 */
	public function test_update() {
		$update_data   = [
			'user_name' => $this->new_test_user_name,
			'comment'   => $this->new_test_comment,
		];
		$where         = [ 'ID' => 1 ];
		$update_result = DB::update( self::TABLE_NAME, $update_data, $where );
		$this->assertSame( 1, $update_result );
	}

	/**
	 * Test delete()
	 *
	 * @depends test_update
	 */
	public function test_delete() {
		// Wrong case.
		$wrong_where  = [ 'user_name' => $this->test_user_name ];
		$wrong_result = DB::delete( self::TABLE_NAME, $wrong_where );
		$this->assertFalse( $wrong_result );
		// Currect case.
		$where  = [ 'user_name' => $this->new_test_user_name ];
		$result = DB::delete( self::TABLE_NAME, $where );
		$this->assertTrue( $result );
	}

	/**
	 * Testing value for options table
	 *
	 * @var string
	 */
	private $first_option_name   = 'my_first_option_name';
	private $first_option_value  = 'my_first_option_value';
	private $second_option_name  = 'my_second_option_name';
	private $second_option_value = [
		'drink' => 'beer',
		'where' => 'bar',
		'who'   => 'friends',
	];

	/**
	 * Test get_option()
	 */
	public function test_get_option() {
		$this->assertFalse( DB::get_option( 'wrong_option_name' ) );
	}

	/**
	 * Test update_option(), get_option()
	 */
	public function test_insert_option() {
		/**
		 * Insert pattern 1
		 */
		DB::update_option( $this->first_option_name, $this->first_option_value );
		$this->assertSame( $this->first_option_value, DB::get_option( $this->first_option_name ) );
		/**
		 * Insert pattern 2
		 */
		DB::update_option( $this->second_option_name, $this->second_option_value );
		$this->assertSame( $this->second_option_value, DB::get_option( $this->second_option_name ) );
	}

	/**
	 * Test update_option(), get_option()
	 *
	 * @depends test_insert_option
	 */
	public function test_update_option() {
		// Check option value exists.
		$this->assertSame( $this->first_option_value, DB::get_option( $this->first_option_name ) );
		$this->assertSame( $this->second_option_value, DB::get_option( $this->second_option_name ) );
		/**
		 * Update pattern 1
		 */
		$first_update_option_value = 'I am super first option value';
		DB::update_option( $this->first_option_name, $first_update_option_value );
		$this->assertSame( $first_update_option_value, DB::get_option( $this->first_option_name ) );
		/**
		 * Update pattern 2
		 */
		$second_update_option_value = 'I cloud not go home.';
		DB::update_option( $this->second_option_name, $second_update_option_value );
		$this->assertSame( $second_update_option_value, DB::get_option( $this->second_option_name ) );
	}

	/**
	 * Test update_option()
	 *
	 * @return void
	 */
	public function test_update_option_iregular() {
		$option_name = 'iregular_option';

		DB::update_option( $option_name, '' );
		$this->assertSame( '', DB::get_option( $option_name ) );

		DB::update_option( $option_name, null );
		$this->assertSame( '', DB::get_option( $option_name ) );

		DB::update_option( $option_name, true );
		$this->assertSame( '1', DB::get_option( $option_name ) );

		DB::update_option( $option_name, false );
		$this->assertSame( '', DB::get_option( $option_name ) );

		DB::update_option( $option_name, [] );
		$this->assertSame( [], DB::get_option( $option_name ) );

		DB::update_option( $option_name, [ 1, 2, 3 ] );
		$this->assertSame( [ 1, 2, 3 ], DB::get_option( $option_name ) );

		DB::update_option( $option_name, [ 'a', 'b', 'c' ] );
		$this->assertSame( [ 'a', 'b', 'c' ], DB::get_option( $option_name ) );

		$test_array = [
			'a' => 1,
			'b' => 2,
			'c' => [],
		];
		DB::update_option( $option_name, $test_array );
		$this->assertSame( $test_array, DB::get_option( $option_name ) );
	}

	/**
	 * Test delete_option()
	 *
	 * @depends test_update_option
	 */
	public function test_delete_option() {
		$this->assertTrue( DB::delete_option( $this->first_option_name ) );
		$this->assertFalse( DB::get_option( $this->first_option_name ) );
	}

	/**
	 * Test check tables
	 *
	 * @doesNotPerformAssertions
	 */
	public function test_check_tables() {
		return;
		// To check list of test database table, use below command.
		global $wpdb;
		var_dump( $wpdb->get_results( 'SHOW TABLES' ) );
		$this->assertTrue( true );
	}

	/**
	 * Test drop_table()
	 */
	public function test_drop_table() {
		$this->assertTrue( DB::drop_table( self::TABLE_NAME ) );
		$this->assertTrue( DB::drop_table( DB::OPTIONS_TABLE ) );
	}

}
