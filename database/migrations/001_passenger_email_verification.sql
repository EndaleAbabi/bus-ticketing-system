-- Run this migration on an existing SafeWay database.
ALTER TABLE users ADD COLUMN first_name VARCHAR(75) NULL AFTER full_name;
ALTER TABLE users ADD COLUMN last_name VARCHAR(75) NULL AFTER first_name;
ALTER TABLE users ADD COLUMN age INT NULL AFTER last_name;
ALTER TABLE users ADD COLUMN email_verified TINYINT(1) NOT NULL DEFAULT 0 AFTER phone;
ALTER TABLE users ADD COLUMN verification_code_hash VARCHAR(255) NULL AFTER email_verified;
ALTER TABLE users ADD COLUMN verification_expires DATETIME NULL AFTER verification_code_hash;
