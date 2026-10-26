<?php
namespace Lab;

use PDO;

final class EmailHistory
{
    public function __construct(private PDO $db) {}

    public function isComplete(string $campaignId, string $customerId): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM sent_marketing_emails 
            WHERE campaign_id = :campaign_id AND customer_id = :customer_id AND status IN ('SENT', 'SKIPPED')
        ");
        $stmt->execute([':campaign_id' => $campaignId, ':customer_id' => $customerId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function saveOutcome(array $data): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO sent_marketing_emails 
            (campaign_id, customer_id, request_id, promo_code, recipient_email, subject, status, provider, message_id, reason, processed_at, sent_at)
            VALUES (:campaign_id, :customer_id, :request_id, :promo_code, :recipient_email, :subject, :status, :provider, :message_id, :reason, :processed_at, :sent_at)
            ON DUPLICATE KEY UPDATE 
                status = VALUES(status), 
                provider = VALUES(provider), 
                message_id = VALUES(message_id), 
                reason = VALUES(reason), 
                processed_at = VALUES(processed_at), 
                sent_at = VALUES(sent_at)
        ");
        $stmt->execute($data);
    }
}