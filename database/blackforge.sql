-- BLACKFORGE schema (MySQL / XAMPP)

-- Safe re-import for development:
-- DROP DATABASE blackforge;

CREATE DATABASE IF NOT EXISTS blackforge
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE blackforge;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL,
  full_name VARCHAR(190) NOT NULL,
  birth_date DATE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('user','admin') NOT NULL DEFAULT 'user',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_users_email (email),
  KEY idx_users_role (role)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS products (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(220) NOT NULL,
  brand VARCHAR(120) NOT NULL,
  description TEXT NULL,
  diameter_inch DECIMAL(4,1) NOT NULL,           -- R15..R22+
  bolt_pattern VARCHAR(40) NOT NULL,             -- разболтовка, напр. 5x112
  width_inch DECIMAL(4,1) NOT NULL,              -- ширина
  et_offset INT NOT NULL,                        -- ET
  material ENUM('cast','forged') NOT NULL,       -- литые/кованые
  type ENUM('sport','lux') NOT NULL,             -- спортивные/люкс
  color VARCHAR(80) NOT NULL,
  price DECIMAL(12,2) NOT NULL,
  stock_qty INT NOT NULL DEFAULT 0,
  popularity INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_products_brand (brand),
  KEY idx_products_diameter (diameter_inch),
  KEY idx_products_price (price),
  KEY idx_products_popularity (popularity),
  KEY idx_products_created (created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS product_images (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  product_id INT UNSIGNED NOT NULL,
  url VARCHAR(255) NOT NULL,
  is_primary TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_images_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  KEY idx_images_product (product_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS favorites (
  user_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, product_id),
  CONSTRAINT fk_fav_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_fav_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS promo_codes (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(40) NOT NULL,
  discount_type ENUM('percent','fixed') NOT NULL,
  discount_value DECIMAL(12,2) NOT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_promo_code (code),
  KEY idx_promo_active (active)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS orders (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  customer_email VARCHAR(190) NOT NULL,
  customer_name VARCHAR(190) NOT NULL,
  status ENUM('new','paid','shipped','cancelled') NOT NULL DEFAULT 'new',
  promo_code VARCHAR(40) NULL,
  subtotal DECIMAL(12,2) NOT NULL,
  discount DECIMAL(12,2) NOT NULL DEFAULT 0,
  total DECIMAL(12,2) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  KEY idx_orders_status (status),
  KEY idx_orders_created (created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS order_items (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  product_name VARCHAR(220) NOT NULL,
  unit_price DECIMAL(12,2) NOT NULL,
  qty INT NOT NULL,
  line_total DECIMAL(12,2) NOT NULL,
  CONSTRAINT fk_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
  KEY idx_items_order (order_id)
) ENGINE=InnoDB;

-- demo data (можно удалить)
INSERT INTO products (name, brand, description, diameter_inch, bolt_pattern, width_inch, et_offset, material, type, color, price, stock_qty, popularity)
VALUES
('BLACKFORGE Vortex R19', 'BLACKFORGE', 'Премиальный литой диск с геометрией лучей и выразительной глубиной.', 19.0, '5x112', 8.5, 35, 'cast', 'sport', 'Graphite', 28990.00, 12, 120),
('Noir Lux R20', 'AURELIA', 'Люкс‑серия: строгая форма, металлический блеск, баланс веса и прочности.', 20.0, '5x114.3', 9.0, 40, 'forged', 'lux', 'Black Polished', 55990.00, 6, 90),
('Torque Edge R18', 'FERRON', 'Технологичная геометрия, идеально для динамичной посадки.', 18.0, '5x108', 8.0, 45, 'cast', 'sport', 'Silver', 21990.00, 18, 70);

INSERT INTO promo_codes (code, discount_type, discount_value, active)
VALUES ('BLACK10', 'percent', 10, 1);

