-- Urban Fur Clinic database schema.
-- SAFE TO IMPORT MULTIPLE TIMES: all CREATE statements use IF NOT EXISTS,
-- and the app boots the schema automatically when the database is missing.
CREATE DATABASE IF NOT EXISTS `urbanfurclinic_db`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `urbanfurclinic_db`;

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
(1, '{"clinic_logo_url":"","cancellation_policy":"","appointment_reminders":true,"reminder_hours_before":24,"accepted_payment_methods":["Cash","Card","GCash","Pay Later"],"reorder_alerts":true,"supplier_info":"","expiration_alert_days":30,"reports_default_period":"monthly","reports_export_format":"csv","notifications":{"appointment_email":false,"appointment_sms":false,"low_stock_email":false,"low_stock_sms":false,"unpaid_email":false,"unpaid_sms":false},"theme_color":"#2d83b5","theme_mode":"system","sidebar_color":"#0284c7","backup_frequency":"weekly","last_backup_at":null}');

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
  account_user_id INT UNSIGNED NULL UNIQUE,
  full_name VARCHAR(150) NOT NULL,
  phone VARCHAR(40) NOT NULL,
  email VARCHAR(190) NULL UNIQUE,
  emergency_phone VARCHAR(40) NULL,
  address VARCHAR(255) NULL,
  status ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_clients_account_user FOREIGN KEY (account_user_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pets (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  client_id INT UNSIGNED NOT NULL,
  name VARCHAR(100) NOT NULL,
  species VARCHAR(60) NOT NULL,
  breed VARCHAR(100) NOT NULL,
  gender VARCHAR(20) NULL,
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
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_inventory_name (name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS catalog_categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_type ENUM('service', 'inventory') NOT NULL,
  name VARCHAR(100) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_catalog_categories_type_name (category_type, name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS appointments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  pet_id INT UNSIGNED NOT NULL,
  service_id INT UNSIGNED NULL,
  staff_id INT UNSIGNED NULL,
  appointment_at DATETIME NOT NULL,
  status ENUM('Pending', 'Scheduled', 'In Progress', 'Completed', 'Cancelled') NOT NULL DEFAULT 'Scheduled',
  notes TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_appointments_slot (appointment_at, staff_id, status),
  CONSTRAINT fk_appointments_pet FOREIGN KEY (pet_id) REFERENCES pets(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_appointments_service FOREIGN KEY (service_id) REFERENCES services(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_appointments_staff FOREIGN KEY (staff_id) REFERENCES staff(id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS appointment_services (
  appointment_id INT UNSIGNED NOT NULL,
  service_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (appointment_id, service_id),
  CONSTRAINT fk_appointment_services_appointment FOREIGN KEY (appointment_id) REFERENCES appointments(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_appointment_services_service FOREIGN KEY (service_id) REFERENCES services(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT IGNORE INTO appointment_services (appointment_id, service_id)
SELECT id, service_id FROM appointments WHERE service_id IS NOT NULL;

CREATE TABLE IF NOT EXISTS medical_records (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  pet_id INT UNSIGNED NOT NULL,
  recorded_on DATE NOT NULL,
  diagnosis VARCHAR(255) NOT NULL,
  treatment TEXT NULL,
  prescription TEXT NULL,
  notes TEXT NULL,
  recorded_by INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_medical_records_pet_date (pet_id, recorded_on),
  CONSTRAINT fk_medical_records_pet FOREIGN KEY (pet_id) REFERENCES pets(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_medical_records_user FOREIGN KEY (recorded_by) REFERENCES users(id)
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

INSERT INTO services (name, category, description, price)
SELECT seed.name, seed.category, seed.description, seed.price
FROM (
  SELECT 'Full Groom Summer cut S' AS name, 'Grooming Services - Full Groom Packages' AS category, 'Full groom package; size S.' AS description, 500.00 AS price
  UNION ALL SELECT 'Full Groom Puppy cut S', 'Grooming Services - Full Groom Packages', 'Full groom package; puppy cut, size S.', 600.00
  UNION ALL SELECT 'Full Groom Summer cut M', 'Grooming Services - Full Groom Packages', 'Full groom package; size M.', 650.00
  UNION ALL SELECT 'Full Groom Puppy cut M', 'Grooming Services - Full Groom Packages', 'Full groom package; puppy cut, size M.', 750.00
  UNION ALL SELECT 'Full Groom Summer cut L', 'Grooming Services - Full Groom Packages', 'Full groom package; size L.', 850.00
  UNION ALL SELECT 'Full Groom Puppy cut L', 'Grooming Services - Full Groom Packages', 'Full groom package; puppy cut, size L.', 950.00
  UNION ALL SELECT 'Full Groom Summer cut XL', 'Grooming Services - Full Groom Packages', 'Full groom package; size XL.', 950.00
  UNION ALL SELECT 'Full Groom Puppy cut XL', 'Grooming Services - Full Groom Packages', 'Full groom package; puppy cut, size XL.', 1000.00
  UNION ALL SELECT 'Sanitary Groom XS/Puppies', 'Grooming Services - Sanitary Groom', 'Sanitary groom for XS pets or puppies.', 200.00
  UNION ALL SELECT 'Sanitary Groom Small/Puppies', 'Grooming Services - Sanitary Groom', 'Sanitary groom for small pets or puppies.', 250.00
  UNION ALL SELECT 'Sanitary Groom Small Dog/Cat', 'Grooming Services - Sanitary Groom', 'Sanitary groom for small dogs or cats.', 400.00
  UNION ALL SELECT 'Sanitary Groom Small w/ Poodle feet/shave', 'Grooming Services - Sanitary Groom', 'Sanitary groom; small, with Poodle feet/shave.', 350.00
  UNION ALL SELECT 'Sanitary Groom Medium', 'Grooming Services - Sanitary Groom', 'Sanitary groom for medium pets.', 450.00
  UNION ALL SELECT 'Sanitary Groom Large', 'Grooming Services - Sanitary Groom', 'Sanitary groom for large pets.', 550.00
  UNION ALL SELECT 'Sanitary Groom XLarge', 'Grooming Services - Sanitary Groom', 'Sanitary groom for extra-large pets.', 650.00
  UNION ALL SELECT 'Ear Clean / Nail Trim', 'Grooming Services - Add-on Services', 'Grooming add-on service.', 50.00
  UNION ALL SELECT 'Dematting / Poodle Feet / Face Trim / Ear Clean', 'Grooming Services - Add-on Services', 'Grooming add-on service.', 100.00
) AS seed
WHERE NOT EXISTS (
  SELECT 1 FROM services existing
  WHERE existing.name = seed.name
);

INSERT INTO services (name, category, description, price)
SELECT seed.name, seed.category, seed.description, seed.price
FROM (
  SELECT '5 in 1' AS name, 'Veterinary Services' AS category, 'Veterinary service.' AS description, 500.00 AS price
  UNION ALL SELECT '8 in 1', 'Veterinary Services', 'Veterinary service.', 550.00
  UNION ALL SELECT '6 in 1', 'Veterinary Services', 'Veterinary service.', 650.00
  UNION ALL SELECT '4 in 1', 'Veterinary Services', 'Veterinary service.', 950.00
  UNION ALL SELECT 'KVC', 'Veterinary Services', 'Veterinary service.', 500.00
  UNION ALL SELECT 'Anti-rabies', 'Veterinary Services', 'Veterinary service.', 300.00
  UNION ALL SELECT 'Blood Chemistry', 'Veterinary Services', 'Veterinary service.', 2500.00
  UNION ALL SELECT 'CBC', 'Veterinary Services', 'Veterinary service.', 700.00
  UNION ALL SELECT 'Ultrasound', 'Veterinary Services', 'Veterinary service.', 1000.00
  UNION ALL SELECT 'CBV Clinic', 'Veterinary Services', 'Veterinary service.', 300.00
  UNION ALL SELECT 'PR', 'Veterinary Services', 'Veterinary service.', 500.00
  UNION ALL SELECT 'Skin scraping/smear test/fecalysis', 'Veterinary Services', 'Veterinary service.', 250.00
  UNION ALL SELECT 'Injectable small', 'Veterinary Services', 'Veterinary service.', 250.00
  UNION ALL SELECT 'Injectable medium', 'Veterinary Services', 'Veterinary service.', 350.00
  UNION ALL SELECT 'Injectable large', 'Veterinary Services', 'Veterinary service.', 450.00
  UNION ALL SELECT 'Injectable xlarge', 'Veterinary Services', 'Veterinary service.', 550.00
  UNION ALL SELECT 'IV fluid set', 'Veterinary Services', 'Veterinary service.', 500.00
  UNION ALL SELECT 'Replacement can/ivline/ivfluid', 'Veterinary Services', 'Veterinary service.', 150.00
  UNION ALL SELECT 'Confinement 1st day', 'Veterinary Services', 'Veterinary service.', 1600.00
  UNION ALL SELECT 'Confinement succeeding days', 'Veterinary Services', 'Veterinary service.', 1000.00
  UNION ALL SELECT 'Emergency fee', 'Veterinary Services', 'Veterinary service.', 1000.00
  UNION ALL SELECT 'Burial fee', 'Veterinary Services', 'Veterinary service.', 1000.00
  UNION ALL SELECT 'Deworming pups/kittens', 'Veterinary Services', 'Veterinary service.', 100.00
  UNION ALL SELECT 'Deworming <2.5kg', 'Veterinary Services', 'Veterinary service.', 150.00
  UNION ALL SELECT 'Deworming <5kg', 'Veterinary Services', 'Veterinary service.', 200.00
  UNION ALL SELECT 'Deworming <7.5kg', 'Veterinary Services', 'Veterinary service.', 250.00
  UNION ALL SELECT 'Deworming <10kg', 'Veterinary Services', 'Veterinary service.', 300.00
  UNION ALL SELECT 'Deworming <15kg', 'Veterinary Services', 'Veterinary service.', 350.00
  UNION ALL SELECT 'Deworming <20kg', 'Veterinary Services', 'Veterinary service.', 400.00
  UNION ALL SELECT 'Deworming <30kg', 'Veterinary Services', 'Veterinary service.', 550.00
  UNION ALL SELECT 'Pet board large', 'Veterinary Services', 'Veterinary service.', 550.00
  UNION ALL SELECT 'Pet board small', 'Veterinary Services', 'Veterinary service.', 350.00
  UNION ALL SELECT 'Pet board medium', 'Veterinary Services', 'Veterinary service.', 450.00
  UNION ALL SELECT 'Amputation', 'Veterinary Services', 'Veterinary service.', 8000.00
  UNION ALL SELECT 'Castration small/cat', 'Veterinary Services', 'Veterinary service.', 1500.00
  UNION ALL SELECT 'Castration small', 'Veterinary Services', 'Veterinary service.', 2000.00
  UNION ALL SELECT 'Castration medium', 'Veterinary Services', 'Veterinary service.', 2500.00
  UNION ALL SELECT 'Castration large', 'Veterinary Services', 'Veterinary service.', 3500.00
  UNION ALL SELECT 'Catheterization small/cats', 'Veterinary Services', 'Veterinary service.', 1500.00
  UNION ALL SELECT 'Catheterization medium', 'Veterinary Services', 'Veterinary service.', 2000.00
  UNION ALL SELECT 'Catheterization large', 'Veterinary Services', 'Veterinary service.', 2500.00
  UNION ALL SELECT 'Cherry eye removal', 'Veterinary Services', 'Veterinary service.', 3000.00
  UNION ALL SELECT 'Colopexy', 'Veterinary Services', 'Veterinary service.', 5000.00
  UNION ALL SELECT 'CS small/cat', 'Veterinary Services', 'Veterinary service.', 8500.00
  UNION ALL SELECT 'CS small breeds', 'Veterinary Services', 'Veterinary service.', 10000.00
  UNION ALL SELECT 'CS large', 'Veterinary Services', 'Veterinary service.', 13000.00
  UNION ALL SELECT 'Cystotomy', 'Veterinary Services', 'Veterinary service.', 5000.00
  UNION ALL SELECT 'Cyst removal', 'Veterinary Services', 'Veterinary service.', 5500.00
  UNION ALL SELECT 'Ear hematoma', 'Veterinary Services', 'Veterinary service.', 4500.00
  UNION ALL SELECT 'Euthanasia', 'Veterinary Services', 'Veterinary service.', 500.00
  UNION ALL SELECT 'Eyeball enucleation', 'Veterinary Services', 'Veterinary service.', 5000.00
  UNION ALL SELECT 'Hernia correction S-M', 'Veterinary Services', 'Veterinary service.', 7000.00
  UNION ALL SELECT 'Hernia correction S', 'Veterinary Services', 'Veterinary service.', 3000.00
  UNION ALL SELECT 'Spay cat', 'Veterinary Services', 'Veterinary service.', 2500.00
  UNION ALL SELECT 'Spay small', 'Veterinary Services', 'Veterinary service.', 3500.00
  UNION ALL SELECT 'Spay medium', 'Veterinary Services', 'Veterinary service.', 4000.00
  UNION ALL SELECT 'Spay large', 'Veterinary Services', 'Veterinary service.', 6000.00
  UNION ALL SELECT 'Tail docking', 'Veterinary Services', 'Veterinary service.', 100.00
  UNION ALL SELECT 'Tarsorraphy', 'Veterinary Services', 'Veterinary service.', 2500.00
  UNION ALL SELECT 'Whelping assistance', 'Veterinary Services', 'Veterinary service.', 4500.00
  UNION ALL SELECT 'Wound repair small', 'Veterinary Services', 'Veterinary service.', 1500.00
  UNION ALL SELECT 'Wound repair medium', 'Veterinary Services', 'Veterinary service.', 3000.00
  UNION ALL SELECT 'Wound repair large', 'Veterinary Services', 'Veterinary service.', 4500.00
  UNION ALL SELECT 'SDS', 'Veterinary Services', 'Veterinary service.', 300.00
  UNION ALL SELECT 'Med certificate', 'Veterinary Services', 'Veterinary service.', 650.00
  UNION ALL SELECT 'Oxygen', 'Veterinary Services', 'Veterinary service.', 500.00
  UNION ALL SELECT 'Zoletil', 'Veterinary Services', 'Veterinary service.', 0.00
  UNION ALL SELECT 'Nacalvit', 'Veterinary Services', 'Veterinary service.', 250.00
  UNION ALL SELECT 'Mastectomy', 'Veterinary Services', 'Veterinary service.', 8000.00
) AS seed
WHERE NOT EXISTS (
  SELECT 1 FROM services existing
  WHERE existing.name = seed.name AND existing.category = seed.category
);

INSERT IGNORE INTO inventory (sku, name, category, quantity, minimum_quantity, unit_price) VALUES
('DOCX-0001', 'Doxy Tablet Papi', 'Tablets/Capsule', 12, 5, 25.00),
('DOCX-0002', 'Enroxa', 'Tablets/Capsule', 28, 5, 20.00),
('DOCX-0003', 'Ketoconazole Tablet', 'Tablets/Capsule', 9, 5, 60.00),
('DOCX-0004', 'LIV52', 'Tablets/Capsule', 34, 5, 25.00),
('DOCX-0005', 'Nefrotec Tab', 'Tablets/Capsule', 17, 5, 25.00),
('DOCX-0006', 'Prednisone Tab', 'Tablets/Capsule', 21, 5, 10.00),
('DOCX-0007', 'Tramadol', 'Tablets/Capsule', 7, 5, 25.00),
('DOCX-0008', 'Albendazole**', 'Tablets/Capsule', 31, 5, 0.00),
('DOCX-0009', 'Proxantel***', 'Tablets/Capsule', 14, 5, 0.00),
('DOCX-0010', 'Canine wormer', 'Tablets/Capsule', 25, 5, 0.00),
('DOCX-0011', 'PETCLAV', 'Tablets/Capsule', 11, 5, 35.00),
('DOCX-0012', 'Allercare', 'Syrup', 24, 5, 400.00),
('DOCX-0013', 'Betaceph', 'Syrup', 8, 5, 475.00),
('DOCX-0014', 'Metoclop Care', 'Syrup', 33, 5, 300.00),
('DOCX-0015', 'Doxytic', 'Syrup', 16, 5, 600.00),
('DOCX-0016', 'Emervit', 'Syrup', 19, 5, 250.00),
('DOCX-0017', 'Emerplex', 'Syrup', 27, 5, 300.00),
('DOCX-0018', 'Enmalac', 'Syrup', 10, 5, 300.00),
('DOCX-0019', 'Ferro C', 'Syrup', 36, 5, 350.00),
('DOCX-0020', 'Hemapet', 'Syrup', 13, 5, 550.00),
('DOCX-0021', 'Hepatosure', 'Syrup', 22, 5, 400.00),
('DOCX-0022', 'Iron', 'Syrup', 6, 5, 300.00),
('DOCX-0023', 'LCVIT', 'Syrup', 29, 5, 250.00),
('DOCX-0024', 'Livotine', 'Syrup', 18, 5, 395.00),
('DOCX-0025', 'Metrocare', 'Syrup', 32, 5, 400.00),
('DOCX-0026', 'Microxin', 'Syrup', 15, 5, 440.00),
('DOCX-0027', 'Mucotan', 'Syrup', 26, 5, 450.00),
('DOCX-0028', 'MVP', 'Syrup', 12, 5, 250.00),
('DOCX-0029', 'Nacalvit', 'Syrup', 30, 5, 250.00),
('DOCX-0030', 'NeproCare', 'Syrup', 20, 5, 550.00),
('DOCX-0031', 'Nerveplex', 'Syrup', 9, 5, 650.00),
('DOCX-0032', 'Nutrical', 'Syrup', 35, 5, 275.00),
('DOCX-0033', 'OB', 'Syrup', 14, 5, 300.00),
('DOCX-0034', 'Pet-Clav', 'Syrup', 23, 5, 400.00),
('DOCX-0035', 'Prozoa', 'Syrup', 17, 5, 400.00),
('DOCX-0036', 'Redipenia', 'Syrup', 28, 5, 700.00),
('DOCX-0037', 'SC derma', 'Syrup', 11, 5, 550.00),
('DOCX-0038', 'Sulpyrine', 'Syrup', 31, 5, 400.00),
('DOCX-0039', 'Tolfenol', 'Syrup', 7, 5, 350.00),
('DOCX-0040', 'Tritozine', 'Syrup', 25, 5, 400.00),
('DOCX-0041', 'Doctor Pooch Dewormer', 'Syrup', 16, 5, 0.00),
('DOCX-0042', 'Vernifuge/Pirantel', 'Syrup', 21, 5, 0.00),
('DOCX-0043', 'Dextrose Powder 100g', 'Other Treatments', 19, 5, 100.00),
('DOCX-0044', 'Dextrose Powder 300g', 'Other Treatments', 8, 5, 180.00),
('DOCX-0045', 'Frontline 10â€“20', 'Other Treatments', 33, 5, 550.00),
('DOCX-0046', 'Gentamicin Sulfate', 'Other Treatments', 15, 5, 565.00),
('DOCX-0047', 'Iozin', 'Other Treatments', 27, 5, 275.00),
('DOCX-0048', 'Nexgard Combo Small', 'Other Treatments', 11, 5, 700.00),
('DOCX-0049', 'Nexgard Combo Large', 'Other Treatments', 24, 5, 800.00),
('DOCX-0050', 'Nexgard (S) 2kgâ€“4kg', 'Other Treatments', 18, 5, 700.00),
('DOCX-0051', 'Nexgard (M) 4kgâ€“10kg', 'Other Treatments', 6, 5, 750.00),
('DOCX-0052', 'Nexgard (L) 10kgâ€“25kg', 'Other Treatments', 31, 5, 950.00),
('DOCX-0053', 'Nexgard (XL) 25kgâ€“50kg', 'Other Treatments', 13, 5, 1000.00),
('DOCX-0054', 'Nexgard Spectra (XS) 2â€“3.5kg', 'Other Treatments', 22, 5, 900.00),
('DOCX-0055', 'Nexgard Spectra (S) 3.5â€“7.5kg', 'Other Treatments', 10, 5, 950.00),
('DOCX-0056', 'Nexgard Spectra (M) 7.5â€“15kg', 'Other Treatments', 36, 5, 1000.00),
('DOCX-0057', 'Nexgard Spectra (L) 15â€“30kg', 'Other Treatments', 14, 5, 1100.00),
('DOCX-0058', 'Optacure', 'Other Treatments', 25, 5, 390.00),
('DOCX-0059', 'Otiderm', 'Other Treatments', 9, 5, 665.00),
('DOCX-0060', 'Probio Care', 'Other Treatments', 30, 5, 350.00),
('DOCX-0061', 'Tympa Pet', 'Other Treatments', 17, 5, 390.00),
('DOCX-0062', 'Venoma Spray', 'Other Treatments', 28, 5, 400.00),
('DOCX-0063', 'Vetnoderm Cream', 'Other Treatments', 12, 5, 300.00),
('DOCX-0064', 'CCV Testkit', 'Other Treatments', 23, 5, 850.00),
('DOCX-0065', 'CDV/CPV Testkit', 'Other Treatments', 7, 5, 1500.00),
('DOCX-0066', 'CPV/GIA/CCV Testkit', 'Other Treatments', 34, 5, 1500.00),
('DOCX-0067', 'EHRL/ANA/BAB/HW Testkit', 'Other Treatments', 16, 5, 1800.00),
('DOCX-0068', 'CPV/CDV/EHR Testkit', 'Other Treatments', 20, 5, 1600.00),
('DOCX-0069', 'AOZI Dog Can', 'Dog Food and Treats', 26, 5, 150.00),
('DOCX-0070', 'Chewbone Small', 'Dog Food and Treats', 11, 5, 15.00),
('DOCX-0071', 'Chewbone Medium', 'Dog Food and Treats', 32, 5, 30.00),
('DOCX-0072', 'Chewbone Large', 'Dog Food and Treats', 18, 5, 85.00),
('DOCX-0073', 'Chewbone XL', 'Dog Food and Treats', 7, 5, 125.00),
('DOCX-0074', 'Dental Chews', 'Dog Food and Treats', 29, 5, 15.00),
('DOCX-0075', 'Doggie/Milky Biscuit 70g', 'Dog Food and Treats', 14, 5, 100.00),
('DOCX-0076', 'Doggie Biscuit 200g', 'Dog Food and Treats', 21, 5, 250.00),
('DOCX-0077', 'Goatâ€™s Milk', 'Dog Food and Treats', 10, 5, 275.00),
('DOCX-0078', 'AOZI Pouch Dog', 'Dog Food and Treats', 35, 5, 65.00),
('DOCX-0079', 'Topbreed Wet Food', 'Dog Food and Treats', 17, 5, 60.00),
('DOCX-0080', 'Topbreed Treats', 'Dog Food and Treats', 24, 5, 80.00),
('DOCX-0081', 'General Cologne', 'Grooming Products', 12, 5, 150.00),
('DOCX-0082', 'General Pet Soap (Red)', 'Grooming Products', 30, 5, 160.00),
('DOCX-0083', 'General Pet Soap (Yellow)', 'Grooming Products', 9, 5, 150.00),
('DOCX-0084', 'Royal Tail Cologne', 'Grooming Products', 22, 5, 300.00),
('DOCX-0085', 'Royal Tail Soap', 'Grooming Products', 15, 5, 150.00),
('DOCX-0086', 'Royal Tail 200ml', 'Grooming Products', 33, 5, 175.00),
('DOCX-0087', 'Royal Tail 500ml', 'Grooming Products', 18, 5, 300.00),
('DOCX-0088', 'Royal Tail 1L', 'Grooming Products', 6, 5, 550.00),
('DOCX-0089', 'Royal Tail 1GAL', 'Grooming Products', 27, 5, 1300.00),
('DOCX-0090', 'Ketoconazole Shampoo', 'Grooming Products', 13, 5, 300.00),
('DOCX-0091', 'Papi Pet Powder', 'Grooming Products', 31, 5, 275.00),
('DOCX-0092', 'Vetnoderm Soap', 'Grooming Products', 20, 5, 180.00),
('DOCX-0093', 'Bed Medium', 'Accessories', 8, 5, 550.00),
('DOCX-0094', 'Bed Small', 'Accessories', 25, 5, 450.00),
('DOCX-0095', 'Cage 1', 'Accessories', 14, 5, 1250.00),
('DOCX-0096', 'Cage 2', 'Accessories', 32, 5, 1850.00),
('DOCX-0097', 'Cage 3', 'Accessories', 11, 5, 2500.00),
('DOCX-0098', 'Cage 4', 'Accessories', 19, 5, 2800.00),
('DOCX-0099', 'Cage Coated Gray Small', 'Accessories', 7, 5, 1700.00),
('DOCX-0100', 'Michiko Nylon (M)', 'Accessories', 28, 5, 95.00),
('DOCX-0101', 'Michiko Pattern (L)', 'Accessories', 16, 5, 150.00),
('DOCX-0102', 'Michiko Pattern (S)', 'Accessories', 23, 5, 110.00),
('DOCX-0103', 'Charles Pet Collar S', 'Accessories', 12, 5, 65.00),
('DOCX-0104', 'Charles Pet Collar M', 'Accessories', 35, 5, 85.00),
('DOCX-0105', 'Charles Pet Collar L', 'Accessories', 9, 5, 115.00),
('DOCX-0106', 'Charles Pet Collar XL', 'Accessories', 21, 5, 135.00),
('DOCX-0107', 'E-Collar #1', 'Accessories', 17, 5, 350.00),
('DOCX-0108', 'E-Collar #2', 'Accessories', 30, 5, 250.00),
('DOCX-0109', 'E-Collar #3', 'Accessories', 6, 5, 230.00),
('DOCX-0110', 'E-Collar #4', 'Accessories', 26, 5, 210.00),
('DOCX-0111', 'E-Collar #5', 'Accessories', 14, 5, 185.00),
('DOCX-0112', 'E-Collar #6', 'Accessories', 33, 5, 175.00),
('DOCX-0113', 'E-Collar #7', 'Accessories', 10, 5, 130.00),
('DOCX-0114', 'Female Diaper XXS', 'Accessories', 22, 5, 20.00),
('DOCX-0115', 'Female Diaper XS', 'Accessories', 11, 5, 20.00),
('DOCX-0116', 'Female Diaper S', 'Accessories', 29, 5, 25.00),
('DOCX-0117', 'Female Diaper M', 'Accessories', 8, 5, 30.00),
('DOCX-0118', 'Female Diaper L', 'Accessories', 24, 5, 35.00),
('DOCX-0119', 'Female Diaper XL', 'Accessories', 16, 5, 40.00),
('DOCX-0120', 'Female Diaper XXL', 'Accessories', 31, 5, 45.00),
('DOCX-0121', 'Male Diaper XS', 'Accessories', 13, 5, 20.00),
('DOCX-0122', 'Male Diaper S', 'Accessories', 27, 5, 25.00),
('DOCX-0123', 'Male Diaper M', 'Accessories', 18, 5, 30.00),
('DOCX-0124', 'Male Diaper L', 'Accessories', 7, 5, 35.00),
('DOCX-0125', 'Male Diaper XL', 'Accessories', 34, 5, 40.00),
('DOCX-0126', 'Male Diaper XXL', 'Accessories', 20, 5, 45.00),
('DOCX-0127', 'Plastic Double L', 'Accessories', 15, 5, 200.00),
('DOCX-0128', 'Plastic Double M', 'Accessories', 28, 5, 150.00),
('DOCX-0129', 'Square S', 'Accessories', 9, 5, 125.00),
('DOCX-0130', 'Stainless Colored S', 'Accessories', 23, 5, 125.00),
('DOCX-0131', 'Stainless Colored M', 'Accessories', 12, 5, 175.00),
('DOCX-0132', 'Stainless Colored L', 'Accessories', 36, 5, 225.00),
('DOCX-0133', 'Plastic M', 'Accessories', 17, 5, 75.00),
('DOCX-0134', 'Plastic L', 'Accessories', 6, 5, 100.00),
('DOCX-0135', 'Water Feeder', 'Accessories', 25, 5, 275.00),
('DOCX-0136', 'Leash & Harness S', 'Accessories', 19, 5, 145.00),
('DOCX-0137', 'Leash & Harness M', 'Accessories', 32, 5, 195.00),
('DOCX-0138', 'Leash & Harness L', 'Accessories', 11, 5, 245.00),
('DOCX-0139', 'Chocker S', 'Accessories', 27, 5, 65.00),
('DOCX-0140', 'Chocker M', 'Accessories', 14, 5, 95.00),
('DOCX-0141', 'Chocker L', 'Accessories', 21, 5, 125.00),
('DOCX-0142', 'Muzzle S', 'Accessories', 8, 5, 75.00),
('DOCX-0143', 'Muzzle M', 'Accessories', 33, 5, 100.00),
('DOCX-0144', 'Muzzle Nylon M', 'Accessories', 16, 5, 200.00),
('DOCX-0145', 'Cat Litter', 'Accessories', 24, 5, 450.00),
('DOCX-0146', 'Comb Small', 'Accessories', 10, 5, 275.00),
('DOCX-0147', 'Comb Rubber Handle', 'Accessories', 29, 5, 250.00),
('DOCX-0148', 'Comb Transparent Handle', 'Accessories', 18, 5, 150.00),
('DOCX-0149', 'Toothbrush', 'Accessories', 7, 5, 50.00),
('DOCX-0150', 'Toy Dental Michiko', 'Accessories', 35, 5, 300.00),
('DOCX-0151', 'Cat Toy Shark', 'Accessories', 12, 5, 125.00),
('DOCX-0152', 'Curve Toy Squeaky', 'Accessories', 26, 5, 150.00),
('DOCX-0153', 'Magic Wand', 'Accessories', 15, 5, 150.00),
('DOCX-0154', 'Rope Toy', 'Accessories', 31, 5, 200.00),
('DOCX-0155', 'Rubber Bone w/Spike Small', 'Accessories', 9, 5, 250.00),
('DOCX-0156', 'Rubber Bone w/Spike XSmall', 'Accessories', 22, 5, 200.00),
('DOCX-0157', 'Rubber Bone w/Spike Big', 'Accessories', 17, 5, 195.00),
('DOCX-0158', '3-Ring Rubber Toy', 'Accessories', 28, 5, 175.00),
('DOCX-0159', 'Dumbbell Flat Toy', 'Accessories', 6, 5, 150.00),
('DOCX-0160', 'Dono Pad Small/Pack', 'Accessories', 23, 5, 750.00),
('DOCX-0161', 'Nursing Kit Small 60ml', 'Accessories', 13, 5, 150.00),
('DOCX-0162', 'Nursing Kit Big 150ml', 'Accessories', 30, 5, 200.00),
('DOCX-0163', 'Travel Bottle S', 'Accessories', 19, 5, 95.00),
('DOCX-0164', 'Travel Bottle L', 'Accessories', 25, 5, 175.00),
('DOCX-0165', 'Travel Bottle Pink&Blue', 'Accessories', 11, 5, 195.00);

UPDATE services
SET category = 'Grooming Services'
WHERE category LIKE 'Grooming Services - %';

UPDATE inventory
SET category = CASE
  WHEN category LIKE 'Grooming Products - %' THEN 'Grooming Products'
  WHEN category LIKE 'Accessories - %' THEN 'Accessories'
  ELSE category
END
WHERE sku LIKE 'DOCX-%';

INSERT IGNORE INTO catalog_categories (category_type, name)
SELECT 'service', category FROM services GROUP BY category;

INSERT IGNORE INTO catalog_categories (category_type, name)
SELECT 'inventory', category FROM inventory GROUP BY category;

INSERT IGNORE INTO catalog_categories (category_type, name)
VALUES ('inventory', 'Grooming Services');

