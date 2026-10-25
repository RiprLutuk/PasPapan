//! Concrete Postgres, object-storage, and email adapters belong here.
pub mod mailer;
pub mod storage;

use sqlx::{PgPool, postgres::PgPoolOptions};

pub fn env_var(key: &str) -> Result<String, std::env::VarError> {
    if let Ok(value) = std::env::var(key) {
        return Ok(value);
    }

    for candidate in [".env", "apps/api/.env"] {
        if let Ok(contents) = std::fs::read_to_string(candidate) {
            for raw_line in contents.lines() {
                let line = raw_line.trim();
                if line.is_empty() || line.starts_with('#') {
                    continue;
                }
                if let Some((k, v)) = line.split_once('=')
                    && k.trim() == key
                {
                    let trimmed = v.trim().trim_matches('"').trim_matches('\'');
                    return Ok(trimmed.to_owned());
                }
            }
        }
    }

    Err(std::env::VarError::NotPresent)
}

pub async fn connect(database_url: &str) -> Result<PgPool, sqlx::Error> {
    PgPoolOptions::new()
        .max_connections(10)
        .connect(database_url)
        .await
}

pub async fn migrate(pool: &PgPool) -> Result<(), sqlx::migrate::MigrateError> {
    sqlx::migrate!("../../apps/api/migrations").run(pool).await
}
