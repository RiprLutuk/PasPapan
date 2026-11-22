use std::time::Duration;

use paspapan_adapters::mailer::{
    MailAttachment, MailMessage, Mailer, build_account_ready_email, build_notification_email,
    build_password_reset_email,
};
use serde_json::Value;
use sqlx::PgPool;
use tracing::{error, info, warn};
use tracing_subscriber::EnvFilter;
use uuid::Uuid;

#[tokio::main]
async fn main() -> Result<(), Box<dyn std::error::Error>> {
    tracing_subscriber::fmt()
        .with_env_filter(EnvFilter::from_default_env())
        .init();

    let database_url =
        paspapan_adapters::env_var("DATABASE_URL").map_err(|_| "DATABASE_URL must be set")?;
    let pool = paspapan_adapters::connect(&database_url).await?;
    let mailer = Mailer::from_env();

    info!(
        driver = mailer.driver_name(),
        "outbox worker booted; polling outbox_events"
    );

    let mut ticker = tokio::time::interval(Duration::from_secs(5));
    loop {
        tokio::select! {
            _ = ticker.tick() => {
                if let Err(err)=sqlx::query("SELECT dispatch_announcements(NULL::uuid)").execute(&pool).await {
                    error!(error=%err,"failed to deliver scheduled announcements");
                }
                if let Err(err)=enqueue_due_reports(&pool).await { error!(error=%err,"failed to enqueue scheduled reports"); }
                if let Err(err)=purge_expired_integrity_events(&pool).await { error!(error=%err,"failed to purge expired integrity events"); }
                if let Err(err) = process_pending_outbox(&pool, &mailer).await {
                    error!(error = %err, "failed to process outbox batch");
                }
            }
            _ = tokio::signal::ctrl_c() => {
                info!("shutting down outbox worker");
                break;
            }
        }
    }

    Ok(())
}

/// Retention is a privacy control, not just a policy value in the admin UI.
/// Only reviewed signals are purged; unresolved risk stays available to reviewers.
async fn purge_expired_integrity_events(pool: &PgPool) -> Result<u64, sqlx::Error> {
    let result = sqlx::query(
        "DELETE FROM attendance_integrity_events event
         USING attendance_integrity_policies policy
         WHERE event.company_id = policy.company_id
           AND event.reviewed_at IS NOT NULL
           AND event.reviewed_at < NOW() - (policy.retention_days * INTERVAL '1 day')",
    )
    .execute(pool)
    .await?;
    Ok(result.rows_affected())
}

async fn enqueue_due_reports(pool: &PgPool) -> Result<(), sqlx::Error> {
    let mut tx = pool.begin().await?;
    let rows=sqlx::query_as::<_,(Uuid,Uuid,String,String,String,String)>("SELECT id,company_id,report_key,format,frequency,recipient_email FROM report_schedules WHERE is_active AND next_run_at<=NOW() FOR UPDATE SKIP LOCKED").fetch_all(&mut *tx).await?;
    for (id, company_id, key, format, frequency, email) in rows {
        let event = Uuid::now_v7();
        sqlx::query("INSERT INTO outbox_events(id,company_id,event_type,payload) VALUES($1,$2,'report.scheduled',$3)").bind(event).bind(company_id).bind(serde_json::json!({"schedule_id":id,"report_key":key,"format":format,"recipient_email":email})).execute(&mut *tx).await?;
        let interval = match frequency.as_str() {
            "daily" => "1 day",
            "weekly" => "7 days",
            _ => "1 month",
        };
        sqlx::query("UPDATE report_schedules SET last_run_at=NOW(),next_run_at=NOW()+$1::interval WHERE id=$2").bind(interval).bind(id).execute(&mut *tx).await?;
    }
    tx.commit().await
}

fn pdf_escape(value: &str) -> String {
    value
        .replace('\\', "\\\\")
        .replace('(', "\\(")
        .replace(')', "\\)")
}

