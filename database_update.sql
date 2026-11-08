-- ============================================================
-- Update database untuk fitur Dashboard, Kategori, dan Laporan
-- Jalankan sekali di phpMyAdmin / MySQL:
--   mysql -u root stokbarang < database_update.sql
-- ============================================================

USE stokbarang;

-- Tabel kategori barang
CREATE TABLE IF NOT EXISTS kategori (
    idkategori INT(11) NOT NULL AUTO_INCREMENT,
    namakategori VARCHAR(50) NOT NULL,
    PRIMARY KEY (idkategori)
) ENGINE=InnoDB;

-- Relasi barang ke kategori + batas minimum stok
ALTER TABLE stok
    ADD COLUMN IF NOT EXISTS idkategori INT(11) NULL DEFAULT NULL AFTER deskripsi;

ALTER TABLE stok
    ADD COLUMN IF NOT EXISTS stok_minimum INT(11) NOT NULL DEFAULT 0 AFTER stock;
