# SANITIZE-CHECK — phpaftershell-lab

Machine run 2026-11-20 · Standard: GITHUB-PLAN §3 脱敏条款 + wave-c 门禁格式 ·
Inversions per card directive: items **13/14 are REVERSED** for this standalone
lab — the flag value MUST appear in `SOLUTION.md` (WT published with flag) and
`.env.example` (flag-rotation hook). Scanner = `scan_gate.py` (programmatic),
not hand-filled. This file names the patterns, so it is excluded from its own
scan. Exclusions: `.git/`, this file.

| # | check | pattern | result |
|---|---|---|---|
| 1 | audit-key leak | `audit-key` | PASS 0 hits |
| 2 | kanban card ids | `\bt_[0-9a-f]{8}\b` | PASS 0 hits |
| 3 | internal war-ledger jargon | `LEDGER\|出厂单\|出厂记录\|交旗\|连营\|靶区\|卡号` | PASS 0 hits |
| 4 | local paths | `/Users/m5\|~/pentest` | PASS 0 hits |
| 5 | internal IP 192.168.x | `192\.168\.` | PASS 0 hits |
| 6 | prod net 172.31.x | `172\.31\.` | PASS 0 hits (lab net re-specified to `10.17.91.0/24`) |
| 7 | avatar names | `\b(Rook\|Beaver\|Owl\|Magpie\|Parrot\|Squirrel\|Peacock\|Bumblebee\|Fox\|Hermes)\b` | PASS 0 hits |
| 8 | owner name | `\b(Tani\|YunGang\|谷运刚)\b\|タニ` | PASS 0 hits |
| 9 | internal vocabulary | `\bforge\b\|beaver-patch\|靶场内部` | PASS 0 hits |
| 10 | attack-box marks | `\bKali\b` | PASS 0 hits |
| 11 | batch ids | `\bb1[0-9]\b(?![0-9a-f])` | PASS 0 hits |
| 12 | XBEN upstream ids | `XBEN[-_]?\d` | PASS 0 hits (not an upstream-derived lab) |
| 13 | **flag published in WT (INVERTED)** | exact flag string in `SOLUTION.md` | PASS (2 occurrences) |
| 14 | **flag published in .env.example (INVERTED)** | exact flag string in `.env.example` | PASS (1 occurrence) |

Binary `zap` scanned with all banned patterns above (latin-1 decode): PASS 0 hits.

## Flag placement audit (expect: WT + .env.example + flags/flag.txt only)

`.env.example`, `SOLUTION.md`, `flags/flag.txt` — matches exactly; CHALLENGE.md
and READMEs carry no flag value (black-box brief stays spoiler-free).

## File-level strays

No `dev_*`/`probe_*`/`verify_*`/`calib*`/`dbg_*`/`matrix_*` scripts, no `*.log`,
no `*.bak`, no `__pycache__`, no `oracle.php`, no `zap.c`/`zaptst` source, no
`.env` real file, no audit-key JSON. Rook battle-history artifact sets
(BRIEF/WIN/exploit_path*.py) were never copied in.

## semgrep supply-chain pre-scan

`semgrep scan --config p/default` on the full tree: **1 finding, by design** —
`php.lang.security.exec-use` on `app/lib/portal.php:71` (`system('wc -l '.$file)`),
which *is* the lab's intended vulnerable sink. Third-party dependency
manifests: none (no composer.json/package.json/requirements) → nothing to pin,
all shipped code is first-party. Supply-chain findings: 0.

## Syntax / structure gates

- `docker compose config -q`: VALID (both services, ports 19042/19043, net 10.17.91.0/24)
- `bash -n RESET.sh`: PASS · `python3 -m py_compile exploit.py`: PASS
- required docs present: README.md / README.zh.md / README.ja.md / CHALLENGE.md /
  SOLUTION.md / DIFFICULTY.md / LICENSE / RESET.sh / .gitignore / .env.example /
  docker-compose.yml / Dockerfile / app/ / config/ / config-patched/ / flags/ /
  exploit.py / zap — all present

## Flag anchor three-way reconciliation

| source | sha8 (rstrip sha256) |
|---|---|
| outbound `flags/flag.txt` | `31b06a9d` |
| production `flags/flag.txt` (live container `/flag`) | `31b06a9d` |
| internal ledger anchor | `31b06a9d` |

**MATCH 3/3.** Flag value `flag{a8f3344dd22e3294ededed}`.

## Smoke test (outbound tree, standalone build)

Fresh `docker compose up -d --build --wait` from this directory: both images
build clean, both containers Healthy.

- `python3 exploit.py` → `[1] HIT sid=7c7570c3ce446226d6a61a9af4d44a92 FLAG:flag{a8f3344dd22e3294ededed} (9.6s)`
- `python3 exploit.py --port 19043 --expect-miss` → `[1] carrier upt missing (len=0)` / `patch holds` (exit 0)

A/B differential confirmed on the outbound build itself, not just the
internal tree.

---

**Verdict: FAILS: 0**

Gate-2 re-check belongs to the publishing proxy before each `git push`.
