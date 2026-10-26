<?php
namespace Lab;

use Aws\Sqs\SqsClient;
use PDO;

final class QueuePublisher
{
    public function __construct(
        private PDO $db,
        private array $awsConfig,
        private string $queueUrl,
        private ActivityLogger $logger
    ) {}

    public function publishPending(): void
    {
        $sqs = new SqsClient($this->awsConfig);

        $stmt = $this->db->query("SELECT * FROM marketing_outbox WHERE status = 'PENDING'");
        $pending = $stmt->fetchAll();

        $updateStmt = $this->db->prepare("
            UPDATE marketing_outbox 
            SET status = 'QUEUED', queued_at = :queued_at, sqs_message_id = :sqs_message_id 
            WHERE request_id = :request_id
        ");

        foreach ($pending as $row) {
            $payload = [
                'requestId'  => $row['request_id'],
                'campaignId' => $row['campaign_id'],
                'customerId' => $row['customer_id'],
                'promoCode'  => $row['promo_code'],
                'subject'    => $row['subject'],
                'body'       => $row['body']
            ];

            $result = $sqs->sendMessage([
                'QueueUrl'    => $this->queueUrl,
                'MessageBody' => json_encode($payload, JSON_THROW_ON_ERROR)
            ]);

            $messageId = $result->get('MessageId');
            $queuedAt = gmdate('Y-m-d H:i:s');

            $updateStmt->execute([
                ':queued_at'      => $queuedAt,
                ':sqs_message_id' => $messageId,
                ':request_id'     => $row['request_id']
            ]);

            $this->logger->record('queued', [
                'requestId'    => $row['request_id'],
                'campaignId'   => $row['campaign_id'],
                'customerId'   => $row['customer_id'],
                'sqsMessageId' => $messageId
            ]);
        }

        echo "Published " . count($pending) . " pending messages to SQS.\n";
    }
}