fn report_pdf(title: &str, lines: &[String]) -> Vec<u8> {
    let mut text = format!(
        "BT /F1 18 Tf 50 790 Td ({}) Tj /F1 10 Tf 0 -28 Td ",
        pdf_escape(title)
    );
    for line in lines.iter().take(45) {
        text.push_str(&format!("({}) Tj 0 -15 Td ", pdf_escape(line)));
    }
    text.push_str("ET");
    let objects = ["<< /Type /Catalog /Pages 2 0 R >>".to_owned(), "<< /Type /Pages /Kids [3 0 R] /Count 1 >>".to_owned(), "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>".to_owned(), "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>".to_owned(), format!("<< /Length {} >>\nstream\n{}\nendstream", text.len(), text)];
    let mut output = b"%PDF-1.4\n".to_vec();
    let mut offsets = Vec::new();
    for (index, object) in objects.iter().enumerate() {
        offsets.push(output.len());
        output.extend_from_slice(format!("{} 0 obj\n{}\nendobj\n", index + 1, object).as_bytes());
    }
    let xref = output.len();
    output.extend_from_slice(
        format!("xref\n0 {}\n0000000000 65535 f \n", objects.len() + 1).as_bytes(),
    );
    for offset in offsets {
        output.extend_from_slice(format!("{offset:010} 00000 n \n").as_bytes());
    }
    output.extend_from_slice(
        format!(
            "trailer << /Size {} /Root 1 0 R >>\nstartxref\n{xref}\n%%EOF",
            objects.len() + 1
        )
        .as_bytes(),
    );
    output
}

async fn scheduled_report_attachment(
    pool: &PgPool,
    company_id: Uuid,
    key: &str,
    format: &str,
) -> Result<MailAttachment, sqlx::Error> {
    let (header, lines) = match key {
        "attendance" => ("employee,date,status", sqlx::query_scalar::<_, String>("SELECT concat(u.full_name, ',', a.attendance_date::text, ',', CASE WHEN a.checked_in_at IS NULL THEN 'not_checked_in' ELSE 'present' END) FROM attendances a JOIN users u ON u.id=a.user_id WHERE a.company_id=$1 ORDER BY a.attendance_date DESC LIMIT 1000").bind(company_id).fetch_all(pool).await?),
        "payroll" => ("employee,period_end,net_amount", sqlx::query_scalar::<_, String>("SELECT concat(u.full_name, ',', p.period_end::text, ',', r.net_amount::text) FROM payroll_records r JOIN payroll_periods p ON p.id=r.period_id JOIN users u ON u.id=r.user_id WHERE r.company_id=$1 ORDER BY p.period_end DESC LIMIT 1000").bind(company_id).fetch_all(pool).await?),
        "leave" => ("employee,starts_on,ends_on,status", sqlx::query_scalar::<_, String>("SELECT concat(u.full_name, ',', r.starts_on::text, ',', r.ends_on::text, ',', r.status) FROM workforce_requests r JOIN users u ON u.id=r.user_id WHERE r.company_id=$1 AND r.kind='leave' ORDER BY r.created_at DESC LIMIT 1000").bind(company_id).fetch_all(pool).await?),
        _ => ("journal,date,account,debit,credit", sqlx::query_scalar::<_, String>("SELECT concat(j.number, ',', j.entry_date::text, ',', a.code, ',', l.debit_cents::text, ',', l.credit_cents::text) FROM accounting_journals j JOIN accounting_journal_lines l ON l.journal_id=j.id JOIN accounting_accounts a ON a.id=l.account_id WHERE j.company_id=$1 AND j.status='posted' ORDER BY j.entry_date DESC LIMIT 1000").bind(company_id).fetch_all(pool).await?),
    };
    if format == "pdf" {
        Ok(MailAttachment {
            filename: format!("{key}-report.pdf"),
            content_type: "application/pdf".to_owned(),
            bytes: report_pdf(&format!("PasPapan {key} report"), &lines),
        })
    } else {
        let mut content = String::from(header);
        content.push('\n');
        content.push_str(&lines.join("\n"));
        content.push('\n');
        Ok(MailAttachment {
            filename: format!("{key}-report.csv"),
            content_type: "text/csv; charset=utf-8".to_owned(),
            bytes: content.into_bytes(),
        })
    }
}

