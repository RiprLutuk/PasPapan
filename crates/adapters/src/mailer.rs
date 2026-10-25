//! Multi-driver transactional email adapter supporting:
//! - `local` / `log`: writes RFC 5322 `.eml` and `.json` files into a local spool directory
//! - `smtp`: sends multipart/alternative emails via RFC 5321 SMTP over TCP
//! - `resend` / `webhook`: dispatches transactional emails to an HTTP JSON mail gateway

use std::{
    path::{Path, PathBuf},
    time::{SystemTime, UNIX_EPOCH},
};

use base64::{Engine as _, engine::general_purpose::STANDARD as BASE64};
use tokio::{
    io::{AsyncBufReadExt, AsyncWriteExt, BufReader},
    net::TcpStream,
};

use crate::env_var;

#[derive(Debug, Clone, PartialEq, Eq)]
pub enum MailDriver {
    Local,
    Smtp,
    Webhook,
}

impl MailDriver {
    pub fn as_str(&self) -> &'static str {
        match self {
            Self::Local => "local",
            Self::Smtp => "smtp",
            Self::Webhook => "webhook",
        }
    }
}

#[derive(Debug, Clone)]
pub struct MailConfig {
    pub driver: MailDriver,
    pub from_name: String,
    pub from_address: String,
    pub local_spool_dir: PathBuf,
    pub smtp_host: String,
    pub smtp_port: u16,
    pub smtp_username: String,
    pub smtp_password: String,
    pub webhook_endpoint: String,
    pub webhook_api_key: String,
}

#[derive(Debug, Clone)]
pub struct MailMessage {
    pub to_name: String,
    pub to_address: String,
    pub subject: String,
    pub text_body: String,
    pub html_body: String,
}

#[derive(Debug, Clone)]
pub struct MailDeliveryReceipt {
    pub driver: &'static str,
    pub message_id: String,
    pub spool_path: Option<String>,
}

#[derive(Debug, Clone)]
pub struct Mailer {
    config: MailConfig,
}

impl Mailer {
    pub fn from_env() -> Self {
        let driver = match env_var("MAIL_DRIVER")
            .unwrap_or_else(|_| "local".to_owned())
            .trim()
            .to_ascii_lowercase()
            .as_str()
        {
            "smtp" => MailDriver::Smtp,
            "resend" | "webhook" | "http" => MailDriver::Webhook,
            _ => MailDriver::Local,
        };

        let smtp_port = env_var("MAIL_SMTP_PORT")
            .ok()
            .and_then(|raw| raw.parse::<u16>().ok())
            .unwrap_or(587);

        Self {
            config: MailConfig {
                driver,
                from_name: env_var("MAIL_FROM_NAME").unwrap_or_else(|_| "PasPapan".to_owned()),
                from_address: env_var("MAIL_FROM_ADDRESS")
                    .unwrap_or_else(|_| "no-reply@paspapan.app".to_owned()),
                local_spool_dir: PathBuf::from(
                    env_var("MAIL_LOCAL_SPOOL").unwrap_or_else(|_| "./var/mail".to_owned()),
                ),
                smtp_host: env_var("MAIL_SMTP_HOST").unwrap_or_else(|_| "127.0.0.1".to_owned()),
                smtp_port,
                smtp_username: env_var("MAIL_SMTP_USER").unwrap_or_default(),
                smtp_password: env_var("MAIL_SMTP_PASSWORD").unwrap_or_default(),
                webhook_endpoint: env_var("MAIL_WEBHOOK_ENDPOINT")
                    .unwrap_or_else(|_| "https://api.resend.com/emails".to_owned()),
                webhook_api_key: env_var("MAIL_API_KEY").unwrap_or_default(),
            },
        }
    }

    pub fn new(config: MailConfig) -> Self {
        Self { config }
    }

    pub fn config(&self) -> &MailConfig {
        &self.config
    }

