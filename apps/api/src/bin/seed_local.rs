//! Deterministic local/demo fixtures. It resets only `local-demo` and `@local.test`.
use argon2::{
    Argon2, PasswordHasher,
    password_hash::{SaltString, rand_core::OsRng},
};
use uuid::Uuid;

const COMPANY: &str = "d0000000-0000-4000-8000-000000000001";
const OWNER: &str = "d0000000-0000-4000-8000-000000000011";
const ADMIN: &str = "d0000000-0000-4000-8000-000000000012";
const MANAGER: &str = "d0000000-0000-4000-8000-000000000013";
const USER: &str = "d0000000-0000-4000-8000-000000000014";
const STAFF: &str = "d0000000-0000-4000-8000-000000000015";
const ACCOUNTS: [(&str, &str, &str, &str, &str); 5] = [
    (
        OWNER,
        "Superadmin Demo",
        "superadmin@local.test",
        "superadmin-local",
        "owner",
    ),
    (
        ADMIN,
        "Admin Demo",
        "admin@local.test",
        "admin-local-123",
        "admin",
    ),
    (
        MANAGER,
        "Manager Operasional",
        "manager@local.test",
        "manager-local-123",
        "manager",
    ),
    (
        USER,
        "Ayu Karyawan",
        "user@local.test",
        "user-local-123",
        "employee",
    ),
    (
        STAFF,
        "Bima Lapangan",
        "staff@local.test",
        "staff-local-123",
        "employee",
    ),
];

#[tokio::main]
async fn main() -> Result<(), Box<dyn std::error::Error>> {
    let database_url =
        paspapan_adapters::env_var("DATABASE_URL").map_err(|_| "DATABASE_URL must be set")?;
    if !is_local_database(&database_url) {
        return Err("paspapan-seed-local only accepts localhost databases".into());
    }
    let pool = paspapan_adapters::connect(&database_url).await?;
    sqlx::query(RESET_SQL).execute(&pool).await?;
    sqlx::query("DELETE FROM users WHERE email LIKE '%@local.test'")
        .execute(&pool)
        .await?;
    let mut tx = pool.begin().await?;
    sqlx::query("INSERT INTO companies(id,name,slug,timezone) VALUES($1,'PasPapan Local Demo','local-demo','Asia/Jakarta')").bind(id(COMPANY)).execute(&mut *tx).await?;
    for (user_id, name, email, password, role) in ACCOUNTS {
        sqlx::query("INSERT INTO users(id,email,password_hash,full_name,email_verified_at) VALUES($1,$2,$3,$4,NOW())")
            .bind(id(user_id)).bind(email).bind(password_hash(password)?).bind(name).execute(&mut *tx).await?;
        sqlx::query("INSERT INTO company_memberships(company_id,user_id,role) VALUES($1,$2,$3)")
            .bind(id(COMPANY))
            .bind(id(user_id))
            .bind(role)
            .execute(&mut *tx)
            .await?;
    }
    sqlx::query(SEED_SQL).execute(&mut *tx).await?;
    tx.commit().await?;
    println!(
        "Seeded local-demo: 5 accounts, 30 days attendance, and demo data across all rewrite modules."
    );
    println!(
        "superadmin@local.test / superadmin-local | admin@local.test / admin-local-123 | user@local.test / user-local-123"
    );
    Ok(())
}

fn id(value: &str) -> Uuid {
    Uuid::parse_str(value).expect("static UUID")
}
fn password_hash(password: &str) -> Result<String, Box<dyn std::error::Error>> {
    Ok(Argon2::default()
        .hash_password(password.as_bytes(), &SaltString::generate(&mut OsRng))
        .map_err(|error| std::io::Error::other(error.to_string()))?
        .to_string())
}
fn is_local_database(value: &str) -> bool {
    value.contains("@127.0.0.1:") || value.contains("@localhost:") || value.contains("@[::1]:")
}

