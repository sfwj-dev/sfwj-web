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

### 有償テーマを配置する

`addons` ディレクトリに以下のプラグイン・テーマをダウンロードしてください。

```
- addons
  - themes
    - lightning-pro
  - plugins
    - vk-all-in-one-expansin-unit
    - vk-blocks-pro
```

### ローカルのWordPressをセットアップする

```
# 必要なPHPライブラリをインストール
comopser install
# 必要なnpmパッケージをダウンロード
npm install
# wp-envを利用してWordPressを立てる
npm run setup
# WordPressを停止する
npm stop
# WordPressを再開する
npm start
```

PhpStormなどのIDEを利用していて、WordPressのローカルインストールが必要な場合は以下のWP-CLIコマンドを利用してください。

```
# WordPressをローカルのwpディレクトリにダウンロード
mkdir wp
cd wp
wp core download
```

リポジトリのwpディレクトリにWordPressがダウンロードされます。

### 各種チェックツール

### ユニットテストを実行する

wp-envが実行されている状態で以下のコマンドを実行すると、ユニットテストを行うことができます。

```
npm run test
```

#### PHP構文チェック

PHP CodeSnifferを利用して構文チェックを行います。

```
composer lint
```

軽微な文法エラーは以下のコマンドで修正できます。

```
composer fix
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
