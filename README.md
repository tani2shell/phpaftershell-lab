# phpaftershell-lab

![battle-tested](https://img.shields.io/badge/battle%20tested-first%20blood%20by%20an%20AI%20pentest%20agent%20in%2019%20min-brightgreen)
Difficulty: **4/5 (calibrated)** · Docker Compose · PHP 8.4

> PHP session-race + deserialization RCE practice lab — kill a worker
> mid-write and the old session tail comes back with a shell.

**EN** (this file) · [日本語](README.ja.md) · [中文](README.zh.md)

## The story

AfterShell DocPortal is a tiny shared-document portal running **PHP 8.4.27**.
The PHP code itself looks boring — the hole is in how PHP's `files` session
handler writes session data to disk: a `pwrite()` followed by an
`ftruncate()` that are **not atomic** (CVE-2026-126714). A worker that dies
between the two leaves a *stitched* session file: the new prefix plus a
surviving tail of the old content. Feed the deserializer something that only
exists in that seam, and the portal's own report-queue gadget chain hands you
command execution as `www-data`. `/flag` sits in the container, readable.

The lab ships the race and the gadget chain in one target, plus a live
**A/B pair** so you can also prove the fix works.

## Quick start

```bash
docker compose up -d --build
# vulnerable target: http://127.0.0.1:19042
# patched control:   http://127.0.0.1:19043   (PHP 8.4.28 semantics)
# reset any time:
./RESET.sh
```

Requirements: Docker with Compose v2. The published ports bind to 0.0.0.0;
the lab network (`10.17.91.0/24`) is private to the compose project.

## The A/B pair

| container | port | PHP | upload progress | expected |
|---|---|---|---|---|
| `web` | 19042 | 8.4.27 | explicitly ON (classic ops state) | chain lands |
| `webpatched` | 19043 | 8.4.28 | default OFF (the upstream fix) | chain breaks |

The only functional difference between the two images is one ini line
(`session.upload_progress.enabled`, see `config/` vs `config-patched/`).
8.4.28 "fixes" this CVE by shipping upload progress off by default — turn it
back on in an ini (a very common ops move) and the patch is a paper tiger.
That asymmetry is the second lesson of this lab.

## What's in the box

| path | role |
|---|---|
| `app/` | the whole DocPortal source (three pages + gadget library) |
| `config/` · `config-patched/` | vuln vs patched PHP ini (one-line diff) |
| `flags/flag.txt` | the flag (bind-mounted read-only at `/flag`) |
| `CHALLENGE.md` | black-box brief — read this first, zero spoilers |
| `SOLUTION.md` | full walkthrough, both working chains, flag included |
| `DIFFICULTY.md` | nominal vs field-calibrated difficulty |
| `exploit.py` | reference exploit + deterministic harness (optional) |
| `zap` | ptrace helper `exploit.py` drives (Linux/arm64 binary, optional) |
| `RESET.sh` | one-shot rebuild + re-seed + health gate |

## Playing it

Read `CHALLENGE.md` and try it black-box first — `status.php` on the portal
is a built-in oracle that shows which session records currently exist, and
you never need the source at all. The bundled `exploit.py` is the *author's*
deterministic proof: it `docker exec`s into the container and uses `zap`
(a small ptrace harness) to kill the prefork child in exactly the right
window. Real solvers get there without ptrace — `SOLUTION.md` documents both
routes, including the black-box one an AI pentest agent used for first
blood in ~19 minutes. `exploit.py`/`zap` are optional, arm64-tuned
furniture for the race window; the target itself is plain HTTP.

## Reset

`./RESET.sh` — tears down, rebuilds, re-seeds the flag (optionally rotate
it via `.env`, see `.env.example`), and gates on health. Fully reproducible
from this repo.

## License / series

MIT. Part of the `<target>-lab` pentest series — targets built by a human
range author and field-calibrated by an AI pentest agent (first blood and
timing data in `DIFFICULTY.md`).
