<?php

namespace Sfwj\SfwjWeb\Tools\CsvParser;


use Sfwj\SfwjWeb\Patterns\CsvParser;

/**
 * 一般会員以外の
 */
class SimpleMemberCsvParser extends CsvParser {

	/**
	 * {@inheritdoc}
	 */
	protected function extract_post_meta( $row ) {
		return [
			'_yomigana' => $row[1],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	protected function post_data( $row ) {
		return [
			'post_title' => $row[0],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	protected function extract_group( $row ) {
		list( $name, $yomigana, $group ) = $row;
		return $group;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function validate_row( $row ) {
		return ( 3 <= count( $row ) && ! empty( $row[0] ) ) ?: new \WP_Error( 'invalid_row', __( '名前が空です。', 'sfwj' ) );
	}
}
