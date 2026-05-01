# 動作確認チェックリスト

各 Phase 完了時に Daiki さんが本番（`/media/`）で動作確認するための手順書。

---

## Phase 1：プラグイン基盤（CPT + ACF）

### 事前準備

- 本番に **ACF Pro** が有効化されていること
- 本番に **All in One SEO Pack（AIOSEO）** が有効化されていること（Phase 1 では未使用だが導入確認）

### デプロイ手順

1. ローカルの `plugin/yd-supervisor/` を丸ごと FTP で本番 `/media/wp-content/plugins/yd-supervisor/` にアップロード
2. 期待される本番ファイル構成：
   ```
   /media/wp-content/plugins/yd-supervisor/
   ├── yd-supervisor.php
   ├── readme.txt
   ├── uninstall.php
   └── inc/
       ├── class-cpt-doctor.php
       └── class-acf-fields.php
   ```
3. WP管理画面 → プラグイン → **「YD Supervisor」を有効化**

### 検証項目

#### A. プラグイン有効化

- [ ] プラグイン一覧に「YD Supervisor」が表示される
- [ ] 有効化時にエラー（PHP Fatal、白画面）が出ない
- [ ] サイトの既存ページ（トップ・既存記事）に表示崩れがない

#### B. 管理画面メニュー

- [ ] 管理画面サイドバーに「**監修医師**」メニューが表示される（位置は「投稿」の直下、ビジネスマンアイコン）
- [ ] 「監修医師」メニュー → 「監修医師一覧」「新規医師を追加」のサブメニューが表示される

#### C. ACFフィールド表示

「監修医師」→「新規医師を追加」を開き、本文エディタの下に以下のフィールドが**順番通り**に表示されるか確認：

- [ ] 敬称（必須・初期値 `Dr.`）
- [ ] 役職・専門医資格（必須）
- [ ] 専門分野（必須・セレクトボックスで6種類：泌尿器科 / 皮膚科 / 形成外科 / 美容皮膚科 / 内科 / 一般診療）
- [ ] 所属クリニック名（必須）
- [ ] 所属クリニック公式URL（必須・URL形式）
- [ ] 出身大学・大学院（任意）
- [ ] 公式URL（リピーター・「URLを追加」ボタンで行追加可）
- [ ] 経歴（WYSIWYG エディタ）
- [ ] 保有資格（テキストエリア）
- [ ] AGA治療経験年数（数値）
- [ ] 監修者コメント（テキストエリア）
- [ ] 医師免許取得年（数値）

#### D. 投稿作成・保存

ダミーデータで監修医師を1件作成し、保存できるか確認：

- [ ] タイトル：例「田中 太郎」
- [ ] 必須フィールドを全て入力して**公開**できる
- [ ] 必須フィールド未入力時にエラーが出る（または保存はされても警告される）
- [ ] アイキャッチ画像が設定できる
- [ ] 公開後、「監修医師一覧」に表示される
- [ ] 一覧から再編集すると、入力した値が正しく復元される

#### E. URL確認（rewrite rules）

- [ ] 公開した監修医師のシングルURL `https://your-doctor.jp/media/doctor/{slug}/` にアクセスして 404 にならない（テンプレート未実装のため SWELL のデフォルト single 表示で OK）
- [ ] アーカイブURL `https://your-doctor.jp/media/doctor/` にアクセスして 404 にならない

> **注：** Phase 1 ではテンプレートを作成していないため、表示は SWELL のデフォルト single/archive で構いません。重要なのは 404 にならず、CPT として認識されていること。

#### F. ACF Pro 無効時の挙動（オプション）

- ACF Pro を一時無効化すると、管理画面に黄色の警告バーが出る（プラグインは落ちず、CPT のみ生きている）
- 確認後 ACF Pro を再有効化

#### G. REST API 確認（オプション・curl で）

```bash
curl https://your-doctor.jp/media/wp-json/wp/v2/doctors
```

