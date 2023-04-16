<?php

namespace Sfwj\SfwjWeb\Tools\CsvParser;


use Sfwj\SfwjWeb\MemberWorks;
use Sfwj\SfwjWeb\Patterns\CsvParser;

/**
 * 一般会員をインポートするパーサー
 *
 * @see https://docs.google.com/spreadsheets/d/1MEkU8lHvd2Jsaz1JxFmeysH85m9A7m8vV8bNG2HWSpY/edit#gid=1780145910
 */
class NormalMemberCsvParser extends CsvParser {

	/**
	 * {@inheritdoc}
	 */
	protected function extract_post_meta( $row ) {
		$meta = [
			'_yomigana'      => $row[ $this->column_to_index( 'E' ) ],
			'name_en'        => $row[ $this->column_to_index( 'F' ) ],
			'desc_en'        => $row[ $this->column_to_index( 'H' ) ],
			'desc_misc'      => $row[ $this->column_to_index( 'I' ) ],
			'_profile_pic'   => $row[ $this->column_to_index( 'J' ) ],
			'_thumbnail_pic' => $row[ $this->column_to_index( 'K' ) ],
			'_external_url'  => implode( "\n", array_values( array_filter( [
				$row[ $this->column_to_index( 'L' ) ],
				$row[ $this->column_to_index( 'M' ) ],
				$row[ $this->column_to_index( 'N' ) ],
			] ) ) ),
		];
		return $meta;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function post_data( $row ) {
		$data = [
			'post_title'   => $row[3],
			'post_content' => wpautop( $row[ $this->column_to_index( 'G' ) ] ),
			'post_date'    => ! empty( $row[0] ) ? str_replace( '/', '-', $row[0] ) : current_time( 'mysql' ),
		];
		$slug = $this->extract_slug( $row );
		if ( $slug ) {
			$data['post_name'] = $slug;
		}

		return $data;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function extract_group( $row ) {
		return '一般会員';
	}

	/**
	 * {@inheritdoc}
	 */
	protected function validate_row( $row ) {
		return ( 11 <= count( $row ) && ! empty( $row[3] ) ) ?: new \WP_Error( 'invalid_row', __( '名前が空です。', 'sfwj' ) );
	}

	/**
	 * 英語名が入力されていればそれをスラッグにし、なければ何もしない
	 *
	 * @param string[] $row CSVの行
	 * @return string
	 */
	private function extract_slug( $row ) {
		if ( ! empty( $row[5] ) ) {
			return $row[5];
		}
		$name = strtolower( $row[5] );
		$name = remove_accents( $name );
		return preg_replace( '/[^a-z0-9]/', '-', $name );
	}

	/**
	 * {@inheritdoc}
	 */
	protected function after_post_insert( $post_id, $row ) {
		$work_index = $this->column_to_index( 'O' );
		$error      = new \WP_Error();
		for ( $i = 0; $i < 10; $i++ ) {
			$start_index    = $i * 3 + $work_index;
			$title_or_index = $row[ $start_index ];
			if ( empty( $title_or_index ) ) {
				// タイトルが空の場合はスキップ。
				continue;
			}
			$maybe_isbn = preg_replace( '/[\-ー]/u', '', trim( $title_or_index ) );
			if ( preg_match( '/^[0-9]{13}$/u', $maybe_isbn ) ) {
				// ISBN13
				$isbn = $maybe_isbn;
			} elseif ( preg_match( '/^[0-9]{9}[0-9xX]$/u', $maybe_isbn ) ) {
				// ISBN10
				$isbn = $this->isbn10_to_13( $maybe_isbn );
			} else {
				$isbn = '';
			}
			// ISBNありなしで処理を変更
			$author_work = MemberWorks::get();
			if ( $isbn ) {
				$result = $author_work->register_work_with_isbn( $post_id, $isbn );
			} elseif ( $title_or_index ) {
				$result = $author_work->register_work( $post_id, $title_or_index, $row[ $start_index + 1 ], $row[ $start_index + 2 ] );
			}
			if ( is_wp_error( $result ) ) {
				$error->add( $result->get_error_code(), $result->get_error_message() );
			}
		}
		return $error->get_error_messages() ? $error : true;
	}
}
