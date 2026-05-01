# REST API リファレンス

`https://your-doctor.jp/media/wp-json/` 配下の REST API 仕様書。
外部記事作成システム（前中氏システム）からの連携を想定する。

---

## 1. 認証

書き込み（POST / PUT / PATCH / DELETE）には WordPress 標準の
**Application Password** で Basic 認証する。

### Application Password の発行手順
1. WP管理画面（`https://your-doctor.jp/media/wp-admin/`）にログイン
2. ユーザー → プロフィール
3. ページ最下部「アプリケーションパスワード」セクション
4. 用途名（例：`nakamae-system`）を入力 → 「新しいアプリケーションパスワードを追加」
5. 表示された 24 文字パスワード（例：`abcd EFGH 1234 ...`）を**安全な場所に保管**
   - **画面遷移すると二度と表示されない**

### curl での使用例
```bash
curl -u "{wp_username}:{application_password}" \
  https://your-doctor.jp/media/wp-json/wp/v2/posts/{id}
```

### 権限
- 読み取り（GET）：認証不要（公開記事・公開医師）
- 書き込み：`edit_posts` 権限が必要（投稿者ロール以上）

---

## 2. エンドポイント一覧

| HTTP | エンドポイント | 用途 |
|---|---|---|
| GET | `/wp-json/wp/v2/doctors` | 監修医師一覧 |
| GET | `/wp-json/wp/v2/doctors/{id}` | 監修医師詳細 |
| POST | `/wp-json/wp/v2/doctors` | 監修医師作成 |
| POST | `/wp-json/wp/v2/doctors/{id}` | 監修医師更新 |
| DELETE | `/wp-json/wp/v2/doctors/{id}` | 監修医師削除 |
| GET | `/wp-json/wp/v2/posts` | 記事一覧 |
| GET | `/wp-json/wp/v2/posts/{id}` | 記事詳細 |
| POST | `/wp-json/wp/v2/posts` | 記事作成 |
| POST | `/wp-json/wp/v2/posts/{id}` | 記事更新 |

---

## 3. 監修医師（doctors）API

### 3.1 取得：GET /wp-json/wp/v2/doctors

```bash
curl https://your-doctor.jp/media/wp-json/wp/v2/doctors
```

レスポンス（要約）：
```json
[
  {
    "id": 141,
    "date": "2026-05-01T13:55:54",
    "modified": "2026-05-01T13:55:54",
    "slug": "tanaka-taro",
    "status": "publish",
    "type": "yd_doctor",
    "link": "https://your-doctor.jp/media/doctor/tanaka-taro/",
    "title": { "rendered": "田中 太郎" },
    "content": { "rendered": "<p>...</p>", "protected": false },
    "excerpt": { "rendered": "<p>...</p>", "protected": false },
    "featured_media": 70,
    "acf": {
      "yd_honorific_prefix": "Dr.",
      "yd_job_title": "泌尿器科専門医",
      "yd_medical_specialty": "Urologic",
      "yd_clinic_name": "東京泌尿器科クリニック",
      "yd_clinic_url": "https://example-clinic.jp/",
      "yd_alumni_of": "東京大学医学部",
      "yd_same_as_urls": [
        { "url": "https://..." }
      ],
      "yd_career": "<p>...</p>",
      "yd_qualifications": "...",
      "yd_aga_experience_years": 15,
      "yd_supervisor_comment": "...",
      "yd_doctor_license_year": 2008
    }
  }
]
```

### 3.2 監修医師の作成：POST /wp-json/wp/v2/doctors

```bash
curl -X POST https://your-doctor.jp/media/wp-json/wp/v2/doctors \
  -u "{username}:{app_password}" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "山田 太郎",
    "status": "publish",
    "excerpt": "AGA治療を専門とする泌尿器科医。",
    "fields": {
      "yd_honorific_prefix": "Dr.",
      "yd_job_title": "泌尿器科専門医",
      "yd_medical_specialty": "Urologic",
      "yd_clinic_name": "山田クリニック",
      "yd_clinic_url": "https://example-clinic.jp/"
    }
  }'
```

> ACF Pro が REST 公開する際、書き込みは `fields`（リクエスト時）/ 読み取りは `acf`（レスポンス時）でやり取りされる仕様です。

成功時は新規 doctor の JSON が返る（`id` 含む）。

### 3.3 監修医師の更新：POST /wp-json/wp/v2/doctors/{id}

```bash
curl -X POST https://your-doctor.jp/media/wp-json/wp/v2/doctors/141 \
  -u "{username}:{app_password}" \
  -H "Content-Type: application/json" \
  -d '{
    "fields": {
      "yd_supervisor_comment": "更新されたコメント"
    }
  }'
```

