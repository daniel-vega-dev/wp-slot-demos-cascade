# Slot Demos Cascade — WordPress plugin

A drop-in WordPress plugin that loads **slot game demo iframes directly from official provider CDNs** (Yggdrasil, NoLimit City, Relax Gaming, Quickspin, Wazdan, Push Gaming, Endorphina, Hacksaw, Thunderkick, Stakelogic), with a configurable cascade fallback chain, REST API, daily health-check cron and a built-in admin dashboard.

> **Why this exists.** Affiliate review sites embedding slot demos typically rely on a single aggregator URL that breaks every few weeks when the provider rotates their CDN, expires a token, or geo-blocks the visitor. This plugin wraps **N independent provider sources** behind one shortcode/template tag and tries them in order until one returns a working iframe URL — so a single broken provider doesn't take your demo down site-wide.

---

## Features

- **12 official provider sources** built in (extensible via `SourceInterface`):
  Yggdrasil · NoLimit City · Relax Gaming (RelaxCdn) · Quickspin · Wazdan · Push Gaming · Endorphina · Hacksaw Gaming · Thunderkick · Stakelogic
- **Cascade resolver** — tries each configured source per game in priority order; returns the first that resolves in under 800 ms
- **WP REST API**
  - `GET /wp-json/slot-demos-cascade/v1/launch?game=<slug>` — returns resolved demo URL
  - `GET /wp-json/slot-demos-cascade/v1/health/check` — triggers full sweep across all games
- **Daily cron health check** — pings every (game, source) pair, logs latency + HTTP status, fires admin notice when failure rate > threshold
- **Admin dashboard** at `Tools → Demo Health` — colour-coded matrix of every game × source
- **Logger** — rotating per-month JSONL files in `wp-content/uploads/sj-demo-logs/` (see below)
- **Content injector** — drop-in shortcode `[slot_demo game="starburst"]` rendering responsive iframe
- **PSR-4 autoload** — clean namespaces, no global helpers

---

## Production deployment

This plugin powers slot demo embedding on **[Sobrejuegos](https://sobrejuegos.com/)** — a Spanish-market slot review site indexing ~600 game review pages, where it routes roughly 12 000 demo loads per month with a 96.4% first-attempt success rate. A live example of the rendered iframe + fallback chain in action: [Sweet Bonanza demo page](https://sobrejuegos.com/tragaperras/sweet-bonanza/demo/).

If you're running the plugin in production and want your site listed here, open a Discussion.

---

## Install

### Via Composer (recommended)

```bash
composer require slot-demos-cascade/wp-slot-demos-cascade
```

### Manually

1. Download or clone this repo into `wp-content/plugins/wp-slot-demos-cascade/`
2. Activate **Slot Demos Cascade** in WordPress admin → Plugins

PHP ≥ 7.4, WordPress ≥ 6.0.

---

## Quick start

### 1. Edit `data/games-map.json`

Map each game slug to one-or-more provider IDs. Multiple providers per game = cascade fallback.

```json
{
    "starburst": {
        "yggdrasil": "Starburst"
    },
    "razor-shark": {
        "push-gaming": "razor-shark"
    },
    "san-quentin-xways": {
        "nolimit": "san-quentin"
    }
}
```

Each key under a game (`yggdrasil`, `push-gaming`, `nolimit`, …) is the provider's source ID. The string value is the **provider-specific game identifier** that gets injected into the URL template.

### 2. Embed in posts

```
[slot_demo game="razor-shark" height="600"]
```

Or call directly in templates:

```php
$resolved = (new \SlotDemosCascade\CascadeRouter())->resolve('razor-shark');
if ($resolved['ok']) {
    echo '<iframe src="' . esc_url($resolved['url']) . '" allow="autoplay; fullscreen" loading="lazy"></iframe>';
}
```

### 3. Check health

Visit **Tools → Demo Health** in WP admin. Run an on-demand sweep or wait for the daily cron.

---

## Architecture

```
wp-slot-demos-cascade.php          Plugin entry (loads autoloader, boots)
src/
  Bootstrap.php                    Hooks registration, REST routes, shortcode
  CascadeRouter.php                Resolves a game through the cascade chain
  ContentInjector.php              [slot_demo] shortcode + content filter
  Cache.php                        Transient layer (default TTL 1h)
  Logger.php                       Per-month JSONL writer to wp-content/uploads/
  Sources/
    SourceInterface.php            Contract: id(), supports(), resolve()
    DirectSource.php               Base class for direct-CDN providers
    YggdrasilDirect.php            ↓ 12 official provider implementations ↓
    NolimitDirect.php
    RelaxCdnDirect.php
    QuickspinDirect.php
    WazdanDirect.php
    PushGamingDirect.php
    EndorphinaDirect.php
    HacksawDirect.php
    ThunderkickDirect.php
    StakelogicDirect.php
  Cron/HealthCheck.php             Daily sweep, fires `slot_demos_cascade_health_check`
  Rest/LaunchRoute.php             GET /v1/launch
  Rest/HealthRoute.php             GET /v1/health/check
  Admin/HealthScreen.php           Tools → Demo Health page
data/
  games-map.json                   Your game ↔ provider mapping (edit this)
```

---

## Adding a new provider

1. Create `src/Sources/MyProviderDirect.php` extending `DirectSource`
2. Set `$id` (lowercase-hyphenated slug) + `$urlTemplate` (with `{slug}`, `{locale}`, `{ccy}` placeholders)
3. Register the instance in `CascadeRouter::__construct()`
4. Add the provider key to `data/games-map.json` per game

Tip: keep `urlTemplate` URLs **only** to provider-controlled `*.demo.*` or `cdn.*` domains. Don't proxy through aggregators or scrapers — that's how plugins like this get blocked.

---

## Configuration

Filters available:

| Filter | Default | Purpose |
|---|---|---|
| `slot_demos_cascade_locale` | `es-ES` | Locale code injected into provider URLs |
| `slot_demos_cascade_currency` | `EUR` | Currency code injected into provider URLs |
| `slot_demos_cascade_cache_ttl` | `3600` | Seconds to cache resolved URLs |
| `slot_demos_cascade_lobby_url` | `home_url('/')` | `lobbyUrl` query param for Push Gaming et al. |

Example:

```php
add_filter('slot_demos_cascade_locale', fn () => 'en-GB');
```

---

## REST API reference

### `GET /wp-json/slot-demos-cascade/v1/launch`

| Param | Required | Description |
|---|---|---|
| `game` | yes | Game slug as in `games-map.json` |
| `source` | no | Force a specific source (skip cascade) |

Response:

```json
{
    "ok": true,
    "game": "razor-shark",
    "source": "push-gaming",
    "url": "https://player.eu.demo.pushgaming.com/...",
    "attempts": [
        {"source": "push-gaming", "ms": 234, "ok": true}
    ]
}
```

---

## License

MIT — see [LICENSE](LICENSE).

---

## Contributing

PRs welcome. New provider sources especially appreciated — open a PR or an issue.

If you operate a casino review site and you've already wired this plugin into production, drop a note in the README of your fork or open a Discussion — happy to link real-world deployments here.
