<?php
namespace Lab;

use Exception;
use PDO;

final class CampaignPreparer
{
    public function __construct(private PDO $db) {}

    public function prepare(string $campaignId, string $promoCode): void
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM marketing_outbox WHERE campaign_id = :campaign_id");
        $stmt->execute([':campaign_id' => $campaignId]);
        $existingCount = (int)$stmt->fetchColumn();

        if ($existingCount === 50) {
            echo "Campaign {$campaignId} already prepared with 50 recipients. Keeping original selection.\n";
            return;
        }

        if ($existingCount > 0 && $existingCount !== 50) {
            throw new Exception("Inconsistent outbox state: found {$existingCount} existing requests for {$campaignId}.");
        }

        $selectStmt = $this->db->query("
            SELECT customer_id FROM customers 
            WHERE marketing_opt_in = 1 
            ORDER BY RAND() 
            LIMIT 50
        ");
        $recipients = $selectStmt->fetchAll(PDO::FETCH_COLUMN);

        $this->db->beginTransaction();
        try {
            $insertStmt = $this->db->prepare("
                INSERT INTO marketing_outbox 
                (request_id, campaign_id, customer_id, promo_code, subject, body, status, created_at)
                VALUES (:request_id, :campaign_id, :customer_id, :promo_code, :subject, :body, 'PENDING', :created_at)
            ");

            $now = gmdate('Y-m-d H:i:s');
            $subject = "Exclusive Promotion: " . $promoCode;
            $body = "Congratulations! You have been selected to receive our promotional offer code: " . $promoCode;

            foreach ($recipients as $customerId) {
                $requestId = bin2hex(random_bytes(16));
                $insertStmt->execute([
                    ':request_id' => $requestId,
                    ':campaign_id' => $campaignId,
                    ':customer_id' => $customerId,
                    ':promo_code' => $promoCode,
                    ':subject' => $subject,
                    ':body' => $body,
                    ':created_at' => $now
                ]);
            }

            $this->db->commit();
            echo "Successfully staged 50 PENDING requests for campaign {$campaignId}.\n";
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}