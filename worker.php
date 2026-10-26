<?php
require_once __DIR__ . '/config.php';

$db = (new Lab\Database())->connect();
$logger = new Lab\ActivityLogger();
$history = new Lab\EmailHistory($db);
$sender = new Lab\EmailSender();

$processor = new Lab\MarketingProcessor($db, $history, $sender, $logger);

$worker = new Lab\QueueWorker(
    $db,
    Lab\Settings::queue(),
    Lab\Settings::required('SQS_QUEUE_URL'),
    $processor,
    $logger
);

$worker->run();