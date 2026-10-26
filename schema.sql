CREATE DATABASE IF NOT EXISTS marketing_lab CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE marketing_lab;

DROP TABLE IF EXISTS sent_marketing_emails;
DROP TABLE IF EXISTS marketing_outbox;
DROP TABLE IF EXISTS customers;

-- 1. Customers Table
CREATE TABLE customers (
    customer_id VARCHAR(32) NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(254) NOT NULL,
    marketing_opt_in TINYINT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (customer_id),
    UNIQUE KEY uk_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Marketing Outbox Table
CREATE TABLE marketing_outbox (
    request_id CHAR(32) NOT NULL,
    campaign_id VARCHAR(64) NOT NULL,
    customer_id VARCHAR(32) NOT NULL,
    promo_code VARCHAR(64) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    status ENUM('PENDING', 'QUEUED') NOT NULL DEFAULT 'PENDING',
    created_at DATETIME NOT NULL,
    queued_at DATETIME NULL,
    sqs_message_id VARCHAR(100) NULL,
    PRIMARY KEY (request_id),
    CONSTRAINT fk_outbox_customer FOREIGN KEY (customer_id) REFERENCES customers (customer_id),
    UNIQUE KEY uk_campaign_customer (campaign_id, customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Sent Marketing Emails History Table
CREATE TABLE sent_marketing_emails (
    campaign_id VARCHAR(64) NOT NULL,
    customer_id VARCHAR(32) NOT NULL,
    request_id VARCHAR(100) NOT NULL,
    promo_code VARCHAR(64) NOT NULL,
    recipient_email VARCHAR(254) NULL,
    subject VARCHAR(255) NOT NULL,
    status ENUM('SENT', 'SKIPPED', 'FAILED') NOT NULL,
    provider VARCHAR(50) NULL,
    message_id VARCHAR(255) NULL,
    reason TEXT NULL,
    processed_at DATETIME NOT NULL,
    sent_at DATETIME NULL,
    PRIMARY KEY (campaign_id, customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;