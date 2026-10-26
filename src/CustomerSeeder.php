<?php
namespace Lab;

use Faker\Generator;
use PDO;

final class CustomerSeeder
{
    public function __construct(
        private PDO $db,
        private Generator $faker
    ) {}

    public function seed(): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO customers (customer_id, full_name, email, marketing_opt_in, created_at)
            VALUES (:customer_id, :full_name, :email, :marketing_opt_in, :created_at)
            ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), marketing_opt_in = VALUES(marketing_opt_in)
        ");

        $now = gmdate('Y-m-d H:i:s');

        for ($i = 1; $i <= 10000; $i++) {
            $customerId = sprintf('CUST-%05d', $i);
            $email = sprintf('customer%05d@example.com', $i);
            $fullName = $this->faker->name();
            $optIn = ($i <= 8000) ? 1 : 0;

            $stmt->execute([
                ':customer_id' => $customerId,
                ':full_name' => $fullName,
                ':email' => $email,
                ':marketing_opt_in' => $optIn,
                ':created_at' => $now
            ]);
        }
    }
}