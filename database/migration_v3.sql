-- =====================================================
-- Migration V3: Payment Gateway (Manual Verification)
-- =====================================================

USE event_tiket;

-- 1. Alter orders table
ALTER TABLE orders
    ADD COLUMN invoice_code VARCHAR(30) UNIQUE NULL AFTER id_user,
    ADD COLUMN expired_at   DATETIME NULL AFTER status;

-- Update existing paid orders to have invoice_code
UPDATE orders SET invoice_code = CONCAT('INV-', DATE_FORMAT(tanggal_order, '%Y%m%d'), '-', LPAD(id_order, 5, '0')) WHERE invoice_code IS NULL;

-- 2. Create payment_confirmation table
CREATE TABLE IF NOT EXISTS payment_confirmation (
    id_confirmation INT PRIMARY KEY AUTO_INCREMENT,
    id_order        INT NOT NULL UNIQUE,
    sender_name     VARCHAR(100) NOT NULL,
    bank_name       VARCHAR(50) NOT NULL,
    notes           TEXT NULL,
    payment_proof   VARCHAR(255) NOT NULL,
    status          ENUM('pending','approved','rejected') DEFAULT 'pending',
    reject_reason   TEXT NULL,
    confirmed_by    INT NULL,
    confirmed_at    DATETIME NULL,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_order) REFERENCES orders(id_order) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (confirmed_by) REFERENCES users(id_user) ON DELETE SET NULL ON UPDATE CASCADE
);

-- 3. Update existing statuses before altering ENUM
UPDATE orders SET status = 'pending_payment' WHERE status = 'pending';
UPDATE orders SET status = 'expired'         WHERE status = 'cancel';

-- 4. Rebuild ENUM (MySQL requires MODIFY COLUMN)
ALTER TABLE orders
    MODIFY COLUMN status ENUM('pending_payment','waiting_confirmation','paid','rejected','expired') NOT NULL DEFAULT 'pending_payment';
