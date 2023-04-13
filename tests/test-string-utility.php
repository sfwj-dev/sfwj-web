<?php


/**
 * Test autoloader.
 *
 * @package kunoichi
 */
class StringUtilityTest extends WP_UnitTestCase {

	public function test_column() {
		$parser = new \Sfwj\SfwjWeb\Tools\CsvParser\NormalMemberCsvParser(0);
		$this->assertEquals( 3, $parser->column_to_index( 'D' ) );
		$this->assertEquals( 29, $parser->column_to_index( 'AC' ) );
	}
}
