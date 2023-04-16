<?php

namespace Sfwj\SfwjWeb\Patterns;


/**
 * シングルトンパターン
 *
 * すべての処理を1クラスにまとめるためのもの
 */
abstract class SingletonPattern {

	/**
	 * @var static[] インスタンスを保持する
	 */
	private static $instances = [];

	/**
	 * コンストラクタ。拡張禁止。
	 */
	final protected function __construct() {
		$this->init();
	}

	/**
	 * コンストラクタ内で実行される。フックなどを登録。
	 *
	 * @return void
	 */
	protected function init() {
		// Do something here.
	}

	/**
	 * インスタンスを取得する。
	 *
	 * @return static
	 */
	public static function get() {
		$class_name = get_called_class();
		if ( ! isset( self::$instances[ $class_name ] ) ) {
			self::$instances[ $class_name ] = new static();
		}
		return self::$instances[ $class_name ];
	}
}
