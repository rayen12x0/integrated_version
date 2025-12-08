-- Add admin_notes column to stories table
ALTER TABLE stories ADD COLUMN admin_notes TEXT DEFAULT NULL COMMENT 'Admin notes for moderation purposes';