# Security Policy

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 2.2.x   | ✅ Active support  |
| 2.1.x   | ⚠️ Security fixes only |
| < 2.0   | ❌ End of life     |

## Reporting a Vulnerability

If you discover a security vulnerability in PasPapan, please report it responsibly.

**⚠️ Do NOT open a public GitHub issue for security vulnerabilities.**

### How to Report

1. **Email**: Send details to **admin@pandanteknik.com**
2. **Subject**: `[SECURITY] PasPapan - Brief description`
3. **Include**:
   - Description of the vulnerability
   - Steps to reproduce
   - Potential impact
   - Suggested fix (if any)

### Response Timeline

- **Acknowledgment**: Within 48 hours
- **Initial Assessment**: Within 5 business days
- **Fix & Release**: Depending on severity, typically within 7-14 days

### What to Expect

- We will acknowledge your report within 48 hours.
- We will work with you to understand and validate the issue.
- We will release a fix and credit you (unless you prefer anonymity).

## Security Best Practices for Deployment

- Always use **HTTPS** in production.
- Set `APP_DEBUG=false` in production `.env`.
- Keep `APP_KEY` secret and unique per installation.
- Regularly run `composer audit` to check for dependency vulnerabilities.
- Use strong passwords and enable 2FA where possible.

Thank you for helping keep PasPapan and its users safe! 🔒
