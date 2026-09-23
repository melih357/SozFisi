-- SözFişi WAMP / MySQL başlangıç veritabanı
-- UYARI: Bu dosya tabloları silip yeniden kurar. Canlı veride çalıştırmayın.

CREATE DATABASE IF NOT EXISTS sozfisi
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_turkish_ci;
USE sozfisi;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS schema_migrations;
DROP TABLE IF EXISTS reports;
DROP TABLE IF EXISTS reminders;
DROP TABLE IF EXISTS completions;
DROP TABLE IF EXISTS addendums;
DROP TABLE IF EXISTS attachments;
DROP TABLE IF EXISTS event_logs;
DROP TABLE IF EXISTS otp_codes;
DROP TABLE IF EXISTS change_requests;
DROP TABLE IF EXISTS approvals;
DROP TABLE IF EXISTS clauses;
DROP TABLE IF EXISTS agreement_versions;
DROP TABLE IF EXISTS agreement_parties;
DROP TABLE IF EXISTS agreements;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    phone VARCHAR(40) NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('user','admin') NOT NULL DEFAULT 'user',
    plan ENUM('free','plus','pro','enterprise') NOT NULL DEFAULT 'free',
    email_verified_at DATETIME NULL,
    phone_verified_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_users_role (role),
    INDEX idx_users_created_at (created_at)
) ENGINE=InnoDB;

CREATE TABLE agreements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    public_code VARCHAR(20) NOT NULL UNIQUE,
    creator_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(160) NOT NULL,
    type ENUM('service','purchase_service','sale','debt','loan_item','shared_expense','deposit','other') NOT NULL DEFAULT 'other',
    status ENUM('draft','sent','viewed','changes_requested','ready','approved','active','completion_pending','completed','cancelled','disputed','archived') NOT NULL DEFAULT 'draft',
    currency CHAR(3) NOT NULL DEFAULT 'TRY',
    total_amount DECIMAL(14,2) NULL,
    start_date DATE NULL,
    end_date DATE NULL,
    risk_level ENUM('green','yellow','red') NOT NULL DEFAULT 'green',
    invite_token CHAR(56) NOT NULL UNIQUE,
    invite_expires_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_agreements_creator FOREIGN KEY (creator_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_agreements_creator_status (creator_id, status),
    INDEX idx_agreements_updated_at (updated_at),
    INDEX idx_agreements_invite_expiry (invite_expires_at)
) ENGINE=InnoDB;

CREATE TABLE agreement_parties (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    agreement_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    display_name VARCHAR(120) NOT NULL,
    role ENUM('creator','guest') NOT NULL,
    contact_type ENUM('email','phone','none') NOT NULL DEFAULT 'none',
    contact_value VARCHAR(190) NULL,
    verification_level ENUM('none','account','otp','advanced') NOT NULL DEFAULT 'none',
    invite_status ENUM('pending','invited','viewed','accepted','not_mine','declined') NOT NULL DEFAULT 'pending',
    invited_at DATETIME NULL,
    verified_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_parties_agreement FOREIGN KEY (agreement_id) REFERENCES agreements(id) ON DELETE CASCADE,
    CONSTRAINT fk_parties_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_parties_agreement_role (agreement_id, role),
    INDEX idx_parties_contact (contact_type, contact_value)
) ENGINE=InnoDB;

CREATE TABLE agreement_versions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    agreement_id BIGINT UNSIGNED NOT NULL,
    version_number INT UNSIGNED NOT NULL,
    content_json LONGTEXT NOT NULL,
    hash CHAR(64) NOT NULL,
    created_by_user_id BIGINT UNSIGNED NULL,
    created_by_label VARCHAR(120) NULL,
    created_at DATETIME NOT NULL,
    frozen_at DATETIME NULL,
    CONSTRAINT fk_versions_agreement FOREIGN KEY (agreement_id) REFERENCES agreements(id) ON DELETE CASCADE,
    CONSTRAINT fk_versions_user FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY uq_agreement_version (agreement_id, version_number),
    INDEX idx_versions_hash (hash)
) ENGINE=InnoDB;

