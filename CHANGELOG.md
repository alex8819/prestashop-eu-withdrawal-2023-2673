# Changelog

All notable changes to this project are documented here.
The format is based on [Keep a Changelog](https://keepachangelog.com/) and
this project adheres to [Semantic Versioning](https://semver.org/).

## [0.1.0-beta] — first public release
### Added
- Statutory withdrawal button ("Recedi dal contratto") on the customer order‑detail page.
- Full and partial withdrawal (per‑product selection with quantities).
- Secure guest lookup (order reference + email) for non‑registered customers.
- Confirmation page with explicit "Confirm withdrawal" function (Art. 11a §3).
- Durable‑medium acknowledgement email (with statement, date and time) + merchant notification.
- Automatic private note added to the order.
- Back‑office management panel: requests list, statuses, resend receipt, order link.
- Configurable withdrawal window (days, start from delivery or order date), eligible order states,
  guest toggle, merchant email.
- 5 languages: IT, EN, FR, DE, ES.
- Privacy by design (anonymised IP).
- SEO/cache safe: `noindex` + `no-store` on module pages, no assets on public cached pages.

### Notes
- Beta release: please test on staging and report issues. Not legal advice.
