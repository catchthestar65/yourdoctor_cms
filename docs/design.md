# AGAメディア基盤 設計書 v1.0

**対象サイト：** `https://your-doctor.jp/media/`
**作成日：** 2026年5月1日
**作成主体：** 株式会社YUKATAN
**前提：** SWELL 2.16.0 / 子テーマ `swell_child_custmon` 適用済み / AIOSEO導入予定

---

## 0. 本書の位置づけ

本書は **AGAメディア立ち上げ（2026年5月予定）に向けた技術基盤の設計書** である。実装着手前に技術選定・構造設計・責務分離の合意形成を行うことを目的とする。実装は本書を基にClaude Codeで行い、GitHubリポジトリ経由で前中氏（記事作成システム担当）と共有する。

設計の対象範囲は以下の3点：

1. **監修者・著者プロフィールページのテンプレート設計**（プラグイン化）
2. **記事のターゲットKW記録機能**（REST API対応）
3. **SEO構造化データの自動出力**（CMS連動）

子テーマの基本構造（ヘッダー・フッター・配色・フォント）は v2 適用済みのため、本書では子テーマには「最小限の修正」のみ加える方針とする。

---

## 1. プロジェクト概要・目的

### 1.1 ビジネス目的

- AGA関連メディアを `your-doctor.jp/media/` 配下で2026年5月に立ち上げる
- E-E-A-T 対策として医師監修者を配置し、SEO評価を最大化する
- 監修者情報・著者情報を構造化データとしてGoogleに正確に伝える
- 記事作成システム（前中氏開発）から WordPress へ自動入稿できる土台を整える

### 1.2 技術目的

- **保守性**：テーマ切替に耐える機能設計（CPTはプラグイン側に置く）
- **拡張性**：将来の他カテゴリ展開（AGA以外）に耐える汎用性
- **連携性**：REST API経由で外部システムから読み書き可能
- **可視性**：GitHub上でコード変更履歴が追跡可能

### 1.3 確定済み技術選定

| 項目 | 選定 |
|---|---|
| SEOプラグイン | All in One SEO Pack (AIOSEO) |
| カスタムフィールド | ACF Pro（必須） |
| 監修者機能の実装場所 | 独立プラグイン `yd-supervisor` |
| ACFフィールド定義方式 | PHP `acf_add_local_field_group()`（コードが正） |
| 著者の概念 | WP標準の `wp_users`（ログインユーザー） |
| 監修者の概念 | カスタム投稿タイプ（CPT） |

---

## 2. 全体アーキテクチャ

### 2.1 構成図

```
┌─────────────────────────────────────────────────────────────┐
│                  WordPress (your-doctor.jp/media/)           │
│                                                              │
│  ┌──────────────────┐      ┌──────────────────────────────┐ │
│  │  Theme           │      │  Plugin                      │ │
│  │  swell_child_    │      │  yd-supervisor               │ │
│  │  custmon         │      │                              │ │
│  │                  │      │  - CPT: yd_doctor            │ │
│  │  - header.php    │ uses │  - ACF Field Groups          │ │
│  │  - footer.php    │ ───→ │  - Meta: target_keyword      │ │
│  │  - style.css     │      │  - JSON-LD出力               │ │
│  │  - functions.php │      │  - AIOSEOフィルター連携      │ │
│  │  - single.php    │      │  - REST API拡張              │ │
│  │   (要修正)       │      │                              │ │
│  └──────────────────┘      └──────────────────────────────┘ │
│         ↑                            ↑                       │
│         │                            │                       │
│  ┌──────┴────────────────────────────┴──────────────────┐   │
│  │  Required Plugins                                    │   │
│  │  - All in One SEO Pack (AIOSEO)                      │   │
│  │  - Advanced Custom Fields PRO                        │   │
│  │  - SWELL                                             │   │
│  └──────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
                                   ↑
                                   │ REST API
                                   │
                          ┌────────┴────────┐
                          │  外部連携       │
                          │  - 前中氏システム│
                          │  - GAS / n8n等  │
                          └─────────────────┘
```

### 2.2 責務分離

| レイヤー | 責務 | 実装場所 |
|---|---|---|
| **データ層** | CPT定義、メタフィールド定義、リレーション保存 | プラグイン |
| **API層** | REST APIエンドポイント拡張、フィールド公開 | プラグイン |
| **構造化データ層** | JSON-LD生成、AIOSEOフックへの追記 | プラグイン |
| **表示層（HTML）** | テンプレート、見た目、CSS | 子テーマ |
| **テンプレートタグ** | 表示用ヘルパー関数 | プラグイン（テンプレートファイル参照） |

**重要原則：プラグインを無効化してもデータは消えない。子テーマを切り替えても機能は維持される。**

### 2.3 利用技術スタック