const SEED_SQL: &str = r#"
DO $$
DECLARE
 c uuid := 'd0000000-0000-4000-8000-000000000001'; owner_id uuid := 'd0000000-0000-4000-8000-000000000011'; admin_id uuid := 'd0000000-0000-4000-8000-000000000012'; manager_id uuid := 'd0000000-0000-4000-8000-000000000013'; user_id uuid := 'd0000000-0000-4000-8000-000000000014'; staff_id uuid := 'd0000000-0000-4000-8000-000000000015'; shift_id uuid := gen_random_uuid(); leave_id uuid := gen_random_uuid(); cash uuid := gen_random_uuid(); ar uuid := gen_random_uuid(); ap uuid := gen_random_uuid(); revenue uuid := gen_random_uuid(); expense uuid := gen_random_uuid(); accounting_period uuid := gen_random_uuid(); payroll_period uuid := gen_random_uuid(); project_id uuid := gen_random_uuid(); thread_id uuid := gen_random_uuid(); product_a uuid := gen_random_uuid(); product_b uuid := gen_random_uuid(); journal_id uuid := gen_random_uuid();
BEGIN
 INSERT INTO employee_profiles(company_id,user_id,employee_code,manager_user_id,started_on) VALUES (c,owner_id,'DEMO-001',NULL,current_date-365),(c,admin_id,'DEMO-002',owner_id,current_date-300),(c,manager_id,'DEMO-003',owner_id,current_date-240),(c,user_id,'DEMO-004',manager_id,current_date-180),(c,staff_id,'DEMO-005',manager_id,current_date-120);
 INSERT INTO shifts(id,company_id,name,starts_at,ends_at) VALUES(shift_id,c,'Reguler 08.00 - 17.00','08:00','17:00');
 INSERT INTO attendance_locations(id,company_id,name,qr_token,latitude,longitude,radius_meters,qr_expires_at) VALUES(gen_random_uuid(),c,'Kantor Tangerang',repeat('a',64),-6.1783,106.6319,300,now()+interval '7 days');
 INSERT INTO shift_assignments(id,company_id,user_id,shift_id,work_date) SELECT gen_random_uuid(),c,member,shift_id,day::date FROM generate_series(current_date-29,current_date,'1 day') day CROSS JOIN (VALUES(manager_id),(user_id),(staff_id)) x(member);
 -- `generate_series(date, date, interval)` yields timestamptz under PostgreSQL.
 -- Cast it back to a calendar date before applying the company's timezone; otherwise
 -- the server session timezone can shift seeded attendance by a full day.
 INSERT INTO attendances(id,company_id,user_id,attendance_date,checked_in_at,checked_out_at,check_in_latitude,check_in_longitude,check_out_latitude,check_out_longitude,work_mode,shift_snapshot,attendance_timezone,checkout_grace_minutes,check_in_policy_snapshot) SELECT gen_random_uuid(),c,member,day::date,((day::date)+time '08:05') AT TIME ZONE 'Asia/Jakarta',((day::date)+time '17:05') AT TIME ZONE 'Asia/Jakarta',-6.1783,106.6319,-6.1781,106.6321,CASE WHEN extract(dow FROM day)=3 THEN 'wfh' ELSE 'onsite' END,jsonb_build_object('id',shift_id,'name','Reguler 08.00 - 17.00','starts_at','08:00','ends_at','17:00','crosses_midnight',false,'checkout_grace_minutes',240,'max_work_minutes',960),'Asia/Jakarta',240,jsonb_build_object('mode',CASE WHEN extract(dow FROM day)=3 THEN 'wfh' ELSE 'onsite' END,'wfh_approved',extract(dow FROM day)=3) FROM generate_series(current_date-29,current_date,'1 day') day CROSS JOIN (VALUES(manager_id),(user_id),(staff_id)) x(member) WHERE extract(dow FROM day) BETWEEN 1 AND 5;
 INSERT INTO organization_reference_data(id,company_id,kind,name,annual_days) VALUES(leave_id,c,'leave_type','Cuti Tahunan',12);
 INSERT INTO employee_leave_entitlements(company_id,user_id,leave_type_id,year,days) VALUES(c,user_id,leave_id,extract(year FROM current_date)::int,12),(c,staff_id,leave_id,extract(year FROM current_date)::int,12);
 INSERT INTO workforce_requests(id,company_id,user_id,kind,starts_on,ends_on,minutes,amount_cents,reason,status,leave_type_id,charged_leave_days,created_at) VALUES (gen_random_uuid(),c,user_id,'leave',current_date+4,current_date+5,NULL,NULL,'Cuti keluarga','pending_manager',leave_id,2,now()-interval '1 day'),(gen_random_uuid(),c,staff_id,'overtime',current_date-3,current_date-3,120,NULL,'Support closing bulanan','approved',NULL,NULL,now()-interval '4 days'),(gen_random_uuid(),c,user_id,'reimbursement',current_date-6,current_date-6,NULL,185000,'Transport kunjungan klien','pending_admin',NULL,NULL,now()-interval '2 days'),(gen_random_uuid(),c,staff_id,'cash_advance',current_date-8,current_date-8,NULL,500000,'Uang muka kegiatan lapangan','approved',NULL,NULL,now()-interval '8 days');
 INSERT INTO payroll_periods(id,company_id,period_start,period_end,status) VALUES(payroll_period,c,date_trunc('month',current_date-interval '1 month')::date,(date_trunc('month',current_date)::date-1),'published');
 INSERT INTO payroll_records(id,period_id,company_id,user_id,gross_amount,deduction_amount) VALUES(gen_random_uuid(),payroll_period,c,user_id,7500000,250000),(gen_random_uuid(),payroll_period,c,staff_id,6800000,180000),(gen_random_uuid(),payroll_period,c,manager_id,10000000,500000);
 INSERT INTO announcements(id,company_id,author_id,title,body,status,published_at) VALUES(gen_random_uuid(),c,admin_id,'Town hall bulanan','Agenda KPI, keselamatan kerja, dan target penjualan bulan ini.','published',now()-interval '2 days');
 INSERT INTO notifications(id,company_id,user_id,title,body,created_at) SELECT gen_random_uuid(),c,member,'Aktivitas demo tersedia','Data dummy 30 hari telah siap untuk dieksplorasi.',now()-interval '1 hour' FROM (VALUES(owner_id),(admin_id),(manager_id),(user_id),(staff_id)) x(member);
 INSERT INTO assets(id,company_id,asset_code,name,serial_number,condition,notes) VALUES(gen_random_uuid(),c,'LT-DEMO-01','Laptop Operasional','PP-LOC-001','good','Assigned demo device'),(gen_random_uuid(),c,'PH-DEMO-01','Android Scanner','PP-LOC-002','good','Attendance scanner');
 INSERT INTO asset_assignments(id,company_id,asset_id,user_id,assigned_by,assigned_at,acknowledged_at) SELECT gen_random_uuid(),c,id,user_id,admin_id,now()-interval '20 days',now()-interval '19 days' FROM assets WHERE company_id=c AND asset_code='LT-DEMO-01';
 INSERT INTO hr_checklists(id,company_id,user_id,title,kind,completed_at,completed_by) VALUES(gen_random_uuid(),c,user_id,'Onboarding Ayu','onboarding',NULL,NULL),(gen_random_uuid(),c,staff_id,'Onboarding Bima','onboarding',now()-interval '100 days',manager_id);
 INSERT INTO performance_goals(id,company_id,user_id,title,unit,target,actual,due_on,status,evidence,review_note,reviewed_by) VALUES(gen_random_uuid(),c,user_id,'Kunjungan pelanggan','kunjungan',20,14,current_date+14,'active','14 visit evidence uploaded','',NULL),(gen_random_uuid(),c,staff_id,'Penyelesaian tugas','tugas',30,30,current_date-2,'approved','Semua checklist selesai','Disetujui manager',manager_id);
 INSERT INTO operations_clients(id,company_id,name,contact_name,contact_phone) VALUES(gen_random_uuid(),c,'PT Nusantara Retail','Nadia Putri','081234567890'); INSERT INTO operations_branches(id,company_id,name,kind,address) VALUES(gen_random_uuid(),c,'Gudang Tangerang','warehouse','Tangerang, Banten');
 INSERT INTO operations_projects(id,company_id,manager_user_id,code,name,description,status) VALUES(project_id,c,manager_id,'OPS-DEMO-01','Rollout POS Cabang','Implementasi perangkat dan pelatihan kasir','active');
 INSERT INTO operations_tasks(id,company_id,project_id,assigned_to,title,priority,due_on,status) VALUES(gen_random_uuid(),c,project_id,user_id,'Verifikasi perangkat cabang','high',current_date+2,'in_progress'),(gen_random_uuid(),c,project_id,staff_id,'Pelatihan kasir','normal',current_date+5,'todo'),(gen_random_uuid(),c,project_id,manager_id,'Review kesiapan go-live','high',current_date+7,'todo');
 INSERT INTO operations_visit_evidence(id,company_id,project_id,user_id,visited_at,latitude,longitude,accuracy_meters,address,notes) VALUES(gen_random_uuid(),c,project_id,user_id,now()-interval '2 days',-6.1783,106.6319,12,'Kantor Tangerang','Perangkat kasir dan koneksi sudah diverifikasi.');
 INSERT INTO collaboration_threads(id,company_id,project_id,created_by,kind,title) VALUES(thread_id,c,project_id,manager_id,'project','Rollout POS - Koordinasi'); INSERT INTO collaboration_thread_members(thread_id,company_id,user_id,role,last_read_at) VALUES(thread_id,c,manager_id,'owner',now()),(thread_id,c,user_id,'member',now()-interval '1 day'),(thread_id,c,staff_id,'member',NULL); INSERT INTO collaboration_messages(id,company_id,thread_id,author_id,body,created_at) VALUES(gen_random_uuid(),c,thread_id,manager_id,'Kickoff rollout dimulai. Mohon cek checklist masing-masing.',now()-interval '2 days'),(gen_random_uuid(),c,thread_id,user_id,'Checklist perangkat sudah 70% selesai.',now()-interval '1 day'); INSERT INTO collaboration_meetings(id,company_id,thread_id,title,join_url,starts_at,ends_at,created_by) VALUES(gen_random_uuid(),c,thread_id,'Daily rollout sync','https://meet.example.test/paspapan-demo',now()+interval '1 day',now()+interval '1 day 30 minutes',manager_id);
 INSERT INTO custom_form_templates(id,company_id,title,category,description,fields,created_by) VALUES(gen_random_uuid(),c,'Laporan Kunjungan','Operations','Form bukti kunjungan cabang','[{"key":"branch","label":"Cabang","type":"text","required":true},{"key":"summary","label":"Ringkasan","type":"textarea","required":true},{"key":"photo","label":"Foto","type":"file","required":false}]'::jsonb,admin_id);
 INSERT INTO custom_form_submissions(id,company_id,template_id,submitted_by,status,payload,reviewed_by,reviewed_at) SELECT gen_random_uuid(),c,id,user_id,'reviewed','{"branch":"Gudang Tangerang","summary":"Audit perangkat selesai."}'::jsonb,manager_id,now()-interval '1 day' FROM custom_form_templates WHERE company_id=c AND title='Laporan Kunjungan';
 INSERT INTO accounting_accounts(id,company_id,code,name,account_type,normal_balance) VALUES(cash,c,'1101','Kas dan Bank','asset','debit'),(ar,c,'1102','Piutang Usaha','asset','debit'),(ap,c,'2101','Utang Usaha','liability','credit'),(revenue,c,'4101','Pendapatan Penjualan','income','credit'),(expense,c,'5101','Beban Operasional','expense','debit'); INSERT INTO accounting_periods(id,company_id,starts_on,ends_on,status) VALUES(accounting_period,c,date_trunc('month',current_date)::date,(date_trunc('month',current_date)+interval '1 month - 1 day')::date,'open'); INSERT INTO accounting_journals(id,company_id,period_id,number,entry_date,status,reference,description,created_by,posted_at) VALUES(journal_id,c,accounting_period,'JRN-DEMO-001',current_date-interval '4 days','draft','OPEN-001','Saldo awal kas',admin_id,NULL); INSERT INTO accounting_journal_lines(id,journal_id,account_id,debit_cents,credit_cents,memo) VALUES(gen_random_uuid(),journal_id,cash,1000000,0,'Modal awal'),(gen_random_uuid(),journal_id,revenue,0,1000000,'Pendapatan awal'); UPDATE accounting_journals SET status='posted',posted_at=now() WHERE id=journal_id;
 INSERT INTO accounting_reconciliations(id,company_id,account_id,statement_date,statement_balance_cents,ledger_balance_cents,note,reconciled_by) VALUES(gen_random_uuid(),c,cash,current_date-1,1000000,1000000,'Rekonsiliasi demo selesai',admin_id);
 INSERT INTO commercial_ledger_mappings(company_id,cash_account_id,receivable_account_id,payable_account_id,revenue_account_id,expense_account_id,updated_by) VALUES(c,cash,ar,ap,revenue,expense,admin_id); INSERT INTO commercial_customers(id,company_id,name,email,phone,address) VALUES(gen_random_uuid(),c,'PT Pelanggan Demo','finance@pelanggan.demo','081299991111','Jakarta'),(gen_random_uuid(),c,'CV Vendor Demo','billing@vendor.demo','081288882222','Bandung'); INSERT INTO commercial_documents(id,company_id,kind,number,status,issue_date,due_date,total_cents,notes,created_by,approved_by,approved_at,approval_note) VALUES(gen_random_uuid(),c,'quote','QUO-DEMO-001','issued',current_date-8,current_date+7,2500000,'Penawaran implementasi',admin_id,owner_id,now()-interval '7 days','Disetujui'),(gen_random_uuid(),c,'invoice','INV-DEMO-001','paid',current_date-15,current_date-1,1250000,'Invoice maintenance',admin_id,owner_id,now()-interval '14 days','Disetujui'),(gen_random_uuid(),c,'vendor_bill','BILL-DEMO-001','issued',current_date-5,current_date+10,850000,'Perlengkapan kantor',admin_id,owner_id,now()-interval '4 days','Disetujui');
 INSERT INTO commercial_document_lines(id,document_id,description,quantity,unit_price_cents,total_cents) SELECT gen_random_uuid(),id,'Layanan dan implementasi',1,total_cents,total_cents FROM commercial_documents WHERE company_id=c; INSERT INTO commercial_payments(id,company_id,document_id,amount_cents,method,reference,created_by) SELECT gen_random_uuid(),c,id,total_cents,'transfer','PAY-DEMO-001',admin_id FROM commercial_documents WHERE company_id=c AND number='INV-DEMO-001';
 INSERT INTO pos_products(id,company_id,sku,name,price_cents,stock_qty) VALUES(product_a,c,'DEMO-001','Paket Attendance Basic',150000,32),(product_b,c,'DEMO-002','Kartu RFID',25000,120); INSERT INTO pos_sales(id,company_id,receipt_number,status,subtotal_cents,discount_cents,tax_cents,total_cents,payment_method,cashier_id,created_at) VALUES(gen_random_uuid(),c,'POS-DEMO-0001','paid',175000,10000,16500,181500,'qris',admin_id,now()-interval '1 day'),(gen_random_uuid(),c,'POS-DEMO-0002','refunded',150000,0,15000,165000,'cash',admin_id,now()-interval '3 days');
 INSERT INTO pos_sale_lines(id,sale_id,product_id,product_name,quantity,unit_price_cents,total_cents) SELECT gen_random_uuid(),sale.id,product_a,'Paket Attendance Basic',1,150000,150000 FROM pos_sales sale WHERE sale.company_id=c;
 INSERT INTO device_sync_devices(id,company_id,user_id,device_key_hash,label,platform,credential_hash,credential_expires_at,last_seen_at) VALUES(gen_random_uuid(),c,user_id,'demo-device-hash','Ayu Android','android','demo-credential-hash',now()+interval '30 days',now()); INSERT INTO attendance_integrity_policies(company_id,min_accuracy_meters,max_speed_kmh,retention_days,reviewer_user_id,require_device_attestation,updated_by) VALUES(c,75,160,90,manager_id,false,admin_id); INSERT INTO attendance_integrity_events(id,company_id,user_id,event_type,severity,metadata,created_at) VALUES(gen_random_uuid(),c,user_id,'low_accuracy','warning','{"accuracy_meters":120}'::jsonb,now()-interval '2 days'),(gen_random_uuid(),c,staff_id,'device_signal','info','{"device":"demo"}'::jsonb,now()-interval '5 days'); INSERT INTO report_schedules(id,company_id,report_key,format,frequency,recipient_email,next_run_at,created_by) VALUES(gen_random_uuid(),c,'attendance','pdf','weekly','admin@local.test',now()+interval '7 days',admin_id),(gen_random_uuid(),c,'accounting','csv','monthly','superadmin@local.test',now()+interval '30 days',owner_id); INSERT INTO workspace_records(id,company_id,created_by,module_key,title,detail,status,amount,created_at) VALUES(gen_random_uuid(),c,admin_id,'analytics','Ringkasan metrik demo','Data aktivitas 30 hari','Aktif',NULL,now()-interval '1 day'),(gen_random_uuid(),c,admin_id,'document_requests','Surat keterangan kerja','Permintaan dokumen karyawan','Diproses',NULL,now()-interval '3 days');
