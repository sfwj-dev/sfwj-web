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

	public function test_sheet_id() {
		$url1 = 'https://docs.google.com/spreadsheets/d/19Gs86W1mznCMlIVCmWfM8WQ2IZPvwIAiXNK5N4G7r0A/edit?gid=1056589072#gid=1056589072';
		$url2 = 'https://docs.google.com/spreadsheets/d/19Gs86W1mznCMlIVCmWfM8WQ2IZPvwIAiXNK5N4G7r0A/edi#gid=1056589072';
		$this->assertEquals( sfwj_extract_sheet_id( $url1 ) , '1056589072' );
		$this->assertEquals( sfwj_extract_sheet_id( $url2 ) , '1056589072' );
		$url3 = 'https://example.com';
		$this->assertInstanceOf( 'WP_Error', sfwj_extract_sheet_id( $url3 ) );
	}
}
