-- SQL script to create the registration_requests table if it doesn't exist
-- This table is used to track user registration requests and their approval status

CREATE TABLE IF NOT EXISTS `registration_requests` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `requested_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `approved_by` INT(11) NULL DEFAULT NULL,
    `approved_at` TIMESTAMP NULL DEFAULT NULL,
    `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    PRIMARY KEY (`id`),
    UNIQUE KEY `user_id` (`user_id`),
    KEY `approved_by` (`approved_by`),
    CONSTRAINT `registration_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `registration_requests_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert existing pending users into registration_requests table if they don't exist
INSERT IGNORE INTO registration_requests (user_id, requested_at, status)
SELECT id, created_at, 'pending'
FROM users
WHERE registration_status = 'pending'
AND id NOT IN (SELECT user_id FROM registration_requests);

-- Update existing approved users in registration_requests table
UPDATE registration_requests rr
JOIN users u ON rr.user_id = u.id
SET rr.status = 'approved', rr.approved_at = u.created_at
WHERE u.registration_status = 'approved' AND rr.status = 'pending';

-- Update existing rejected users in registration_requests table
UPDATE registration_requests rr
JOIN users u ON rr.user_id = u.id
SET rr.status = 'rejected', rr.approved_at = u.created_at
WHERE u.registration_status = 'rejected' AND rr.status = 'pending';