END $$;
"#;

const RESET_SQL: &str = r#"
DO $$
DECLARE demo_company uuid;
BEGIN
 SELECT id INTO demo_company FROM companies WHERE slug='local-demo';
 IF demo_company IS NULL THEN RETURN; END IF;
 DELETE FROM audit_events WHERE company_id=demo_company;
 DELETE FROM request_decisions WHERE company_id=demo_company; DELETE FROM request_payments WHERE company_id=demo_company; DELETE FROM workforce_requests WHERE company_id=demo_company;
 DELETE FROM payroll_records WHERE company_id=demo_company; DELETE FROM payroll_periods WHERE company_id=demo_company;
 DELETE FROM attendance_integrity_events WHERE company_id=demo_company; DELETE FROM attendance_integrity_policies WHERE company_id=demo_company;
 DELETE FROM device_sync_media_queue WHERE company_id=demo_company; DELETE FROM device_sync_mutations WHERE company_id=demo_company; DELETE FROM device_sync_devices WHERE company_id=demo_company;
 DELETE FROM report_schedules WHERE company_id=demo_company; DELETE FROM workspace_records WHERE company_id=demo_company;
 DELETE FROM collaboration_meetings WHERE company_id=demo_company; DELETE FROM collaboration_messages WHERE company_id=demo_company; DELETE FROM collaboration_thread_members WHERE company_id=demo_company; DELETE FROM collaboration_threads WHERE company_id=demo_company;
 DELETE FROM operations_visit_evidence WHERE company_id=demo_company; DELETE FROM operations_task_checklist_items WHERE company_id=demo_company; DELETE FROM operations_tasks WHERE company_id=demo_company; DELETE FROM operations_projects WHERE company_id=demo_company; DELETE FROM operations_clients WHERE company_id=demo_company; DELETE FROM operations_branches WHERE company_id=demo_company;
 DELETE FROM custom_form_submissions WHERE company_id=demo_company; DELETE FROM custom_form_templates WHERE company_id=demo_company;
 DELETE FROM commercial_payments WHERE company_id=demo_company; DELETE FROM commercial_document_lines WHERE document_id IN (SELECT id FROM commercial_documents WHERE company_id=demo_company); DELETE FROM commercial_documents WHERE company_id=demo_company; DELETE FROM commercial_customers WHERE company_id=demo_company; DELETE FROM commercial_ledger_mappings WHERE company_id=demo_company;
 DELETE FROM pos_sale_lines WHERE sale_id IN (SELECT id FROM pos_sales WHERE company_id=demo_company); DELETE FROM pos_sales WHERE company_id=demo_company; DELETE FROM pos_products WHERE company_id=demo_company;
 DELETE FROM accounting_reconciliations WHERE company_id=demo_company; DELETE FROM accounting_journal_lines WHERE journal_id IN (SELECT id FROM accounting_journals WHERE company_id=demo_company); DELETE FROM accounting_journals WHERE company_id=demo_company; DELETE FROM accounting_periods WHERE company_id=demo_company; DELETE FROM accounting_accounts WHERE company_id=demo_company;
 DELETE FROM performance_goals WHERE company_id=demo_company; DELETE FROM hr_checklist_items WHERE company_id=demo_company; DELETE FROM hr_checklists WHERE company_id=demo_company;
 DELETE FROM asset_assignments WHERE company_id=demo_company; DELETE FROM assets WHERE company_id=demo_company;
 DELETE FROM announcement_reads WHERE company_id=demo_company; DELETE FROM announcements WHERE company_id=demo_company; DELETE FROM notifications WHERE company_id=demo_company; DELETE FROM outbox_events WHERE company_id=demo_company;
 DELETE FROM attendances WHERE company_id=demo_company; DELETE FROM shift_assignments WHERE company_id=demo_company; DELETE FROM shifts WHERE company_id=demo_company; DELETE FROM attendance_locations WHERE company_id=demo_company;
 DELETE FROM employee_leave_entitlements WHERE company_id=demo_company; DELETE FROM organization_reference_data WHERE company_id=demo_company; DELETE FROM employee_profiles WHERE company_id=demo_company; DELETE FROM company_memberships WHERE company_id=demo_company;
 DELETE FROM companies WHERE id=demo_company;
END $$;
"#;
