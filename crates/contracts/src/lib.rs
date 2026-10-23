use serde::{Deserialize, Serialize};

#[derive(Serialize)]
pub struct HealthResponse {
    pub status: &'static str,
}

#[derive(Debug, Deserialize)]
pub struct LoginRequest {
    pub email: String,
    pub password: String,
}

#[derive(Debug, Deserialize)]
pub struct RegisterRequest {
    pub company_name: String,
    pub company_slug: String,
    pub full_name: String,
    pub email: String,
    pub password: String,
}

#[derive(Debug, Serialize)]
pub struct SessionResponse {
    pub expires_in_seconds: i64,
}

#[derive(Debug, Serialize)]
pub struct CurrentSessionResponse {
    pub user: CurrentUser,
    pub company: CurrentCompany,
}

#[derive(Debug, Serialize)]
pub struct CurrentUser {
    pub id: String,
    pub full_name: String,
    pub email: String,
}

#[derive(Debug, Serialize)]
pub struct CurrentCompany {
    pub id: String,
    pub name: String,
    pub role: String,
}

#[derive(Debug, Serialize)]
pub struct TodayAttendanceResponse {
    pub date: String,
    pub status: String,
    pub shift_name: Option<String>,
    pub starts_at: Option<String>,
    pub ends_at: Option<String>,
}