部分更新（指定したフィールドのみ更新）。

### 3.4 アイキャッチ画像の設定

医師の顔写真は WP 標準の featured_media を使用：

```bash
# 1) 画像をアップロード
curl -X POST https://your-doctor.jp/media/wp-json/wp/v2/media \
  -u "{username}:{app_password}" \
  -H "Content-Type: image/jpeg" \
  -H "Content-Disposition: attachment; filename=tanaka.jpg" \
  --data-binary @/path/to/tanaka.jpg

# レスポンスの id を控えて、doctor に紐付け：
curl -X POST https://your-doctor.jp/media/wp-json/wp/v2/doctors/141 \
  -u "{username}:{app_password}" \
  -H "Content-Type: application/json" \
  -d '{ "featured_media": 999 }'
```

### 3.5 ACF フィールド一覧

| フィールドキー | 型 | 必須 | 用途 |
|---|---|---|---|
| `yd_honorific_prefix` | string | ◯ | 敬称（"Dr." など）|
| `yd_job_title` | string | ◯ | 役職・専門医資格 |
| `yd_medical_specialty` | enum | ◯ | 専門分野（下記表参照）|
| `yd_clinic_name` | string | ◯ | 所属クリニック名 |
| `yd_clinic_url` | url | ◯ | 所属クリニック公式URL |
| `yd_alumni_of` | string | △ | 出身大学・大学院 |
| `yd_same_as_urls` | repeater | △ | 公式URL（学会等）|
| `yd_career` | wysiwyg | △ | 経歴 |
| `yd_qualifications` | string | △ | 保有資格（改行区切り）|
| `yd_aga_experience_years` | number | △ | AGA治療経験年数 |
| `yd_supervisor_comment` | string | △ | 監修者コメント |
| `yd_doctor_license_year` | number | △ | 医師免許取得年 |

`yd_medical_specialty` の有効値：

| 保存値 | 表示 | 出力時 schema URI |
|---|---|---|
| `Urologic` | 泌尿器科 | https://schema.org/Urologic |
| `Dermatologic` | 皮膚科 | https://schema.org/Dermatologic |
| `PlasticSurgery` | 形成外科 | https://schema.org/PlasticSurgery |
| `DermatologicSurgery` | 美容皮膚科 | https://schema.org/DermatologicSurgery |
| `InternalMedicine` | 内科 | https://schema.org/InternalMedicine |
| `PrimaryCare` | 一般診療 | https://schema.org/PrimaryCare |

`yd_same_as_urls` のリピーター構造：
```json
"yd_same_as_urls": [
  { "url": "https://example-society.jp/profile/tanaka/" },
  { "url": "https://example-clinic.jp/doctors/tanaka/" }
]
```

---

## 4. 記事（posts）API

### 4.1 記事の取得

```bash
curl https://your-doctor.jp/media/wp-json/wp/v2/posts/123
```

レスポンス（独自フィールドの位置に注意）：
```json
{
  "id": 123,
  "title": { "rendered": "AGAの初期症状とは" },
  "content": { ... },
  "status": "publish",
  "meta": {
    "yd_target_keyword": "AGA 初期症状"
  },
  "yd_supervisors": [141, 142]
}
```

| フィールド | 位置 | 仕組み |
|---|---|---|
| `yd_target_keyword` | `meta.yd_target_keyword` | `register_post_meta` 経由 |
| `yd_supervisors` | **トップレベル** `yd_supervisors` | `register_rest_field` 経由 |

> `yd_supervisors` は ACF post_object 由来でシリアライズ配列保存のため、
> `meta` 配下ではなくトップレベルの独自フィールドとして提供します。

### 4.2 記事作成 / 監修者アサイン同時実施

外部システムから記事入稿時、監修者・ターゲットKW を同時指定：

```bash
curl -X POST https://your-doctor.jp/media/wp-json/wp/v2/posts \
  -u "{username}:{app_password}" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "AGAの初期症状とは｜見逃さないための5つのサイン",
    "content": "<p>記事本文HTML...</p>",
    "status": "draft",
    "categories": [3],
    "meta": {
      "yd_target_keyword": "AGA 初期症状"
    },
    "yd_supervisors": [141, 142]
  }'
```

成功時は新規記事の JSON が返る。

### 4.3 既存記事への監修者更新

```bash
curl -X POST https://your-doctor.jp/media/wp-json/wp/v2/posts/123 \
  -u "{username}:{app_password}" \
  -H "Content-Type: application/json" \
  -d '{
    "yd_supervisors": [141, 142]
  }'
```

`yd_supervisors` 配列を**置き換える**（追加ではない）。既存値を保持したい
場合はクライアント側で merge 処理を行う。