CREATE TABLE clauses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    version_id BIGINT UNSIGNED NOT NULL,
    clause_key VARCHAR(60) NOT NULL,
    title VARCHAR(160) NOT NULL,
    content TEXT NOT NULL,
    sort_order INT NOT NULL DEFAULT 10,
    source ENUM('user','local-ai','mutual-change','system') NOT NULL DEFAULT 'user',
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_clauses_version FOREIGN KEY (version_id) REFERENCES agreement_versions(id) ON DELETE CASCADE,
    INDEX idx_clauses_version_sort (version_id, sort_order),
    INDEX idx_clauses_key (clause_key)
) ENGINE=InnoDB;

CREATE TABLE approvals (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    agreement_id BIGINT UNSIGNED NOT NULL,
    version_id BIGINT UNSIGNED NOT NULL,
    party_id BIGINT UNSIGNED NOT NULL,
    approval_type ENUM('creator','guest') NOT NULL,
    method ENUM('session','otp','email','e_signature') NOT NULL,
    approved_at DATETIME NOT NULL,
    ip_hash CHAR(64) NULL,
    user_agent_hash CHAR(64) NULL,
    otp_id BIGINT UNSIGNED NULL,
    CONSTRAINT fk_approvals_agreement FOREIGN KEY (agreement_id) REFERENCES agreements(id) ON DELETE CASCADE,
    CONSTRAINT fk_approvals_version FOREIGN KEY (version_id) REFERENCES agreement_versions(id) ON DELETE CASCADE,
    CONSTRAINT fk_approvals_party FOREIGN KEY (party_id) REFERENCES agreement_parties(id) ON DELETE CASCADE,
    INDEX idx_approvals_version_party (version_id, party_id),
    INDEX idx_approvals_agreement (agreement_id)
) ENGINE=InnoDB;

CREATE TABLE change_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    agreement_id BIGINT UNSIGNED NOT NULL,
    version_id BIGINT UNSIGNED NOT NULL,
    party_id BIGINT UNSIGNED NULL,
    clause_key VARCHAR(60) NOT NULL,
    old_content TEXT NOT NULL,
    requested_content TEXT NOT NULL,
    note TEXT NULL,
    status ENUM('pending','accepted','rejected','cancelled') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL,
    resolved_at DATETIME NULL,
    CONSTRAINT fk_changes_agreement FOREIGN KEY (agreement_id) REFERENCES agreements(id) ON DELETE CASCADE,
    CONSTRAINT fk_changes_version FOREIGN KEY (version_id) REFERENCES agreement_versions(id) ON DELETE CASCADE,
    CONSTRAINT fk_changes_party FOREIGN KEY (party_id) REFERENCES agreement_parties(id) ON DELETE SET NULL,
    INDEX idx_changes_agreement_status (agreement_id, status)
) ENGINE=InnoDB;

CREATE TABLE otp_codes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    agreement_id BIGINT UNSIGNED NOT NULL,
    party_id BIGINT UNSIGNED NULL,
    purpose VARCHAR(100) NOT NULL,
    code_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    attempt_count TINYINT UNSIGNED NOT NULL DEFAULT 0,
    consumed_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_otp_agreement FOREIGN KEY (agreement_id) REFERENCES agreements(id) ON DELETE CASCADE,
    CONSTRAINT fk_otp_party FOREIGN KEY (party_id) REFERENCES agreement_parties(id) ON DELETE CASCADE,
    INDEX idx_otp_lookup (agreement_id, party_id, purpose, consumed_at),
    INDEX idx_otp_expiry (expires_at)
) ENGINE=InnoDB;

ALTER TABLE approvals
    ADD CONSTRAINT fk_approvals_otp FOREIGN KEY (otp_id) REFERENCES otp_codes(id) ON DELETE SET NULL;

CREATE TABLE event_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    agreement_id BIGINT UNSIGNED NOT NULL,
    actor_user_id BIGINT UNSIGNED NULL,
    actor_label VARCHAR(120) NULL,
    event_type VARCHAR(80) NOT NULL,
    old_value_json LONGTEXT NULL,
    new_value_json LONGTEXT NULL,
    ip_hash CHAR(64) NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_events_agreement FOREIGN KEY (agreement_id) REFERENCES agreements(id) ON DELETE CASCADE,
    CONSTRAINT fk_events_user FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_events_agreement_created (agreement_id, created_at),
    INDEX idx_events_type (event_type)
) ENGINE=InnoDB;

