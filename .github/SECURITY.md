# Security Policy

## Reporting a vulnerability

If you believe you have discovered a vulnerability in EspoCRM, please contact us via [this](https://www.espocrm.com/contacts/) or [this](https://www.espocrm.com/support/) forms. Or create a private vulnerability report on GitHub.

What reports we do not accept:

- Executing PHP code by an extension or during the installation or upgrade process.
- Exposing contacts though a target list, campaign or mass email, considering the user has access to them.
- SSRF in IMAP/SMTP with TOCTOU.

## Supported versions

For severe vulnerabilities we provide fixes for 2 minor versions (the second number in the version string) back from the current stable version.