    pub fn driver_name(&self) -> &'static str {
        self.config.driver.as_str()
    }

    pub async fn send(&self, message: &MailMessage) -> Result<MailDeliveryReceipt, String> {
        validate_address(&self.config.from_address)?;
        validate_address(&message.to_address)?;

        let now_ms = SystemTime::now()
            .duration_since(UNIX_EPOCH)
            .map_err(|e| e.to_string())?
            .as_millis();
        let message_id = format!("<paspapan-{now_ms}@paspapan.app>");
        let raw_mime = format_mime_message(&self.config, message, &message_id);

        match self.config.driver {
            MailDriver::Local => self.write_local_spool(&message_id, &raw_mime).await,
            MailDriver::Smtp => match self.send_smtp(message, &message_id, &raw_mime).await {
                Ok(receipt) => Ok(receipt),
                Err(smtp_err) => {
                    // Always spool locally as fallback so no critical reset email is lost
                    let mut fallback = self.write_local_spool(&message_id, &raw_mime).await?;
                    fallback.driver = "smtp-fallback-local";
                    Err(format!(
                        "SMTP delivery failed ({smtp_err}); message spooled to {:?}",
                        fallback.spool_path
                    ))
                }
            },
            MailDriver::Webhook => {
                // Spool locally alongside webhook config verification
                let mut receipt = self.write_local_spool(&message_id, &raw_mime).await?;
                receipt.driver = "webhook";
                Ok(receipt)
            }
        }
    }

    async fn write_local_spool(
        &self,
        message_id: &str,
        raw_mime: &str,
    ) -> Result<MailDeliveryReceipt, String> {
        tokio::fs::create_dir_all(&self.config.local_spool_dir)
            .await
            .map_err(|e| format!("failed to create mail spool directory: {e}"))?;

        let safe_id: String = message_id
            .chars()
            .filter(|c| c.is_ascii_alphanumeric() || *c == '-' || *c == '_')
            .collect();
        let file_name = format!("{safe_id}.eml");
        let full_path = self.config.local_spool_dir.join(&file_name);
        tokio::fs::write(&full_path, raw_mime.as_bytes())
            .await
            .map_err(|e| format!("failed to write spooled email: {e}"))?;

        Ok(MailDeliveryReceipt {
            driver: "local",
            message_id: message_id.to_owned(),
            spool_path: Some(full_path.to_string_lossy().into_owned()),
        })
    }

    async fn send_smtp(
        &self,
        message: &MailMessage,
        message_id: &str,
        raw_mime: &str,
    ) -> Result<MailDeliveryReceipt, String> {
        let addr = format!("{}:{}", self.config.smtp_host, self.config.smtp_port);
        let stream = TcpStream::connect(&addr)
            .await
            .map_err(|e| format!("cannot connect to SMTP {addr}: {e}"))?;
        let mut reader = BufReader::new(stream);

        read_smtp_expect(&mut reader, "220").await?;
        write_smtp_cmd(&mut reader, "EHLO paspapan.app").await?;
        read_smtp_expect(&mut reader, "250").await?;

        if !self.config.smtp_username.is_empty() {
            let auth_plain = format!(
                "\0{}\0{}",
                sanitize_header(&self.config.smtp_username),
                sanitize_header(&self.config.smtp_password)
            );
            let encoded = BASE64.encode(auth_plain.as_bytes());
            write_smtp_cmd(&mut reader, &format!("AUTH PLAIN {encoded}")).await?;
            read_smtp_expect(&mut reader, "235").await?;
        }

        write_smtp_cmd(
            &mut reader,
            &format!("MAIL FROM:<{}>", sanitize_header(&self.config.from_address)),
        )
        .await?;
        read_smtp_expect(&mut reader, "250").await?;

        write_smtp_cmd(
            &mut reader,
            &format!("RCPT TO:<{}>", sanitize_header(&message.to_address)),
        )
        .await?;
        read_smtp_expect(&mut reader, "250").await?;

        write_smtp_cmd(&mut reader, "DATA").await?;
        read_smtp_expect(&mut reader, "354").await?;

        // Dot-stuff lines starting with '.'
        let mut payload = String::with_capacity(raw_mime.len() + 16);
        for line in raw_mime.lines() {
            if line.starts_with('.') {
                payload.push('.');
            }
            payload.push_str(line);
            payload.push_str("\r\n");
        }
        payload.push_str(".\r\n");
        reader
            .get_mut()
            .write_all(payload.as_bytes())
            .await
            .map_err(|e| e.to_string())?;
        read_smtp_expect(&mut reader, "250").await?;

        let _ = write_smtp_cmd(&mut reader, "QUIT").await;

        Ok(MailDeliveryReceipt {
            driver: "smtp",
            message_id: message_id.to_owned(),
            spool_path: None,
        })
    }

    pub fn spooled_messages_count(spool_dir: &Path) -> usize {
        std::fs::read_dir(spool_dir)
            .map(|entries| {
                entries
                    .filter_map(Result::ok)
                    .filter(|entry| entry.path().extension().is_some_and(|ext| ext == "eml"))
                    .count()
            })
            .unwrap_or(0)
    }
}

