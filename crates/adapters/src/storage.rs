use crate::env_var;
use hmac::{Hmac, Mac};
use sha2::{Digest, Sha256};
use std::path::{Component, Path, PathBuf};
use std::time::{SystemTime, UNIX_EPOCH};

type HmacSha256 = Hmac<Sha256>;

#[derive(Clone, Debug, PartialEq, Eq)]
pub enum StorageDriver {
    Local,
    S3,
    R2,
    Minio,
}

impl StorageDriver {
    pub fn as_str(&self) -> &'static str {
        match self {
            Self::Local => "local",
            Self::S3 => "s3",
            Self::R2 => "r2",
            Self::Minio => "minio",
        }
    }
}

#[derive(Clone, Debug)]
pub struct StorageConfig {
    pub driver: StorageDriver,
    pub local_root: PathBuf,
    pub bucket: String,
    pub region: String,
    pub endpoint: String,
    pub access_key_id: String,
    pub secret_access_key: String,
    pub public_base_url: Option<String>,
    pub force_path_style: bool,
}

#[derive(Clone, Debug)]
pub struct ObjectStorage {
    config: StorageConfig,
}

impl ObjectStorage {
    pub fn from_env() -> Result<Self, String> {
        let raw_driver = env_var("STORAGE_DRIVER")
            .unwrap_or_else(|_| "local".to_owned())
            .trim()
            .to_ascii_lowercase();

        let driver = match raw_driver.as_str() {
            "" | "local" | "fs" | "filesystem" => StorageDriver::Local,
            "s3" | "aws" => StorageDriver::S3,
            "r2" | "cloudflare" | "cloudflare-r2" => StorageDriver::R2,
            "minio" => StorageDriver::Minio,
            other => return Err(format!("unsupported STORAGE_DRIVER: {other}")),
        };

        let local_root = PathBuf::from(
            env_var("STORAGE_LOCAL_ROOT").unwrap_or_else(|_| "./var/objects".to_owned()),
        );
        let bucket = env_var("STORAGE_BUCKET").unwrap_or_else(|_| "paspapan".to_owned());
        let region = env_var("STORAGE_REGION").unwrap_or_else(|_| match driver {
            StorageDriver::R2 => "auto".to_owned(),
            _ => "ap-southeast-1".to_owned(),
        });
        let endpoint = env_var("STORAGE_ENDPOINT").unwrap_or_else(|_| match driver {
            StorageDriver::R2 => "https://example.r2.cloudflarestorage.com".to_owned(),
            StorageDriver::Minio => "http://127.0.0.1:9000".to_owned(),
            _ => format!("https://s3.{region}.amazonaws.com"),
        });
        let access_key_id = env_var("STORAGE_ACCESS_KEY_ID").unwrap_or_default();
        let secret_access_key = env_var("STORAGE_SECRET_ACCESS_KEY").unwrap_or_default();
        let public_base_url = env_var("STORAGE_PUBLIC_BASE_URL")
            .ok()
            .map(|value| value.trim().trim_end_matches('/').to_owned())
            .filter(|value| !value.is_empty());
        let force_path_style = env_var("STORAGE_FORCE_PATH_STYLE")
            .map(|value| !matches!(value.trim(), "0" | "false" | "FALSE"))
            .unwrap_or(true);

        if driver != StorageDriver::Local
            && (bucket.trim().is_empty()
                || endpoint.trim().is_empty()
                || access_key_id.trim().is_empty()
                || secret_access_key.trim().is_empty())
        {
            return Err(
                "S3/R2/MinIO storage requires STORAGE_BUCKET, STORAGE_ENDPOINT, STORAGE_ACCESS_KEY_ID, and STORAGE_SECRET_ACCESS_KEY"
                    .to_owned(),
            );
        }

        Ok(Self {
            config: StorageConfig {
                driver,
                local_root,
                bucket,
                region,
                endpoint: endpoint.trim_end_matches('/').to_owned(),
                access_key_id,
                secret_access_key,
                public_base_url,
                force_path_style,
            },
        })
    }

    pub fn new(config: StorageConfig) -> Self {
        Self { config }
    }

