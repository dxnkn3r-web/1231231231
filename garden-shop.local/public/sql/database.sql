SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- ============ КАТЕГОРИИ ============
CREATE TABLE `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `parent_id` INT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `categories` (`id`, `name`, `parent_id`) VALUES
(1, 'Деревья', NULL),
(2, 'Кустарники', NULL),
(3, 'Цветы', NULL);

-- ============ ТОВАРЫ ============
CREATE TABLE `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT,
  `sku` VARCHAR(50) UNIQUE,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `price` DECIMAL(10,2) NOT NULL,
  `stock_quantity` INT DEFAULT 0,
  `image_url` VARCHAR(500),
  `soil_type` VARCHAR(50),
  `light_level` VARCHAR(50),
  `moisture` VARCHAR(50),
  `frost_resistance` VARCHAR(50),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `products`
(`category_id`, `sku`, `name`, `description`, `price`, `stock_quantity`, `image_url`, `soil_type`, `light_level`, `moisture`, `frost_resistance`) VALUES
(1, 'ART-001', 'Яблоня Антоновка', 'Классический сорт, отлично растет в средней полосе России. Плоды крупные, ароматные.', 1500.00, 10, '/uploads/yablonya.png', 'Чернозем', 'Солнце', 'Влажная', 'Высокая'),
(2, 'ART-002', 'Сирень Обыкновенная', 'Ароматный кустарник, неприхотлив, любит солнце. Идеален для живой изгороди.', 800.00, 25, '/uploads/siren_.png', 'Песчаная', 'Солнце', 'Засухоустойчивая', 'Высокая'),
(3, 'ART-003', 'Пион Травянистый', 'Крупные яркие цветы, требует полутени и регулярного полива.', 1200.00, 15, '/uploads/piontrav.png', 'Глинистая', 'Полутень', 'Влажная', 'Средняя'),
(1, 'ART-004', 'Дуб Черешчатый', 'Мощное дерево-долгожитель. Засухоустойчив, морозостоек.', 2500.00, 5, '/uploads/dubcheresh.png', 'Чернозем', 'Солнце', 'Засухоустойчивая', 'Высокая'),
(3, 'ART-005', 'Роза Парковая', 'Эффектный кустарник с длительным цветением. Любит солнце.', 1800.00, 8, '/uploads/rozaparkova.png', 'Чернозем', 'Солнце', 'Влажная', 'Средняя'),
(1, 'ART-006', 'Клён Остролистный', 'Быстрорастущее дерево с красивой кроной. Переносит полутень.', 1600.00, 12, '/uploads/klenostr.png', 'Глинистая', 'Полутень', 'Влажная', 'Высокая'),
(2, 'ART-007', 'Туя Западная', 'Вечнозелёный кустарник для живой изгороди. Морозостойкая.', 950.00, 30, '/uploads/tuyazapad.png', 'Песчаная', 'Солнце', 'Влажная', 'Высокая');

-- Связи товаров используются карточкой товара и панелью администратора.
CREATE TABLE `related_products` (
  `product_id` INT NOT NULL,
  `related_id` INT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`product_id`, `related_id`),
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`related_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============ ПОЛЬЗОВАТЕЛИ ============
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(255) UNIQUE NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(255),
  `phone` VARCHAR(20),
  `is_admin` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Выполните в существующей базе сайта, не удаляя другие таблицы.
CREATE TABLE IF NOT EXISTS `password_resets` (
  `user_id` INT NOT NULL,
  `token_hash` CHAR(64) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `password_resets_token_hash` (`token_hash`),
  CONSTRAINT `password_resets_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============ ЗАКАЗЫ ============
CREATE TABLE `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT,
  `status` ENUM('pending','paid','shipped','cancelled') DEFAULT 'pending',
  `total_amount` DECIMAL(10,2) NOT NULL,
  `payment_id` VARCHAR(255),
  `delivery_address` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============ ПОЗИЦИИ ЗАКАЗА ============
CREATE TABLE `order_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT,
  `product_id` INT,
  `product_name` VARCHAR(255),
  `quantity` INT NOT NULL,
  `price_at_moment` DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============ ЛОГ СИНХРОНИЗАЦИИ С 1С ============
CREATE TABLE `sync_log` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `sync_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `products_updated` INT,
  `status` VARCHAR(50)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============ АКЦИИ ============
CREATE TABLE `promotions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255),
  `discount_percent` INT,
  `starts_at` DATE,
  `ends_at` DATE,
  `is_active` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============ ИНДЕКСЫ ============
CREATE INDEX idx_products_light ON products(light_level);
CREATE INDEX idx_products_stock ON products(stock_quantity);
CREATE INDEX idx_products_price ON products(price);
CREATE INDEX idx_orders_user ON orders(user_id);