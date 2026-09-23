-- SözFişi güncelleme dosyası
-- Fresh kurulumda yalnızca database.sql çalıştırmanız yeterlidir.
-- Bu dosya, mevcut bir kuruluma güvenli/tekrarlanabilir küçük güncellemeler uygulamak için örnektir.

USE sozfisi;

CREATE TABLE IF NOT EXISTS schema_migrations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    migration VARCHAR(190) NOT NULL UNIQUE,
    applied_at DATETIME NOT NULL
) ENGINE=InnoDB;

-- İlk şema sürümünü kayıt altına alır. INSERT IGNORE sayesinde tekrar çalıştırılabilir.
INSERT IGNORE INTO schema_migrations (migration, applied_at)
VALUES ('001_initial_schema', NOW());

-- Revize örneği:
-- ALTER TABLE agreements ADD COLUMN example_column VARCHAR(100) NULL;
-- INSERT IGNORE INTO schema_migrations (migration, applied_at)
-- VALUES ('002_example_column', NOW());
