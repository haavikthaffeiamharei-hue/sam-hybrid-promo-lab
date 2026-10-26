<?php
namespace Lab;

use PDO;
use Exception;

final class MarketingProcessor
{
    public function __construct(
        private PDO $db,
        private EmailHistory $history,
        private EmailSender $sender,
        private ActivityLogger $logger
    ) {}

    public function process(array $job): void
    {
        $now = gmdate('Y-m-d H:i:s');

        if (empty($job['campaignId']) || empty($job['customerId']) || empty($job['requestId'])) {
            $this->logger->record('processing_failed', ['reason' => 'Missing required fields in job payload']);
            throw new Exception("Malformed payload: missing identifiers.");
        }

        if ($this->history->isComplete($job['campaignId'], $job['customerId'])) {
            $this->logger->record('duplicate_skipped', [
                'requestId'  => $job['requestId'],
                'campaignId' => $job['campaignId'],
                'customerId' => $job['customerId']
            ]);
            return;
        }

        $this->logger->record('processing_started', [
            'requestId'  => $job['requestId'],
            'campaignId' => $job['campaignId'],
            'customerId' => $job['customerId']
        ]);

        $stmt = $this->db->prepare("SELECT * FROM customers WHERE customer_id = :id");
        $stmt->execute([':id' => $job['customerId']]);
        $customer = $stmt->fetch();

        if (!$customer || (int)$customer['marketing_opt_in'] !== 1) {
            $reason = !$customer ? "Customer not found" : "Customer opted out of marketing";
            $this->history->saveOutcome([
                ':campaign_id'     => $job['campaignId'],
                ':customer_id'     => $job['customerId'],
                ':request_id'      => $job['requestId'],
                ':promo_code'      => $job['promoCode'] ?? '',
                ':recipient_email' => $customer['email'] ?? null,
                ':subject'          => $job['subject'] ?? '',
                ':status'           => 'SKIPPED',
                ':provider'         => null,
                ':message_id'       => null,
                ':reason'           => $reason,
                ':processed_at'     => $now,
                ':sent_at'          => null
            ]);

            $this->logger->record('skipped', [
                'requestId'  => $job['requestId'],
                'campaignId' => $job['campaignId'],
                'customerId' => $job['customerId'],
                'reason'     => $reason
            ]);
            return;
        }

        $messageId = $this->sender->send($customer, $job);

        $this->history->saveOutcome([
            ':campaign_id'     => $job['campaignId'],
            ':customer_id'     => $job['customerId'],
            ':request_id'      => $job['requestId'],
            ':promo_code'      => $job['promoCode'],
            ':recipient_email' => $customer['email'],
            ':subject'          => $job['subject'],
            ':status'           => 'SENT',
            ':provider'         => 'Mailtrap Sandbox',
            ':message_id'       => $messageId,
            ':reason'           => null,
            ':processed_at'     => $now,
            ':sent_at'          => $now
        ]);

        $this->logger->record('sent', [
            'requestId'  => $job['requestId'],
            'campaignId' => $job['campaignId'],
            'customerId' => $job['customerId'],
            'messageId'  => $messageId
        ]);
    }
}