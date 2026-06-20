# Changelog

All notable changes to this project are documented here.
The format is based on [Keep a Changelog](https://keepachangelog.com/) and
this project adheres to [Semantic Versioning](https://semver.org/).

## [0.3.0-beta]
### Added (distinctive features)
- **Exemptions engine (Art. 16 / 59)**: per-category exclusion of products from withdrawal, with a localised
  legal reason shown to the customer; exempt items are non-selectable and the button hides when all items are exempt.
- **Verifiable audit register**: each acknowledgement gets a unique **verification code**; a public
  **receipt verification page** (`noindex`, privacy-safe) lets anyone check authenticity; code shown in the
  email, on the success page and in the back office; CSV export of the register.
### Changed
- Back-office display name: **PsRecessoFacile EU**.
- DB upgrade `0.3.0` adds the `verification_code` column to existing installs.

## [0.2.0-beta]
### Added
- Genuine **two-step confirmation** (declaration → review & confirm) — reinforces Art. 11a §3, no dark patterns.
- **Annex I-B model withdrawal form** included in the on-screen declaration and in the durable-medium email (5 languages).
- **Customisable button label** per language from the back-office panel (statutory wording as default).

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
