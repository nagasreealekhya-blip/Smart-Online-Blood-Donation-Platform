-- LifeFlow Blood Donation Platform - MySQL Schema
-- Import this file into your MySQL database before running the app.
-- Usage: mysql -u root -p lifeflow < database.sql

CREATE DATABASE IF NOT EXISTS lifeflow CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE lifeflow;

-- ── Users ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    full_name     VARCHAR(255)  NOT NULL,
    email         VARCHAR(255)  NOT NULL UNIQUE,
    phone         VARCHAR(30)   DEFAULT NULL,
    password_hash VARCHAR(255)  NOT NULL,
    role          ENUM('donor','patient','hospital','admin') NOT NULL DEFAULT 'donor',
    gender        ENUM('male','female','other') DEFAULT NULL,
    age           TINYINT UNSIGNED DEFAULT NULL,
    city          VARCHAR(100)  DEFAULT NULL,
    state         VARCHAR(100)  DEFAULT NULL,
    status        ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active',
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ── Donor Profiles ──────────────────────────────────────
CREATE TABLE IF NOT EXISTS donor_profiles (
    id                 INT AUTO_INCREMENT PRIMARY KEY,
    user_id            INT NOT NULL UNIQUE,
    blood_group        ENUM('A+','A-','B+','B-','O+','O-','AB+','AB-') NOT NULL,
    availability_status ENUM('available','unavailable','temporarily_unavailable') NOT NULL DEFAULT 'available',
    last_donation_date DATE DEFAULT NULL,
    total_donations    INT UNSIGNED NOT NULL DEFAULT 0,
    reward_points      INT UNSIGNED NOT NULL DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ── Patient Profiles ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS patient_profiles (
    id                 INT AUTO_INCREMENT PRIMARY KEY,
    user_id            INT NOT NULL UNIQUE,
    blood_group_needed ENUM('A+','A-','B+','B-','O+','O-','AB+','AB-') NOT NULL,
    hospital_name      VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ── Hospital Profiles ────────────────────────────────────
CREATE TABLE IF NOT EXISTS hospital_profiles (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL UNIQUE,
    hospital_name   VARCHAR(255) NOT NULL,
    license_number  VARCHAR(100) NOT NULL,
    city            VARCHAR(100) DEFAULT NULL,
    state           VARCHAR(100) DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ── Admin Profiles ───────────────────────────────────────
CREATE TABLE IF NOT EXISTS admin_profiles (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT NOT NULL UNIQUE,
    admin_id     VARCHAR(50)  NOT NULL UNIQUE,
    access_level ENUM('super','operations','reports') NOT NULL DEFAULT 'operations',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ── Blood Requests ───────────────────────────────────────
CREATE TABLE IF NOT EXISTS blood_requests (
    id                   INT AUTO_INCREMENT PRIMARY KEY,
    request_code         VARCHAR(50) NOT NULL UNIQUE,
    requested_by_user_id INT NOT NULL,
    patient_name         VARCHAR(255) NOT NULL,
    blood_group_needed   ENUM('A+','A-','B+','B-','O+','O-','AB+','AB-') NOT NULL,
    units_required       TINYINT UNSIGNED NOT NULL DEFAULT 1,
    hospital_name        VARCHAR(255) NOT NULL,
    location             VARCHAR(255) DEFAULT NULL,
    contact_number       VARCHAR(30)  DEFAULT NULL,
    urgency_level        ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
    needed_by            DATE DEFAULT NULL,
    medical_notes        TEXT DEFAULT NULL,
    status               ENUM('pending','accepted','approved','fulfilled','cancelled') NOT NULL DEFAULT 'pending',
    fulfilled_by_user_id INT DEFAULT NULL,
    created_at           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (requested_by_user_id) REFERENCES users(id),
    FOREIGN KEY (fulfilled_by_user_id) REFERENCES users(id)
);

-- ── Blood Inventory ──────────────────────────────────────
CREATE TABLE IF NOT EXISTS blood_inventory (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    hospital_user_id  INT NOT NULL,
    blood_group       ENUM('A+','A-','B+','B-','O+','O-','AB+','AB-') NOT NULL,
    units_available   SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    status_level      ENUM('normal','low','critical') NOT NULL DEFAULT 'normal',
    last_updated      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_hospital_bg (hospital_user_id, blood_group),
    FOREIGN KEY (hospital_user_id) REFERENCES users(id)
);

-- ── Appointments ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS appointments (
    id                   INT AUTO_INCREMENT PRIMARY KEY,
    donor_user_id        INT NOT NULL,
    hospital_user_id     INT NOT NULL,
    blood_request_id     INT DEFAULT NULL,
    appointment_datetime DATETIME NOT NULL,
    appointment_type     VARCHAR(100) NOT NULL DEFAULT 'Whole Blood Donation',
    notes                TEXT DEFAULT NULL,
    status               ENUM('scheduled','completed','cancelled') NOT NULL DEFAULT 'scheduled',
    created_at           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (donor_user_id)    REFERENCES users(id),
    FOREIGN KEY (hospital_user_id) REFERENCES users(id),
    FOREIGN KEY (blood_request_id) REFERENCES blood_requests(id) ON DELETE SET NULL
);

-- ── Donation History ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS donation_history (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    donor_user_id     INT NOT NULL,
    hospital_user_id  INT NOT NULL,
    blood_group       ENUM('A+','A-','B+','B-','O+','O-','AB+','AB-') NOT NULL,
    units_donated     TINYINT UNSIGNED NOT NULL DEFAULT 1,
    donation_date     DATE NOT NULL,
    certificate_issued TINYINT(1) NOT NULL DEFAULT 0,
    notes             TEXT DEFAULT NULL,
    created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (donor_user_id)    REFERENCES users(id),
    FOREIGN KEY (hospital_user_id) REFERENCES users(id)
);

-- ── Notifications ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS notifications (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    title      VARCHAR(255) NOT NULL,
    message    TEXT NOT NULL,
    type       ENUM('info','success','warning','error') NOT NULL DEFAULT 'info',
    is_read    TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ── Contact Messages ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS contact_messages (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(255) NOT NULL,
    email        VARCHAR(255) NOT NULL,
    subject      VARCHAR(255) NOT NULL,
    message      TEXT NOT NULL,
    is_resolved  TINYINT(1) NOT NULL DEFAULT 0,
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ── Newsletter Subscribers ───────────────────────────────
CREATE TABLE IF NOT EXISTS newsletter_subscribers (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    email        VARCHAR(255) NOT NULL UNIQUE,
    is_active    TINYINT(1) NOT NULL DEFAULT 1,
    subscribed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ── Admin Logs ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS admin_logs (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    admin_id    INT NOT NULL,
    action      VARCHAR(255) NOT NULL,
    entity_type VARCHAR(100) DEFAULT NULL,
    entity_id   INT DEFAULT NULL,
    details     TEXT DEFAULT NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id)
);

-- ── Sample Data ──────────────────────────────────────────
-- Admin user (password: Admin@123)
INSERT IGNORE INTO users (full_name, email, phone, password_hash, role, gender, age, city, state, status) VALUES
('Admin User', 'admin@lifeflow.com', '9000000001', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'male', 30, 'Mumbai', 'Maharashtra', 'active');
INSERT IGNORE INTO admin_profiles (user_id, admin_id, access_level)
SELECT id, 'ADM-001', 'super' FROM users WHERE email='admin@lifeflow.com';

-- Donor (password: Donor@123)
INSERT IGNORE INTO users (full_name, email, phone, password_hash, role, gender, age, city, state, status) VALUES
('Arjun Sharma', 'donor@lifeflow.com', '9000000002', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'donor', 'male', 28, 'Pune', 'Maharashtra', 'active');
INSERT IGNORE INTO donor_profiles (user_id, blood_group, availability_status, total_donations, reward_points)
SELECT id, 'O+', 'available', 4, 200 FROM users WHERE email='donor@lifeflow.com';

-- Patient (password: Patient@123)
INSERT IGNORE INTO users (full_name, email, phone, password_hash, role, gender, age, city, state, status) VALUES
('Priya Singh', 'patient@lifeflow.com', '9000000003', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'patient', 'female', 35, 'Delhi', 'Delhi', 'active');
INSERT IGNORE INTO patient_profiles (user_id, blood_group_needed, hospital_name)
SELECT id, 'B+', 'Apollo Hospital Delhi' FROM users WHERE email='patient@lifeflow.com';

-- Hospital (password: Hospital@123)
INSERT IGNORE INTO users (full_name, email, phone, password_hash, role, gender, age, city, state, status) VALUES
('City Medical Center', 'hospital@lifeflow.com', '9000000004', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'hospital', 'other', NULL, 'Mumbai', 'Maharashtra', 'active');
INSERT IGNORE INTO hospital_profiles (user_id, hospital_name, license_number, city, state)
SELECT id, 'City Medical Center', 'MH-HOSP-2024-001', 'Mumbai', 'Maharashtra' FROM users WHERE email='hospital@lifeflow.com';

-- Blood requests
INSERT IGNORE INTO blood_requests (request_code, requested_by_user_id, patient_name, blood_group_needed, units_required, hospital_name, location, contact_number, urgency_level, status)
SELECT CONCAT('REQ-', u.id, '-001'), u.id, 'Ravi Kumar', 'AB-', 2, 'AIIMS Delhi', 'New Delhi', '9876543210', 'critical', 'pending'
FROM users u WHERE u.email='patient@lifeflow.com' LIMIT 1;

INSERT IGNORE INTO blood_requests (request_code, requested_by_user_id, patient_name, blood_group_needed, units_required, hospital_name, location, contact_number, urgency_level, status)
SELECT CONCAT('REQ-', u.id, '-002'), u.id, 'Sunita Devi', 'O-', 3, 'Fortis Hospital', 'Bangalore', '9876500001', 'high', 'pending'
FROM users u WHERE u.email='patient@lifeflow.com' LIMIT 1;

INSERT IGNORE INTO blood_requests (request_code, requested_by_user_id, patient_name, blood_group_needed, units_required, hospital_name, location, contact_number, urgency_level, status)
SELECT CONCAT('REQ-', u.id, '-003'), u.id, 'Vikram Joshi', 'B+', 1, 'Max Hospital', 'Mumbai', '9876500002', 'medium', 'pending'
FROM users u WHERE u.email='patient@lifeflow.com' LIMIT 1;
