<?php
/**
 * AfterShell DocPortal — shared library
 * (range integration, no-op when absent)
 */

class Portal
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /** Uploaded document list for the portal front page. */
    public static function documents(): array
    {
        $out = [];
        foreach (glob(UPLOAD_DIR . '/*') ?: [] as $f) {
            if (is_file($f)) {
                $out[] = ['name' => basename($f), 'size' => filesize($f)];
            }
        }
        return $out;
    }

    /** Generated report list (portal feature). */
    public static function reports(): array
    {
        $out = [];
        foreach (glob(REPORT_DIR . '/*') ?: [] as $f) {
            if (is_file($f)) {
                $out[] = basename($f);
            }
        }
        sort($out);
        return $out;
    }
}

/**
 * A queued report-generation job. When a session is resumed, pending
 * jobs re-warm their output template so the queue stays hot.
 */
class ReportJob
{
    public $template = null;   // template selector
    public $owner    = 'guest';

    public function __wakeup()
    {
        // resume: warm the job's output template via the log rotator
        if (!empty($this->template)) {
            $rot           = new LogRotator();
            $rot->file     = $this->template;
            $rot->rotate();
        }
    }
}

/** Housekeeping helper for rotating portal logs. */
class LogRotator
{
    public $file = '/var/log/apache2/access.log';

    public function rotate(): bool
    {
        // FIXME: escape $file before the shell (PORTAL-311)
        $rc = 1;
        @system('wc -l ' . $this->file . ' 2>/dev/null', $rc);
        return $rc === 0;
    }
}
