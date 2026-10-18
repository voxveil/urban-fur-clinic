-- Urban Fur Clinic database schema.
-- SAFE TO IMPORT MULTIPLE TIMES: all CREATE statements use IF NOT EXISTS,
-- and the app boots the schema automatically when the database is missing.
CREATE DATABASE IF NOT EXISTS ` urbanfurclinic`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE ` urbanfurclinic`;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(80) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  full_name VARCHAR(150) NOT NULL,
  role VARCHAR(80) NOT NULL DEFAULT 'Staff',
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS clinic_settings (
  id TINYINT UNSIGNED PRIMARY KEY,
  clinic_name VARCHAR(150) NOT NULL DEFAULT 'Urban Fur Veterinary Clinic',
  phone VARCHAR(40) NOT NULL DEFAULT '',
  email VARCHAR(190) NOT NULL DEFAULT '',
  address VARCHAR(255) NOT NULL DEFAULT '',
  opening_time TIME NOT NULL DEFAULT '08:00:00',
  closing_time TIME NOT NULL DEFAULT '18:00:00',
  appointment_interval_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 30,
  tax_rate DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
  default_minimum_quantity INT UNSIGNED NOT NULL DEFAULT 15,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT IGNORE INTO clinic_settings (id) VALUES (1);

CREATE TABLE IF NOT EXISTS clinic_preferences (
  id TINYINT UNSIGNED PRIMARY KEY,
  preferences_json LONGTEXT NOT NULL,
  updated_by INT UNSIGNED NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_clinic_preferences_user FOREIGN KEY (updated_by) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT IGNORE INTO clinic_preferences (id, preferences_json) VALUES
(1, '{"clinic_logo_url":"","cancellation_policy":"","appointment_reminders":true,"reminder_hours_before":24,"accepted_payment_methods":["Cash","Card","GCash","Pay Later"],"reorder_alerts":true,"supplier_info":"","expiration_alert_days":30,"reports_default_period":"monthly","reports_export_format":"csv","notifications":{"appointment_email":false,"appointment_sms":false,"low_stock_email":false,"low_stock_sms":false,"unpaid_email":false,"unpaid_sms":false},"theme_color":"#2d83b5","backup_frequency":"weekly"}');

CREATE TABLE IF NOT EXISTS audit_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  action VARCHAR(100) NOT NULL,
  details TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_audit_logs_created_at (created_at),
  CONSTRAINT fk_audit_logs_user FOREIGN KEY (user_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS staff (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(150) NOT NULL,
  role VARCHAR(80) NOT NULL,
  phone VARCHAR(40) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  status ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS clients (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(150) NOT NULL,
  phone VARCHAR(40) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  address VARCHAR(255) NULL,
  status ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pets (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  client_id INT UNSIGNED NOT NULL,
  name VARCHAR(100) NOT NULL,
  species VARCHAR(60) NOT NULL,
  breed VARCHAR(100) NOT NULL,
  birth_date DATE NULL,
  notes TEXT NULL,
  status ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_pets_client FOREIGN KEY (client_id) REFERENCES clients(id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS services (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  category VARCHAR(100) NOT NULL,
  description TEXT NULL,
  price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS inventory (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sku VARCHAR(80) NOT NULL UNIQUE,
  name VARCHAR(150) NOT NULL,
  category VARCHAR(100) NOT NULL,
  quantity INT UNSIGNED NOT NULL DEFAULT 0,
  minimum_quantity INT UNSIGNED NOT NULL DEFAULT 0,
  unit_price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS appointments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  pet_id INT UNSIGNED NOT NULL,
  service_id INT UNSIGNED NULL,
  staff_id INT UNSIGNED NULL,
  appointment_at DATETIME NOT NULL,
  status ENUM('Scheduled', 'In Progress', 'Completed', 'Cancelled') NOT NULL DEFAULT 'Scheduled',
  notes TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_appointments_pet FOREIGN KEY (pet_id) REFERENCES pets(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_appointments_service FOREIGN KEY (service_id) REFERENCES services(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_appointments_staff FOREIGN KEY (staff_id) REFERENCES staff(id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS invoices (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  client_id INT UNSIGNED NOT NULL,
  pet_id INT UNSIGNED NULL,
  appointment_id INT UNSIGNED NULL,
  amount DECIMAL(10, 2) NOT NULL,
  payment_method VARCHAR(60) NULL,
  status ENUM('Paid', 'Pending', 'Overdue') NOT NULL DEFAULT 'Pending',
  issued_on DATE NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_invoices_client FOREIGN KEY (client_id) REFERENCES clients(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_invoices_pet FOREIGN KEY (pet_id) REFERENCES pets(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_invoices_appointment FOREIGN KEY (appointment_id) REFERENCES appointments(id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS invoice_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  invoice_id INT UNSIGNED NOT NULL,
  service_id INT UNSIGNED NULL,
  description VARCHAR(255) NOT NULL,
  quantity DECIMAL(10, 2) NOT NULL DEFAULT 1.00,
  unit_price DECIMAL(10, 2) NOT NULL,
  CONSTRAINT fk_invoice_items_invoice FOREIGN KEY (invoice_id) REFERENCES invoices(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_invoice_items_service FOREIGN KEY (service_id) REFERENCES services(id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;
