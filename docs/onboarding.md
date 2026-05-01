# オンボーディングガイド

新規参画者向けの環境構築・開発フロー・デプロイ手順。

> このリポジトリは Private のため、まず読み取り権限が付与されている
> ことを確認してください。アクセスできない場合は Daiki さんに依頼。

---

## 1. このプロジェクトとは

`https://your-doctor.jp/media/` AGAメディアの WordPress 子テーマ + プラグイン。

担当できる作業の例：
- プラグイン `yd-supervisor` の機能追加・修正
- 子テーマ `swell_child_custmon` のデザイン調整
- ドキュメント整備
- 外部システム（記事作成・GAS 等）からの REST 連携

外部システム（前中氏システム）からの連携が主目的の方は、本書の §4 と
[`api.md`](api.md) を集中して読んでください。コードを直接変更しない場合は、
ローカル環境構築（§2）はスキップしても OK です。

---

## 2. ローカル環境構築

### 必須ツール

| ツール | バージョン | 用途 |
|---|---|---|
| Git | 2.30+ | 言うまでもなく |
| GitHub アカウント | — | コラボレーター登録が必要 |
| PHP | 8.0+ | `php -l` 構文チェック用（推奨） |
| エディタ | VS Code 等 | お好みで |

PHP は macOS の場合 Homebrew で：

```bash
brew install php
```

### リポジトリのクローン

SSH 認証推奨：

```bash
git clone git@github.com:catchthestar65/yourdoctor_cms.git
cd yourdoctor_cms
```

GitHub アカウントが複数ある場合は、SSH config で正しい鍵を指定するか、
HTTPS + Personal Access Token でクローンする。

### ローカル WP 環境（任意）

このプロジェクトは**ローカル WP 環境を構築しない方針**で進めています。
動作確認は本番（`/media/` 配下）に直接デプロイしてから行います。
理由：

- 新規メディアで既存記事ゼロ → 壊しても失うコンテンツがない
- サブディレクトリ運用 → 親サイト `your-doctor.jp/` には影響しない

ローカル WP を立てたい場合は **Local by Flywheel** が推奨ですが、
SWELL 親テーマ（有料）と ACF Pro（有料）のインストールが必要なため
セットアップに時間がかかります。

---

## 3. ブランチ運用 / 開発フロー

### ブランチ

| ブランチ | 役割 |
|---|---|
| `main` | 本番反映済み。`main` への push で自動 FTP デプロイ |
| `develop` | 統合検証（必要時のみ。現状はあまり使わない） |
| `feature/xxx` | 機能・修正単位の作業ブランチ |

### 通常フロー

```bash
# 1. main を最新化
git checkout main
git pull origin main

# 2. 機能ブランチを切る
git checkout -b feature/your-task-description

# 3. コードを編集

# 4. PHP 構文チェック（変更ファイルがあれば）
php -l plugin/yd-supervisor/inc/your-file.php

# 5. コミット
git add ...
git commit -m "feat(supervisor): 概要を簡潔に"

# 6. push
git push -u origin feature/your-task-description

# 7. GitHub UI で PR 作成 → main マージ
#    マージで自動 FTP デプロイがトリガーされる
```

### コミット規約（Conventional Commits）

```
<type>(<scope>): <件名>

<本文>
```

**type 一覧：**

| type | 用途 |
|---|---|
| `feat` | 新機能 |
| `fix` | バグ修正 |
| `docs` | ドキュメント |
| `refactor` | コード再構成（挙動変更なし） |
| `style` | フォーマット |
| `test` | テスト追加・修正 |
| `chore` | CI 設定 / 依存更新 / その他 |

**scope の例：**

| scope | 対象 |
|---|---|
| `supervisor` | プラグイン本体 |
| `theme` | 子テーマ |
| `ci` | GitHub Actions / .gitignore 等 |
| `docs` | ドキュメント全般 |
| `setup` | setup-guide.md |

**例：**
```
feat(supervisor): yd_supervisors を REST API に公開

register_post_meta で配列型として登録、
sanitize_callback で yd_doctor 以外の ID を除外。
```

### PR の作り方

1. ブランチを push 後、GitHub が「Compare & pull request」リンクを表示
2. base = `main`, compare = `feature/your-task` で PR 作成
3. タイトルは feature ブランチの趣旨を 1 行で
4. 本文に
   - 何を変更したか（要点）
   - 動作確認手順 / チェックリスト
5. Reviewer に Daiki さんを指定（任意）
6. Merge ボタンでマージ → main へ

---

## 4. コーディング規約

### PHP

- WordPress Coding Standards 準拠
- インデント：**タブ**（4 幅）
- ファイル末尾に空行 1 行
- 関数 / クラスにプレフィックス `yd_` または `YD_`
- ファイル冒頭に直接アクセス防止：
  ```php
  if ( ! defined( 'ABSPATH' ) ) {
      exit;
  }
  ```
- 関数 / クラスに docblock コメント

### 命名規則

| 種別 | 例 |
|---|---|
| 関数 | `yd_get_post_supervisors()` |
| クラス | `YD_CPT_Doctor` |
| 定数 | `YD_SUPERVISOR_VERSION` |
| メタキー | `yd_target_keyword` |
| CPT | `yd_doctor` |
| ACF フィールドキー | `field_yd_xxxx` |
| ACF フィールド名 | `yd_xxxx` |
| フックタグ | `yd_supervisor_*` |