    pub fn config(&self) -> &StorageConfig {
        &self.config
    }

    pub fn driver_name(&self) -> &'static str {
        self.config.driver.as_str()
    }

    pub fn build_object_key(
        company_id: &str,
        object_id: &str,
        file_name: &str,
    ) -> Result<String, &'static str> {
        let trimmed = file_name.trim();
        if trimmed.is_empty() || trimmed.len() > 180 {
            return Err("invalid file name");
        }
        let sanitized: String = trimmed
            .chars()
            .map(|ch| {
                if ch.is_ascii_alphanumeric() || matches!(ch, '.' | '-' | '_') {
                    ch
                } else {
                    '_'
                }
            })
            .collect();
        let clean = sanitized.trim_matches('.');
        if clean.is_empty() {
            return Err("invalid file name");
        }
        Ok(format!("{company_id}/{object_id}-{clean}"))
    }

    fn resolve_local_path(&self, object_key: &str) -> Result<PathBuf, String> {
        let rel = Path::new(object_key);
        if rel.is_absolute() {
            return Err("object key must be relative".to_owned());
        }
        for component in rel.components() {
            match component {
                Component::Normal(_) => {}
                _ => return Err("invalid path traversal in object key".to_owned()),
            }
        }
        Ok(self.config.local_root.join(rel))
    }

    pub async fn put_object(
        &self,
        object_key: &str,
        _content_type: &str,
        bytes: &[u8],
    ) -> Result<(), String> {
        let path = self.resolve_local_path(object_key)?;
        if let Some(parent) = path.parent() {
            tokio::fs::create_dir_all(parent)
                .await
                .map_err(|err| err.to_string())?;
        }
        tokio::fs::write(&path, bytes)
            .await
            .map_err(|err| err.to_string())?;
        Ok(())
    }

    pub async fn get_object(&self, object_key: &str) -> Result<Vec<u8>, String> {
        let path = self.resolve_local_path(object_key)?;
        tokio::fs::read(&path).await.map_err(|err| err.to_string())
    }

    pub async fn delete_object(&self, object_key: &str) -> Result<(), String> {
        let path = self.resolve_local_path(object_key)?;
        match tokio::fs::remove_file(&path).await {
            Ok(()) => Ok(()),
            Err(err) if err.kind() == std::io::ErrorKind::NotFound => Ok(()),
            Err(err) => Err(err.to_string()),
        }
    }

    pub fn download_url_or_presigned(
        &self,
        file_id: &str,
        object_key: &str,
        expires_seconds: u64,
    ) -> Result<String, String> {
        if let Some(public_base) = &self.config.public_base_url {
            return Ok(format!(
                "{public_base}/{}",
                object_key.trim_start_matches('/')
            ));
        }
        match self.config.driver {
            StorageDriver::Local => Ok(format!("/v1/files/{file_id}/download")),
            StorageDriver::S3 | StorageDriver::R2 | StorageDriver::Minio => {
                let now = SystemTime::now()
                    .duration_since(UNIX_EPOCH)
                    .map_err(|err| err.to_string())?
                    .as_secs();
                self.presign_url("GET", object_key, expires_seconds, now)
            }
        }
    }

    /// Generates an AWS Signature Version 4 (AWS4-HMAC-SHA256) presigned URL compatible with
    /// AWS S3, Cloudflare R2 (`region = "auto"`), MinIO, DigitalOcean Spaces, and Wasabi.
    pub fn presign_url(
        &self,
        method: &str,
        object_key: &str,
        expires_seconds: u64,
        now_unix: u64,
    ) -> Result<String, String> {
        let clean_key = object_key.trim_start_matches('/');
        if clean_key.is_empty() || clean_key.contains("..") {
            return Err("invalid object key".to_owned());
        }

        let (scheme, host, base_path) = parse_endpoint(&self.config.endpoint)?;
        let (canonical_host, canonical_uri) = if self.config.force_path_style {
            let prefix = if base_path.is_empty() {
                format!(
                    "/{}/{}",
                    uri_encode(&self.config.bucket, true),
                    uri_encode(clean_key, false)
                )
            } else {
                format!(
                    "{}/{}/{}",
                    base_path.trim_end_matches('/'),
                    uri_encode(&self.config.bucket, true),
                    uri_encode(clean_key, false)
                )
            };
            (host, prefix)
        } else {
            (
                format!("{}.{}", self.config.bucket, host),
                format!(
                    "{}/{}",
                    base_path.trim_end_matches('/'),
                    uri_encode(clean_key, false)
                ),
            )
        };

        let (date_stamp, amz_date) = format_amz_timestamps(now_unix);
        let credential_scope = format!("{date_stamp}/{}/s3/aws4_request", self.config.region);
        let credential = format!("{}/{credential_scope}", self.config.access_key_id);

        let canonical_query = format!(
            "X-Amz-Algorithm=AWS4-HMAC-SHA256&X-Amz-Credential={}&X-Amz-Date={}&X-Amz-Expires={}&X-Amz-SignedHeaders=host",
            uri_encode(&credential, true),
            amz_date,
            expires_seconds.clamp(1, 604_800)
        );

        let canonical_request = format!(
            "{method}\n{canonical_uri}\n{canonical_query}\nhost:{canonical_host}\n\nhost\nUNSIGNED-PAYLOAD"
        );
        let canonical_request_hash = hex_encode(&Sha256::digest(canonical_request.as_bytes()));

        let string_to_sign =
            format!("AWS4-HMAC-SHA256\n{amz_date}\n{credential_scope}\n{canonical_request_hash}");

        let signing_key = derive_signing_key(
            &self.config.secret_access_key,
            &date_stamp,
            &self.config.region,
            "s3",
        )?;
        let signature = hex_encode(&hmac_sha256(&signing_key, string_to_sign.as_bytes())?);

        Ok(format!(
            "{scheme}://{canonical_host}{canonical_uri}?{canonical_query}&X-Amz-Signature={signature}"
        ))
    }
}