CREATE TABLE attachments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    agreement_id BIGINT UNSIGNED NOT NULL,
    uploaded_by_user_id BIGINT UNSIGNED NULL,
    original_name VARCHAR(190) NOT NULL,
    stored_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    size_bytes BIGINT UNSIGNED NOT NULL,
    sha256 CHAR(64) NOT NULL,
    visibility ENUM('both','creator','guest') NOT NULL DEFAULT 'both',
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_attachments_agreement FOREIGN KEY (agreement_id) REFERENCES agreements(id) ON DELETE CASCADE,
    CONSTRAINT fk_attachments_user FOREIGN KEY (uploaded_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_attachments_agreement (agreement_id),
    INDEX idx_attachments_hash (sha256)
) ENGINE=InnoDB;

CREATE TABLE addendums (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    agreement_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(160) NOT NULL,
    reason TEXT NOT NULL,
    additional_amount DECIMAL(14,2) NULL,
    new_end_date DATE NULL,
    status ENUM('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
    invite_token CHAR(40) NOT NULL UNIQUE,
    created_by_user_id BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    approved_at DATETIME NULL,
    CONSTRAINT fk_addendums_agreement FOREIGN KEY (agreement_id) REFERENCES agreements(id) ON DELETE CASCADE,
    CONSTRAINT fk_addendums_user FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_addendums_agreement_status (agreement_id, status)
) ENGINE=InnoDB;

CREATE TABLE completions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    agreement_id BIGINT UNSIGNED NOT NULL,
    requested_by_user_id BIGINT UNSIGNED NOT NULL,
    request_note TEXT NULL,
    status ENUM('pending','completed','partial','problem') NOT NULL DEFAULT 'pending',
    response_note TEXT NULL,
    responded_by_party_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL,
    responded_at DATETIME NULL,
    CONSTRAINT fk_completions_agreement FOREIGN KEY (agreement_id) REFERENCES agreements(id) ON DELETE CASCADE,
    CONSTRAINT fk_completions_user FOREIGN KEY (requested_by_user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_completions_party FOREIGN KEY (responded_by_party_id) REFERENCES agreement_parties(id) ON DELETE SET NULL,
    INDEX idx_completions_agreement_status (agreement_id, status)
) ENGINE=InnoDB;

CREATE TABLE reminders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    agreement_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    remind_at DATETIME NOT NULL,
    channel ENUM('in_app','email','sms') NOT NULL DEFAULT 'in_app',
    status ENUM('scheduled','sent','cancelled') NOT NULL DEFAULT 'scheduled',
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_reminders_agreement FOREIGN KEY (agreement_id) REFERENCES agreements(id) ON DELETE CASCADE,
    CONSTRAINT fk_reminders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_reminders_due (status, remind_at)
) ENGINE=InnoDB;

CREATE TABLE reports (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    agreement_id BIGINT UNSIGNED NULL,
    reporter_name VARCHAR(120) NULL,
    reporter_email VARCHAR(190) NULL,
    type ENUM('not_mine','abuse','illegal_content','privacy','other') NOT NULL DEFAULT 'other',
    description TEXT NOT NULL,
    status ENUM('open','reviewing','closed') NOT NULL DEFAULT 'open',
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_reports_agreement FOREIGN KEY (agreement_id) REFERENCES agreements(id) ON DELETE SET NULL,
    INDEX idx_reports_status_created (status, created_at)
) ENGINE=InnoDB;

CREATE TABLE schema_migrations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    migration VARCHAR(190) NOT NULL UNIQUE,
    applied_at DATETIME NOT NULL
) ENGINE=InnoDB;

INSERT INTO schema_migrations (migration, applied_at)
VALUES ('001_initial_schema', NOW());

-- Yerel yönetici hesabı
-- E-posta: admin@sozfisi.local
-- Şifre: Admin123!
-- Canlıya çıkmadan önce bu hesabı silin veya şifresini değiştirin.
INSERT INTO users (name, email, phone, password_hash, role, plan, created_at, updated_at)
VALUES ('SözFişi Yönetici', 'admin@sozfisi.local', NULL, '$2y$12$H6U26q1nbQ2CfRU2bWb6U.0TJVzUOruCklwOfDMumFkyn1RCjT1eG', 'admin', 'pro', NOW(), NOW());