### セキュリティ

- 入力：`sanitize_text_field`, `esc_url_raw`, `absint` 等で必ずサニタイズ
- 出力：`esc_html`, `esc_attr`, `esc_url` で必ずエスケープ
- nonce 検証 / `current_user_can()` 権限チェック

### i18n

- 文字列は `__('text', 'yd-supervisor')` で翻訳可能化
- テキストドメイン：`yd-supervisor`

---

## 5. デプロイ

### 自動デプロイ（推奨）

`main` ブランチへの push で `.github/workflows/deploy.yml` が
自動的に FTP デプロイを実行：

- `plugin/yd-supervisor/` → `${FTP_BASE_DIR}plugins/yd-supervisor/`
- `theme/swell_child_custmon/` → `${FTP_BASE_DIR}themes/swell_child_custmon/`

進行は GitHub の **Actions** タブで確認可能。

### 必要な GitHub Secrets / Variables（設定済み）

- Secrets：`FTP_SERVER` `FTP_USERNAME` `FTP_PASSWORD`
- Variables：`FTP_BASE_DIR=/wp-content/`

これらは Daiki さんが設定済みのため、新規参画者は触らない。

### 手動デプロイ（オプション）

GitHub → **Actions タブ** → 「Deploy to production」 →
**Run workflow** ボタン → ブランチを `main` にして実行。

---

## 6. 動作確認

各機能の検証チェックリストは [`test-checklist.md`](test-checklist.md) を参照。
特に Phase 6（記事下監修者カード）が一番フロントから見える機能なので、
触り始めるならまずそこから動作確認をどうぞ。

### 重要：動作確認は本番で

ローカル WP 環境を持たない方針のため、main にマージ → デプロイされた
本番で確認します。安全策：

1. PHP 構文チェック（`php -l`）を必ず通してから push
2. 大きい変更は feature ブランチで PR を立てて、自動デプロイ前に diff レビュー
3. 万一不具合：FTP で `yd-supervisor` ディレクトリ名を `yd-supervisor.bak` にリネーム → 即復旧

---

## 7. リポジトリ構造の早見表

```
yourdoctor_cms/
├── README.md                    プロジェクト概要・状況
├── docs/
│   ├── design.md                設計書（仕様の正）
│   ├── api.md                   REST API リファレンス
│   ├── onboarding.md            本書
│   ├── setup-guide.md           初期環境構築（Daiki さん用）
│   └── test-checklist.md        各 Phase の検証手順
├── theme/
│   └── swell_child_custmon/     SWELL 子テーマ
│       ├── style.css            親サイト準拠の独自カラーリング
│       ├── functions.php        Google Fonts / SVG 許可
│       ├── header.php           独自ヘッダー（監修医師ナビ含む）
│       └── footer.php           独自フッター
├── plugin/
│   └── yd-supervisor/           メインプラグイン
│       ├── yd-supervisor.php    プラグインヘッダー / 各クラスのロード
│       ├── readme.txt
│       ├── uninstall.php        no-op（データ保持）
│       ├── inc/
│       │   ├── class-cpt-doctor.php           CPT 登録
│       │   ├── class-post-meta.php            register_post_meta（KW / supervisors）
│       │   ├── class-acf-fields.php           ACF フィールドグループ定義
│       │   ├── class-template-loader.php      テンプレート振替 + CSS enqueue
│       │   ├── class-schema.php               aioseo_schema_output フィルター
│       │   ├── class-article-supervisor.php   the_content フィルター（記事下カード）
│       │   └── helpers.php                    テンプレートタグ群
│       ├── templates/
│       │   ├── single-yd_doctor.php
│       │   ├── archive-yd_doctor.php
│       │   └── partials/
│       │       ├── doctor-profile.php
│       │       ├── doctor-card.php
│       │       └── reviewed-posts.php
│       └── assets/css/
│           ├── doctor-pages.css
│           └── article-supervisor.css
├── scripts/                     デプロイ補助（現状未使用）
└── .github/workflows/
    └── deploy.yml               FTP 自動デプロイ
```

---

## 8. 困ったときは

| シナリオ | 対処 |
|---|---|
| 本番が落ちた（白画面） | FTP で `yd-supervisor` を `yd-supervisor.bak` にリネーム → 即復旧 → ログ確認 |
| デプロイが失敗 | GitHub → Actions タブのログを確認。よくある原因：FTP_BASE_DIR ずれ、Secrets 漏れ |
| ACF フィールドが管理画面に出ない | ACF Pro が有効化されているか確認（プラグイン → 一覧） |
| 監修医師ページが 404 | WP管理画面 → 設定 → パーマリンク → 「変更を保存」（rewrite 再生成） |
| REST API でフィールドが取れない | 認証 / 権限 / フィールド名（先頭 `yd_` 含めて）を確認 |
| 構造化データが出ない | AIOSEO が有効か、監修者を 1 名以上アサインしているか |

それでも解決しないときは Daiki さん（株式会社YUKATAN）まで。
