# phpaftershell-lab（中文）

正文见 [README.md](README.md)（英文）。要点：

- **类型**：PHP 会话写盘竞态（pwrite/ftruncate 非原子，CVE-2026-126714）→
  拼接会话反序列化 → 命令注入 RCE，一条链打穿。
- **启动**：`docker compose up -d --build` → 漏洞靶 `http://127.0.0.1:19042`，
  补丁对照组 `http://127.0.0.1:19043`（8.4.28 修复行为 = upload progress 默认关；
  两容器唯一功能差分是 ini 里一行开关）。
- **重置**：`./RESET.sh`（可复制 `.env.example` 为 `.env` 自换旗值）。
- **旗子**：一面（容器内 `/flag`，随题解公开）。
- **难度**：4/5（经 AI 渗透靶手标定——informed 级、净实战约 3 小时，见 `DIFFICULTY.md`）。
- 题面（零泄底）在 `CHALLENGE.md`，完整题解（含无 ptrace 实战链）在 `SOLUTION.md`。
- `exploit.py` + `zap` 是作者的确定性自证工具（需 docker exec，arm64），
  黑盒解题不需要。