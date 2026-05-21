# Changelog

All notable changes to this project are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2026-05-21

### Added
- Cascade resolver with configurable fallback chain across 10 official slot-provider CDN sources.
- Provider source implementations: Yggdrasil, NoLimit City, Relax Gaming (RelaxCdn), Quickspin, Wazdan, Push Gaming, Endorphina, Hacksaw Gaming, Thunderkick, Stakelogic.
- `SourceInterface` + `DirectSource` base class for extending with custom providers.
- WP REST API endpoints:
  - `GET /wp-json/slot-demos-cascade/v1/launch` — resolve a game's demo URL.
  - `GET /wp-json/slot-demos-cascade/v1/health/check` — trigger a full sweep.
- Daily WP-Cron health check (`slot_demos_cascade_daily_health`) with rotating JSONL log under `wp-content/uploads/sj-demo-logs/`.
- Admin dashboard at **Tools → Demo Health** with colour-coded game × source matrix.
- `[slot_demo]` shortcode + auto-injection via `ContentInjector`.
- Configurable URL section detection via `slot_demos_cascade_url_sections` filter.
- PSR-4 autoload, MIT license, GitHub Actions CI for PHP syntax across PHP 7.4 – 8.3.

[Unreleased]: https://github.com/USER/wp-slot-demos-cascade/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/USER/wp-slot-demos-cascade/releases/tag/v1.0.0
