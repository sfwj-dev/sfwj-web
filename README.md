# SFWJ Custom Plugin

Tags: lightning  
Contributors: sfwj-admin, Takahashi_Fumiki  
Tested up to: 6.1  
Requires at least: 5.9  
Requires PHP: 7.4  
Stable Tag: nightly  
License: GPLv3 or later  
License URI: http://www.gnu.org/licenses/gpl-3.0.txt

日本SF作家クラブ専用のカスタムプラグイン

## Description

W.I.P

## Installation

[Github リリース](https://github.com/sfwj-admin/sfwj-web/releases/)からzipをダウンロードし、プラグイン > 新規追加からアップロードしてください。

## Development

開発にはcomposerとnpmが必要です。

### ローカルのWordPressをセットアップする

```
#必要なパッケージをダウンロード
npm install
#wp-envを利用してWordPressを立てる
npm run setup
```

PhpStormなどのIDEを利用していて、WordPressのローカルインストールが必要な場合は以下のコマンドを利用してください。

```
# 必要なライブラリをインストール
comopser install
# WordPressをローカルにダウンロード
composer setup
```

リポジトリのwpディレクトリにWordPressがダウンロードされます。

### 各種チェックツール

#### PHP構文チェック

PHP CodeSnifferを利用して構文チェックを行います。

```
composer lint
```

#### CSS＆JSの構文チェック

stylelintとeslintを利用して構文チェックを行います。

```
npm run lint
```

## FAQ

W.I.P

## Changelog

### 1.0.0

* 最初のリリース