- [ ] 200 OK が返り、登録した監修医師が JSON 配列で返る
- ※ ACF フィールド本体はまだ REST に出ていません（Phase 2 以降で対応）

### 不具合が出た場合

- **白画面・致命的エラー**：FTP で `/media/wp-content/plugins/yd-supervisor/` ディレクトリ名を `yd-supervisor.bak/` にリネーム → サイト復旧 → ログを確認して報告
- **管理画面崩れのみ**：プラグイン → 「YD Supervisor」を無効化 → 報告

### Phase 1 で**確認しないもの**（Phase 2 以降）

- 記事から監修者を選択する機能（→ Phase 2）
- 監修者シングルページのテンプレート（→ Phase 3）
- ターゲットKW入力欄（→ Phase 4）
- 構造化データ出力（→ Phase 5）
- ヘッダーナビへの「Doctors」追加（→ Phase 6・案A確定）

---

## Phase 2：記事との関連付け

### 事前準備

- Phase 1 が動作確認済みで、監修医師（yd_doctor）が **1 件以上 公開状態で**登録されていること

### デプロイ手順

main マージ → GitHub Actions が自動デプロイ。差分は以下：
- `plugin/yd-supervisor/yd-supervisor.php`（バージョン 0.2.0、helpers.php 読み込み）
- `plugin/yd-supervisor/inc/class-acf-fields.php`（post 用フィールドグループ追加）
- `plugin/yd-supervisor/inc/helpers.php`（新規）

### 検証項目

#### A. 記事編集画面に監修者選択欄が出るか

1. WP管理画面 → 投稿 → 新規追加（または既存記事を編集）
2. 右サイドバーに「**監修医師**」フィールドグループが出現することを確認

  - [ ] 「監修医師」セレクト（複数選択可・ドロップダウン検索可・空値許容）
  - [ ] 「医療スキーマを無効化」チェックボックス（デフォルト OFF）

#### B. 監修者の選択・保存

1. 「監修医師」フィールドで Phase 1 で作ったダミー医師を選択
2. 記事を**下書き保存**または**公開**
3. ページを再読み込み → 選択した医師が**選択状態として復元**されている

  - [ ] 1 名選択 → 保存 → 再読み込みで選択維持
  - [ ] 2 名以上選択 → 保存 → 全員選択維持
  - [ ] 0 名（未選択）→ 保存 → 警告なくスルー

#### C. ヘルパー関数の動作（オプション・REST 経由）

「監修医師付き」記事の生 meta が保存されているか REST API で確認（auth 不要）：

```
GET https://your-doctor.jp/media/wp-json/wp/v2/posts/{記事ID}
```

レスポンスの `meta` または `acf` 配下を見て、`yd_supervisors` に医師の投稿ID配列が入っていれば OK。

> ※ ACF が REST に出すかは ACF Pro の設定次第。出てなければ Phase 4/5 で `register_post_meta` 経由で公開する設計のため、このフェーズでは未確認でも構いません。

#### D. 既存記事への副作用

  - [ ] 既存記事を開いて「監修医師」フィールドが表示される（既存値は空）
  - [ ] 既存記事を一度「更新」しても、本文・メタ情報・公開ステータスが意図せず変わらない
  - [ ] フロントエンド表示が壊れていない

### Phase 2 で**確認しないもの**（Phase 3 以降）

- 監修者カードの記事下表示（→ Phase 6）
- 監修者シングルページの「監修記事一覧」（→ Phase 3）
- 構造化データへの reviewedBy 出力（→ Phase 5）

---

## Phase 3：監修者ページのテンプレート

### 事前準備

- Phase 1, 2 が動作確認済み
- ダミー医師が 1 件以上公開状態（顔写真・経歴・所属クリニック等を入力推奨）
- そのダミー医師を監修者に設定した記事が **1 件以上 公開状態**

### デプロイ手順

