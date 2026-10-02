# Security Policy

## Reporting a vulnerability

If you believe you have discovered a vulnerability in EspoCRM, please contact us via [this](https://www.espocrm.com/contacts/) or [this](https://www.espocrm.com/support/) forms. Or create a private vulnerability report on GitHub.

What reports we do not accept:

- Executing PHP code by an extension, during extension installation or upgrade process.
- Exposing contacts through a target list, campaign or mass email features, considering the user has access to these features.
- SSRF in IMAP/SMTP with TOCTOU.

Submitting multiple unverified reports without a proper proof of concept
(for example, by simply copy-pasting LLM-generated output) may be considered abuse of the reporting process
and may result in the reporting account being blocked.

Previously published advisories or CVEs do not necessarily establish precedents for handling similar reports in the future.
The fact that a particular issue was previously published as a vulnerability does not mean that a similar issue
will necessarily be addressed or published in the future.
