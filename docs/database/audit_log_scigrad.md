# Database change required (run manually)

SciGrade does **not** run this migration for you. Create the table on the `scigrad` connection database (e.g. `eoffice` / `scigraddb`) before audit events are persisted.

```sql
CREATE TABLE IF NOT EXISTS audit_log_scigrad (
  log_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  event VARCHAR(80) NOT NULL COMMENT 'เช่น auth.login, grade_report.create',
  actor_username VARCHAR(10) NULL COMMENT 'username บุคลากรที่กระทำ (ตอน impersonate = คนที่ถูกเข้าแทน)',
  actor_role VARCHAR(30) NULL COMMENT 'instructor|dept_admin|faculty_admin|super_admin',
  impersonator_username VARCHAR(10) NULL COMMENT 'Super Admin จริงตอนเข้าใช้งานแทน',
  subject_type VARCHAR(60) NULL COMMENT 'เช่น grade_report, grade_report_file, privilege',
  subject_id VARCHAR(64) NULL COMMENT 'PK ของ subject (string)',
  ip_address VARCHAR(45) NULL,
  user_agent VARCHAR(500) NULL,
  request_method VARCHAR(10) NULL,
  request_path VARCHAR(255) NULL,
  metadata JSON NULL COMMENT 'รายละเอียดเพิ่มเติมของเหตุการณ์',
  created_at DATETIME NOT NULL,
  PRIMARY KEY (log_id),
  KEY idx_audit_event_created (event, created_at),
  KEY idx_audit_actor_created (actor_username, created_at),
  KEY idx_audit_subject (subject_type, subject_id),
  KEY idx_audit_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### MariaDB / MySQL เก่าที่ไม่มีชนิด JSON

ถ้า server ไม่รองรับ `JSON` ให้แทนบรรทัด `metadata` ด้วย:

```sql
  metadata LONGTEXT NULL COMMENT 'JSON string',
```

### Events ที่ระบบบันทึก (ตัวอย่าง)

| Event | ความหมาย |
|-------|----------|
| `auth.login` | เข้าสู่ระบบสำเร็จ |
| `auth.logout` | ออกจากระบบ |
| `auth.denied` | Google login แต่ไม่ใช่บุคลากรในระบบ |
| `role.switch` | เปลี่ยนบทบาทใน session |
| `impersonation.start` / `impersonation.stop` | เข้าใช้งานแทน / กลับบัญชีเดิม |
| `grade_report.create` / `.update` / `.delete` | CRUD รายงานผล |
| `grade_report.submit_corrections` | อาจารย์ส่งการแก้ไข |
| `grade_report.review` | อนุมัติ/ไม่อนุมัติ/ส่งกลับ (dept/faculty) |
| `grade_report_file.upload` / `.view` / `.delete` | ไฟล์แนบรายงาน |
| `grade_report_file.download_zip` | ดาวน์โหลด ZIP |
| `privilege.create` / `.update` / `.delete` | จัดการสิทธิ์ |
| `dept_submission.receive` | คณะรับเอกสารจากสาขา |