main マージ → 自動デプロイ。差分は以下：
- `plugin/yd-supervisor/yd-supervisor.php`（バージョン 0.3.0、template-loader 読込）
- `plugin/yd-supervisor/inc/class-template-loader.php`（新規）
- `plugin/yd-supervisor/inc/helpers.php`（`yd_get_doctor_reviewed_posts()` 追加）
- `plugin/yd-supervisor/templates/single-yd_doctor.php`（新規）
- `plugin/yd-supervisor/templates/archive-yd_doctor.php`（新規）
- `plugin/yd-supervisor/templates/partials/doctor-profile.php`（新規）
- `plugin/yd-supervisor/templates/partials/doctor-card.php`（新規）
- `plugin/yd-supervisor/templates/partials/reviewed-posts.php`（新規）
- `plugin/yd-supervisor/assets/css/doctor-pages.css`（新規）

### 重要：パーマリンク再生成

Phase 1 でも CPT は登録済みですが、念のため Phase 3 デプロイ後に：

1. WP管理画面 → **設定 → パーマリンク設定**
2. 何も変更せず **「変更を保存」** ボタンを押す（rewrite rules を再生成）

### 検証項目

#### A. 監修者シングルページ

`https://your-doctor.jp/media/doctor/{slug}/`（{slug} は登録した医師の URL スラッグ）

- [ ] 404 にならず、ページが表示される
- [ ] 顔写真（円形 160px）が表示される
- [ ] 「Dr. 田中太郎」のように敬称＋氏名が表示される
- [ ] 役職・所属クリニック名が表示される（クリニック名は外部リンク化）
- [ ] 経歴・保有資格・出身大学・経験年数・免許取得年が定義リストで表示される
- [ ] 監修者コメントがコーラルカラーの枠で表示される
- [ ] sameAs URL（リピーター登録分）が「関連リンク」として外部リンク表示
- [ ] **「○○医師が監修した記事」セクション**が表示される
- [ ] その医師を監修者に設定した記事カードが並ぶ
- [ ] 記事カードクリックで該当記事に遷移できる
- [ ] レスポンシブ：スマホ表示でレイアウトが崩れない（顔写真センター寄せ）

#### B. 監修者一覧アーカイブ

`https://your-doctor.jp/media/doctor/`

- [ ] 404 にならず、「監修医師一覧」見出しが表示される
- [ ] 登録済み医師のカードが 2 カラムグリッドで並ぶ
- [ ] 各カードに 顔写真（円形 80px）/ 敬称+氏名 / 役職 / クリニック名 が表示
- [ ] カードクリックで該当医師のシングルページに遷移
- [ ] 医師が 13 件以上いる場合、ページネーションが表示される
- [ ] レスポンシブ：スマホで 1 カラムになる

#### C. CSS 読み込み

ブラウザの開発者ツール → Network タブで以下が読み込まれていることを確認：

- [ ] `/wp-content/plugins/yd-supervisor/assets/css/doctor-pages.css?ver=0.3.0`
- [ ] yd_doctor 関連ページ**以外**（例：通常の記事ページ、ホーム）では doctor-pages.css は **読み込まれない**

#### D. 既存ページへの副作用

- [ ] トップページ・既存記事ページが従来通り表示される
- [ ] 子テーマヘッダー・フッターが正しく表示される

### 想定される表示崩れと対応

- **SWELL の親テーマレイアウトとマージン重複**：余白の崩れがあれば共有してください、CSS で調整します
- **顔写真未設定**：プレースホルダー（薄ベージュの円）が表示されます
- **記事監修なし状態**：「○○医師が監修した記事」セクションごと非表示

### Phase 3 で**確認しないもの**（Phase 4 以降）

- 記事ターゲットKW入力欄（→ Phase 4）
- 構造化データ JSON-LD（→ Phase 5）
- 記事ページに監修者カードが表示される機能（→ Phase 6）
- ヘッダーナビへの「Doctors」リンク（→ Phase 6）
