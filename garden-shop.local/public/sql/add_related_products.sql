-- Для существующей базы: выбрать garden_shop и выполнить один раз.
-- Существующие товары, пользователи и заказы не изменяются.
CREATE TABLE IF NOT EXISTS `related_products` (
  `product_id` INT NOT NULL,
  `related_id` INT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`product_id`, `related_id`),
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`related_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
