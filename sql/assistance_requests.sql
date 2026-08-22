CREATE TABLE IF NOT EXISTS `assistance_requests` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `client_name` VARCHAR(150) NOT NULL,
    `address` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(30) NOT NULL,
    `email` VARCHAR(255) NULL,
    `nif` VARCHAR(9) NULL,
    `equipment_type` VARCHAR(100) NOT NULL,
    `has_warranty` TINYINT(1) NOT NULL DEFAULT 0,
    `equipment_label_photo` VARCHAR(255) NOT NULL,
    `invoice_photo` VARCHAR(255) NULL,
    `symptom` TEXT NOT NULL,
    `ip_address` VARCHAR(45) NULL,
    `status` ENUM('new', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'new',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
