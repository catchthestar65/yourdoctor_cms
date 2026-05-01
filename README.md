# Your Doctor Media

`https://your-doctor.jp/media/` のWordPressサイトに関するソースコードリポジトリ。

## 概要

AGA関連メディアの基盤として、以下を実装する：

- **子テーマ** `swell_child_custmon` ：SWELLベースの子テーマ（親サイト準拠デザイン）
- **プラグイン** `yd-supervisor` ：監修医師管理・ターゲットKW記録・構造化データ自動出力

## ディレクトリ構成

```
your-doctor-media/
├── README.md                       本ファイル
├── docs/
│   ├── design.md                   設計書 v1.0
│   ├── setup-guide.md              セットアップ手順
│   └── claude-code-instructions.md  Claude Code向け初回指示プロンプト
├── theme/
│   └── swell_child_custmon/        SWELL子テーマ（既存・FTPで取得して配置）
├── plugin/
│   └── yd-supervisor/              監修医師プラグイン（Claude Codeが実装）
├── scripts/                         デプロイ補助スクリプト
└── .github/workflows/
    └── deploy.yml                  GitHub Actions FTPデプロイ（任意）
```

## 必須プラグイン（本番環境）

- SWELL（テーマ・別途購入済み）
- All in One SEO Pack（AIOSEO）
- Advanced Custom Fields PRO

## 開発フロー

1. ローカルで `git clone`
2. Claude Code でプロジェクトオープン
3. 設計書（`docs/design.md`）に基づいて実装
4. `git commit && git push`
5. 本番反映（手動FTP or GitHub Actions）

詳細は `docs/setup-guide.md` 参照。

## ブランチ運用

- `main` ：本番反映済み
- `develop` ：統合・検証ブランチ
- `feature/xxx` ：機能ブランチ

## 開発担当

- 株式会社YUKATAN
- 前中氏（記事作成システム連携）

## ライセンス

商用案件のため非公開。
