-- À exécuter si la base existe déjà (ajout du mode de paiement)
USE car_rental;
ALTER TABLE reservations
  ADD COLUMN payment_method ENUM('online','cash') DEFAULT 'online' AFTER note;
