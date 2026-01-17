-- Add attachment column to messages table
ALTER TABLE messages ADD COLUMN attachment VARCHAR(500) NULL AFTER message;
