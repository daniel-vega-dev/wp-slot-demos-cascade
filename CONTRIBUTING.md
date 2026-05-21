# Contributing

Thanks for your interest in improving Slot Demos Cascade.

## Reporting issues

Please use the provided bug-report template under `.github/ISSUE_TEMPLATE/`. Include WordPress + PHP versions, the affected provider source, and any relevant log lines from `wp-content/uploads/sj-demo-logs/`.

## Submitting changes

1. Fork the repository.
2. Create a feature branch off `main`:
   ```bash
   git checkout -b feat/my-new-provider
   ```
3. Make your changes. Match the existing code style (PSR-12, no global helpers, no static state outside `Bootstrap`).
4. Run `php -l` on each changed file. CI will do the same on push.
5. Update `CHANGELOG.md` under `## [Unreleased]`.
6. Commit using clear messages — ideally one logical change per commit.
7. Open a pull request describing what changed and why.

## Adding a new provider source

The most common contribution. Steps:

1. Create `src/Sources/<ProviderName>Direct.php` extending `DirectSource`.
2. Set `$id` (e.g. `'my-provider'`) and `$urlTemplate` using `{slug}`, `{locale}`, `{ccy}` placeholders.
3. Register the instance in `CascadeRouter::__construct()` constructor.
4. Document the new source in the `README.md` features list.
5. Add at least one example entry to `data/games-map.json` (or the `_comment` field) showing how to use it.

Please **do not** add aggregator scrapers (sources that scrape competitor sites for URLs). The plugin's scope is limited to **official provider CDNs only** — direct relationships where the URL pattern is stable and documented.

## Code of Conduct

This project follows the [Contributor Covenant Code of Conduct](CODE_OF_CONDUCT.md). By participating you agree to abide by its terms.

## License

By submitting a contribution you agree to license your work under the [MIT License](LICENSE).
