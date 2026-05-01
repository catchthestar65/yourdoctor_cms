# セットアップガイド

このドキュメントは、Daiki さん（株式会社YUKATAN）が **Claude Code に開発を任せる** ための環境構築手順を記載します。

## 前提条件

| 項目 | 状態 |
|---|---|
| `your-doctor.jp/media/` WordPress | インストール済み・SWELL有効・初期状態（テスト記事のみ） |
| 子テーマ `swell_child_custmon` | 適用済み（v2版・4ファイル） |
| FTPアクセス | 利用可能 |
| phpMyAdminアクセス | 利用可能 |
| WP管理者ログイン | 利用可能 |
| Claude Code | インストール済み |
| Git / GitHub アカウント | 利用可能（既存組織あり） |

---

## STEP 1：GitHubリポジトリの作成

1. GitHub の既存組織配下に **Private リポジトリ** を新規作成
2. リポジトリ名：`your-doctor-media`（一案。お好みで変更可）
3. 初期化オプションは **すべてOFF**（README追加なし、.gitignore追加なし）
4. リポジトリURLを控える（例：`git@github.com:your-org/your-doctor-media.git`）

---

## STEP 2：本リポジトリ初期構成のローカル展開

提供された `your-doctor-media-starter.zip` を解凍し、ローカルの作業ディレクトリに配置してください。

```bash
# 例：~/Projects/your-doctor-media/ に展開
cd ~/Projects/
unzip your-doctor-media-starter.zip
cd your-doctor-media/
```

---

## STEP 3：既存子テーマファイルをFTPで取得して配置

**FileZilla等でFTP接続し、以下4ファイルをDLしてリポジトリ内の `theme/swell_child_custmon/` に配置：**

| サーバー側パス | リポジトリ側配置先 |
|---|---|
| `/media/wp-content/themes/swell_child_custmon/style.css` | `theme/swell_child_custmon/style.css` |
| `/media/wp-content/themes/swell_child_custmon/functions.php` | `theme/swell_child_custmon/functions.php` |
| `/media/wp-content/themes/swell_child_custmon/header.php` | `theme/swell_child_custmon/header.php` |
| `/media/wp-content/themes/swell_child_custmon/footer.php` | `theme/swell_child_custmon/footer.php` |

これにより、Claude Codeが既存コードを把握できる状態になります。

---

## STEP 4：必須プラグインのインストール（本番側）

本番のWP管理画面（`https://your-doctor.jp/media/wp-admin/`）にログインし、以下をインストール：

1. **All in One SEO Pack（AIOSEO）**
   - プラグイン → 新規追加 → 「All in One SEO Pack」検索 → インストール → 有効化
   - 初期セットアップウィザードを完了

2. **Advanced Custom Fields PRO（ACF Pro）**
   - 別途購入したACF ProのZIPをアップロード（プラグイン → 新規追加 → プラグインのアップロード）
   - 有効化 → ライセンスキー登録（設定 → ACF → アップデート）

---

## STEP 5：Application Password の発行

REST API認証およびGitHub Actions（自動デプロイ採用時）用に Application Password を発行します。

1. WP管理画面 → ユーザー → プロフィール
2. ページ最下部 **「アプリケーションパスワード」** セクション
3. 名前：`claude-code-development`（用途別に複数発行推奨）
4. **「新しいアプリケーションパスワードを追加」** クリック
5. 表示された 24文字パスワード（例：`abcd EFGH 1234 wxyz ...`）を**安全な場所に保管**
6. 同様に `nakamae-system`（前中氏連携用）など用途別に発行

⚠️ **このパスワードは画面遷移すると二度と表示されません。必ず控えてください。**

---

## STEP 6：Gitの初期化とリモート連携

```bash
cd ~/Projects/your-doctor-media/
git init
git add .
git commit -m "Initial commit: project skeleton with design doc"
git branch -M main
git remote add origin git@github.com:your-org/your-doctor-media.git
git push -u origin main

# develop ブランチを作成
git checkout -b develop
git push -u origin develop
```

GitHub上で `main` と `develop` の2ブランチが見えていればOKです。

---

## STEP 7：Claude Code の起動と初回プロンプト送信

1. ターミナルで作業ディレクトリに移動：
   ```bash
   cd ~/Projects/your-doctor-media/
   ```

2. Claude Code を起動：
   ```bash
   claude
   ```

3. **`docs/claude-code-instructions.md` の内容をコピーして、Claude Code に最初のメッセージとして送信**

これによりClaude Codeはプロジェクトの全体像、設計書、実装方針を理解した上で、Phase 1から順番に実装を開始します。

