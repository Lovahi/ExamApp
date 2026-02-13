
SET NAMES 'utf8mb4';
SET CHARACTER SET utf8mb4;
-- schema.sql
CREATE DATABASE IF NOT EXISTS demo_app
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE demo_app;

CREATE TABLE IF NOT EXISTS items (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
);

INSERT INTO items (name) VALUES
('Comprar leche'),
('Preparar práctica PHP'),
('Leer documentación de fetch()');