- PHP 8.0+ （SWELL推奨環境）
- WordPress 6.4+
- ACF Pro（ローカルフィールド定義をPHPで管理）
- AIOSEO（構造化データの基盤、`reviewedBy` のみ追記）
- JSON-LD（構造化データ形式）

---

## 3. 監修者プラグイン `yd-supervisor`

### 3.1 CPT定義

#### 3.1.1 基本パラメータ

| 項目 | 値 |
|---|---|
| 内部slug | `yd_doctor`（プラグインプレフィックス付きで衝突回避） |
| URL slug (rewrite) | `doctor` |
| アーカイブURL | `/media/doctor/`（一覧） |
| シングルURL | `/media/doctor/{post_name}/`（個別） |
| public | true |
| has_archive | true（一覧ページ自動生成） |
| supports | `['title', 'editor', 'thumbnail', 'excerpt']` |
| menu_position | 5（投稿の直下） |
| menu_icon | `dashicons-businessperson` |
| show_in_rest | true（REST API対応） |
| rest_base | `doctors` |

#### 3.1.2 ラベル

```php
'labels' => [
    'name'               => '監修医師',
    'singular_name'      => '監修医師',
    'add_new'            => '新規医師を追加',
    'add_new_item'       => '新規監修医師を追加',
    'edit_item'          => '監修医師を編集',
    'all_items'          => '監修医師一覧',
    'menu_name'          => '監修医師',
],
```

#### 3.1.3 タクソノミー（必要なら）

**第1段階：タクソノミー無し**で開始する。

**将来検討：** 「専門分野」タクソノミー（`yd_specialty`）を追加し、皮膚科・形成外科・泌尿器科などで医師を分類する案がある。MVPでは保留。

### 3.2 ACFフィールド定義（PHP定義）

#### 3.2.1 フィールドグループ：監修医師プロフィール

`yd_doctor` 投稿タイプに紐付けるフィールドグループ。

| フィールドキー | ラベル | タイプ | 必須 | 構造化データ用途 |
|---|---|---|---|---|
| `yd_honorific_prefix` | 敬称 | text（デフォルト "Dr."） | ◯ | `honorificPrefix` |
| `yd_job_title` | 役職・専門医資格 | text（例「泌尿器科専門医」） | ◯ | `jobTitle` |
| `yd_medical_specialty` | 専門分野 | select | ◯ | `medicalSpecialty` |
| `yd_clinic_name` | 所属クリニック名 | text | ◯ | `memberOf.name` |
| `yd_clinic_url` | 所属クリニック公式URL | url | ◯ | `memberOf.url` |
| `yd_alumni_of` | 出身大学・大学院 | text | △ | `alumniOf` |
| `yd_same_as_urls` | 公式URL（複数） | repeater (url) | △ | `sameAs[]` |
| `yd_career` | 経歴 | wysiwyg | △ | （表示用） |
| `yd_qualifications` | 保有資格 | textarea（改行区切り） | △ | （表示用） |
| `yd_aga_experience_years` | AGA治療経験年数 | number | △ | （表示用） |
| `yd_supervisor_comment` | 監修者コメント | textarea | △ | （表示用） |
| `yd_doctor_license_year` | 医師免許取得年 | number | △ | （表示用） |

**注：** 顔写真は CPT の `featured_image`（アイキャッチ画像）を使用する。`thumbnail` をsupportsに含めているため標準UIで設定可能。

#### 3.2.2 `yd_medical_specialty` の選択肢（schema.org準拠）

主要なAGA関連専門分野を schema.org の MedicalSpecialty enumeration に基づいてプリセット：

| 表示ラベル | 保存値（DB） | 出力時の値 |
|---|---|---|
| 泌尿器科 | `Urologic` | `https://schema.org/Urologic` |
| 皮膚科 | `Dermatologic` | `https://schema.org/Dermatologic` |
| 形成外科 | `PlasticSurgery` | `https://schema.org/PlasticSurgery` |
| 美容皮膚科 | `DermatologicSurgery` | `https://schema.org/DermatologicSurgery` |
| 内科 | `InternalMedicine` | `https://schema.org/InternalMedicine` |
| 一般診療 | `PrimaryCare` | `https://schema.org/PrimaryCare` |

DB保存値はキー（`Urologic`等）のみとし、JSON-LD出力時にPHP側でプレフィックス（`https://schema.org/`）を付与する。これにより schema.org の URI 仕様変更にも対応しやすい。

#### 3.2.3 フィールドグループ：記事への監修者紐付け

`post`（一般記事）投稿タイプに紐付けるフィールドグループ。

| フィールドキー | ラベル | タイプ | 必須 | 用途 |
|---|---|---|---|---|
| `yd_supervisors` | 監修医師 | post_object（複数選択、`yd_doctor`のみ表示） | △ | リレーション |
| `yd_disable_medical_schema` | 医療スキーマを無効化 | true_false | △ | MedicalWebPage化を除外 |

