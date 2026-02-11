-- Migration: Add items_needed column to RequestItems table
-- Date: 2026-01-14
-- Purpose: Support Services category special handling

USE LRNPH_OJT;
GO

-- Check if column exists before adding
IF NOT EXISTS (
    SELECT * FROM sys.columns 
    WHERE object_id = OBJECT_ID(N'[dbo].[RequestItems]') 
    AND name = 'items_needed'
)
BEGIN
    ALTER TABLE RequestItems
    ADD items_needed VARCHAR(500) NULL;
    
    PRINT 'Successfully added items_needed column to RequestItems table';
END
ELSE
BEGIN
    PRINT 'items_needed column already exists in RequestItems table';
END
GO
