# Security Policy

## Reporting a vulnerability

If you discover a security vulnerability in Slot Demos Cascade, please **do not** open a public issue.

Instead, report it privately by:

1. Opening a [GitHub Security Advisory](../../security/advisories/new) on this repository, or
2. Emailing the maintainer (see `git log` for current email).

We will acknowledge your report within 72 hours and aim to publish a fix and advisory within 14 days for high-severity issues.

## Supported versions

| Version | Supported          |
| ------- | ------------------ |
| 1.x     | :white_check_mark: |

## Scope

In scope:

- Authentication / authorisation bypass on plugin REST routes
- Stored or reflected XSS via plugin-rendered output
- SQL injection via plugin queries
- Server-side request forgery (SSRF) via provider source URL resolution
- Improper input validation on `games-map.json` parsing
- Information disclosure via plugin logs

Out of scope:

- Vulnerabilities in third-party provider CDNs themselves
- Issues affecting only outdated WordPress core (≤ 5.x) or PHP (≤ 7.3)
- Social-engineering attacks on plugin administrators
