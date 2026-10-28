//! Bounded, process-local telemetry. No URLs, identities or request bodies retained.
use super::*;
use std::{
    collections::HashMap,
    sync::{Arc, Mutex},
    time::{Instant, SystemTime, UNIX_EPOCH},
};

#[derive(Clone)]
pub struct Traffic(
    Arc<Mutex<HashMap<Uuid, Vec<Bucket>>>>,
    u64,
    Arc<Mutex<HashMap<Uuid, Vec<Failure>>>>,
);
#[derive(Clone, serde::Serialize)]
pub struct Failure {
    at: u64,
    status: u16,
    method: String,
    route: String,
    duration_ms: u64,
}
impl Default for Traffic {
    fn default() -> Self {
        Self(
            Arc::default(),
            SystemTime::now()
                .duration_since(UNIX_EPOCH)
                .unwrap_or_default()
                .as_secs(),
            Arc::default(),
        )
    }
}
#[derive(Clone, Default, serde::Serialize)]
pub struct Bucket {
    pub hour: u64,
    pub observed: bool,
    pub requests: u64,
    pub errors: u64,
    pub duration_ms: u64,
}
fn hour() -> u64 {
    SystemTime::now()
        .duration_since(UNIX_EPOCH)
        .unwrap_or_default()
        .as_secs()
        / 3600
}
impl Traffic {
    fn failure(&self, company: Uuid, status: u16, method: String, route: String, duration_ms: u64) {
        let now = SystemTime::now()
            .duration_since(UNIX_EPOCH)
            .unwrap_or_default()
            .as_secs();
        let Ok(mut tenants) = self.2.lock() else {
            return;
        };
        tenants.retain(|_, entries| {
            entries.retain(|e| e.at + 86400 > now);
            !entries.is_empty()
        });
        if !tenants.contains_key(&company) && tenants.len() >= 1000 {
            return;
        }
        let entries = tenants.entry(company).or_default();
        entries.push(Failure {
            at: now,
            status,
            method,
            route,
            duration_ms,
        });
        if entries.len() > 20 {
            entries.remove(0);
        }
    }
    pub fn failures(&self, company: Uuid) -> Vec<Failure> {
        self.2
            .lock()
            .ok()
            .and_then(|t| t.get(&company).cloned())
            .unwrap_or_default()
            .into_iter()
            .rev()
            .collect()
    }
    pub fn started_at(&self) -> u64 {
        self.1
    }
    fn record(&self, company: Uuid, status: u16, duration_ms: u64, now: u64) {
        let Ok(mut tenants) = self.0.lock() else {
            return;
        };
        tenants.retain(|_, buckets| {
            buckets.retain(|b| b.hour + 24 > now);
            !buckets.is_empty()
        });
        if !tenants.contains_key(&company) && tenants.len() >= 1000 {
            return;
        }
        let buckets = tenants.entry(company).or_default();
        let index = match buckets.iter().position(|b| b.hour == now) {
            Some(i) => i,
            None => {
                buckets.push(Bucket {
                    hour: now,
                    ..Default::default()
                });
                buckets.len() - 1
            }
        };
        let b = &mut buckets[index];
        b.requests += 1;
        b.errors += u64::from(status >= 500);
        b.duration_ms += duration_ms;
    }
    pub fn snapshot(&self, company: Uuid) -> Vec<Bucket> {
        let now = hour();
        let tenants = self.0.lock().ok();
        (now.saturating_sub(23)..=now)
            .map(|h| {
                let mut bucket = tenants
                    .as_ref()
                    .and_then(|t| t.get(&company))
                    .and_then(|b| b.iter().find(|b| b.hour == h))
                    .cloned()
                    .unwrap_or(Bucket {
                        hour: h,
                        ..Default::default()
                    });
                bucket.observed = h >= self.1 / 3600;
                bucket
            })
            .collect()
    }
}
pub async fn observe(State(state): State<AppState>, request: Request, next: Next) -> Response {
    let started = Instant::now();
    let method = request.method().to_string();
    let route = request
        .extensions()
        .get::<axum::extract::MatchedPath>()
        .map(|p| p.as_str().to_owned())
        .unwrap_or_else(|| "[unmatched]".into());
    let company = if let Some(token) = session_token(request.headers()) {
        sqlx::query_scalar::<_,Uuid>("SELECT s.company_id FROM sessions s JOIN users u ON u.id=s.user_id JOIN company_memberships m ON m.company_id=s.company_id AND m.user_id=s.user_id WHERE s.token_hash=$1 AND s.revoked_at IS NULL AND s.expires_at>now() AND u.status='active' AND m.employment_status='active' AND (m.access_revoked_at IS NULL OR s.created_at>m.access_revoked_at)")
            .bind(hash_session_token(token)).fetch_optional(&state.pool).await.ok().flatten()
    } else {
        None
    };
    let response = next.run(request).await;
    if let Some(company) = company {
        if response.status().is_server_error() {
            state.traffic.failure(
                company,
                response.status().as_u16(),
                method,
                route,
                started.elapsed().as_millis().min(u64::MAX as u128) as u64,
            );
        }
        state.traffic.record(
            company,
            response.status().as_u16(),
            started.elapsed().as_millis().min(u64::MAX as u128) as u64,
            hour(),
        );
    }
    response
}
#[cfg(test)]
mod tests {
    use super::*;
    #[test]
    fn traffic_is_tenant_scoped_and_expires_old_buckets() {
        let traffic = Traffic::default();
        let a = Uuid::now_v7();
        let b = Uuid::now_v7();
        let now = hour();
        traffic.record(a, 200, 12, now);
        traffic.record(a, 503, 20, now);
        traffic.record(b, 200, 5, now);
        assert_eq!(traffic.snapshot(a).last().unwrap().requests, 2);
        assert_eq!(traffic.snapshot(a).last().unwrap().errors, 1);
        assert_eq!(traffic.snapshot(b).last().unwrap().requests, 1);
        assert!(!traffic.snapshot(a).first().unwrap().observed);
        assert!(traffic.snapshot(a).last().unwrap().observed);
        traffic.failure(a, 503, "GET".into(), "/v1/files/{id}/download".into(), 20);
        assert_eq!(traffic.failures(a)[0].status, 503);
        assert!(traffic.failures(b).is_empty());
        traffic.record(a, 200, 1, now + 24);
        assert!(traffic.0.lock().unwrap()[&a].iter().all(|b| b.hour > now));
    }
}
