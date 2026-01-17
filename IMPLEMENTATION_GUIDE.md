# Implementation Guide: Student Upload Verification System

## Quick Setup Guide

### Step 1: Database Migration
Run the SQL migration to add all required tables and columns:

```bash
cd c:\wamp64\www\result_system
mysql -u root -p result_system < migrations/add_student_upload_approval_system.sql
```

If using different credentials:
```bash
mysql -u [username] -p[password] [database_name] < migrations/add_student_upload_approval_system.sql
```

### Step 2: Verify Installation
Check that all tables were created:
```sql
-- Connect to your database
mysql -u root -p result_system

-- Check for new tables
SHOW TABLES LIKE 'student_%';
SHOW TABLES LIKE 'upload_%';

-- Check notes table modifications
DESC notes;  -- Should show approval_status, approved_by, etc.
```

### Step 3: Update File Permissions
```bash
# Ensure uploads directory exists and is writable
mkdir -p c:\wamp64\www\result_system\uploads\notes
# Windows doesn't use chmod, but verify write permissions exist
```

### Step 4: Test the System
1. **Student Upload Test**:
   - Login as student
   - Go to "Upload PDF"
   - Upload a file
   - Verify self-declaration checkbox appears
   - Upload shows as "Pending Approval"

2. **Teacher Review Test**:
   - Login as teacher
   - Go to "Verify Uploads"
   - See pending uploads
   - Try approve/reject/flag actions

3. **Penalty System Test**:
   - Flag an upload as plagiarism
   - Check student penalties view
   - Test appeal submission

## System Architecture

```
┌─────────────────────────────────────────────────────┐
│         Student Upload Verification System          │
└─────────────────────────────────────────────────────┘
                           │
        ┌──────────────────┼──────────────────┐
        │                  │                  │
    ┌───▼───┐          ┌───▼────┐        ┌───▼──────┐
    │Student│          │Teacher │        │ Admin    │
    │Module │          │Module  │        │Module    │
    └───┬───┘          └───┬────┘        └───┬──────┘
        │                  │                  │
┌───────▼──────┐    ┌──────▼──────┐    ┌─────▼──────┐
│Upload PDF    │    │Verify       │    │Manage      │
│Declaration   │    │Approvals    │    │System      │
│Check Status  │    │Flag Issues  │    │Reports     │
└───────┬──────┘    └──────┬──────┘    └─────┬──────┘
        │                  │                  │
        └──────────────────┼──────────────────┘
                           │
            ┌──────────────▼──────────────┐
            │      Database Layer        │
            ├──────────────────────────┤
            │ notes                    │
            │ student_upload_decl.    │
            │ student_penalties       │
            │ upload_approval_log     │
            └──────────────────────────┘
```

## File Structure

```
result_system/
├── student_upload_pdf.php              # Student upload interface
├── student_penalties_view.php           # Student penalty & appeals view
├── teacher_verify_student_uploads.php  # Teacher verification panel
├── teacher_student_penalties.php        # Teacher penalty management
├── notification_helper.php              # Notification system
├── migrations/
│   └── add_student_upload_approval_system.sql
├── uploads/notes/                       # Uploaded files storage
└── STUDENT_UPLOAD_VERIFICATION_SYSTEM.md
```

## API Reference

### Notification Functions

#### `notifyStudentUploadApproved($student_id, $title, $subject, $conn)`
Sends email to student when upload is approved.

**Example**:
```php
notifyStudentUploadApproved(5, "Database Notes", "Data Structures", $conn);
```

#### `notifyStudentUploadRejected($student_id, $title, $reason, $conn)`
Sends email to student when upload is rejected.

**Example**:
```php
notifyStudentUploadRejected(5, "Database Notes", "Incomplete content", $conn);
```

#### `notifyStudentPlagiarismFlag($student_id, $title, $reason, $points, $conn)`
Sends email to student when content is flagged for plagiarism.

**Example**:
```php
notifyStudentPlagiarismFlag(5, "Database Notes", "Content matched online source", 10, $conn);
```

#### `notifyTeacherPendingUploads($teacher_id, $count, $subject, $conn)`
Sends email to teacher about pending uploads.

**Example**:
```php
notifyTeacherPendingUploads(3, 5, "Data Structures", $conn);
```

#### `notifyStudentAppealResolved($student_id, $penalty_type, $decision, $conn)`
Sends email to student about appeal resolution.

