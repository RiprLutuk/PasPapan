use serde::{Deserialize, Serialize};

#[derive(Deserialize)]
pub struct CreateEmployeeRequest {
    pub full_name: String,
    pub email: String,
    pub password: String,
    pub employee_code: String,
    pub manager_user_id: Option<String>,
}

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
pub struct PasswordResetRequest {
    pub email: String,
}

#[derive(Debug, Deserialize)]
pub struct ConfirmPasswordResetRequest {
    pub token: String,
    pub new_password: String,
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
    pub capabilities: Vec<String>,
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
    pub work_mode: Option<String>,
    pub date: String,
    pub status: String,
    pub check_in_time: Option<String>,
    pub check_out_time: Option<String>,
    pub shift_name: Option<String>,
    pub starts_at: Option<String>,
    pub ends_at: Option<String>,
}

#[derive(Debug, Deserialize)]
pub struct CheckInRequest {
    pub mode: Option<String>,
    pub latitude: f64,
    pub longitude: f64,
    pub qr_token: String,
}

#[derive(Debug, Deserialize)]
pub struct CheckOutRequest {
    pub mode: Option<String>,
    pub latitude: f64,
    pub longitude: f64,
    pub qr_token: String,
}

#[derive(Debug, Deserialize)]
pub struct CreateShiftRequest {
    pub name: String,
    pub starts_at: String,
    pub ends_at: String,
    pub crosses_midnight: bool,
}

#[derive(Debug, Deserialize)]
pub struct AssignShiftRequest {
    pub user_id: String,
    pub shift_id: String,
    pub work_date: String,
}

#[derive(Debug, Deserialize)]
pub struct DeleteShiftAssignmentRequest {
    pub user_id: String,
    pub work_date: String,
}

#[derive(Debug, Serialize)]
pub struct ShiftAssignmentResponse {
    pub id: String,
    pub user_id: String,
    pub shift_id: String,
    pub shift_name: String,
    pub starts_at: String,
    pub ends_at: String,
    pub crosses_midnight: bool,
    pub work_date: String,
}

#[derive(Debug, Serialize)]
pub struct EmployeeResponse {
    pub id: String,
    pub full_name: String,
    pub email: String,
    pub role: String,
    pub employee_code: Option<String>,
}

#[derive(Debug, Serialize)]
pub struct ShiftResponse {
    pub id: String,
    pub name: String,
    pub starts_at: String,
    pub ends_at: String,
    pub crosses_midnight: bool,
}

#[derive(Debug, Deserialize)]
pub struct CreateRequestRequest {
    pub kind: String,
    pub starts_on: String,
    pub ends_on: String,
    pub minutes: Option<i64>,
    pub amount_cents: Option<i64>,
    pub target_user_id: Option<String>,
    pub attachment_file_id: Option<String>,
    pub leave_type_id: Option<String>,
    pub corrected_check_in: Option<String>,
    pub corrected_check_out: Option<String>,
    pub reason: String,
}

#[derive(Debug, Serialize)]
pub struct WorkforceRequestResponse {
    pub id: String,
    pub kind: String,
    pub starts_on: String,
    pub ends_on: String,
    pub minutes: Option<i64>,
    pub amount_cents: Option<i64>,
    pub target_user_id: Option<String>,
    pub target_user_name: Option<String>,
    pub attachment_file_id: Option<String>,
    pub attachment_file_name: Option<String>,
    pub attachment_download_url: Option<String>,
    pub reason: String,
    pub status: String,
    pub decision_note: Option<String>,
    pub decided_by: Option<String>,
    pub details: serde_json::Value,
}

#[derive(Debug, Deserialize)]
pub struct DecideRequestRequest {
    pub decision: String,
    pub note: Option<String>,
}
