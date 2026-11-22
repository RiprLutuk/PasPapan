use std::time::Duration;

use paspapan_adapters::mailer::{
    MailMessage, Mailer, build_account_ready_email, build_notification_email,
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
                mailer.send(&MailMessage{to_name:"Report recipient".to_owned(),to_address:email.to_owned(),subject:format!("PasPapan {key} report ready"),text_body:format!("Laporan {key} format {format} telah dijadwalkan. Buka dan unduh laporan: {url}"),html_body:format!("<p>Laporan <strong>{key}</strong> format <strong>{format}</strong> telah dijadwalkan.</p><p><a href=\"{url}\">Buka Reports</a></p>")}).await
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