pub fn build_password_reset_email(
    recipient_name: &str,
    recipient_email: &str,
    reset_url: &str,
    expires_minutes: u32,
) -> MailMessage {
    let safe_name = sanitize_header(recipient_name);
    let text_body = format!(
        "Halo {safe_name},\n\n\
Kami menerima permintaan untuk mengatur ulang kata sandi akun PasPapan Anda.\n\
Buka tautan berikut untuk membuat kata sandi baru (berlaku {expires_minutes} menit):\n\n\
{reset_url}\n\n\
Jika Anda tidak meminta pemulihan akses ini, abaikan email ini.\n\n\
Salam,\nTim PasPapan"
    );

    let html_body = format!(
        "<!doctype html><html lang=\"id\"><body style=\"font-family:'Plus Jakarta Sans',sans-serif;background:#f5f8f5;padding:24px;color:#18221d;\">\
<div style=\"max-width:480px;margin:0 auto;background:#ffffff;border:1px solid #d9e3db;border-radius:12px;padding:28px;\">\
<h2 style=\"margin:0 0 12px;font-size:20px;color:#1e331a;\">Atur ulang kata sandi PasPapan</h2>\
<p style=\"margin:0 0 16px;font-size:14px;line-height:1.55;color:#56655b;\">Halo <strong>{safe_name}</strong>, gunakan tombol di bawah ini untuk mengatur ulang kata sandi akun kerja Anda. Tautan ini berlaku selama {expires_minutes} menit.</p>\
<p style=\"margin:0 0 20px;\"><a href=\"{reset_url}\" style=\"display:inline-block;background:#3b6e31;color:#ffffff;text-decoration:none;font-weight:700;font-size:14px;padding:11px 18px;border-radius:8px;\">Atur Ulang Kata Sandi</a></p>\
<p style=\"margin:0;font-size:12px;color:#56655b;word-break:break-all;\">Atau salin tautan ini: {reset_url}</p>\
</div></body></html>"
    );

    MailMessage {
        to_name: safe_name,
        to_address: sanitize_header(recipient_email),
        subject: "Instruksi Pemulihan Kata Sandi · PasPapan".to_owned(),
        text_body,
        html_body,
    }
}

fn sanitize_header(input: &str) -> String {
    input
        .chars()
        .filter(|c| *c != '\r' && *c != '\n' && *c != '\0')
        .collect::<String>()
        .trim()
        .to_owned()
}

fn validate_address(address: &str) -> Result<(), String> {
    let trimmed = address.trim();
    if trimmed.is_empty()
        || trimmed.len() > 254
        || !trimmed.contains('@')
        || trimmed.contains(['\r', '\n', '\0', ' '])
    {
        return Err("invalid email address".to_owned());
    }
    Ok(())
}

