-- Migration: Add sponsor column to events table
-- Date: 2026-04-19

ALTER TABLE events
ADD COLUMN IF NOT EXISTS sponsor_id INT,
ADD FOREIGN KEY (sponsor_id) REFERENCES sponsors(id) ON DELETE SET NULL,
ADD INDEX idx_sponsor (sponsor_id);