**Example**:
```php
notifyStudentAppealResolved(5, "plagiarism", "approved", $conn);
```

### Utility Functions

#### `getPendingNotifications($user_id, $user_type, $conn)`
Returns array of pending notifications for dashboard.

**Example**:
```php
$notifications = getPendingNotifications(5, 'student', $conn);
// Returns array with pending uploads, penalties, etc.
```

## Database Queries Reference

### Find all pending uploads for a subject
```sql
SELECT n.*, u.first_name, u.last_name 
FROM notes n
JOIN users u ON n.uploader_id = u.id
WHERE n.subject_id = 22 AND n.approval_status = 'pending';
```

### Get student penalties
```sql
SELECT sp.*, u.first_name, u.last_name
FROM student_penalties sp
JOIN users u ON sp.student_id = u.id
WHERE sp.student_id = 5 AND sp.status = 'active';
```

### Get teacher's assignment with pending uploads
```sql
SELECT DISTINCT s.id, s.subject_name, COUNT(n.id) as pending_count
FROM subjects_master s
JOIN teacher_assigned_subjects tas ON s.id = tas.subject_id
JOIN notes n ON s.id = n.subject_id
WHERE tas.teacher_id = 3 AND n.approval_status = 'pending'
GROUP BY s.id;
```

## Common Configuration Changes

### Change Default Penalty Points
Edit `teacher_verify_student_uploads.php`:
```php
// Line ~80, change second parameter for plagiarism points:
$penalty_stmt->bind_param("iisi", $upload['uploader_id'], $upload_id, $plagiarism_reason, 10);
                                                                              // Change this ^^
```

### Change Allowed File Types
Edit `student_upload_pdf.php`:
```php
$allowed = ['pdf','doc','docx','zip']; // Add xlsx, ppt, etc as needed
```

### Modify Declaration Text
Edit `student_upload_pdf.php`:
```php
$declaration_text = "I declare that this is my original and authentic study material...";
// Change the text between quotes
```

## Troubleshooting

### Tables don't exist
```sql
-- Check if migration was applied
SHOW TABLES LIKE 'student_penalties';
-- If not exists, manually run migration SQL
```

### Permission denied uploading
```bash
# Check folder permissions
dir "uploads\notes\"
# Ensure write permissions are set for IIS/Apache user
```

### Teacher can't see uploads
- Verify teacher is assigned to the subject (check `teacher_assigned_subjects` table)
- Ensure upload is in 'pending' status
- Check if upload is for a student (uploader_role = 'student')

### Notifications not sending
- Verify `mail_config.php` is properly configured
- Check email server connection
- Ensure `sendMail()` function is available
- Check error logs

## Performance Optimization

For large datasets, add indexes:
```sql
-- If not already added by migration
ALTER TABLE notes ADD INDEX idx_approval_status (approval_status);
ALTER TABLE student_penalties ADD INDEX idx_student_status (student_id, status);
ALTER TABLE upload_approval_log ADD INDEX idx_upload_teacher (upload_id, teacher_id);
```

## Security Checklist

- ✅ IP addresses logged for declarations
- ✅ Role-based access control
- ✅ SQL injection prevention (prepared statements)
- ✅ File upload validation
- ✅ Declaration creates accountability
- ✅ Audit trail maintained
- ⚠️  TODO: Add file type MIME checking
- ⚠️  TODO: Add virus scan integration
- ⚠️  TODO: Encrypt sensitive data

## Compliance

### GDPR/Privacy
- Declaration timestamps stored
- IP addresses logged
- Appeal records maintained
- Student consent recorded

### Academic Integrity
- Self-declarations enforced
- Plagiarism detection support
- Penalty tracking
- Appeal process available

## Integration Points

### With Student Dashboard
Add widget to show:
- Pending approvals count
- Active penalties
- Upcoming deadlines

### With Teacher Dashboard
Add widget to show:
- Uploads needing review
- Appeals pending
- Recent flaggings

### With Admin Panel
Add reports for:
- Plagiarism statistics
- Penalty history
- Appeal resolution rates

## Next Steps

1. **Test thoroughly** with sample data
2. **Train teachers** on verification process
3. **Communicate rules** to students
4. **Monitor penalties** for fairness
5. **Gather feedback** and iterate
6. **Consider advanced features** like plagiarism API integration

---

**Last Updated**: January 2026
**Version**: 1.0
**Status**: Production Ready
