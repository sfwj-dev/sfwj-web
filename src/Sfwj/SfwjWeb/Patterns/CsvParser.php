<?php

namespace Sfwj\SfwjWeb\Patterns;

use function Sfwj\SfwjWeb\get_author_term;
use function Sfwj\SfwjWeb\create_author_term;

/**
 * CSVをパースするクラス
 */
abstract class CsvParser {

	/**
	 * @var \WP_Post CSVのアタッチメント投稿タイプ
	 */
	protected $csv = null;

	/**
	 * コンストラクタ
	 *
	 * @param int $file_id
	 * @return void
	 */
	final public function __construct( $file_id ) {
		$this->csv = get_post( $file_id );
	}

	/**
	 * CSVをインポートする。
	 *
	 * @return int|\WP_Error 成功したら更新した件数、失敗したらWP_Error
	 */
	public function parse() {
		if ( ! $this->csv ) {
			return new \WP_Error( 'invalid_csv', __( 'CSVが指定されていません。', 'sfwj' ) );
		}
		$csv_path = get_attached_file( $this->csv->ID );
		if ( ! $csv_path || ! file_exists( $csv_path ) ) {
			return new \WP_Error( 'invalid_csv', __( 'CSVが見つかりません。', 'sfwj' ) );
		}
		// CSVを読み込み
		$errors = new \WP_Error();
		$file_obj = new \SplFileObject( $csv_path );
		$file_obj->setFlags( \SplFileObject::READ_CSV );
		$line_no = 0;
		$success = 0;
		foreach ( $file_obj as $row ) {
			$line_no++;
			if ( 1 === $line_no ) {
				// ヘッダー行はスキップ
				continue;
			}
			$result = $this->parse_row( $row );
			if ( is_wp_error( $result ) ) {
				$errors->add( $result->get_error_code(), $result->get_error_message() );
			} else {
				$success++;
			}
		}
		if ( $errors->get_error_message() ) {
			return $errors;
		} else {
			return $success;
		}
	}

	/**
	 * CSVの行が問題ないか検討する
	 *
	 * @param string[] $row CSVの各行
	 * @return true|\WP_Error
	 */
	abstract protected function validate_row( $row );

	/**
	 * CSVの各行をインポートする。
	 *
	 * @param string[] $row CSVの1行
	 * @return int|\WP_Error
	 */
	protected function parse_row( $row ) {
		// 投稿を作成する
		$post_args = $this->build_post_data( $row );
		$post_id   = wp_insert_post( $post_args, true );
		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}
		// 投稿メタを保存する
		$meta = $this->extract_post_meta( $row );
		foreach ( $meta as $key => $value ) {
			update_post_meta( $post_id, $key, $value );
		}
		// メンバー種別を保存する
		$group = $this->extract_group( $row );
		if ( $group ) {
			$term_result = wp_set_object_terms( $post_id, $group, 'member-status' );
			if ( is_wp_error( $term_result ) ) {
				return $term_result;
			}
		}
		// 作家名のタグを作成する。
		$this->create_tag( $post_id );
		// 投稿を保存したあとに何かをする
		$result = $this->after_post_insert( $post_id, $row );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		return $post_id;
	}

	/**
	 * 投稿データを作成する
	 *
	 * @param string[] $row CSVの1行
	 * @return array
	 */
	protected function build_post_data( $row ) {
		return array_merge( [
			'post_type'   => 'member',
			'post_status' => 'publish',
		], $this->post_data( $row ) );
	}

	/**
	 * 投稿データを作成する
	 *
	 * @param string[] $row CSVの1行
	 *
	 * @return array
	 */
	abstract protected function post_data( $row );

	/**
	 * 会員種別を抽出する
	 *
	 * @param string[] $row CSVの1行
	 * @return string
	 */
	abstract protected function extract_group( $row );

	/**
	 * 投稿メタデータを抽出する
	 *
	 * @param string[] $row CSVの1行
	 * @return array meta_key => value 形式の連想配列
	 */
	abstract protected function extract_post_meta( $row );

	/**
	 * 投稿を保存したあとに何かをする
	 *
	 * @param int      $post_id 投稿ID
	 * @param string[] $row     CSVの1行
	 *
	 * @return true|\WP_Error
	 */
	protected function after_post_insert( $post_id, $row ) {
		return true;
	}

	/**
	 * 作家と同姓同名のタグを作り、アサインする。
	 *
	 * @param int $post_id 作家タグ
	 * @return void
	 */
	protected function create_tag( $post_id ) {
		$term = get_author_term( $post_id );
		if ( $term ) {
			return;
		}
		create_author_term( $post_id );
	}
}
