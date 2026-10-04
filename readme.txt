=== GioContextAi ===
Contributors: giovannibevacqua
Tags: ai, llms.txt, ai.txt, chatgpt, artificial intelligence
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.2.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Generate machine-readable AI context files for your WordPress site, including llms.txt, llms-full.txt, and ai.txt.

== Description ==

**GioContextAi** helps WordPress websites expose their content in a clean, structured format for AI systems, crawlers, and other machine-readable applications.

The plugin dynamically generates virtual endpoints for:

* `llms.txt` — a structured Markdown index of selected website content.
* `llms-full.txt` — an extended version containing the full text of selected published content.
* `ai.txt` — a dedicated endpoint for AI-related crawler directives and sitemap information.

The files are generated dynamically through WordPress rewrite rules. No physical files need to be created on your server.

GioContextAi is designed to work alongside your existing SEO tools. It can automatically respect `noindex` settings detected from supported SEO plugins, helping prevent content that should not be indexed from being included in the generated AI context.

The Lite version provides the core AI context generation engine. GioContextAi Pro adds advanced controls for businesses, developers, agencies, and websites that need more control over how their content is represented to AI systems.

== Features ==

### AI Context Files

* **llms.txt generation** — Automatically creates a structured Markdown index of selected published content.
* **llms-full.txt generation** — Provides an extended machine-readable version including the content of selected posts and pages.
* **ai.txt generation** — Provides a dedicated endpoint for AI crawler directives and sitemap information.
* **Virtual endpoints** — Files are served through WordPress rewrite rules without creating physical files on the server.

### Content Control

* **Post Type selection** — Choose which public post types should be included in the generated context.
* **Published content only** — Only appropriate published content is considered for inclusion.
* **Noindex awareness** — Automatically excludes content marked as `noindex` through supported SEO plugins.
* **Protected content safeguards** — Password-protected content is not exposed through the generated context.
* **System page safeguards** — Sensitive WordPress and WooCommerce system pages can be excluded from AI context generation.

### Performance

* **Transient caching** — Generated context files are cached to reduce unnecessary database queries when crawlers request the endpoints.
* **Automatic cache invalidation** — Cache is refreshed when relevant content is modified.
* **No physical files** — The plugin does not create or maintain static files on your server.

### Administration

* **Simple configuration** — Configure included post types directly from the WordPress administration area.
* **Endpoint previews** — Preview the generated context files directly from the plugin settings.
* **Native WordPress integration** — Uses WordPress rewrite rules and APIs rather than requiring server-level configuration.

== Pro Version ==

**GioContextAi Pro** extends the core engine with advanced AI context management and optimization tools.

Pro features include:

* **Global Brand Voice** — Define your brand personality, tone, and communication style.
* **Custom AI Instructions** — Add global instructions that can be included in the AI context.
* **Custom Post Type support** — Extend context management to custom post types such as WooCommerce products and portfolios.
* **Taxonomy support** — Include categories, tags, product categories, and other public taxonomies.
* **Per-content controls** — Exclude individual posts, pages, products, or taxonomy terms from AI context.
* **Custom context instructions** — Add contextual notes and instructions to individual resources.
* **AI Readiness Audit** — Analyze multiple dimensions of your site's AI context configuration and receive actionable recommendations.
* **AI Context Inspector** — Inspect how individual content is represented within the generated AI context.
* **AI crawler analytics** — Monitor requests to supported AI context endpoints and analyze crawler activity.
* **Advanced AI feed capabilities** — Create structured machine-readable feeds for selected WordPress content.
* **Priority support and updates** — Pro customers receive priority support and product updates during their active license period.

For information about Pro features and licensing, visit the GioSuite website.

== How It Works ==

After activation, GioContextAi registers virtual WordPress endpoints for the generated context files.

For example:

* `/llms.txt`
* `/llms-full.txt`
* `/ai.txt`

When one of these endpoints is requested, GioContextAi generates the appropriate output using your WordPress content and configuration.

The generated files can then be accessed by compatible crawlers, AI systems, developer tools, or other applications that retrieve machine-readable website information.

GioContextAi does **not** guarantee that a specific AI service or crawler will access or use these endpoints. The plugin provides the structured endpoints and content; how external systems discover, retrieve, or use them is determined by those systems.

== Performance and Caching ==

GioContextAi uses WordPress transient caching for generated context output.

This prevents the plugin from rebuilding the same content for every request and reduces unnecessary database queries, particularly when context endpoints are accessed repeatedly by automated crawlers.

Relevant caches are automatically invalidated when supported content is modified.

== SEO Plugin Compatibility ==

GioContextAi is designed to work alongside traditional SEO plugins.

The Lite version can detect `noindex` settings from supported SEO plugins, including:

* Yoast SEO
* Rank Math
* SEOPress

Content marked as `noindex` can therefore be excluded from the generated AI context.