**ACF post_object フィールド設定：**
- `post_type` = `yd_doctor`
- `multiple` = true
- `return_format` = `id`（投稿ID配列で返却）
- `allow_null` = true（監修者なし許容）

### 3.3 記事との関連付け

#### 3.3.1 データ構造

記事（post）の `wp_postmeta` テーブルに以下のメタが保存される：

```
post_id     | meta_key          | meta_value
123         | yd_supervisors    | a:2:{i:0;i:456;i:1;i:789;}  (シリアライズ配列)
```

ACF post_object フィールドの内部実装に依存。ACFは内部的にシリアライズ配列で保存。

#### 3.3.2 取得API

プラグインで以下のヘルパー関数を提供：

```php
yd_get_post_supervisors( $post_id ): WP_Post[]
// 戻り値: yd_doctor 投稿オブジェクトの配列

yd_get_supervisor_data( $doctor_id ): array
// 戻り値: 構造化データ生成に必要な全フィールドを連想配列で返す
```

#### 3.3.3 双方向参照

「**この監修者が監修した記事一覧**」を取得するには、ACF の post_object フィールドから逆引きクエリを発行する必要がある。

```php
// 監修医師シングルページで使用
$args = [
    'post_type'   => 'post',
    'meta_query'  => [
        [
            'key'     => 'yd_supervisors',
            'value'   => '"' . $doctor_id . '"',  // シリアライズ値内の検索
            'compare' => 'LIKE',
        ],
    ],
];
$reviewed_posts = get_posts( $args );
```

**注意点：** シリアライズ配列に対するLIKE検索のため、`$doctor_id` の前後に `"` を付ける必要がある。記事数が増加した際のパフォーマンス劣化に備え、将来的にはカスタムテーブルでのリレーション管理を検討する。MVPでは標準ACF実装で問題ない。

### 3.4 テンプレートタグ・ヘルパー関数

プラグインは以下のテンプレートタグを提供する。子テーマの `single.php` などから呼び出して使う。

```php
// 記事の監修者カードを表示（既定HTMLを返す）
yd_render_supervisor_cards( $post_id = null );

// 記事の監修者を取得（オブジェクト配列）
yd_get_post_supervisors( $post_id = null ): array;

// 監修者シングルページで「監修した記事一覧」を取得
yd_get_doctor_reviewed_posts( $doctor_id, $args = [] ): WP_Query;

// 監修者プロフィールカード（簡易版）を出力
yd_render_doctor_profile_card( $doctor_id );

// 構造化データ（reviewedBy配列）を取得
yd_get_reviewed_by_schema( $post_id ): array;
```

### 3.5 REST API

#### 3.5.1 標準REST APIエンドポイント

CPT登録時に `show_in_rest => true, rest_base => 'doctors'` を指定することで以下のエンドポイントが自動生成される：

```
GET    /wp-json/wp/v2/doctors          監修医師一覧
GET    /wp-json/wp/v2/doctors/{id}     監修医師詳細
POST   /wp-json/wp/v2/doctors          監修医師作成（要認証）
PUT    /wp-json/wp/v2/doctors/{id}     監修医師更新（要認証）
DELETE /wp-json/wp/v2/doctors/{id}     監修医師削除（要認証）
```

#### 3.5.2 ACFフィールドのREST公開

ACFは標準では REST API に出力しないため、プラグインで以下を行う：

```php
// 各ACFフィールドを register_rest_field() で公開
add_action( 'rest_api_init', 'yd_register_doctor_rest_fields' );
```

公開対象：3.2.1 で定義した全フィールド（`yd_*` キー）

#### 3.5.3 記事 → 監修者のリレーション公開

`/wp-json/wp/v2/posts/{id}` のレスポンスに、紐付いた監修者情報を埋め込む：

```json
{
  "id": 123,
  "title": "...",
  "yd_supervisors": [
    {
      "id": 456,
      "name": "田中太郎",
      "honorific_prefix": "Dr.",
      "job_title": "泌尿器科専門医",
      "url": "https://your-doctor.jp/media/doctor/tanaka-taro/"
    }
  ]
}
```

### 3.6 シングル・アーカイブテンプレート

#### 3.6.1 シングル：`/media/doctor/{slug}/`

**表示構成（上から）：**

1. **プロフィールヘッダー**
   - 顔写真（アイキャッチ画像）
   - 敬称 + 氏名（"Dr. 田中太郎"）
   - 役職・専門医資格
   - 所属クリニック名（クリックで外部リンク `target="_blank"`）
2. **詳細セクション**
   - 経歴
   - 保有資格
   - 出身大学
   - AGA治療経験年数
   - 監修者コメント
3. **公式リンクセクション**
   - sameAs URLs（学会プロフィール、クリニック紹介ページなど）