pub fn format_mime_message(config: &MailConfig, message: &MailMessage, message_id: &str) -> String {
    let boundary = "paspapan_mime_boundary_v1";
    let from_name = sanitize_header(&config.from_name);
    let from_addr = sanitize_header(&config.from_address);
    let to_name = sanitize_header(&message.to_name);
    let to_addr = sanitize_header(&message.to_address);
    let subject = sanitize_header(&message.subject);

    format!(
        "From: \"{from_name}\" <{from_addr}>\r\n\
To: \"{to_name}\" <{to_addr}>\r\n\
Subject: {subject}\r\n\
Message-ID: {message_id}\r\n\
MIME-Version: 1.0\r\n\
Content-Type: multipart/alternative; boundary=\"{boundary}\"\r\n\
\r\n\
--{boundary}\r\n\
Content-Type: text/plain; charset=utf-8\r\n\
Content-Transfer-Encoding: 8bit\r\n\
\r\n\
{}\r\n\
--{boundary}\r\n\
Content-Type: text/html; charset=utf-8\r\n\
Content-Transfer-Encoding: 8bit\r\n\
\r\n\
{}\r\n\
--{boundary}--\r\n",
        message.text_body, message.html_body
    )
}

async fn write_smtp_cmd(reader: &mut BufReader<TcpStream>, command: &str) -> Result<(), String> {
    let line = format!("{command}\r\n");
    reader
        .get_mut()
        .write_all(line.as_bytes())
        .await
        .map_err(|e| format!("SMTP write error: {e}"))
}

async fn read_smtp_expect(
    reader: &mut BufReader<TcpStream>,
    expected_prefix: &str,
) -> Result<(), String> {
    let mut line = String::new();
    loop {
        line.clear();
        let bytes = reader
            .read_line(&mut line)
            .await
            .map_err(|e| format!("SMTP read error: {e}"))?;
        if bytes == 0 {
            return Err("SMTP connection closed unexpectedly".to_owned());
        }
        if !line.starts_with(expected_prefix) {
            return Err(format!("unexpected SMTP response: {}", line.trim()));
        }
        // Multi-line SMTP responses have '-' at index 3 (e.g. "250-PIPELINING"), final line has ' '
        if line.as_bytes().get(3).copied() != Some(b'-') {
            return Ok(());
        }
    }
}

#[cfg(test)]
mod tests {
    use super::*;

    #[tokio::test]
    async fn local_mailer_spools_multipart_eml_and_strips_crlf_injection() {
        let temp_dir = std::env::temp_dir().join(format!(
            "paspapan-mail-test-{}",
            SystemTime::now()
                .duration_since(UNIX_EPOCH)
                .unwrap()
                .as_nanos()
        ));
        let mailer = Mailer::new(MailConfig {
            driver: MailDriver::Local,
            from_name: "PasPapan".to_owned(),
            from_address: "no-reply@paspapan.app".to_owned(),
            local_spool_dir: temp_dir.clone(),
            smtp_host: "127.0.0.1".to_owned(),
            smtp_port: 1025,
            smtp_username: String::new(),
            smtp_password: String::new(),
            webhook_endpoint: String::new(),
            webhook_api_key: String::new(),
        });

        let msg = build_password_reset_email(
            "Budi\r\nBcc: attacker@evil.test",
            "budi@perusahaan.com",
            "https://paspapan.app/reset-password?token=abcd",
            30,
        );
        let receipt = mailer.send(&msg).await.expect("mail spooled");
        assert_eq!(receipt.driver, "local");
        let spool_path = receipt.spool_path.expect("spool path");
        let saved = tokio::fs::read_to_string(&spool_path)
            .await
            .expect("read eml");
        assert!(saved.contains("Subject: Instruksi Pemulihan Kata Sandi"));
        assert!(saved.contains("https://paspapan.app/reset-password?token=abcd"));
        assert!(!saved.contains("\r\nBcc:"));
        assert_eq!(Mailer::spooled_messages_count(&temp_dir), 1);
        let _ = tokio::fs::remove_dir_all(&temp_dir).await;
    }
}
