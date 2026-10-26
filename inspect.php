<?php
require_once __DIR__ . '/config.php';

$db = (new Lab\Database())->connect();

echo "--- CUSTOMER COUNTS ---\n";
$stmt = $db->query("SELECT marketing_opt_in, COUNT(*) as total FROM customers GROUP BY marketing_opt_in");
while ($row = $stmt->fetch()) {
    echo "Opt-in (" . $row['marketing_opt_in'] . "): " . $row['total'] . "\n";
}

echo "\n--- OUTBOX STATUS COUNTS ---\n";
$stmt = $db->query("SELECT status, COUNT(*) as total FROM marketing_outbox GROUP BY status");
while ($row = $stmt->fetch()) {
    echo "Outbox (" . $row['status'] . "): " . $row['total'] . "\n";
}

echo "\n--- SENT EMAILS HISTORY COUNTS ---\n";
$stmt = $db->query("SELECT status, COUNT(*) as total FROM sent_marketing_emails GROUP BY status");
while ($row = $stmt->fetch()) {
    echo "Sent History (" . $row['status'] . "): " . $row['total'] . "\n";
}