4. **監修記事一覧セクション**（重要）
   - 「○○医師が監修した記事」見出し
   - 該当監修者が監修した記事のカード一覧
   - ページネーション対応

**テンプレートファイル：** `/wp-content/plugins/yd-supervisor/templates/single-yd_doctor.php`
（プラグイン内のテンプレートを `template_include` フィルターで優先採用。子テーマで上書き可能。）

#### 3.6.2 アーカイブ：`/media/doctor/`

**表示構成：**

- ページタイトル「監修医師一覧」
- 全監修医師のカード一覧（顔写真、氏名、役職、所属）
- 各カードから個別シングルページへリンク

**テンプレートファイル：** `/wp-content/plugins/yd-supervisor/templates/archive-yd_doctor.php`

### 3.7 サイト動線（代替案の提案）

「監修者一覧への動線」について、3案を比較する。Daikiさんに最終選択していただきたい。

#### 案A：グローバルナビに「Doctors」追加【推奨】

**動線：** ヘッダーナビに `/media/doctor/` への直接リンクを追加

**メリット：**
- 親サイトの「ドクターの素顔」と整合性が取れる
- E-E-A-T を訪問者に最初に印象付けられる
- Googleクローラーから「重要ページ」として認識されやすい
- 内部リンク構造が強化される

**デメリット：**
- ヘッダーナビ項目が1つ増える（現在親サイト準拠で6項目→7項目）

**実装：** `header.php` のメニューHTMLに1項目追加するのみ。最小工数。

#### 案B：トップページの「監修医師」セクションのみ

**動線：** トップページに専用セクションを設置し、そこから監修者一覧・各監修者ページへ遷移

**メリット：**
- ヘッダーナビをシンプルに保てる
- トップページ訪問者には印象的に見せられる

**デメリット：**
- 記事ページから監修者一覧への動線が弱い
- 第2階層以降のページで監修者ページへの内部リンク密度が下がる

#### 案C：複合動線（推奨される強化版）

**動線：**
- ヘッダーナビに「Doctors」追加（案A）
- かつトップページに「監修医師」セクション（案B）
- 記事内・記事下に監修者カード（必須）
- フッターに「監修医師一覧」リンク（重複動線）

**メリット：**
- E-E-A-T 効果が最大化
- 親サイトとの統一感も最強

**デメリット：**
- 工数がやや増える（とはいえ各箇所5行程度のHTML追加）

**結論：本書では案Cを推奨**するが、最終決定はDaikiさんに委ねる。

---

## 4. ターゲットKW機能

### 4.1 メタフィールド定義

| 項目 | 値 |
|---|---|
| meta_key | `yd_target_keyword` |
| 型 | string（単一） |
| 単数/複数 | 単数（`single => true`） |
| REST API公開 | true（`show_in_rest => true`） |
| 編集権限 | `edit_posts`（投稿者以上） |
| 適用対象 | `post` 投稿タイプ |

#### 4.1.1 登録コード（プラグイン側）

```php
register_post_meta( 'post', 'yd_target_keyword', [
    'type'         => 'string',
    'description'  => '主ターゲットキーワード（SEO内部管理用）',
    'single'       => true,
    'show_in_rest' => true,
    'auth_callback' => function() {
        return current_user_can( 'edit_posts' );
    },
] );
```

### 4.2 管理画面UI

ACF にて、`post` 投稿編集画面の右サイドバーに「**SEOターゲット情報**」フィールドグループを追加：

| フィールドキー | ラベル | タイプ |
|---|---|---|
| `yd_target_keyword` | 主ターゲットKW | text（単一行） |

**配置：** サイドバー（`position => 'side'`、`style => 'default'`）

**注：** ACFのフィールドキー（`yd_target_keyword`）と register_post_meta のキーを同一にすることで、ACF経由でも REST API経由でも同じデータが読み書き可能になる。

### 4.3 REST API対応

#### 4.3.1 取得

```
GET /wp-json/wp/v2/posts/{id}
```

レスポンス内 `meta` フィールドに含まれる：

```json
{
  "id": 123,
  "title": "AGAの初期症状とは",
  "meta": {
    "yd_target_keyword": "AGA 初期症状"
  }
}
```

#### 4.3.2 更新

```
POST /wp-json/wp/v2/posts/{id}
Content-Type: application/json
Authorization: Basic ...

{
  "meta": {
    "yd_target_keyword": "AGA おすすめクリニック"
  }
}
```

### 4.4 外部連携サンプル

#### 4.4.1 GAS から記事のターゲットKWを取得

```javascript
function getPostTargetKeyword(postId) {
  const url = `https://your-doctor.jp/media/wp-json/wp/v2/posts/${postId}`;
  const response = UrlFetchApp.fetch(url);
  const data = JSON.parse(response.getContentText());
  return data.meta.yd_target_keyword;
}
```

#### 4.4.2 前中氏システムから記事入稿時にKWを同時設定

```python
# Python例
import requests
from requests.auth import HTTPBasicAuth

