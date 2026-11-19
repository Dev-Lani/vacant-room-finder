CREATE DATABASE IF NOT EXISTS roomfinder CHARACTER SET utf8mb4;
USE roomfinder;
DROP TABLE IF EXISTS bookings; DROP TABLE IF EXISTS rooms;
CREATE TABLE rooms (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(60) NOT NULL,
  floor ENUM('Upper','Lower') NOT NULL
);
CREATE TABLE bookings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  room_id INT NOT NULL,
  booking_date DATE NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  class_name VARCHAR(60) NOT NULL,
  lendee_name VARCHAR(80) NOT NULL,
  lendee_id VARCHAR(20) NOT NULL,
  key_lent_by VARCHAR(60) NOT NULL,
  status ENUM('booked','checked-in','completed','cancelled') NOT NULL DEFAULT 'booked',
  checked_in_at DATETIME NULL,
  checked_out_at DATETIME NULL,
  key_returned_by VARCHAR(60) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
  INDEX (room_id, booking_date)
);
CREATE TABLE IF NOT EXISTS admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(40) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  display_name VARCHAR(60) NOT NULL
);
INSERT INTO rooms (name,floor) VALUES
('Laboratory 1','Upper'),('Laboratory 2','Upper'),('Laboratory 3','Upper'),('Laboratory 4','Upper'),
('Room 47','Lower'),('Room 48','Lower'),('AVR','Lower'),('TSL Room','Lower');
INSERT INTO bookings (room_id,booking_date,start_time,end_time,class_name,lendee_name,lendee_id,key_lent_by) VALUES
(1,'2026-11-16','08:00','10:00','BSIT 1-A','Rico Navarro','2025-0101','Admin Lim'),
(4,'2026-11-17','08:00','12:00','BSIT 3-A','Paolo Garcia','2023-0088','Admin Lim'),
(6,'2026-11-17','08:00','10:00','BSIT 2-C','Carlo Bautista','2024-0209','Admin Santos'),
(1,'2026-11-17','09:00','12:00','BSIT 1-B','Juan Dela Cruz','2025-0142','Admin Santos'),
(8,'2026-11-17','09:00','11:00','BSIT 2-B','Mark Villanueva','2024-0193','Admin Santos'),
(2,'2026-11-17','13:00','15:00','BSCS 2-A','Maria Reyes','2024-0317','Admin Santos'),
(7,'2026-11-17','13:00','16:00','Dept. seminar','Liza Ramos','2023-0275','Admin Lim'),
(5,'2026-11-17','15:00','17:00','BSCS 4-A','Ana Mendoza','2022-0451','Admin Lim'),
(8,'2026-11-18','10:00','12:00','BSCS 1-A','Grace Aquino','2025-0064','Admin Lim'),
(1,'2026-11-18','13:00','15:00','BSCS 2-B','Jenny Castillo','2024-0230','Admin Santos'),
(1,'2026-11-19','09:00','12:00','BSIT 3-B','Miguel Torres','2023-0164','Admin Lim'),
(1,'2026-11-20','14:00','17:00','BSCS 4-A','Ana Mendoza','2022-0451','Admin Santos');