### 4.4 バリデーション

- `meta.yd_target_keyword`：`sanitize_text_field` でサニタイズ。ACF UI 上は最大 200 文字
- `yd_supervisors`：配列として受け取り、各要素を `absint` で整数化、`yd_doctor` 投稿の存在チェック、重複除去。**存在しない ID や非 yd_doctor は黙って除外**

### 4.5 監修者を削除（空にする）

```json
{ "yd_supervisors": [] }
```

空配列で監修者ゼロになる。`null` または空文字を送っても同等に扱われる。

---

## 5. サンプルコード

### 5.1 Python（requests）

```python
import requests
from requests.auth import HTTPBasicAuth

BASE = "https://your-doctor.jp/media/wp-json/wp/v2"
AUTH = HTTPBasicAuth("nakamae", "abcd EFGH 1234 wxyz ...")

# 監修者一覧
res = requests.get(f"{BASE}/doctors").json()
doctors = {d["title"]["rendered"]: d["id"] for d in res}

# 記事作成 + 監修者2名アサイン
payload = {
    "title": "AGAの初期症状",
    "content": "<p>...</p>",
    "status": "draft",
    "meta": {
        "yd_target_keyword": "AGA 初期症状",
    },
    "yd_supervisors": [doctors["田中 太郎"], doctors["山田 花子"]],
}
res = requests.post(f"{BASE}/posts", auth=AUTH, json=payload)
print(res.status_code, res.json()["id"])
```

### 5.2 Node.js（fetch）

```javascript
const BASE = 'https://your-doctor.jp/media/wp-json/wp/v2';
const auth = 'Basic ' + Buffer.from('nakamae:abcd EFGH 1234 ...').toString('base64');

async function createPost() {
  const res = await fetch(`${BASE}/posts`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': auth,
    },
    body: JSON.stringify({
      title: 'AGAの初期症状',
      content: '<p>...</p>',
      status: 'draft',
      meta: {
        yd_target_keyword: 'AGA 初期症状',
      },
      yd_supervisors: [141, 142],
    }),
  });
  const json = await res.json();
  console.log(res.status, json.id);
}
```

### 5.3 GAS（Google Apps Script）

```javascript
function getPostTargetKeyword(postId) {
  const url = `https://your-doctor.jp/media/wp-json/wp/v2/posts/${postId}`;
  const response = UrlFetchApp.fetch(url);
  const data = JSON.parse(response.getContentText());
  return data.meta.yd_target_keyword;
}
```

---

## 6. 構造化データ（参考）

`yd_supervisors` を 1 名以上設定した記事は、AIOSEO の出力経由で
自動的に以下の JSON-LD が生成される（フロント表示時）：

- 記事の `WebPage` graph が `MedicalWebPage` に書き換わる
- `reviewedBy`（監修医師の Person オブジェクト群）が追加される
- `lastReviewed` `medicalAudience` `specialty` が追加される

監修医師シングル `/media/doctor/{slug}/` には `Physician` graph が追加される。

詳細は [`design.md` §5](design.md#5-構造化データjson-ld) と
[`test-checklist.md` Phase 5](test-checklist.md) を参照。

REST API 経由で構造化データそのものを取得することはできない（フロントの
HTML レスポンス内 `<script type="application/ld+json">` を読む形になる）。

---

## 7. エラーハンドリング

| ステータス | 意味 | 対処 |
|---|---|---|
| `401` | 認証失敗 | Application Password を再確認 |
| `403` | 権限不足 | 当該ユーザーに `edit_posts` 権限があるか |
| `404` | リソース無し | post ID / doctor ID を再確認 |
| `400` | バリデーションエラー | レスポンスの `code` `message` を確認 |
| `500` | サーバエラー | 当方に連絡。エラーログを送付すると早い |

レスポンス例（エラー）：
```json
{
  "code": "rest_forbidden",
  "message": "You do not have permission to do that.",
  "data": { "status": 403 }
}
```

---

## 8. 既知の制約

- **メディアのアップロード上限**：サーバ側 `upload_max_filesize` に依存
- **同時リクエスト数**：シェアード環境のため、並列度は控えめに（推奨：3 並列まで）
- **RFC 3986 違反 URL**：`yd_clinic_url` `yd_same_as_urls` は `esc_url_raw` で検証されるため、不正な URL 文字列は黙って空に置き換わる
- **タクソノミー**：監修医師には現状タクソノミー無し。将来「専門分野タクソノミー」を追加する可能性あり

---

## 9. 連絡先

- API の不具合・要望 → 当リポジトリの GitHub Issue
- 緊急時 → 株式会社YUKATAN（Daiki さん）まで直接連絡
