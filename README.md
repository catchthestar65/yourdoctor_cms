# Your Doctor Media — yourdoctor_cms

`https://your-doctor.jp/media/` AGAメディア向け WordPress 子テーマ + プラグイン
のソースコードリポジトリ。

## 構成

| 種別 | 名前 | 役割 |
|---|---|---|
| 子テーマ | [`theme/swell_child_custmon/`](theme/swell_child_custmon/) | SWELL ベース・親サイト準拠デザイン。ヘッダー / フッターと SVG 許可・Google Fonts 読み込み |
| プラグイン | [`plugin/yd-supervisor/`](plugin/yd-supervisor/) | 監修医師 CPT・記事ターゲットKW・JSON-LD（MedicalWebPage / Physician）自動出力 |

## 実装状況（v0.7.0 — 2026-05-01 時点）

設計書 [`docs/design.md`](docs/design.md) §9 で定義した 6 フェーズすべて + REST 拡張完了。

| Phase | 内容 | 状態 |
|---|---|---|
| 1 | プラグイン基盤 / `yd_doctor` CPT / ACF プロフィールフィールド | ✅ |
| 2 | 記事 ↔ 監修者リレーション（ACF post_object）+ helpers | ✅ |
| 3 | 監修医師シングル `/media/doctor/{slug}/`・アーカイブ `/media/doctor/`・双方向参照 | ✅ |
| 4 | 記事ターゲットKW（`yd_target_keyword` メタ）+ REST 公開 | ✅ |
| 5 | AIOSEO `aioseo_schema_output` フィルター介入で MedicalWebPage / reviewedBy / Physician 自動出力 | ✅ |
| 6 | 記事下監修者カード + ヘッダーナビ「監修医師」 | ✅ |
| 7 | REST API 拡張（ACF フィールド公開 + `yd_supervisors` メタ公開）| ✅ |

## ドキュメント

| ファイル | 内容 |
|---|---|
| [docs/design.md](docs/design.md) | 詳細設計書 v1.0（仕様の正） |
| [docs/api.md](docs/api.md) | **REST API リファレンス**（外部システム連携向け） |
| [docs/onboarding.md](docs/onboarding.md) | **新規参画者オンボーディング**（環境構築・ブランチ運用・PR / デプロイ） |
| [docs/setup-guide.md](docs/setup-guide.md) | 初期環境構築（Daiki さん用に最初に作った手順） |
| [docs/test-checklist.md](docs/test-checklist.md) | 各 Phase の本番動作確認チェックリスト |

## 必須プラグイン（本番環境）

- **SWELL**（テーマ・別途購入済み）
- **All in One SEO Pack（AIOSEO）** 4.x — 構造化データのベース
- **Advanced Custom Fields PRO** — カスタムフィールド管理

## アーキテクチャ概観

```
┌─────────────────────────────────────────────────────────────────┐
│ WordPress (your-doctor.jp/media/)                               │
│                                                                 │
│  ┌────────────────────┐    ┌────────────────────────────────┐  │
│  │ Theme              │    │ Plugin: yd-supervisor          │  │
│  │ swell_child_       │    │                                │  │
│  │ custmon            │uses│  - CPT: yd_doctor              │  │
│  │                    │ ──►│  - ACF Field Groups (PHP定義)  │  │
│  │ - header.php       │    │  - Meta: yd_target_keyword     │  │
│  │ - footer.php       │    │  - Meta: yd_supervisors        │  │
│  │ - style.css        │    │  - Templates: doctor pages     │  │
│  │ - functions.php    │    │  - JSON-LD via AIOSEO filter   │  │
│  └────────────────────┘    │  - REST API extensions         │  │
│         ↑                   └────────────────────────────────┘  │
│         │                                                       │
│  ┌──────┴───────────────────────────────────────────────────┐   │
│  │ AIOSEO 4.x  /  ACF Pro                                   │   │
│  └──────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
                                  ↑
                                  │ REST API（前中氏システム連携）
                                  │
                          ┌───────┴───────┐
                          │ 外部記事入稿  │
                          │ システム      │
                          └───────────────┘
```

責務分離（[詳細](docs/design.md#22-責務分離)）：
- **データ層 / API 層 / 構造化データ層** はプラグイン側（テーマ切替に強い）
- **表示の HTML / CSS** はテンプレートと assets で完結
- 子テーマは「ヘッダー / フッター / 配色」のみで肥大化させない

## クイックスタート

新規参画者の方はまず [`docs/onboarding.md`](docs/onboarding.md) を読んでください。

最短手順：

```bash
git clone git@github.com:catchthestar65/yourdoctor_cms.git
cd yourdoctor_cms

# 機能ブランチを切って作業
git checkout -b feature/your-task

# 編集 → コミット
git add ...
git commit -m "feat(supervisor): 概要"

git push -u origin feature/your-task
# GitHub で PR 作成 → main マージ → 自動 FTP デプロイ
```

## デプロイ

`main` ブランチへの push で `.github/workflows/deploy.yml` が自動的に
本番（`/wp-content/plugins/yd-supervisor/` および
`/wp-content/themes/swell_child_custmon/`）へ FTP 転送します。

詳細は [`docs/setup-guide.md`](docs/setup-guide.md) STEP 9 と
[`docs/onboarding.md`](docs/onboarding.md) のデプロイ章。

## ブランチ運用

- `main` … 本番反映済み（push で自動デプロイがトリガー）
- `develop` … 統合検証用（必要時のみ）
- `feature/xxx` … 機能ブランチ

各タスク：feature → PR → `main` マージ → 自動デプロイ。

## コミット規約（Conventional Commits）

```
<type>(<scope>): <件名>

<本文>
```

`type` の例：

| type | 用途 |
|---|---|
| `feat` | 新機能 |
| `fix` | バグ修正 |
| `docs` | ドキュメント |
| `refactor` | リファクタ |
| `style` | フォーマット |
| `test` | テスト |
| `chore` | その他（CI 設定、依存更新等） |

`scope` は `supervisor`（プラグイン）/`theme`/`ci`/`docs` 等。

## 開発担当

- **株式会社YUKATAN**（リポジトリオーナー / プラグイン・子テーマ実装）
- **前中氏**（記事作成システムの REST 連携。連携仕様は [`docs/api.md`](docs/api.md)）

## ライセンス

商用案件のため非公開（Private リポジトリ）。
