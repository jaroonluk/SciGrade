# Thesis grade tables (scigrad)

Phase 1 of เมนูส่งผลการเรียนวิทยานิพนธ์/การศึกษาอิสระ. Laravel migration:
`database/migrations/2026_09_05_160000_create_thesis_grade_tables.php`

If the app does not run this migration automatically, apply the SQL on the `scigrad` connection.

```sql
CREATE TABLE IF NOT EXISTS thesis_grade (
  thesis_grade_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  term TINYINT UNSIGNED NOT NULL,
  year SMALLINT UNSIGNED NOT NULL,
  subject_code VARCHAR(20) NOT NULL,
  subject VARCHAR(255) NULL,
  section VARCHAR(4) NOT NULL,
  course_kind VARCHAR(32) NULL,
  username VARCHAR(20) NOT NULL,
  teacher VARCHAR(255) NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'draft',
  checked_proposal TINYINT UNSIGNED NOT NULL DEFAULT 0,
  checked_signed TINYINT UNSIGNED NOT NULL DEFAULT 0,
  submitted_at DATETIME NULL,
  received_at DATETIME NULL,
  received_by VARCHAR(20) NULL,
  return_reason TEXT NULL,
  returned_at DATETIME NULL,
  returned_by VARCHAR(20) NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  PRIMARY KEY (thesis_grade_id),
  UNIQUE KEY uq_thesis_grade_owner_course (username, subject_code, section, term, year),
  KEY idx_thesis_grade_owner_term (username, year, term),
  KEY idx_thesis_grade_status_term (status, year, term),
  KEY idx_thesis_grade_subject (subject_code)
);

CREATE TABLE IF NOT EXISTS thesis_grade_student (
  student_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  thesis_grade_id INT UNSIGNED NOT NULL,
  student_code VARCHAR(20) NOT NULL,
  student_name VARCHAR(255) NULL,
  degree VARCHAR(16) NOT NULL DEFAULT 'master',
  thesis_terms_count TINYINT UNSIGNED NOT NULL DEFAULT 1,
  proposal_approved TINYINT UNSIGNED NOT NULL DEFAULT 0,
  grade VARCHAR(8) NULL,
  progress_credits DECIMAL(5,1) NULL,
  completed TINYINT UNSIGNED NOT NULL DEFAULT 0,
  defense_date DATE NULL,
  note VARCHAR(500) NULL,
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (student_id),
  KEY idx_thesis_student_report (thesis_grade_id)
);

CREATE TABLE IF NOT EXISTS thesis_grade_file (
  file_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  thesis_grade_id INT UNSIGNED NOT NULL,
  student_id INT UNSIGNED NULL,
  file_type VARCHAR(32) NOT NULL DEFAULT 'ts_report',
  original_name VARCHAR(255) NOT NULL,
  stored_path VARCHAR(500) NOT NULL,
  uploaded_at DATETIME NOT NULL,
  username VARCHAR(20) NULL,
  PRIMARY KEY (file_id),
  KEY idx_thesis_file_report (thesis_grade_id),
  KEY idx_thesis_file_type (thesis_grade_id, file_type)
);
```

### `thesis_grade.status`
| Value | Meaning |
|-------|---------|
| `draft` | ร่าง |
| `submitted` | รอสาขา |
| `returned` | ส่งกลับแก้ไข |
| `received` | สาขารับแล้ว |

### `thesis_grade_file.file_type`
| Value | Meaning |
|-------|---------|
| `ts_report` | ใบส่งเกรด TS-รหัสวิชา-กลุ่ม-ภาค-ปี.pdf |
| `s0_letter` | หนังสือชี้แจง S=0 (ผูก `student_id`) |
