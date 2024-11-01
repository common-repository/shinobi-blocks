<?php
/**
 * Class SampleTest
 */

/**
 * Sample test case.
 */
class SampleTest extends WP_UnitTestCase {

	/**
	 * A single example test.
	 */
	public function test_sample() {
		// Replace this with some actual testing code.
		$this->assertTrue( true );
	}

	/**
	 * Check if the values ​​are the same.
	 */
	function test_option_value() {
		update_option( 'shinobi_test', 'my first test!' );
		$this->assertSame( 'my first test!', get_option( 'shinobi_test' ) );
	}

}