async fn process_pending_outbox(pool: &PgPool, mailer: &Mailer) -> Result<usize, sqlx::Error> {
    let app_url = paspapan_adapters::env_var("APP_ORIGIN")
        .ok()
        .and_then(|origins| {
            origins
                .split(',')
                .map(str::trim)
                .find(|origin| !origin.is_empty())
                .map(str::to_owned)
        })
        .unwrap_or_else(|| "https://paspapan.app".to_owned());
    let mut tx = pool.begin().await?;
    let rows = sqlx::query_as::<_, (Uuid, Uuid, String, Value)>(
        "SELECT id, company_id, event_type, payload
         FROM outbox_events
         WHERE processed_at IS NULL AND available_at <= NOW() AND attempts < 5
         ORDER BY available_at ASC, created_at ASC
         LIMIT 25
         FOR UPDATE SKIP LOCKED",
    )
    .fetch_all(&mut *tx)
    .await?;

    let mut processed = 0_usize;
    for (id, company_id, event_type, payload) in rows {
        let result = match event_type.as_str() {
            "auth.password_reset_requested" => {
                let email = payload
                    .get("email")
                    .and_then(Value::as_str)
                    .unwrap_or_default();
                let full_name = payload
                    .get("full_name")
                    .and_then(Value::as_str)
                    .unwrap_or("Rekan Kerja");
                let reset_url = payload
                    .get("reset_url")
                    .and_then(Value::as_str)
                    .unwrap_or("https://paspapan.app/forgot-password");
                let message = build_password_reset_email(full_name, email, reset_url, 30);
                mailer.send(&message).await
            }
            "people.employee_created" => {
                let email = payload
                    .get("email")
                    .and_then(Value::as_str)
                    .unwrap_or_default();
                let full_name = payload
                    .get("full_name")
                    .and_then(Value::as_str)
                    .unwrap_or("Rekan Kerja");
                let login_url = payload
                    .get("login_url")
                    .and_then(Value::as_str)
                    .unwrap_or("https://paspapan.app/login");
                mailer
                    .send(&build_account_ready_email(full_name, email, login_url))
                    .await
            }
            "notification.email" => {
                let notification_id = payload
                    .get("notification_id")
                    .and_then(Value::as_str)
                    .and_then(|value| Uuid::parse_str(value).ok());

                match notification_id {
                    Some(notification_id) => {
                        let row = sqlx::query_as::<_, (String, String, String, String)>(
                            "SELECT u.full_name, u.email, n.title, n.body
                             FROM notifications n
                             JOIN users u ON u.id = n.user_id
                             WHERE n.id = $1 AND n.company_id = $2",
                        )
                        .bind(notification_id)
                        .bind(company_id)
                        .fetch_optional(&mut *tx)
                        .await?;

                        match row {
                            Some((name, email, title, body)) => {
                                mailer
                                    .send(&build_notification_email(
                                        &name, &email, &title, &body, &app_url,
                                    ))
                                    .await
                            }
                            None => Ok(paspapan_adapters::mailer::MailDeliveryReceipt {
                                driver: mailer.driver_name(),
                                message_id: format!("notification-missing-{id}"),
                                spool_path: None,
                            }),
                        }
                    }
                    None => Ok(paspapan_adapters::mailer::MailDeliveryReceipt {
                        driver: mailer.driver_name(),
                        message_id: format!("notification-invalid-{id}"),
                        spool_path: None,
                    }),
                }
            }
            "report.scheduled" => {
                let email = payload
                    .get("recipient_email")
                    .and_then(Value::as_str)
                    .unwrap_or_default();
                let key = payload
                    .get("report_key")
                    .and_then(Value::as_str)
                    .unwrap_or("report");
                let format = payload
                    .get("format")
                    .and_then(Value::as_str)
                    .unwrap_or("csv");
                let url = format!("{app_url}/admin/reports");
                let attachment = scheduled_report_attachment(pool, company_id, key, format).await?;
                mailer.send(&MailMessage{to_name:"Report recipient".to_owned(),to_address:email.to_owned(),subject:format!("PasPapan {key} report"),text_body:format!("Laporan {key} format {format} terlampir. Arsip dan jadwal dapat dikelola di: {url}"),html_body:format!("<p>Laporan <strong>{key}</strong> format <strong>{format}</strong> terlampir.</p><p><a href=\"{url}\">Buka Reports</a></p>"),attachments:vec![attachment]}).await
            }
            _ => Ok(paspapan_adapters::mailer::MailDeliveryReceipt {
                driver: mailer.driver_name(),
                message_id: format!("noop-{id}"),
                spool_path: None,
            }),
        };

        match result {
            Ok(_) => {
                sqlx::query(
                    "UPDATE outbox_events
                     SET processed_at = NOW(), attempts = attempts + 1
                     WHERE id = $1",
                )
                .bind(id)
                .execute(&mut *tx)
                .await?;
                processed += 1;
            }
            Err(err) => {
                warn!(event_id = %id, error = %err, "outbox delivery attempt failed");
                sqlx::query(
                    "UPDATE outbox_events
                     SET attempts = attempts + 1,
                         available_at = NOW() + INTERVAL '60 seconds'
                     WHERE id = $1",
                )
                .bind(id)
                .execute(&mut *tx)
                .await?;
            }
        }
    }

    tx.commit().await?;
    Ok(processed)
}
