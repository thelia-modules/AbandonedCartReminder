
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
    `email_client` VARCHAR(255),
    `locale` VARCHAR(5),
    `status` INTEGER(1) DEFAULT 0,
    `login_token` VARCHAR(255),
    `last_update` DATETIME,
    PRIMARY KEY (`id`),
    INDEX `fi_abandoned_cart_cart_id` (`cart_id`),
    CONSTRAINT `fk_abandoned_cart_cart_id`
        FOREIGN KEY (`cart_id`)
        REFERENCES `cart` (`id`)
        ON UPDATE RESTRICT
        ON DELETE CASCADE
) ENGINE=InnoDB;

# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;
