<?php
namespace Lab;

use Aws\Sqs\SqsClient;
use PDO;
use Exception;

final class QueueWorker
{
    public function __construct(
        private PDO $db,
        private array $awsConfig,
        private string $queueUrl,
        private MarketingProcessor $processor,
        private ActivityLogger $logger
    ) {}

    public function run(): void
    {
        $sqs = new SqsClient($this->awsConfig);
        echo "Worker started. Listening for SQS messages...\n";

        while (true) {
            $result = $sqs->receiveMessage([
                'QueueUrl'            => $this->queueUrl,
                'WaitTimeSeconds'     => 20,
                'MaxNumberOfMessages' => 1
            ]);

            $messages = $result->get('Messages');
            if (empty($messages)) {
                continue;
            }

            foreach ($messages as $message) {
                $sqsMessageId = $message['MessageId'];
                $receiptHandle = $message['ReceiptHandle'];
                $body = json_decode($message['Body'], true);

                $this->logger->record('received', ['sqsMessageId' => $sqsMessageId]);

                try {
                    $this->processor->process($body);

                    $sqs->deleteMessage([
                        'QueueUrl'      => $this->queueUrl,
                        'ReceiptHandle' => $receiptHandle
                    ]);

                    $this->logger->record('deleted', ['sqsMessageId' => $sqsMessageId]);
                    echo "Processed and deleted message {$sqsMessageId}.\n";

                } catch (Exception $e) {
                    $this->logger->record('processing_failed', [
                        'sqsMessageId' => $sqsMessageId,
                        'error'        => $e->getMessage()
                    ]);
                    echo "Failed processing message {$sqsMessageId}: " . $e->getMessage() . "\n";
                }
            }
        }
    }
}