response = requests.post(
    'https://your-doctor.jp/media/wp-json/wp/v2/posts',
    auth=HTTPBasicAuth('username', 'app_password'),
    json={
        'title': 'AGAの初期症状とは',
        'content': '...',
        'status': 'draft',
        'meta': {
            'yd_target_keyword': 'AGA 初期症状',
        },
    },
)
```

### 4.5 構造化データには含めない

ターゲットKWは**内部管理データ**であり、構造化データ（JSON-LD）には出力しない。これは Google のスパム判定リスクを避けるため。

---

## 5. 構造化データ（JSON-LD）

### 5.1 AIOSEOとの責任分離

#### 5.1.1 AIOSEO標準出力（変更しない）

AIOSEOは記事ページに以下を自動出力する：

- `Article` schema（headline, image, datePublished, dateModified, author（WPユーザー）, publisher）
- `WebPage` schema
- `BreadcrumbList` schema
- `Organization` schema
- `Person` schema（author = WPユーザー）

これらは **AIOSEOに任せる**。プラグインで重複生成しない。

#### 5.1.2 プラグインで追加・上書きする要素

| 要素 | 操作 | 理由 |
|---|---|---|
| `@type` を `Article` → `MedicalWebPage` へ変換 | 上書き | AGAは医療領域、MedicalWebPageが適切 |
| `reviewedBy` を追加 | 追加 | AIOSEO標準サポート外 |
| `lastReviewed` を追加 | 追加 | 監修日時。AIOSEO標準サポート外 |
| `medicalAudience` を追加 | 追加 | 想定読者の明示 |
| `specialty` を追加 | 追加 | 記事の専門分野 |

#### 5.1.3 実装方針：AIOSEOフィルター利用

AIOSEOが提供する公式フィルターを利用する：

```php
add_filter( 'aioseo_schema_output', 'yd_modify_schema', 10, 1 );

function yd_modify_schema( $graphs ) {
    // $graphs は出力直前の schema graph 配列
    // ここで Article → MedicalWebPage 変換、reviewedBy 追加 等を行う
    return $graphs;
}
```

**注：** AIOSEOのフィルター仕様はバージョンにより変更される可能性があるため、実装フェーズで最新版を確認する。フィルター名・引数は実装着手時に再検証する。

### 5.2 出力スキーマ：記事ページ

#### 5.2.1 `MedicalWebPage` 化された記事の例

```json
{
  "@context": "https://schema.org",
  "@type": "MedicalWebPage",
  "@id": "https://your-doctor.jp/media/aga-shoki-shojo/#webpage",
  "url": "https://your-doctor.jp/media/aga-shoki-shojo/",
  "name": "AGAの初期症状とは｜見逃さないための5つのサイン",
  "headline": "AGAの初期症状とは｜見逃さないための5つのサイン",
  "description": "...",
  "datePublished": "2026-05-01T10:00:00+09:00",
  "dateModified": "2026-05-15T15:00:00+09:00",
  "lastReviewed": "2026-05-15T15:00:00+09:00",
  "image": "https://your-doctor.jp/media/wp-content/uploads/2026/05/eyecatch.jpg",
  "author": {
    "@type": "Person",
    "name": "山田花子",
    "url": "https://your-doctor.jp/media/author/yamada-hanako/"
  },
  "reviewedBy": {
    "@type": "Person",
    "name": "田中太郎",
    "honorificPrefix": "Dr.",
    "jobTitle": "泌尿器科専門医",
    "medicalSpecialty": "https://schema.org/Urologic",
    "memberOf": {
      "@type": "MedicalOrganization",
      "name": "東京泌尿器科クリニック",
      "url": "https://example-clinic.jp/"
    },
    "alumniOf": "東京大学医学部",
    "sameAs": [
      "https://example-clinic.jp/doctors/tanaka/",
      "https://example-society.jp/profile/tanaka/"
    ]
  },
  "specialty": "https://schema.org/Urologic",
  "medicalAudience": {
    "@type": "PeopleAudience",
    "audienceType": "Patient"
  },
  "publisher": {
    "@type": "Organization",
    "name": "Your Doctor",
    "logo": {
      "@type": "ImageObject",
      "url": "https://your-doctor.jp/wp-content/themes/your-doctor-jp/images/share/logo.svg"
    }
  }
}
```

#### 5.2.2 複数監修者の場合

`reviewedBy` を配列にする：

```json
"reviewedBy": [
  { "@type": "Person", "name": "田中太郎", ... },
  { "@type": "Person", "name": "佐藤次郎", ... }
]
```

### 5.3 出力スキーマ：監修者プロフィールページ

監修者シングルページ（`/media/doctor/{slug}/`）には以下を出力：

```json
{
  "@context": "https://schema.org",
  "@type": "Physician",
  "@id": "https://your-doctor.jp/media/doctor/tanaka-taro/#physician",
  "name": "田中太郎",
  "honorificPrefix": "Dr.",
  "jobTitle": "泌尿器科専門医",
  "image": "https://your-doctor.jp/media/wp-content/uploads/2026/05/tanaka.jpg",
  "url": "https://your-doctor.jp/media/doctor/tanaka-taro/",
  "medicalSpecialty": "https://schema.org/Urologic",
  "memberOf": {
    "@type": "MedicalOrganization",
    "name": "東京泌尿器科クリニック",
    "url": "https://example-clinic.jp/"
  },
  "alumniOf": "東京大学医学部",
  "sameAs": [
    "https://example-clinic.jp/doctors/tanaka/",
    "https://example-society.jp/profile/tanaka/"
  ]
}
```

`Physician` は schema.org の `Person` のサブタイプで、医師に最適化されている。

### 5.4 個別記事の除外機能

#### 5.4.1 仕組み

記事編集画面の ACF フィールド `yd_disable_medical_schema`（true_false）にチェックが入っている場合：

- `MedicalWebPage` 化を**行わない**（標準の `Article` のまま出力）
- `reviewedBy` も**追加しない**

#### 5.4.2 ユースケース

- AGA以外のカテゴリ記事（医療外コンテンツ）
- ニュース・お知らせ記事
- 監修者がアサインされていない記事

#### 5.4.3 自動判定ロジック

実装上は以下のいずれかを満たす場合に「医療スキーマ化しない」：

1. `yd_disable_medical_schema` が true
2. 監修者が1人もアサインされていない（`yd_supervisors` が空）

両方の条件で除外することで、安全側に倒す。

### 5.5 実装方針

#### 5.5.1 フィルター利用フロー

```
[投稿表示]
    ↓
