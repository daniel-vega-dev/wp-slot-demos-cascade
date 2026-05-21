<?php

namespace SlotDemosCascade;

class Logger
{
    public static function logDir(): string
    {
        $up = wp_upload_dir();
        return rtrim($up['basedir'], '/') . '/sj-demo-logs';
    }

    public static function ensureLogDir(): void
    {
        $dir = self::logDir();
        if (!is_dir($dir)) {
            wp_mkdir_p($dir);
            file_put_contents($dir . '/.htaccess', "Order deny,allow\nDeny from all\n");
            file_put_contents($dir . '/index.html', '');
        }
    }

    public static function launch(array $entry): void
    {
        self::write('launches-' . gmdate('Y-m') . '.jsonl', $entry);
    }

    public static function health(array $entry): void
    {
        self::write('health-' . gmdate('Y-m-d') . '.jsonl', $entry);
    }

    private static function write(string $filename, array $entry): void
    {
        self::ensureLogDir();
        $entry['ts'] = gmdate('c');
        $line = wp_json_encode($entry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
        $path = self::logDir() . '/' . $filename;
        file_put_contents($path, $line, FILE_APPEND | LOCK_EX);
    }

    public static function readRecent(string $type, int $limit = 200): array
    {
        $glob = self::logDir() . '/' . $type . '-*.jsonl';
        $files = glob($glob) ?: [];
        rsort($files);
        $entries = [];
        foreach ($files as $file) {
            $lines = @file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
            for ($i = count($lines) - 1; $i >= 0; $i--) {
                $row = json_decode($lines[$i], true);
                if ($row) {
                    $entries[] = $row;
                }
                if (count($entries) >= $limit) {
                    return $entries;
                }
            }
        }
        return $entries;
    }
}
