<?php

use PHPUnit\Framework\TestCase;

final class SecurityTest extends TestCase
{
    public function testNoErrorReportingOff(): void
    {
        $files = glob(__DIR__ . '/../**/*.php');
        $found = [];
        foreach ($files as $f) {
            $s = file_get_contents($f);
            if (strpos($s, "error_reporting(0)") !== false) $found[] = $f;
        }
        $this->assertEmpty($found, 'Found files that disable error_reporting(): ' . implode(', ', $found));
    }

    public function testNoAtFilePutGetContents(): void
    {
        $files = glob(__DIR__ . '/../**/*.php');
        $found = [];
        foreach ($files as $f) {
            $s = file_get_contents($f);
            if (preg_match('/@file_put_contents|@file_get_contents/', $s)) $found[] = $f;
        }
        $this->assertEmpty($found, 'Found @-suppressed file operations: ' . implode(', ', $found));
    }

    public function testNoUnsafeInterpolationInSql(): void
    {
        $files = glob(__DIR__ . '/../**/*.php');
        $danger = [];
        foreach ($files as $f) {
            $s = file_get_contents($f);
            // basic heuristic: look for `id` = '{$var}' or other {$var} inside SQL-like strings
            if (preg_match('/`id`\s*=\s*\'\{\$[A-Za-z0-9_]+\}\'/m', $s) || preg_match('/\{\$[A-Za-z0-9_]+\}/', $s) && preg_match('/(SELECT|UPDATE|INSERT|DELETE).+/i', $s)) {
                $danger[] = $f;
            }
        }
        // allow false-positives but fail if there are obvious matches
        $this->assertEmpty($danger, 'Potential SQL interpolation found in: ' . implode(', ', $danger));
    }

    public function testPhpLint(): void
    {
        $files = array_filter(glob(__DIR__ . '/../**/*.php'), function($p){ return strpos($p, '/vendor/') === false; });
        foreach ($files as $f) {
            $out = null;
            $rc = null;
            exec('php -l ' . escapeshellarg($f) . ' 2>&1', $out, $rc);
            $this->assertEquals(0, $rc, implode("\n", $out));
        }
    }
}
