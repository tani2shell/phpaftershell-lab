# SOLUTION / WALKTHROUGH — phpaftershell

Spoiler zone. The flag is published with this walkthrough by design.

**Flag: `flag{a8f3344dd22e3294ededed}`**

## TL;DR chain

stalled multipart upload (carrier) → completed upload (pivot) → second
stalled upload (replacement) interrupted mid session-write → stitched
session file survives on disk → `evil|O:9:"ReportJob":…` decodes as its own
record → `__wakeup` → `LogRotator::rotate()` → `system("wc -l …;cp /flag
reports/…;#")` → fetch `/reports/p001`.

## The primitive: the files handler does not write atomically

PHP's `session.save_handler = files` persists a session as:

```
openat("/tmp/sess_<sid>", O_RDWR|O_CREAT)
flock(...); fstat(...)
pread64(...)                    ; read old content
ftruncate(fd, 0)                ; trim
pwrite64(fd, new_serialized)    ; write new content
close
```

`ftruncate` + `pwrite` are individually atomic; **the pair is not**. If the
prefork child dies after the new content has been written but while the
length bookkeeping has not taken effect, the file on disk becomes
**new-prefix + surviving old tail** — a *stitched* session.

The `php` serialize handler's decode rules make stitches exploitable
(behavior of 8.4.27, verified on this exact image):

- a record is `key|serialized_value;`;
- the key scanner swallows **any** bytes up to the first `|` — quotes,
  semicolons, garbage included;
- so a payload like `...Xevil|O:9:"ReportJob":1:{s:8:"template";s:44:"…";}…`
  stranded in the tail decodes as an independent `evil` record holding a
  live object, even though it sits inside somebody else's string.

## The gadget (visible in `app/lib/portal.php`)

```
ReportJob::__wakeup()
  → new LogRotator; $rot->file = $this->template; $rot->rotate()
  → system('wc -l ' . $file . ' 2>/dev/null', $rc)   ; $file unescaped
```

Command-injection payload for `template`:
`x;cp /flag /var/www/html/reports/p001;#`
(`/var/www/html/reports/` is world-writable tmpfs and served + listed on
the front page — a built-in exfil shelf.)

## The carrier: `session.upload_progress`

With `session.upload_progress.enabled = 1` (the vuln ini), an in-flight
multipart POST with a `PHP_SESSION_UPLOAD_PROGRESS` field makes PHP tick
progress records into **your** session file every `1%, 1024` bytes. Each
tick serializes the current in-flight upload part's **field name**, verbatim
(`|` is not escaped there, `\"` in it gets unescaped). So:

1. **bootstrap** — `GET /index.php` → session `P` (just `portal_token`).
2. **carrier** — a stalled multipart POST: 20 file parts whose
   `name="…"` fields are ~4.6 KB each (keep every header line under the
   ~5 KB Apache cliff). Part 1's field name embeds
   `evil|O:9:"ReportJob":1:{s:8:"template";s:44:"x;cp /flag …;#"};` in
   escaped form. Hold the body open → ticks write the fat
   `upload_progress_T` record (~95 KB of craft) into the session file.
   `GET /status.php` shows `upload_progress_…` present — your oracle.
3. **pivot** — a tiny *completed* upload (`filename=a`) on the same session:
   the write now on disk is `P + upload_progress_T(carrier, alive) +
   documents|a:1:{…}`. The `documents` record gives the future stitched
   tail a legal final record.
4. **replacement** — a second stalled POST, same 20-part skeleton but with
   10-byte field names. Its ticks serialize a **much smaller** record set
   `R` over the fat one. In the window between the trim and the write of
   that tick, the child must die. Disk then reads `R + old[|R|:]`, and the
   cut point lands inside the carrier's 4.6 KB field-name string —
   resurrecting `evil|O:9:"ReportJob":…` as a decodable record.
5. **trigger** — `GET /status.php` → `session_start()` decodes `evil` →
   `__wakeup` fires → `cp /flag /var/www/html/reports/p001`.
6. **collect** — `GET /reports/p001` (also linked on the front page).

## Two ways to open the window

### (a) Deterministic kill — the bundled `exploit.py` + `zap`

`zap` is a tiny ptrace harness (`docker exec`'d into the container). It
seizes the prefork child that owns the replacement connection, neutralizes
the *pre-write* `ftruncate` by rewriting its length argument to the
file's current size, and `SIGKILL`s the child at the entry stop of the
first `ftruncate` that follows an in-window `pwrite64`. Result: the write
lands, the length never gets trimmed. The reference exploit resolves the
right child via `/proc/net/tcp` → socket inode → fd owner, and lands the
chain in ~10 s per attempt (40-attempt budget). It is a *self-proof* tool,
not required for play (Linux/arm64 binary; the race itself is portable).

### (b) Black-box — no ptrace, no killing anyone (first-blood route)

An AI pentest agent took the portal in ~19 minutes without ever touching
the container. Key observations that replace the kill:

- the **cleanup path** of `upload_progress` (when the request ends without
  completing) performs a *short* session write **without truncating** —
  the old tail survives all by itself;
- the reader tolerates garbage before a `|`: even when the overall
  deserialize *fails*, objects promoted ahead of the broken part still get
  `__wakeup`'d — `status.php` answering with partial records is your
  confirmation;
- the escape alphabet inside a quoted serialized string is just
  `\"`-unescaping plus the raw `|` in `name=` fields.

Same stitch, same gadget, zero root on the box.

## Why `:19043` (the patched twin) does not break

The upstream 8.4.28 fix for CVE-2026-126714 lands as a default flip:
`session.upload_progress.enabled` now **defaults to off** (measured on these
images: `php -n` built-in default is `1` on 8.4.27, `0` on 8.4.28; upstream
also documents "explicitly enabled in php.ini stays enabled"). The control
container's `config-patched/99-portal.ini` keeps that semantics with an
explicit `0`; the vulnerable container keeps the explicit `1` that real ops
so often pins.

With upload progress off, the tick never writes anything to the session
file — the carrier `upload_progress_*` record is never created, and the
chain dies at step 1, *deterministically* (`status.php` never grows the
record; `exploit.py --expect-miss` against `:19043` reports
`carrier upt missing` every single attempt). Note where the chain breaks:
**not** at the race, but at the record that feeds it — the fix removes the
carrier, not the non-atomic write.

## Field notes (honesty section)

- The stitch window on the reference chain is a ~26-byte band inside the
  carrier field name; the deterministic harness lands it, black-box
  cleanup-path stitching is noisier but needs no privileges.
- `session.upload_progress.cleanup = 1` matters: it *creates* the cleanup
  short-write path that variant (b) rides.
- Strict mode is on, but you choose your own session id — pick hex and keep
  every attempt's state in one cookie.
- The report shelf is tmpfs: `./RESET.sh` wipes exfiltrated copies, and a
  container restart wipes them too.

## Flag

`flag{a8f3344dd22e3294ededed}` — first collected by an AI pentest agent,
black-box, in 18'36" of work (see `DIFFICULTY.md`).
