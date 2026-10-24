use argon2::{
    Argon2, PasswordHasher,
    password_hash::{SaltString, rand_core::OsRng},
};
use uuid::Uuid;

const LOCAL_ACCOUNTS: [(&str, &str, &str, &str); 3] = [
    (
        "Superuser Local",
        "superuser@local.test",
        "superuser-local",
        "owner",
    ),
    (
        "Admin Local",
        "admin@local.test",
        "admin-local-123",
        "admin",
    ),
    (
        "User Local",
        "user@local.test",
        "user-local-123",
        "employee",
    ),
];

#[tokio::main]
async fn main() -> Result<(), Box<dyn std::error::Error>> {
    let database_url = std::env::var("DATABASE_URL").map_err(|_| "DATABASE_URL must be set")?;
    let pool = paspapan_adapters::connect(&database_url).await?;
    let mut transaction = pool.begin().await?;
    let company_id = local_company(&mut transaction).await?;

    for (name, email, password, role) in LOCAL_ACCOUNTS {
        let user_id = local_user(&mut transaction, name, email, password).await?;
        sqlx::query(
            "INSERT INTO company_memberships (company_id, user_id, role) VALUES ($1, $2, $3) ON CONFLICT DO NOTHING",
        )
        .bind(company_id)
        .bind(user_id)
        .bind(role)
        .execute(&mut *transaction)
        .await?;
        sqlx::query(
            "INSERT INTO employee_profiles (company_id, user_id, employee_code) VALUES ($1, $2, $3) ON CONFLICT DO NOTHING",
        )
        .bind(company_id)
        .bind(user_id)
        .bind(format!("LOCAL-{}", role.to_ascii_uppercase()))
        .execute(&mut *transaction)
        .await?;
    }

    transaction.commit().await?;
    println!(
        "Local accounts seeded for Local Demo (superuser@local.test, admin@local.test, user@local.test)."
    );
    Ok(())
}

async fn local_company(
    transaction: &mut sqlx::Transaction<'_, sqlx::Postgres>,
) -> Result<Uuid, sqlx::Error> {
    if let Some((id,)) =
        sqlx::query_as::<_, (Uuid,)>("SELECT id FROM companies WHERE slug = 'local-demo'")
            .fetch_optional(&mut **transaction)
            .await?
    {
        return Ok(id);
    }

    let id = Uuid::now_v7();
    sqlx::query("INSERT INTO companies (id, name, slug) VALUES ($1, 'Local Demo', 'local-demo')")
        .bind(id)
        .execute(&mut **transaction)
        .await?;
    Ok(id)
}

async fn local_user(
    transaction: &mut sqlx::Transaction<'_, sqlx::Postgres>,
    full_name: &str,
    email: &str,
    password: &str,
) -> Result<Uuid, Box<dyn std::error::Error>> {
    let password_hash = Argon2::default()
        .hash_password(password.as_bytes(), &SaltString::generate(&mut OsRng))
        .map_err(|error| std::io::Error::other(error.to_string()))?
        .to_string();
    let id = Uuid::now_v7();
    let inserted = sqlx::query("INSERT INTO users (id, email, password_hash, full_name) VALUES ($1, $2, $3, $4) ON CONFLICT DO NOTHING")
        .bind(id)
        .bind(email)
        .bind(password_hash)
        .bind(full_name)
        .execute(&mut **transaction)
        .await?;
    if inserted.rows_affected() == 1 {
        Ok(id)
    } else {
        Ok(
            sqlx::query_as::<_, (Uuid,)>("SELECT id FROM users WHERE email = $1")
                .bind(email)
                .fetch_one(&mut **transaction)
                .await?
                .0,
        )
    }
}
