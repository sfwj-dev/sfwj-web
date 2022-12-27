<?php


/**
 * Test autoloader.
 *
 * @package kunoichi
 */
class AutoloaderTest extends WP_UnitTestCase {

	public function test_register() {
		$this->assertEquals( sfwj_base_dir(), dirname( __DIR__ ) );
		$this->assertTrue( true );
	}
}
