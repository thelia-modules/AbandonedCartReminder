
# This is a fix for InnoDB in MySQL >= 4.1.x
# It "suspends judgement" for fkey relationships until are tables are set.
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- abandoned_cart
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `abandoned_cart`;

CREATE TABLE `abandoned_cart`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `cart_id` INTEGER NOT NULL,
    `customer_id` INTEGER,
    `email` VARCHAR(255) NOT NULL,
    `email_source` TINYINT DEFAULT 0 NOT NULL,
    `locale` VARCHAR(10),
    `status` TINYINT DEFAULT 0 NOT NULL,
    `reminders_sent` TINYINT DEFAULT 0 NOT NULL,
    `last_reminder_at` TIMESTAMP NULL,
    `recovery_link_fingerprint` VARCHAR(64),
    `recovery_link_used_at` TIMESTAMP NULL,
    `recovered_order_id` INTEGER,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `abandoned_cart_cart_id_unique` (`cart_id`),
    INDEX `idx_abandoned_cart_status_last_reminder` (`status`, `last_reminder_at`),
    INDEX `fi_abandoned_cart_customer_id` (`customer_id`),
    INDEX `fi_abandoned_cart_recovered_order_id` (`recovered_order_id`),
    CONSTRAINT `fk_abandoned_cart_cart_id`
        FOREIGN KEY (`cart_id`)
        REFERENCES `cart` (`id`)
        ON UPDATE RESTRICT
        ON DELETE CASCADE,
    CONSTRAINT `fk_abandoned_cart_customer_id`
        FOREIGN KEY (`customer_id`)
        REFERENCES `customer` (`id`)
        ON UPDATE RESTRICT
        ON DELETE SET NULL,
    CONSTRAINT `fk_abandoned_cart_recovered_order_id`
        FOREIGN KEY (`recovered_order_id`)
        REFERENCES `order` (`id`)
        ON UPDATE RESTRICT
        ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- abandoned_cart_reminder_opt_out
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `abandoned_cart_reminder_opt_out`;

CREATE TABLE `abandoned_cart_reminder_opt_out`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `abandoned_cart_reminder_opt_out_email_unique` (`email`)
) ENGINE=InnoDB;

# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;
