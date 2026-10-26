<?php
namespace Lab;

final class ActivityLogger
{
    private string $logFile;

    public function __construct()
    {
        $dir = __DIR__ . '/../logs';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $this->logFile = $dir . '/events.jsonl';
    }

    public function record(string $event, array $context = []): void
    {
        $data = array_merge([
            'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
            'event' => $event
        ], $context);

        file_put_contents($this->logFile, json_encode($data) . "\n", FILE_APPEND);
    }
}