---

## STEP 8：本番反映（デプロイ）

実装したコードを本番に反映する方法は2通り：

### 方法A：手動FTPアップロード（初期推奨）

1. ローカルの `theme/swell_child_custmon/` の変更ファイルを FileZilla で `/media/wp-content/themes/swell_child_custmon/` にアップロード
2. ローカルの `plugin/yd-supervisor/` を丸ごと FileZilla で `/media/wp-content/plugins/yd-supervisor/` にアップロード
3. 初回のみ：WP管理画面 → プラグイン → 「YD Supervisor」を**有効化**
4. ブラウザで `https://your-doctor.jp/media/` を開いて動作確認

### 方法B：GitHub Actions による自動FTPデプロイ（中期で導入推奨）

`.github/workflows/deploy.yml` の設定が必要。詳細は STEP 9 参照。

---

## STEP 9：GitHub Actions 自動デプロイ

`.github/workflows/deploy.yml` 配置済み。`main` ブランチへの push および GitHub UI からの手動実行で、プラグインと子テーマが本番に FTP 転送される。

### 9-1. FTP認証情報を Repository Secrets に登録（初回のみ・必須）

GitHubリポジトリ → Settings → Secrets and variables → Actions → 「New repository secret」

| Secret名 | 値 |
|---|---|
| `FTP_SERVER` | FTPサーバーホスト（例：`ftp.your-doctor.jp` または IP） |
| `FTP_USERNAME` | FTPユーザー名 |
| `FTP_PASSWORD` | FTPパスワード |

### 9-2. （任意）Repository Variables の設定

サーバ側構成が標準と異なる場合のみ、同じ画面の **Variables タブ** で設定。

| Variable名 | デフォルト | 用途 |
|---|---|---|
| `FTP_BASE_DIR` | `/media/wp-content/` | wp-content までの絶対パス（FTPルートが `/media/` 直下なら `/wp-content/` に変更） |
| `FTP_PROTOCOL` | `ftp` | `ftps` / `sftp` 利用時 |
| `FTP_PORT` | `21` | プロトコルに合わせて |

### 9-3. デプロイ実行

- 自動：`feature/* → main` の PR をマージすれば、main の push で workflow が起動
- 手動：GitHub → **Actions タブ** → 「Deploy to production」→ **Run workflow** ボタン

進捗は Actions タブで確認。失敗した場合はログから原因特定（よくある原因：Secrets 未登録 / `FTP_BASE_DIR` のパスずれ / FTP プロトコル不一致）。

### 9-4. デプロイ対象

| ローカル | サーバ |
|---|---|
| `plugin/yd-supervisor/` | `${FTP_BASE_DIR}plugins/yd-supervisor/` |
| `theme/swell_child_custmon/` | `${FTP_BASE_DIR}themes/swell_child_custmon/` |

`.git*` / `.DS_Store` / `.gitkeep` 等は自動除外。

---

## STEP 10：日常運用

### 開発の流れ

1. Claude Code でコード変更（feature ブランチ）
2. `git commit -m "..."` 実行（Claude Code内で完結）
3. PR作成 → develop マージ → main マージ
4. 自動デプロイ（方法B採用時）or 手動FTP（方法A採用時）

### 動作確認

ローカル環境を持たないため、本番（`/media/`）が動作確認の場となります。**Phase 1段階では既存記事への影響がないことを優先**し、変更を最小限に保ちます。

### トラブル時の戻し方

子テーマ・プラグインに不具合が出た場合：

- **プラグイン起因**：WP管理画面 → プラグイン → 「YD Supervisor」を一時無効化
- **子テーマ起因**：FTPで子テーマ名を一時的にリネーム（`swell_child_custmon` → `swell_child_custmon_bak`）→ SWELLの親テーマで動作するようになる
- **致命的エラー（白画面）**：FTPで該当ファイルを直前バージョンに戻す（Gitに履歴があるので復元可能）

---

## 補足：このセットアップ後にClaude Codeへ任せられる作業

- プラグイン `yd-supervisor` の全ファイル新規作成
- 子テーマの修正（single.php追加、ヘッダー/フッターのナビ追加など）
- ACFフィールド定義のPHP化
- AIOSEOフィルターと連携した構造化データの実装
- ドキュメント更新（API仕様書、READMEなど）
- Git操作（commit, push, PR作成）

### Claude Codeにできない作業

- 本番への直接FTPアップロード（手動 or GitHub Actions経由）
- WP管理画面の操作（プラグイン有効化、Application Password発行など）
- 本番DBへの直接アクセス（基本的にプラグインがWP標準APIで対応するため不要）
