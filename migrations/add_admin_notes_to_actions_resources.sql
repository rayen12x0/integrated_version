-- Add admin_notes column to actions table
ALTER TABLE actions ADD COLUMN admin_notes TEXT DEFAULT NULL COMMENT 'Admin notes for action moderation';

-- Add admin_notes column to resources table  
ALTER TABLE resources ADD COLUMN admin_notes TEXT DEFAULT NULL COMMENT 'Admin notes for resource moderation';