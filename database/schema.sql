-- EpicEvents ICT726 Assessment 4 - MySQL 8.0+
-- Import this file once. It creates the schema, relationships and demonstration data.
CREATE DATABASE IF NOT EXISTS epicevents CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE epicevents;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS enquiries;
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS events;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin','member') NOT NULL DEFAULT 'member',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_role_active (role, is_active)
) ENGINE=InnoDB;

CREATE TABLE events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(160) NOT NULL,
    category VARCHAR(60) NOT NULL,
    event_date DATE NOT NULL,
    start_time TIME NOT NULL,
    location VARCHAR(160) NOT NULL,
    capacity INT UNSIGNED NOT NULL,
    price DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0.00,
    image_url VARCHAR(500) NOT NULL,
    description TEXT NOT NULL,
    status ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_events_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT chk_events_capacity CHECK (capacity > 0),
    CONSTRAINT chk_events_price CHECK (price >= 0),
    INDEX idx_events_catalog (status, event_date, category),
    FULLTEXT INDEX ftx_events_content (title, description, location)
) ENGINE=InnoDB;

CREATE TABLE bookings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    event_id BIGINT UNSIGNED NOT NULL,
    booking_reference CHAR(11) NOT NULL UNIQUE,
    quantity TINYINT UNSIGNED NOT NULL,
    accessibility_notes VARCHAR(500) NULL,
    status ENUM('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_bookings_quantity CHECK (quantity BETWEEN 1 AND 10),
    CONSTRAINT fk_bookings_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_bookings_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE RESTRICT,
    INDEX idx_bookings_user (user_id, created_at),
    INDEX idx_bookings_event_status (event_id, status)
) ENGINE=InnoDB;

CREATE TABLE enquiries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    name VARCHAR(80) NOT NULL,
    email VARCHAR(190) NOT NULL,
    subject VARCHAR(120) NOT NULL,
    message TEXT NOT NULL,
    ip_hash CHAR(64) NOT NULL,
    status ENUM('new','in_progress','resolved') NOT NULL DEFAULT 'new',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_enquiries_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_enquiries_status_date (status, created_at)
) ENGINE=InnoDB;

INSERT INTO events (title,category,event_date,start_time,location,capacity,price,image_url,description,status,created_by) VALUES
('Harbour Light Walk','Arts & Culture','2026-10-17','18:30','Barangaroo Reserve, Sydney',350,24.00,'https://images.unsplash.com/photo-1516450360452-9312f5e86fc7?auto=format&fit=crop&w=1200&q=80','Follow an illuminated waterfront trail featuring independent NSW artists, live projection works and relaxed food stations. The route includes step-free access, rest points and clearly marked quiet viewing areas.','published',NULL),
('Hunter Harvest Table','Food & Wine','2026-11-07','12:00','Pokolbin Community Grounds, Hunter Valley',180,89.00,'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?auto=format&fit=crop&w=1200&q=80','Share a long-table lunch celebrating Hunter growers, winemakers and chefs. Your ticket includes seasonal courses, alcohol-free pairings and producer conversations in a shaded outdoor setting.','published',NULL),
('Coastal Roots Live','Music','2026-11-21','15:00','North Byron Parklands, Byron Bay',1200,72.50,'https://images.unsplash.com/photo-1506157786151-b8491531f063?auto=format&fit=crop&w=1200&q=80','An afternoon-to-evening program of Australian roots, soul and contemporary acts across two accessible stages, with local food vendors, water refill points and a dedicated low-sensory zone.','published',NULL),
('Future Work NSW Forum','Business','2026-12-03','09:00','International Convention Centre, Sydney',500,145.00,'https://images.unsplash.com/photo-1540575467063-178a50c2df87?auto=format&fit=crop&w=1200&q=80','A practical one-day forum for NSW leaders exploring responsible AI, adaptable teams and inclusive workplaces through keynotes, small workshops and structured networking sessions.','published',NULL),
('Blue Mountains Family Discovery Day','Community','2026-12-12','10:00','Wentworth Falls Picnic Area, Blue Mountains',220,0.00,'https://images.unsplash.com/photo-1500534314209-a25ddb2bd4297?auto=format&fit=crop&w=1200&q=80','A free family program of guided nature walks, creative workshops and local storytelling. Sessions include accessible options and advance information about terrain, distance and available support.','published',NULL);