GioContextAi does not replace your SEO plugin. It provides an additional machine-readable representation of your website content.

== Installation ==

1. Upload the `giocontextai` folder to the `/wp-content/plugins/` directory, or install the plugin through the WordPress plugin installation interface.
2. Activate **GioContextAi** from the **Plugins** screen.
3. Go to **Settings > GioContextAi**.
4. Select the public post types that should be included in your AI context.
5. Use the available preview tools to verify the generated endpoints.

If the endpoints return a 404 error after installation, go to **Settings > Permalinks** and click **Save Changes** to refresh WordPress rewrite rules.

== Frequently Asked Questions ==

= What is llms.txt? =

`llms.txt` is a proposed convention for providing AI systems with a concise, machine-readable representation of important website content using Markdown.

GioContextAi dynamically generates an `llms.txt` endpoint from your WordPress content.

= What is llms-full.txt? =

`llms-full.txt` is an extended context representation generated by GioContextAi that can include the full text of selected published content.

It is intended for applications that need more information than the concise index provided by `llms.txt`.

= What is ai.txt? =

`ai.txt` is a dedicated machine-readable endpoint generated by GioContextAi for AI-related crawler directives and sitemap information.

Support and interpretation of such directives depend on the individual crawler or AI system.

= Will ChatGPT, Claude, Gemini, or other AI systems automatically read these files? =

Not necessarily.

GioContextAi makes the files available through clean public endpoints. Whether an external AI system or crawler discovers, requests, indexes, or uses those endpoints depends on the policies and technical implementation of that service.

= Does GioContextAi replace my SEO plugin? =

No.

GioContextAi complements traditional SEO plugins by providing additional machine-readable context for AI systems and other automated applications.

It can work alongside plugins such as Yoast SEO, Rank Math, and SEOPress.

= Does GioContextAi expose private content? =

GioContextAi is designed to avoid exposing inappropriate content through its generated context.

Password-protected content is excluded, and supported `noindex` directives can be respected.

Sensitive system pages can also be excluded from the generated context.

You should always review the generated endpoint using the built-in preview tools before making assumptions about what is publicly exposed.

= Do I need to create physical files on my server? =

No.

GioContextAi uses WordPress rewrite rules and virtual endpoints. No physical `llms.txt`, `llms-full.txt`, or `ai.txt` files need to be created.

= What should I do if an endpoint returns a 404 error? =

Go to:

**Settings > Permalinks**

and click **Save Changes**.

This refreshes WordPress rewrite rules.

= Does GioContextAi work with WooCommerce? =

The Lite version can work with standard WordPress content and selected public post types.

GioContextAi Pro provides additional support for custom post types and advanced structured content management, including WooCommerce products.

= Does GioContextAi support multilingual websites? =

Multilingual compatibility depends on the multilingual plugin and the configuration of the website.

Dedicated integrations with multilingual plugins may be provided as GioContextAi evolves.

== Privacy ==

GioContextAi does not send your website content to an external AI service in order to generate the core context files.

The generated files are served directly by your WordPress installation.

If analytics functionality is enabled, request information may be recorded to provide crawler activity reports. Refer to the plugin's privacy documentation and configuration for details about collected and retained data.

== External Services ==

GioContextAi's core context generation does not require an external third-party service.

The plugin generates and serves the context endpoints directly from your WordPress installation.

== Screenshots ==

1. GioContextAi settings and endpoint configuration.
2. Generated `llms.txt` preview.
3. Generated `llms-full.txt` preview.
4. AI context configuration.
5. GioContextAi Pro AI Readiness dashboard.
6. AI crawler analytics.
7. Per-content AI context controls.

== Changelog ==

= 1.2.2 =
* Fixed: `/llms.txt`, `/llms-full.txt`, `/ai.txt` and `/products.json` no longer redirect (301) to a trailing-slash URL on sites with a static front page; they now answer directly with 200.
* Fixed: titles, site name and description are written as clean plain text (no HTML entities such as `&amp;` or `&#8217;`).
* Fixed: URLs with query parameters are no longer encoded as `&#038;` in the generated files.
* Fixed: the `giocontextai_post_item_output` filter is now applied to every resource line.
* Improved: rewrite rules are checked after all endpoints are registered, including `products.json`.
* Improved: cache is cleared when included post types change and when posts or terms are deleted or created.

= 1.0.0 =
* Initial release of GioContextAi Lite.
* Added virtual `llms.txt` endpoint.
* Added virtual `llms-full.txt` endpoint.
* Added virtual `ai.txt` endpoint.
* Added configurable public post type inclusion.
* Added noindex compatibility with Yoast SEO, Rank Math, and SEOPress.
* Added password-protected content safeguards.
* Added safeguards for selected WordPress and WooCommerce system pages.
* Added transient caching for generated context output.
* Added automatic cache invalidation when relevant content changes.
* Added endpoint preview tools.