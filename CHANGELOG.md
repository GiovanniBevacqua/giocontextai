# Changelog

All notable changes to GioContextAi are documented in this file.
The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project uses [Semantic Versioning](https://semver.org/).

## [1.2.2]

### Fixed
- `/llms.txt`, `/llms-full.txt`, `/ai.txt` and `/products.json` no longer redirect (301) to a trailing-slash URL on sites with a static front page; they now answer directly with `200 OK`.
- Titles, site name and description are written as clean plain text, without HTML entities such as `&amp;` or `&#8217;`.
- URLs with query parameters are no longer encoded as `&#038;` in the generated files.
- The `giocontextai_post_item_output` filter is now applied to every resource line.

### Changed
- Rewrite rules are checked after all endpoints are registered, including `products.json`.
- The cache is cleared when the included post types change and when posts or terms are created or deleted.
- Tested up to WordPress 7.1.

## [1.0.0]

### Added
- Initial release.
- Virtual `llms.txt`, `llms-full.txt` and `ai.txt` endpoints.
- Configurable public post type inclusion.
- `noindex` compatibility with Yoast SEO, Rank Math and SEOPress.
- Safeguards for password-protected content and WordPress/WooCommerce system pages.
- Transient caching with automatic invalidation when content changes.
- Endpoint preview tools.

[1.2.2]: https://github.com/GiovanniBevacqua/giocontextai/releases/tag/v1.2.2
[1.0.0]: https://wordpress.org/plugins/giocontextai/