fn parse_endpoint(endpoint: &str) -> Result<(String, String, String), String> {
    let (scheme, rest) = endpoint
        .split_once("://")
        .ok_or_else(|| "endpoint must include http:// or https://".to_owned())?;
    if !matches!(scheme, "http" | "https") {
        return Err("endpoint scheme must be http or https".to_owned());
    }
    let (host, path) = match rest.split_once('/') {
        Some((h, p)) => (h.to_owned(), format!("/{}", p.trim_matches('/'))),
        None => (rest.to_owned(), String::new()),
    };
    if host.is_empty() {
        return Err("endpoint host must not be empty".to_owned());
    }
    Ok((
        scheme.to_owned(),
        host,
        if path == "/" { String::new() } else { path },
    ))
}

fn derive_signing_key(
    secret: &str,
    date_stamp: &str,
    region: &str,
    service: &str,
) -> Result<Vec<u8>, String> {
    let k_secret = format!("AWS4{secret}");
    let k_date = hmac_sha256(k_secret.as_bytes(), date_stamp.as_bytes())?;
    let k_region = hmac_sha256(&k_date, region.as_bytes())?;
    let k_service = hmac_sha256(&k_region, service.as_bytes())?;
    hmac_sha256(&k_service, b"aws4_request")
}

fn hmac_sha256(key: &[u8], data: &[u8]) -> Result<Vec<u8>, String> {
    let mut mac = HmacSha256::new_from_slice(key).map_err(|err| err.to_string())?;
    mac.update(data);
    Ok(mac.finalize().into_bytes().to_vec())
}

fn hex_encode(bytes: &[u8]) -> String {
    let mut out = String::with_capacity(bytes.len() * 2);
    for byte in bytes {
        use std::fmt::Write as _;
        let _ = write!(&mut out, "{byte:02x}");
    }
    out
}

fn uri_encode(input: &str, encode_slash: bool) -> String {
    let mut out = String::with_capacity(input.len());
    for byte in input.bytes() {
        let unreserved = byte.is_ascii_alphanumeric()
            || matches!(byte, b'-' | b'_' | b'.' | b'~')
            || (!encode_slash && byte == b'/');
        if unreserved {
            out.push(byte as char);
        } else {
            use std::fmt::Write as _;
            let _ = write!(&mut out, "%{byte:02X}");
        }
    }
    out
}

