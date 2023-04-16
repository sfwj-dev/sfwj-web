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
		$this->assertEquals( 28, $parser->column_to_index( 'AC' ) );
	}

	public function test_link() {
		$string = 'https://example.com';
		$this->assertEquals( '<a href="https://example.com" target="_blank" rel="noopener noreferrer">https://example.com</a>', sfwj_linkify( $string ) );
		$string = 'https://mstdn.jp/@writer';
		$this->assertEquals( '<a href="https://mstdn.jp/@writer">https://mstdn.jp/@writer</a>', sfwj_linkify( $string, false ) );
	}
}
