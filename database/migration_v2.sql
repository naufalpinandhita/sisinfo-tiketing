-- =====================================================
-- Migration V2: Add deskripsi, jam to event
--               Add nama_attendee to attendee
-- Run this once against the event_tiket database
-- =====================================================

USE event_tiket;

-- Add description and time to event
ALTER TABLE event
    ADD COLUMN IF NOT EXISTS deskripsi TEXT NULL AFTER poster_url,
    ADD COLUMN IF NOT EXISTS jam TIME NULL AFTER tanggal;

-- Add attendee name to attendee
ALTER TABLE attendee
    ADD COLUMN IF NOT EXISTS nama_attendee VARCHAR(100) NULL AFTER id_detail;

-- =====================================================
-- If ADD COLUMN IF NOT EXISTS is not supported (MySQL < 8),
-- use this instead (run only if column doesn't exist):
-- ALTER TABLE event ADD COLUMN deskripsi TEXT NULL;
-- ALTER TABLE event ADD COLUMN jam TIME NULL;
-- ALTER TABLE attendee ADD COLUMN nama_attendee VARCHAR(100) NULL;
-- =====================================================
