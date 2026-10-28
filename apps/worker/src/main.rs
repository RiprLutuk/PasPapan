use std::time::Duration;

use paspapan_adapters::mailer::{Mailer, build_password_reset_email};
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

async fn process_pending_outbox(pool: &PgPool, mailer: &Mailer) -> Result<usize, sqlx::Error> {
    let mut tx = pool.begin().await?;
    let rows = sqlx::query_as::<_, (Uuid, String, Value)>(
        "SELECT id, event_type, payload
         FROM outbox_events
         WHERE processed_at IS NULL AND available_at <= NOW() AND attempts < 5
         ORDER BY available_at ASC, created_at ASC
         LIMIT 25
         FOR UPDATE SKIP LOCKED",
    )
    .fetch_all(&mut *tx)
    .await?;

    let mut processed = 0_usize;
    for (id, event_type, payload) in rows {
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
