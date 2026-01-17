# Student Upload Verification & Penalty System

## Overview
A complete academic integrity system for managing student PDF uploads with teacher verification, self-declarations, and penalty tracking.

## Features

### 1. **Student Upload with Self-Declaration**
- Students upload PDFs (notes, syllabus, past questions) for their subjects
- **Required Declaration**: "I confirm this is my original and authentic study material"
- Uploads are automatically flagged as **"Pending Approval"**
- Prevents accidental plagiarism by making students think about authenticity

**File**: `student_upload_pdf.php`
- Form includes mandatory checkbox for self-declaration
- Declaration text and timestamp are saved in `student_upload_declarations` table
- IP address is recorded for audit trails

### 2. **Teacher Verification Panel**
Teachers assigned to a subject can review pending uploads and take actions:

**File**: `teacher_verify_student_uploads.php`

**Actions Available**:
1. **✅ Approve** - Upload becomes available to all students in that subject
2. **🚫 Reject** - Delete upload with reason provided
3. **⛔ Flag as Plagiarism** - Mark as plagiarized and impose 10-point penalty
4. **View/Download** - Examine the uploaded file

**Features**:
- Filter by subject
- Shows student declaration for each upload
- Student information card (name, email, roll number)
- Comment/reason fields for each action
- Upload approval logging

### 3. **Penalty System**
Tracks academic integrity violations with point-based penalties.

**Database Table**: `student_penalties`

**Penalty Types**:
- **Plagiarism** (10 points) - Content flagged as copied/plagiarized
- **False Declaration** (5 points) - Misleading self-declarations
- **Academic Dishonesty** (15 points) - Major violations

**Penalty Management Features**:
- Active penalties tracking
- Appeal system (students can appeal)
- Removal of penalties by teachers
- Appeal resolution by teachers

### 4. **Student Penalty Appeal System**
Students can appeal penalties with explanations.

**File**: `student_penalties_view.php`

**Process**:
1. Student views their active penalties
2. Clicks "Submit Appeal"
3. Provides detailed appeal reason
4. Teacher reviews and either:
   - Approves appeal (penalty removed)
   - Rejects appeal (penalty remains)

### 5. **Approval Status Display**
All notes now show their verification status:
- ⏳ **Pending Approval** (yellow badge) - Waiting for teacher review
- ✅ **Approved** (green badge) - Verified and available
- ❌ **Rejected** (red badge) - Deleted by teacher
- ⛔ **Flagged** (red badge) - Marked as plagiarized

## Database Schema

### Modified Tables

#### `notes` table (new columns)
```sql
- approval_status ENUM('pending','approved','rejected','plagiarized')
- approved_by INT (teacher who approved)
- approved_at TIMESTAMP
- rejection_reason TEXT
- has_penalty TINYINT(1)
```

### New Tables

#### `student_upload_declarations`
```
- id (PK)
- upload_id → notes.id
- student_id → users.id
- declaration_text TEXT
- agreed_at TIMESTAMP
- ip_address VARCHAR(45)
UNIQUE: upload_id
```

#### `student_penalties`
```
- id (PK)
- student_id → users.id
- upload_id → notes.id (nullable)
- penalty_type ENUM('plagiarism','false_declaration','academic_dishonesty')
- penalty_points INT
- reason TEXT
- imposed_by → users.id (teacher)
- imposed_at TIMESTAMP
- status ENUM('active','removed','appeal_pending','appeal_resolved')
- appeal_reason TEXT
- appeal_date TIMESTAMP
```

#### `upload_approval_log`
```
- id (PK)
- upload_id → notes.id
- teacher_id → users.id
- action ENUM('approved','rejected','flagged_plagiarism','pending')
- action_date TIMESTAMP
- comment TEXT
```

## Installation Steps

### 1. Run Database Migration
```bash
mysql -u root -p result_system < migrations/add_student_upload_approval_system.sql
```

### 2. Update Configuration
- Ensure `db_config.php` is properly configured
- Test database connection

### 3. File Permissions
```bash
mkdir -p uploads/notes/
chmod 755 uploads/notes/
```

## User Workflows

### Student Workflow
1. Navigate to **Student Dashboard** → **Upload PDF**
2. Select subject and file
3. **Read and agree** to self-declaration checkbox
4. **Upload** - Status shows as "Pending Approval"
5. Wait for teacher review
6. Once approved, upload is visible to all students
7. If flagged for plagiarism, can **Submit Appeal**

### Teacher Workflow
1. Navigate to **Teacher Dashboard** → **Verify Uploads**
2. Filter by subject (optional)
3. For each pending upload:
   - Review student info and declaration
   - **View** the file
   - Choose action: Approve, Reject, or Flag Plagiarism
   - Add optional comment/reason
4. View penalty management for appeals in **Student Penalties** page

## Configuration Rules

### Default Penalty Points
- **Plagiarism**: 10 points
- **False Declaration**: 5 points
- **Academic Dishonesty**: 15 points

### Accumulation Rules
- 0-5 points: Warning (can be displayed on student dashboard)
- 6-15 points: Yellow flag (may affect upload privileges)
- 16-20 points: Serious concern (academic dean notification)
- 20+ points: Suspension consideration

## Customization

### Change Penalty Points
Edit in `teacher_verify_student_uploads.php`:
```php
// Line for plagiarism penalty
$penalty_stmt->bind_param("iisi", $upload['uploader_id'], $upload_id, $plagiarism_reason, 10); // Change 10
```

### Change Allowed File Types
Edit in `student_upload_pdf.php`:
```php
$allowed = ['pdf','doc','docx','zip']; // Add or remove types
```

### Notification System (Future Enhancement)
- Email notifications to students on approval/rejection
- Email to teachers on new uploads pending review
- Dashboard badges for pending reviews

## Security Measures

1. **IP Address Logging**: All declarations record user IP for fraud detection
2. **Audit Trail**: Every action logged in `upload_approval_log`
3. **Role-Based Access**: Only assigned teachers can verify their subject's uploads
4. **Self-Declaration**: Creates legal accountability for students
5. **Appeal System**: Ensures fairness in penalty imposition

## Reports & Analytics

Teachers can analyze:
- Plagiarism rate by student/subject
- Common violations
- Appeal success/failure rates
- Student integrity trends

## Related Files

- **Student**: `student_upload_pdf.php`, `student_penalties_view.php`
- **Teacher**: `teacher_verify_student_uploads.php`, `teacher_student_penalties.php`
- **Database**: `migrations/add_student_upload_approval_system.sql`
- **Headers**: Included via `student_header.php`, `teacher_header.php`

## Future Enhancements

1. **Email Notifications**
   - Auto-email students on approval/rejection
   - Notify teachers of pending uploads

2. **Plagiarism Detection API**
   - Integrate with plagiarism checking service
   - Auto-flag suspicious content

3. **Admin Dashboard**
   - View all penalties across system
   - Generate integrity reports
   - Manage penalty rules

4. **Appeal Workflow**
   - Department head review for serious appeals
   - Multiple-level approval process

5. **Student Dashboard Widget**
   - Show penalty points status
   - Upload history with status
   - Appeal status tracking

## Troubleshooting

### Issue: Upload shows "Pending Approval" indefinitely
- Check if teacher has access to verify (assigned to subject)
- Verify subject assignment in database

### Issue: Student can't see "Approve" button
- Ensure teacher is logged in
- Verify teacher is assigned to the upload's subject

### Issue: Penalties not appearing
- Check `student_penalties` table permissions
- Verify student_id matches

## Support & Contact

For questions about this system, contact the development team or check the main README.md file.
