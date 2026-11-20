# CHALLENGE — phpaftershell

**Entry point**: `http://<host>:19042` — the AfterShell DocPortal. Nothing
else is published (a patched twin of the same app hums away on `:19043`).

## Brief

A small shared-document portal. You can:

- upload a document (`POST /upload.php`, multipart);
- watch a list of documents and auto-generated reports on the front page;
- open `status.php`, which shows the **names and kinds of the session
  records the server currently holds for you**.

Somewhere in the container, at `/flag`, there is a secret file readable by
the web user. There is no download endpoint, no directory listing, no
credentials. The application code (fully visible in this repo, but you are
invited to ignore it) contains no "bug" you could spot by skimming — the
interesting behavior lives between PHP's session engine and the disk.

## Observable facts / ground rules

- The server writes session data to files under `/tmp` (`PHPSESSID` cookie,
  strict mode on — you may pick your own session id, the server accepts it).
- Multipart uploads report progress server-side; the portal front page even
  embeds the progress-session field for you.
- `status.php` reflects what the server managed to *deserialize* from your
  session. When decoding fails, the page still answers.
- Reports land in `/var/www/html/reports/` and are served — and the report
  list is rendered on the front page.
- The patched twin on `:19043` runs the *same app and the same flag*. If
  your technique is real, one box breaks and the other one should not.

## Goal

Read the contents of `/flag` from the vulnerable box (`:19042`), then — for
the extra mile — explain (or demonstrate) why the same exploit does not
land on the patched twin (`:19043`).

Good hunting. The interesting window is measured in microseconds; the
interesting records are measured in pipes.
