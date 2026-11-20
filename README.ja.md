# phpaftershell-lab（日本語）

本編は [README.md](README.md)（英語）。要点のみ:

- **ジャンル**: PHP セッション書き込みの競合（pwrite/ftruncate 非アトミック、
  CVE-2026-126714）→ 継ぎ接ぎセッションのデシリアライズ → コマンドインジェクション RCE。
- **起動**: `docker compose up -d --build` → 脆弱ターゲット `http://127.0.0.1:19042`、
  修正版コントロール `http://127.0.0.1:19043`（8.4.28 の修正実態 = upload progress が
  デフォルト OFF。両イメージの機能差分は ini のスイッチ 1 行のみ）。
- **リセット**: `./RESET.sh`（`.env.example` を `.env` にコピーすればフラグ値の自前差し替え可）。
- **フラグ**: 1 枚（コンテナ内 `/flag`。 walkthrough と同時に公開）。
- **難易度**: 4/5（AI ペンテストエージェントのブラックボックス初破 18'36" で較正、`DIFFICULTY.md` 参照）。
- 問題文（ネタバレなし）は `CHALLENGE.md`、完全解答（初破のブラックボックス経路含む）は `SOLUTION.md`。
- `exploit.py` + `zap` は作者の決定論的セルフプルーフ用（docker exec 必要、arm64）。
  ブラックボックス攻略には不要。