[AIOSEO がschema graphを生成]
    ↓
[aioseo_schema_output フィルター実行]
    ↓
[yd-supervisor プラグインが介入]
    ↓
[条件判定：監修者あり & 除外フラグなし]
    ↓
    Yes → @type を MedicalWebPage に変換
        → reviewedBy 追加
        → lastReviewed 追加
        → specialty 追加
        → medicalAudience 追加
    No → そのまま（Article のまま）
    ↓
[ブラウザに出力]
```

#### 5.5.2 検証方法

1. **Google構造化データテストツール**：`https://search.google.com/test/rich-results`
2. **schema.org Validator**：`https://validator.schema.org/`
3. **Search Console の拡張レポート**：本番反映後に確認

---

## 6. 子テーマ側の修正範囲

### 6.1 必須修正

| ファイル | 修正内容 | 工数 |
|---|---|---|
| `single.php`（または既存テンプレート） | 記事下に監修者カード表示の挿入 | 小 |
| `header.php` | グローバルナビに「Doctors」追加（案A/C採用時のみ） | 小 |
| `footer.php` | フッターに「監修医師一覧」リンク追加（案C採用時のみ） | 小 |
| `style.css` | 監修者カード・プロフィールページ用CSS追加 | 中 |

### 6.2 監修者カードのCSS設計（参考）

親サイト「ドクターの素顔」のデザインを参考に、以下の要素を含むカードCSSを子テーマに追加：

- 顔写真（円形、80px）
- 敬称 + 氏名（明朝体、Shippori Mincho B1）
- 役職・専門医資格
- 所属クリニック（クリニックURLへのリンク）
- 「この医師が監修した記事一覧 →」へのリンク

具体的なCSSは実装フェーズで詰める。

### 6.3 修正しないもの

- `functions.php`（プラグインで完結させる）
- `header.php` の構造的な改修（v2 で確定済み）
- `footer.php` の構造的な改修（v2 で確定済み）

---

## 7. ファイル構成

### 7.1 プラグイン `yd-supervisor` 構成

```
yd-supervisor/
├── yd-supervisor.php              プラグインメインファイル（プラグインヘッダー）
├── readme.txt                      プラグイン説明
├── uninstall.php                   アンインストール時のクリーンアップ
├── inc/
│   ├── class-cpt-doctor.php        CPT登録（yd_doctor）
│   ├── class-acf-fields.php        ACFフィールド定義（PHP）
│   ├── class-rest-api.php          REST API拡張（フィールド公開、リレーション埋込）
│   ├── class-schema.php            JSON-LD生成・AIOSEOフィルター介入
│   ├── class-template-loader.php   テンプレートファイルのロード制御
│   └── helpers.php                 テンプレートタグ・ヘルパー関数
├── templates/
│   ├── single-yd_doctor.php        監修医師シングルテンプレート
│   ├── archive-yd_doctor.php       監修医師アーカイブテンプレート
│   └── partials/
│       ├── doctor-card.php         監修医師カード（一覧用）
│       ├── doctor-profile.php      監修医師プロフィール詳細
│       └── reviewed-posts.php      監修記事一覧
├── assets/
│   ├── css/
│   │   └── doctor-pages.css        監修医師ページ専用CSS
│   └── js/
│       └── (必要に応じて)
└── languages/
    └── yd-supervisor.pot          翻訳ファイル
```

