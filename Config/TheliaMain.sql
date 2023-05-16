
# This is a fix for InnoDB in MySQL >= 4.1.x
# It "suspends judgement" for fkey relationships until are tables are set.
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- wish_list
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `wish_list`;

CREATE TABLE `wish_list`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(255),
    `code` VARCHAR(255),
    `customer_id` INTEGER,
    `session_id` VARCHAR(255),
    `created_at` DATETIME,
    `updated_at` DATETIME,
    PRIMARY KEY (`id`),
    INDEX `fi_wish_list_customer_id` (`customer_id`),
    CONSTRAINT `fk_wish_list_customer_id`
        FOREIGN KEY (`customer_id`)
        REFERENCES `customer` (`id`)
        ON UPDATE RESTRICT
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- wish_list_product
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `wish_list_product`;

CREATE TABLE `wish_list_product`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `wish_list_id` INTEGER NOT NULL,
    `product_sale_elements_id` INTEGER NOT NULL,
    `quantity` INTEGER NOT NULL,
    PRIMARY KEY (`id`),
    INDEX `fi_wish_list_product_wish_list_id` (`wish_list_id`),
    INDEX `fi_wish_list_product_sale_elements_id` (`product_sale_elements_id`),
    CONSTRAINT `fk_wish_list_product_wish_list_id`
        FOREIGN KEY (`wish_list_id`)
        REFERENCES `wish_list` (`id`)
        ON UPDATE RESTRICT
        ON DELETE CASCADE,
    CONSTRAINT `fk_wish_list_product_sale_elements_id`
        FOREIGN KEY (`product_sale_elements_id`)
        REFERENCES `product_sale_elements` (`id`)
        ON UPDATE RESTRICT
        ON DELETE CASCADE
) ENGINE=InnoDB;

# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;
