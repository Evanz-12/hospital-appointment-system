-- Hospital Appointment Booking System
-- Database: hospital_db
-- Run this file in phpMyAdmin after creating the `hospital_db` database

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------
-- Table: users
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `full_name`   VARCHAR(150) NOT NULL,
  `email`       VARCHAR(150) NOT NULL UNIQUE,
  `password`    VARCHAR(255) NOT NULL,
  `phone`       VARCHAR(20),
  `role`        ENUM('patient','doctor','admin') NOT NULL DEFAULT 'patient',
  `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP,
  `is_active`              TINYINT(1) DEFAULT 1,
  `password_reset_token`   VARCHAR(64) DEFAULT NULL,
  `password_reset_expires` DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: departments
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `departments` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `name`        VARCHAR(100) NOT NULL UNIQUE,
  `description` TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: doctors
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `doctors` (
  `id`              INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`         INT NOT NULL UNIQUE,
  `department_id`   INT NOT NULL,
  `specialisation`  VARCHAR(150),
  `bio`             TEXT,
  `available_days`  VARCHAR(100) DEFAULT 'Mon,Tue,Wed,Thu,Fri',
  `slot_duration`   INT DEFAULT 30,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: appointments
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `appointments` (
  `id`                INT AUTO_INCREMENT PRIMARY KEY,
  `patient_id`        INT NOT NULL,
  `doctor_id`         INT NOT NULL,
  `appointment_date`  DATE NOT NULL,
  `appointment_time`  TIME NOT NULL,
  `reason`            TEXT,
  `status`            ENUM('pending','approved','declined','completed','cancelled') DEFAULT 'pending',
  `notes`             TEXT,
  `created_at`        DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`patient_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: doctor_unavailability
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `doctor_unavailability` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `doctor_id`   INT NOT NULL,
  `unavail_date` DATE NOT NULL,
  `reason`      VARCHAR(255),
  FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Seed: departments
-- --------------------------------------------------------
INSERT INTO `departments` (`name`, `description`) VALUES
('General Medicine',  'Primary healthcare, routine checkups, and common illnesses.'),
('Cardiology',        'Diagnosis and treatment of heart diseases and cardiovascular conditions.'),
('Paediatrics',       'Medical care for infants, children, and adolescents.'),
('Gynaecology',       'Women\'s reproductive health and obstetric care.'),
('Orthopaedics',      'Bone, joint, and musculoskeletal disorders and surgery.'),
('Dermatology',       'Skin, hair, and nail conditions and treatments.'),
('ENT',               'Ear, nose, and throat disorders and surgeries.'),
('Ophthalmology',     'Eye health, vision disorders, and surgical treatments.');

-- --------------------------------------------------------
-- Seed: admin account
-- Password: admin123  (bcrypt hash generated via PHP password_hash)
-- --------------------------------------------------------
INSERT INTO `users` (`full_name`, `email`, `password`, `role`) VALUES
('Hospital Admin', 'admin@hospital.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- NOTE: The hash above is the default Laravel/PHP hash for "password".
-- Replace it by running setup.php once, or manually execute in phpMyAdmin:
--   UPDATE users SET password = '<output of password_hash("admin123",PASSWORD_BCRYPT)>' WHERE email='admin@hospital.com';
-- A safe pre-generated bcrypt hash for 'admin123':
UPDATE `users` SET `password` = '$2y$10$TKh8H1.PfunDb5Jhe4.ZZuHxV1Y8Q8c0RDVwIv7tRhO6p1Z3rLQGa' WHERE `email` = 'admin@hospital.com';

-- --------------------------------------------------------
-- Seed: sample doctors (2 per department for demo)
-- --------------------------------------------------------

-- Doctor users
INSERT INTO `users` (`full_name`, `email`, `password`, `phone`, `role`) VALUES
('Dr. James Adeyemi',    'james.adeyemi@medibook.com',    '$2y$10$TKh8H1.PfunDb5Jhe4.ZZuHxV1Y8Q8c0RDVwIv7tRhO6p1Z3rLQGa', '08011111111', 'doctor'),
('Dr. Ngozi Okonkwo',    'ngozi.okonkwo@medibook.com',    '$2y$10$TKh8H1.PfunDb5Jhe4.ZZuHxV1Y8Q8c0RDVwIv7tRhO6p1Z3rLQGa', '08022222222', 'doctor'),
('Dr. Chukwuemeka Eze',  'chukwu.eze@medibook.com',       '$2y$10$TKh8H1.PfunDb5Jhe4.ZZuHxV1Y8Q8c0RDVwIv7tRhO6p1Z3rLQGa', '08033333333', 'doctor'),
('Dr. Fatima Bello',     'fatima.bello@medibook.com',     '$2y$10$TKh8H1.PfunDb5Jhe4.ZZuHxV1Y8Q8c0RDVwIv7tRhO6p1Z3rLQGa', '08044444444', 'doctor'),
('Dr. Samuel Nwachukwu', 'samuel.nwachukwu@medibook.com', '$2y$10$TKh8H1.PfunDb5Jhe4.ZZuHxV1Y8Q8c0RDVwIv7tRhO6p1Z3rLQGa', '08055555555', 'doctor'),
('Dr. Amaka Obi',        'amaka.obi@medibook.com',        '$2y$10$TKh8H1.PfunDb5Jhe4.ZZuHxV1Y8Q8c0RDVwIv7tRhO6p1Z3rLQGa', '08066666666', 'doctor'),
('Dr. Tunde Adebayo',    'tunde.adebayo@medibook.com',    '$2y$10$TKh8H1.PfunDb5Jhe4.ZZuHxV1Y8Q8c0RDVwIv7tRhO6p1Z3rLQGa', '08077777777', 'doctor'),
('Dr. Grace Uche',       'grace.uche@medibook.com',       '$2y$10$TKh8H1.PfunDb5Jhe4.ZZuHxV1Y8Q8c0RDVwIv7tRhO6p1Z3rLQGa', '08088888888', 'doctor');

-- Doctor profiles (user_id matches insertion order above; admin is id=1 so doctors start at 2)
INSERT INTO `doctors` (`user_id`, `department_id`, `specialisation`, `bio`, `available_days`, `slot_duration`) VALUES
(2, 1, 'General Practitioner',      'MBBS, FWACP. 10 years experience in primary healthcare.',            'Mon,Tue,Wed,Thu,Fri', 30),
(3, 2, 'Interventional Cardiologist','MBBS, FMCP(Cardiology). Specialises in coronary artery disease.',   'Mon,Wed,Fri',         30),
(4, 3, 'Consultant Paediatrician',  'MBBS, FMCPaed. Expert in childhood diseases and immunisation.',      'Mon,Tue,Thu,Fri',     30),
(5, 4, 'Gynaecologist & Obstetrician','MBBS, FMCOG. Specialises in maternal health and fertility.',       'Tue,Wed,Thu',         30),
(6, 5, 'Orthopaedic Surgeon',       'MBBS, FMCS(Ortho). Expert in joint replacement and sports injuries.','Mon,Tue,Wed,Thu,Fri', 45),
(7, 6, 'Consultant Dermatologist',  'MBBS, FMCP(Dermatology). Specialises in skin cancer and cosmetics.', 'Mon,Wed,Thu',         30),
(8, 7, 'ENT Surgeon',               'MBBS, FMCS(ENT). Expert in sinus surgery and hearing disorders.',    'Tue,Wed,Fri',         30),
(9, 8, 'Consultant Ophthalmologist','MBBS, FMCOphth. Specialises in cataract and refractive surgery.',    'Mon,Tue,Thu,Fri',     30);

COMMIT;
