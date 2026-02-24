CREATE DATABASE IF NOT EXISTS car_rental CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE car_rental;

-- Voitures
CREATE TABLE cars (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  type ENUM('SUV','Berline','Eco','Luxe') NOT NULL,
  price_per_day DECIMAL(10,2) NOT NULL,
  image VARCHAR(255) DEFAULT NULL,
  seats TINYINT DEFAULT 5,
  transmission ENUM('Manuelle','Automatique') DEFAULT 'Manuelle',
  fuel ENUM('Diesel','Essence','Hybride','Electrique') DEFAULT 'Diesel',
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Réservations
CREATE TABLE reservations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  car_id INT NOT NULL,
  full_name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  phone VARCHAR(40) NOT NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  days INT NOT NULL,
  total DECIMAL(10,2) NOT NULL,
  note TEXT NULL,
  payment_method ENUM('online','cash') DEFAULT 'online',
  status ENUM('pending','confirmed','canceled') DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_res_car FOREIGN KEY (car_id) REFERENCES cars(id) ON DELETE CASCADE
);

-- Messages contact
CREATE TABLE messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  phone VARCHAR(40) NULL,
  subject VARCHAR(160) NOT NULL,
  message TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Utilisateurs admin
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(80) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Seed: 6 voitures
INSERT INTO cars (name,type,price_per_day,image,seats,transmission,fuel) VALUES
('Dacia Sandero','Eco',250.00,'images/cars/sandero.jpg',5,'Manuelle','Essence'),
('Renault Clio 5','Eco',280.00,'images/cars/clio5.jpg',5,'Manuelle','Diesel'),
('Hyundai Tucson','SUV',450.00,'images/cars/tucson.jpg',5,'Automatique','Diesel'),
('Kia Sportage','SUV',470.00,'images/cars/sportage.jpg',5,'Automatique','Diesel'),
('Mercedes Classe C','Luxe',900.00,'images/cars/mercedes_c.jpg',5,'Automatique','Essence'),
('BMW Série 3','Luxe',950.00,'images/cars/bmw_3.jpg',5,'Automatique','Essence');

-- Admin par défaut: username=admin, password=admin123
INSERT INTO users (username, password_hash) VALUES
('admin', '$2y$10$e2LUwxtQobPcDT/WX/4qSOCUhPX1dsRqliMp4WvKs/6XGLC8jMsoC');
