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
    role        ENUM('user', 'petugas', 'admin') DEFAULT 'user'
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
    kode_tiket      VARCHAR(50) NOT NULL,
    status_checkin  ENUM('belum', 'sudah') DEFAULT 'belum',
    waktu_checkin   DATETIME NULL,
    FOREIGN KEY (id_detail) REFERENCES order_detail(id_detail)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);
