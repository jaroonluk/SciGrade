# Database change required (run manually)

SciGrade does **not** run this migration for you. Add the column on the `scigrad` connection database (e.g. `eoffice`) before using the new registrar PDF upload.

```sql
ALTER TABLE grade_report_file
  ADD COLUMN file_type VARCHAR(32) NOT NULL DEFAULT 'exam_report'
    COMMENT 'exam_report=แบบรายงานผลการสอบไล่, registrar=ใบส่งผลการศึกษาจาก REG'
  AFTER grade_id;

-- Optional: at most one registrar PDF per report (exam_report may still have multiple)
-- CREATE UNIQUE INDEX uq_grade_file_registrar
--   ON grade_report_file (grade_id, file_type);
```

### Values
| `file_type` | Meaning |
|-------------|---------|
| `exam_report` | แบบรายงานผลการสอบไล่ (existing attachments; default for old rows) |
| `registrar` | ใบส่งผลการศึกษา (downloaded from REG / uploaded by instructor) |

Existing rows keep `exam_report` via the `DEFAULT`.
