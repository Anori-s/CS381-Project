CREATE DATABASE IF NOT EXISTS yic_lost_found;
USE yic_lost_found;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'admin') DEFAULT 'student',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Lost items 
CREATE TABLE lost_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(120) NOT NULL,
    category VARCHAR(50) NOT NULL,
    location VARCHAR(120) NOT NULL,
    date_lost DATE NOT NULL,
    description TEXT NOT NULL,
    contact VARCHAR(120) NOT NULL,
    reward VARCHAR(80) DEFAULT '',
    image_path VARCHAR(255) DEFAULT '',
    status VARCHAR(20) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Found items 
CREATE TABLE found_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(120) NOT NULL,
    category VARCHAR(50) NOT NULL,
    location VARCHAR(120) NOT NULL,
    date_found DATE NOT NULL,
    description TEXT NOT NULL,
    contact VARCHAR(120) NOT NULL,
    held_at VARCHAR(50) DEFAULT 'self',
    image_path VARCHAR(255) DEFAULT '',
    status VARCHAR(20) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Messages table
CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    item_type VARCHAR(10) NOT NULL,
    sender_name VARCHAR(120) NOT NULL,
    sender_email VARCHAR(120) NOT NULL,
    body TEXT NOT NULL,
    is_read TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
-- password is password
INSERT INTO users (name, email, password, role) VALUES
('Norah AlHosain',  '4311085@rcjy.edu.sa', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
('Mariah AlHarbi',  '4311346@rcjy.edu.sa', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
('Admin User',      'admin@rcjy.edu.sa',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Sara AlQahtani',  '4312001@rcjy.edu.sa', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
('Lama AlZahrani',  '4312050@rcjy.edu.sa', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student');

INSERT INTO lost_items (user_id, title, category, location, date_lost, description, contact, reward, status) VALUES
(1, 'iPhone 15 Blue',        'electronics', 'Art Club',           '2026-04-12', 'Blue iPhone 15 with white silicone case. Cracked corner on the top-right.', '4311085@rcjy.edu.sa', '200 SAR', 'active'),
(2, 'Samsung Earbuds',       'electronics', 'Gym',                '2026-01-14', 'Black Galaxy Buds with a pink charging case. Name written inside the lid.', '4311346@rcjy.edu.sa', '',        'active'),
(1, 'Toyota Car Key',        'keys',        'Parking Lot',        '2026-03-13', 'Toyota smart key with a small carabiner and a red lanyard attached.',      '4311085@rcjy.edu.sa', '50 SAR',  'active'),
(2, 'Apple USB-C Charger',   'electronics', 'Lab B1-03',          '2026-01-15', 'White Apple 20W USB-C charger. No cable, just the power brick.',            '4311346@rcjy.edu.sa', '',        'resolved'),
(1, 'Grey Hoodie',           'clothing',    'Building A (Staff)', '2026-02-11', 'Grey hoodie size Large with a small coffee stain on the left sleeve.',      '4311085@rcjy.edu.sa', '',        'active'),
(4, 'ASUS VivoBook Laptop',  'electronics', 'Classes B0-123',     '2026-04-14', 'Silver ASUS VivoBook 15 with a YIC sticker on the back.',                   '4312001@rcjy.edu.sa', '100 SAR', 'active'),
(5, 'Student ID Card',       'accessories', 'Cafeteria',          '2026-05-01', 'YIC student ID card. Photo visible on front.',                              '4312050@rcjy.edu.sa', '',        'active'),
(2, 'Calculus Textbook',     'books',       'Library',            '2026-04-28', 'Calculus textbook with red cover. Name written on the first page.',          '4311346@rcjy.edu.sa', '',        'active'),
(4, 'Black Nike Backpack',   'bags',        'Gate 2 Entrance',    '2026-05-02', 'Black Nike backpack with a small lock on the zipper. Laptop inside.',       '4312001@rcjy.edu.sa', '150 SAR', 'active'),
(1, 'AirPods Pro',           'electronics', 'Library Study Room', '2026-05-05', 'AirPods Pro in white case. Name "Norah" engraved inside the lid.',          '4311085@rcjy.edu.sa', '80 SAR',  'active');

INSERT INTO found_items (user_id, title, category, location, date_found, description, contact, held_at, status) VALUES
(2, 'iPhone 13',         'electronics', 'Cafeteria',        '2026-01-14', 'White iPhone 13 with a clear case. Screen is slightly cracked.',              '4311346@rcjy.edu.sa', 'self',     'active'),
(1, 'Hyundai Car Keys',  'keys',        'Student Entrance', '2026-01-13', 'Hyundai key with a red lanyard charm and a small flashlight attached.',       '4311085@rcjy.edu.sa', 'security', 'active'),
(2, 'Samsung Charger',   'electronics', 'Lab B1-032',       '2026-03-12', 'White Samsung 25W charger adapter, no cable.',                                '4311346@rcjy.edu.sa', 'dept',     'resolved'),
(1, 'Apple Watch',       'accessories', 'Library',          '2026-02-15', 'Silver Apple Watch Series 8, white sport band, no scratches.',                '4311085@rcjy.edu.sa', 'self',     'active'),
(5, 'Green Water Bottle','other',       'Gym',              '2026-04-20', 'Green Hydro Flask with stickers on it. Name tag says Sara.',                  '4312050@rcjy.edu.sa', 'self',     'active'),
(4, 'USB Flash Drive',   'electronics', 'Computer Lab A',   '2026-04-25', '32GB SanDisk flash drive in a blue case. Contains study files.',              '4312001@rcjy.edu.sa', 'dept',     'active'),
(2, 'Reading Glasses',   'accessories', 'Library',          '2026-05-01', 'Black-framed reading glasses in a brown leather case.',                        '4311346@rcjy.edu.sa', 'security', 'active'),
(5, 'Prayer Beads',      'accessories', 'Mosque',           '2026-05-03', 'Brown wooden prayer beads with a silver tassel.',                              '4312050@rcjy.edu.sa', 'self',     'active'),
(1, 'Dell Laptop Charger','electronics','Room D2-15',       '2026-05-06', 'Dell laptop charger, black, 65W. Tip is slightly bent.',                      '4311085@rcjy.edu.sa', 'self',     'active'),
(4, 'Dark Blue Scarf',   'clothing',    'Cafeteria',        '2026-05-07', 'Dark blue chiffon scarf left on a chair near the window.',                    '4312001@rcjy.edu.sa', 'self',     'active');

INSERT INTO messages (item_id, item_type, sender_name, sender_email, body, is_read) VALUES
(1, 'lost',  'Mariah AlHarbi', '4311346@rcjy.edu.sa', 'I think I saw this phone near the Art Club entrance. Please contact me.', 1),
(1, 'found', 'Sara AlQahtani', '4312001@rcjy.edu.sa', 'I found an iPhone near the cafeteria. Is this yours? The case matches your description.', 0),
(3, 'lost',  'Lama AlZahrani', '4312050@rcjy.edu.sa', 'I found a Toyota key in the parking lot last week. Could be yours!', 0);
