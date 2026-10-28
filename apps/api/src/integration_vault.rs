use super::*;
use aes_gcm::{
    Aes256Gcm, Nonce,
    aead::{Aead, KeyInit, Payload},
};
use base64::{Engine, engine::general_purpose::STANDARD};

fn cipher() -> Result<Aes256Gcm, ApiError> {
    let key = paspapan_adapters::env_var("INTEGRATION_MASTER_KEY")
        .map_err(|_| ApiError::Conflict("integration vault is not configured"))?;
    let bytes = STANDARD
        .decode(key.trim())
        .map_err(|_| ApiError::Conflict("invalid integration vault key"))?;
    if bytes.len() != 32 {
        return Err(ApiError::Conflict(
            "integration vault key must contain 32 random bytes",
        ));
    }
    Aes256Gcm::new_from_slice(&bytes).map_err(|_| ApiError::Internal)
}
pub fn ready() -> bool {
    cipher().is_ok()
}
pub fn seal(company: Uuid, id: Uuid, purpose: &str, secret: &str) -> Result<String, ApiError> {
    let mut nonce = [0u8; 12];
    rand::rng().fill_bytes(&mut nonce);
    let aad = format!("paspapan-integration-v1:{company}:{id}:{purpose}");
    let encrypted = cipher()?
        .encrypt(
            Nonce::from_slice(&nonce),
            Payload {
                msg: secret.as_bytes(),
                aad: aad.as_bytes(),
            },
        )
        .map_err(|_| ApiError::Internal)?;
    Ok(format!(
        "v1.{}.{}",
        STANDARD.encode(nonce),
        STANDARD.encode(encrypted)
    ))
}
pub fn open(company: Uuid, id: Uuid, purpose: &str, envelope: &str) -> Result<String, ApiError> {
    decrypt(&cipher()?, company, id, purpose, envelope)
}
fn decrypt(
    cipher: &Aes256Gcm,
    company: Uuid,
    id: Uuid,
    purpose: &str,
    envelope: &str,
) -> Result<String, ApiError> {
    let chunks: Vec<_> = envelope.split('.').collect();
    if chunks.len() != 3 || chunks[0] != "v1" {
        return Err(ApiError::Internal);
    }
    let nonce = STANDARD.decode(chunks[1]).map_err(|_| ApiError::Internal)?;
    if nonce.len() != 12 {
        return Err(ApiError::Internal);
    }
    let bytes = STANDARD.decode(chunks[2]).map_err(|_| ApiError::Internal)?;
    let aad = format!("paspapan-integration-v1:{company}:{id}:{purpose}");
    let plaintext = cipher
        .decrypt(
            Nonce::from_slice(&nonce),
            Payload {
                msg: &bytes,
                aad: aad.as_bytes(),
            },
        )
        .map_err(|_| ApiError::Conflict("integration credential could not be unlocked"))?;
    String::from_utf8(plaintext).map_err(|_| ApiError::Internal)
}
#[cfg(test)]
mod tests {
    use super::*;
    #[test]
    fn authenticated_encryption_binds_credentials_to_tenant_and_provider() {
        let cipher = Aes256Gcm::new_from_slice(&[7u8; 32]).unwrap();
        let tenant = Uuid::now_v7();
        let id = Uuid::now_v7();
        let nonce = [3u8; 12];
        let aad = format!("paspapan-integration-v1:{tenant}:{id}:biometric_enrollment");
        let encrypted = cipher
            .encrypt(
                Nonce::from_slice(&nonce),
                Payload {
                    msg: b"test credential",
                    aad: aad.as_bytes(),
                },
            )
            .unwrap();
        let envelope = format!(
            "v1.{}.{}",
            STANDARD.encode(nonce),
            STANDARD.encode(encrypted)
        );
        assert_eq!(
            decrypt(&cipher, tenant, id, "biometric_enrollment", &envelope)
                .ok()
                .as_deref(),
            Some("test credential")
        );
        assert!(
            decrypt(
                &cipher,
                Uuid::now_v7(),
                id,
                "biometric_enrollment",
                &envelope
            )
            .is_err()
        );
        assert!(decrypt(&cipher, tenant, id, "document_signature", &envelope).is_err());
        assert!(decrypt(&cipher, tenant, id, "biometric_enrollment", "v1.YQ==.YQ==").is_err());
    }
}