fn format_amz_timestamps(unix_secs: u64) -> (String, String) {
    let days = (unix_secs / 86_400) as i64;
    let secs_of_day = unix_secs % 86_400;
    let hour = secs_of_day / 3_600;
    let minute = (secs_of_day % 3_600) / 60;
    let second = secs_of_day % 60;

    // Civil date from days since 1970-01-01 (Howard Hinnant algorithm)
    let z = days + 719_468;
    let era = if z >= 0 { z } else { z - 146_096 } / 146_097;
    let doe = (z - era * 146_097) as u64;
    let yoe = (doe - doe / 1_460 + doe / 36_524 - doe / 146_096) / 365;
    let y = yoe as i64 + era * 400;
    let doy = doe - (365 * yoe + yoe / 4 - yoe / 100);
    let mp = (5 * doy + 2) / 153;
    let d = doy - (153 * mp + 2) / 5 + 1;
    let m = if mp < 10 { mp + 3 } else { mp - 9 };
    let year = if m <= 2 { y + 1 } else { y };

    let date_stamp = format!("{year:04}{m:02}{d:02}");
    let amz_date = format!("{date_stamp}T{hour:02}{minute:02}{second:02}Z");
    (date_stamp, amz_date)
}

#[cfg(test)]
mod tests {
    use super::*;

    #[tokio::test]
    async fn local_storage_roundtrip_and_blocks_traversal() {
        let temp_root =
            std::env::temp_dir().join(format!("paspapan-storage-test-{}", std::process::id()));
        let storage = ObjectStorage::new(StorageConfig {
            driver: StorageDriver::Local,
            local_root: temp_root.clone(),
            bucket: "paspapan".to_owned(),
            region: "auto".to_owned(),
            endpoint: "http://127.0.0.1:9000".to_owned(),
            access_key_id: String::new(),
            secret_access_key: String::new(),
            public_base_url: None,
            force_path_style: true,
        });

        let key = ObjectStorage::build_object_key("company-1", "file-1", "bukti reimbursement.pdf")
            .expect("valid key");
        assert_eq!(key, "company-1/file-1-bukti_reimbursement.pdf");

        storage
            .put_object(&key, "application/pdf", b"PDF-DATA")
            .await
            .expect("put_object succeeds");
        let loaded = storage.get_object(&key).await.expect("get_object succeeds");
        assert_eq!(loaded, b"PDF-DATA");

        storage
            .delete_object(&key)
            .await
            .expect("delete_object succeeds");
        assert!(storage.get_object(&key).await.is_err());

        assert!(
            storage
                .put_object("../escape.txt", "text/plain", b"no")
                .await
                .is_err()
        );
        let _ = tokio::fs::remove_dir_all(&temp_root).await;
    }

    #[test]
    fn generates_valid_s3_and_r2_sigv4_presigned_urls() {
        let r2_storage = ObjectStorage::new(StorageConfig {
            driver: StorageDriver::R2,
            local_root: PathBuf::from("./storage/objects"),
            bucket: "paspapan-prod".to_owned(),
            region: "auto".to_owned(),
            endpoint: "https://acct123.r2.cloudflarestorage.com".to_owned(),
            access_key_id: "R2ACCESSKEY".to_owned(),
            secret_access_key: "R2SECRETKEY".to_owned(),
            public_base_url: None,
            force_path_style: true,
        });

        let url = r2_storage
            .presign_url("GET", "company-1/slip-gaji.pdf", 900, 1_761_350_400)
            .expect("presign succeeds");
        assert!(url.starts_with(
            "https://acct123.r2.cloudflarestorage.com/paspapan-prod/company-1/slip-gaji.pdf?"
        ));
        assert!(url.contains("X-Amz-Algorithm=AWS4-HMAC-SHA256"));
        assert!(url.contains("X-Amz-Credential=R2ACCESSKEY%2F20251025%2Fauto%2Fs3%2Faws4_request"));
        assert!(url.contains("X-Amz-Expires=900"));
        assert!(url.contains("X-Amz-Signature="));
    }
}