### 7.2 子テーマ修正ファイル

```
swell_child_custmon/
├── style.css                  既存 + 監修者カード用CSS追記
├── functions.php              既存（変更なし、または最小限）
├── header.php                 ナビに「Doctors」追加（案A/C採用時）
├── footer.php                 フッターに監修者リンク追加（案C採用時）
└── single.php                 【新規】監修者カードを挿入
```

---

## 8. GitHubリポジトリ構成

### 8.1 リポジトリ名・配置

- **組織：** （Daikiさん指定の既存組織）
- **リポジトリ名候補：** `your-doctor-media`（一案）
- **公開設定：** Private（推奨。商用案件のため）

### 8.2 ディレクトリ構成

```
your-doctor-media/                 リポジトリルート
├── README.md                      プロジェクト概要・セットアップ手順
├── .gitignore                     除外設定
├── .editorconfig                  エディタ設定統一
├── docs/
│   ├── design.md                  本書（設計書）
│   ├── api.md                     REST API仕様書
│   ├── deployment.md              デプロイ手順
│   └── coding-guidelines.md       コーディング規約
├── theme/
│   └── swell_child_custmon/       子テーマ全ファイル
│       ├── style.css
│       ├── functions.php
│       ├── header.php
│       ├── footer.php
│       └── single.php
├── plugin/
│   └── yd-supervisor/             プラグイン全ファイル
│       └── (上記7.1の構成)
└── scripts/                       デプロイ補助スクリプト
    ├── deploy-theme.sh            子テーマFTP転送
    └── deploy-plugin.sh           プラグインFTP転送
```

### 8.3 README.md の構成（提案）

```markdown
# Your Doctor Media

your-doctor.jp/media/ の子テーマとプラグインのソースコード。

## 構成
- theme/swell_child_custmon: SWELL子テーマ
- plugin/yd-supervisor: 監修医師管理プラグイン
- docs/: 設計書・API仕様

## 必須プラグイン
- SWELL（テーマ、別途購入）
- All in One SEO Pack
- Advanced Custom Fields PRO

## デプロイ
FTPで /media/wp-content/themes/, /media/wp-content/plugins/ に配置。
詳細は docs/deployment.md 参照。

## 開発者
- 株式会社YUKATAN
- 前中氏（記事作成システム連携）

## ブランチ運用
- main: 本番環境
- develop: 統合ブランチ
- feature/xxx: 機能ブランチ
```

### 8.4 ブランチ戦略

- **main**：本番（FTPデプロイ済みの状態と一致）
- **develop**：統合・検証用
- **feature/xxx**：機能開発ブランチ
- リリース時：`feature/xxx` → `develop` → `main`（PRベース）

### 8.5 デプロイフロー（手動FTP前提）

サーバーアクセスがFTPのみのため、CIによる自動デプロイは難しい。以下の手動運用を推奨：

1. ローカルで feature ブランチで開発
2. develop ブランチに PR & マージ
3. ローカルで `git pull origin develop`
4. FTPクライアント（FileZilla等）で対象ファイルをサーバーに転送
5. 検証OK後、develop → main マージ

#### 将来：GitHub Actions経由のFTPデプロイ

`SamKirkland/FTP-Deploy-Action` などを使えば、main マージ時に自動FTPデプロイも可能。SSH/FTPの認証情報を Repository Secrets に登録することが前提。第2フェーズで検討。

### 8.6 .gitignore の主要項目

```
# WordPress関連
wp-config.php
.htaccess
wp-content/uploads/
wp-content/cache/
wp-content/upgrade/

# OS / Editor
.DS_Store
Thumbs.db
.vscode/
.idea/

# Build artifacts
node_modules/
*.log

# ACF JSON cache (PHPで定義するためJSON生成は使わない)
acf-json/
```

---

## 9. 実装フェーズ分割

実装を以下の6フェーズに分割する。各フェーズ完了時に動作確認を行ってから次へ進む。

### Phase 1：プラグイン基盤（CPT + ACF）

- プラグインメインファイル作成（プラグインヘッダー）
- `yd_doctor` CPT登録
- ACF フィールドグループ定義（PHP）
- 管理画面で監修医師の登録ができることを確認

**検証項目：**
- 管理画面サイドバーに「監修医師」メニューが出る
- 新規追加で全ACFフィールドが入力できる
- 投稿一覧に登録した医師が表示される

### Phase 2：記事との関連付け

