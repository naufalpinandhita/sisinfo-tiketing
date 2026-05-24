-- =====================================================
-- Database: event_tiket
-- Description: Complete DDL for Event Ticketing System
-- =====================================================

CREATE DATABASE IF NOT EXISTS event_tiket;
USE event_tiket;

-- -----------------------------------------------------
-- Table: users
-- -----------------------------------------------------
CREATE TABLE users (
    id_user     INT PRIMARY KEY AUTO_INCREMENT,
    nama        VARCHAR(100) NOT NULL,
    email       VARCHAR(100) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    role        ENUM('user', 'admin') DEFAULT 'user'
);

-- -----------------------------------------------------
-- Table: venue
-- -----------------------------------------------------
CREATE TABLE venue (
    id_venue    INT PRIMARY KEY AUTO_INCREMENT,
    nama_venue  VARCHAR(100) NOT NULL,
    alamat      TEXT,
    kapasitas   INT
);

-- -----------------------------------------------------
-- Table: event
-- -----------------------------------------------------
CREATE TABLE event (
    id_event    INT PRIMARY KEY AUTO_INCREMENT,
    nama_event  VARCHAR(150) NOT NULL,
    tanggal     DATE NOT NULL,
    poster_url  VARCHAR(255),
    id_venue    INT NOT NULL,
    FOREIGN KEY (id_venue) REFERENCES venue(id_venue)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

-- -----------------------------------------------------
-- Table: tiket
-- -----------------------------------------------------
CREATE TABLE tiket (
    id_tiket    INT PRIMARY KEY AUTO_INCREMENT,
    id_event    INT NOT NULL,
    nama_tiket  VARCHAR(50) NOT NULL,
    harga       INT NOT NULL,
    kuota       INT NOT NULL,
    FOREIGN KEY (id_event) REFERENCES event(id_event)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

-- -----------------------------------------------------
-- Table: voucher
-- -----------------------------------------------------
CREATE TABLE voucher (
    id_voucher      INT PRIMARY KEY AUTO_INCREMENT,
    kode_voucher    VARCHAR(20) NOT NULL UNIQUE,
    potongan        INT NOT NULL,
    kuota           INT NOT NULL,
    status          ENUM('aktif', 'nonaktif') DEFAULT 'aktif'
);

-- -----------------------------------------------------
-- Table: orders
-- -----------------------------------------------------
CREATE TABLE orders (
    id_order        INT PRIMARY KEY AUTO_INCREMENT,
    id_user         INT NOT NULL,
    tanggal_order   DATETIME DEFAULT CURRENT_TIMESTAMP,
    total           INT NOT NULL,
    status          ENUM('pending', 'paid', 'cancel') DEFAULT 'pending',
    id_voucher      INT NULL,
    FOREIGN KEY (id_user) REFERENCES users(id_user)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    FOREIGN KEY (id_voucher) REFERENCES voucher(id_voucher)
        ON DELETE SET NULL
        ON UPDATE CASCADE
);

-- -----------------------------------------------------
-- Table: order_detail
-- -----------------------------------------------------
CREATE TABLE order_detail (
    id_detail   INT PRIMARY KEY AUTO_INCREMENT,
    id_order    INT NOT NULL,
    id_tiket    INT NOT NULL,
    qty         INT NOT NULL,
    subtotal    INT NOT NULL,
    FOREIGN KEY (id_order) REFERENCES orders(id_order)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    FOREIGN KEY (id_tiket) REFERENCES tiket(id_tiket)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

-- -----------------------------------------------------
-- Table: attendee
-- -----------------------------------------------------
CREATE TABLE attendee (
    id_attendee     INT PRIMARY KEY AUTO_INCREMENT,
    id_detail       INT NOT NULL,
    kode_tiket      VARCHAR(50) NOT NULL UNIQUE,
    status_checkin  ENUM('belum', 'sudah') DEFAULT 'belum',
    waktu_checkin   DATETIME NULL,
    FOREIGN KEY (id_detail) REFERENCES order_detail(id_detail)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

-- =====================================================
-- SEED DATA untuk Testing Relasi (FASE 1)
-- =====================================================

INSERT INTO users (nama, email, password, role) VALUES
('Admin Utama', 'admin@event.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('User Biasa', 'user@event.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user');

INSERT INTO venue (nama_venue, alamat, kapasitas) VALUES
('Stadion Utama', 'Jl. Merdeka No. 1', 50000),
('Convention Hall A', 'Jl. Sudirman No. 45', 2000);

INSERT INTO event (nama_event, tanggal, poster_url, id_venue) VALUES
('Konser Musik 2025', '2025-12-25', 'posters/konser_2025.jpg', 1),
('Tech Conference', '2025-11-15', 'posters/tech_conf.jpg', 2);

INSERT INTO tiket (id_event, nama_tiket, harga, kuota) VALUES
(1, 'VIP', 500000, 100),
(1, 'Regular', 200000, 500),
(2, 'Early Bird', 150000, 50),
(2, 'Regular', 300000, 200);

INSERT INTO voucher (kode_voucher, potongan, kuota, status) VALUES
('DISKON50', 50000, 100, 'aktif'),
('EVENT2025', 25000, 50, 'aktif');

INSERT INTO orders (id_user, tanggal_order, total, status, id_voucher) VALUES
(2, NOW(), 450000, 'paid', 1);

INSERT INTO order_detail (id_order, id_tiket, qty, subtotal) VALUES
(1, 1, 1, 500000);

INSERT INTO attendee (id_detail, kode_tiket, status_checkin, waktu_checkin) VALUES
(1, 'EVT-20251225-A8K9L2P3', 'belum', NULL);

-- =====================================================
-- CATATAN TESTING CASCADE DELETE (FASE 1)
-- =====================================================
-- 1. Hapus order dengan id_order=1:
--    order_detail (id=1) harus ikut terhapus
--    attendee (id=1) harus ikut terhapus
--
-- 2. Hapus event dengan id_event=1:
--    tiket (id=1,2) harus ikut terhapus
--    order_detail terkait tiket tersebut harus ikut terhapus
--    attendee terkait harus ikut terhapus
--
-- 3. Hapus venue dengan id_venue=1:
--    event (id=1) harus ikut terhapus
--    kemudian cascade ke tiket, order_detail, attendee
--
-- 4. Hapus user dengan id_user=2:
--    orders (id=1) harus ikut terhapus
--    kemudian cascade ke order_detail, attendee
--
-- Jika ada error FK atau data orphan, FIX sebelum FASE 2.
-- =====================================================