- `post` 投稿タイプにACF post_object フィールド追加
- ヘルパー関数 `yd_get_post_supervisors()` 実装

**検証項目：**
- 記事編集画面で監修医師を複数選択できる
- 保存後、再表示で正しく選択状態が復元される

### Phase 3：監修者ページのテンプレート

- single-yd_doctor.php 作成
- archive-yd_doctor.php 作成
- 監修者カード partial 作成
- doctor-pages.css 作成
- 双方向参照（監修者の監修記事一覧）の実装

**検証項目：**
- `/media/doctor/{slug}/` が表示される
- `/media/doctor/` 一覧が表示される
- 該当監修者が監修した記事一覧が表示される

### Phase 4：ターゲットKW機能

- `register_post_meta()` 登録
- ACFフィールドグループ追加
- REST API での読み書き確認

**検証項目：**
- 記事編集画面右サイドにKW入力欄が出る
- REST API で `meta.yd_target_keyword` が読み書きできる

### Phase 5：構造化データ実装

- AIOSEOフィルター介入実装
- MedicalWebPage 変換ロジック
- reviewedBy 追加ロジック
- 除外設定の判定

**検証項目：**
- Google構造化データテストツールでエラーなし
- 監修者ありの記事は MedicalWebPage として認識される
- 除外フラグONの記事は Article のまま

### Phase 6：子テーマ修正・サイト動線

- single.php に監修者カード挿入
- header.php に「Doctors」ナビ追加（案決定後）
- フッター修正（案C採用時）
- CSS 微調整

**検証項目：**
- 記事ページに監修者カードが表示される
- グローバルナビから監修者一覧へ遷移できる
- 表示崩れがない

---

## 10. テスト・検証方針

### 10.1 機能テスト

各 Phase 完了時に以下を確認：

- 該当機能が想定通り動作する
- 既存機能（記事投稿、表示）に影響していない
- 管理画面でエラーが出ていない

### 10.2 構造化データテスト

- Google Rich Results Test（`https://search.google.com/test/rich-results`）
- Schema.org Validator（`https://validator.schema.org/`）
- AIOSEOの schema 出力との重複がないか確認

### 10.3 REST API テスト

- Postman / Insomnia で各エンドポイントを叩く
- 認証なしでの GET、認証ありでの POST/PUT/DELETE
- エラーケース（不正パラメータ、権限不足）の挙動確認

### 10.4 パフォーマンステスト

- 監修者100名・記事1,000件規模での動作確認
- 双方向参照クエリのレスポンス時間（特に「監修した記事一覧」）
- 必要に応じてキャッシュ・インデックスを検討

### 10.5 ブラウザ・デバイステスト

- Chrome / Safari / Firefox（最新版）
- iOS Safari / Android Chrome
- レスポンシブ表示の確認（PC / タブレット / SP）

---

## 11. 未確定論点（要 Daikiさん判断）

| # | 論点 | 推奨案 | 代替案 |
|---|---|---|---|
| 1 | サイトナビゲーション動線 | 案C（複合：ナビ + トップ + フッター） | 案A（ナビのみ） |
| 2 | 「専門分野」タクソノミーの追加 | MVPでは不要 | 監修者数増加時に追加 |
| 3 | GAS/外部システムの認証方式 | Application Passwords（WP標準） | JWT Auth プラグイン |
| 4 | 監修者シングルページの「監修記事一覧」のページネーション | 1ページ12件・無限スクロールなし | 無限スクロール対応 |
| 5 | プラグインのアンインストール時挙動 | データ保持（uninstall.phpで何もしない） | データ削除（オプション化） |
| 6 | リポジトリ名 | `your-doctor-media` | 別案あれば |

---

## 付録A：用語集

| 用語 | 説明 |
|---|---|
| CPT | Custom Post Type（カスタム投稿タイプ） |
| ACF | Advanced Custom Fields（フィールド管理プラグイン） |
| AIOSEO | All in One SEO Pack |
| E-E-A-T | Experience, Expertise, Authoritativeness, Trustworthiness（Googleの品質基準） |
| YMYL | Your Money or Your Life（人生に重大な影響を与える領域、医療・金融など） |
| MedicalWebPage | schema.org の WebPage サブタイプ。医療情報ページ用 |
| reviewedBy | schema.org のプロパティ。記事を監修した人物を示す |
| sameAs | schema.org のプロパティ。同一エンティティを示す外部URL |

## 付録B：参考リンク

- schema.org MedicalWebPage: https://schema.org/MedicalWebPage
- schema.org Physician: https://schema.org/Physician
- schema.org MedicalSpecialty: https://schema.org/MedicalSpecialty
- AIOSEO Documentation: https://aioseo.com/docs/
- ACF Pro: https://www.advancedcustomfields.com/pro/

---

**改訂履歴**
- v1.0（2026-05-01）：初版作成
