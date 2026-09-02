=== SuperFlare Cache – Cloudflare Full-Page Cache & CDN Optimization ===
Contributors: jumedeenkhan, mozedia
Tags: cloudflare, cache, pagespeed, performance, cdn
Donate link: https://buymeacoffee.com/jumedeenkhan
Requires at least: 5.8
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Speed up your WordPress website with Cloudflare CDN, full-page cache & auto purge. Optimize Page Speed, SEO, performance and Core Web Vitals.

== Description ==

SuperFlare Cache plugin allows you to use **Cloudflare Full-Page Cache** for free and safely bypass logged-in users, admin requests, and anything else you don't want to cache.

Normally, Cloudflare only caches static assets (e.g., CSS, JavaScript, and images), while every visitor to your HTML pages still hits your hosting server directly.

SuperFlare Cache closes that gap with a single, self-managed Cloudflare Cache Rule that serves full pages directly from Cloudflare’s edge.

No manual CDN configuration, no Cache Rules to create, and no guesswork. Works on the **Cloudflare Free plan** and scales with Pro, Business and Enterprise.

= Full-page caching, done safely =

* One **Cloudflare Cache Rule** handles full-page caching — **no manual dashboard setup required**.
* The Cache Rule stays **automatically synchronized** with your settings, creating, updating, or removing the rule as needed.
* Only manages its own rule — existing Cache Rules on your zone are preserved and never reordered or overwritten.
* Automatically **bypasses logged-in users, comment authors, and password-protected pages** at Cloudflare's edge.
* Skips caching for **non-2xx responses (redirects and errors)** so temporary error pages are never served from cache.

= Cache Control & Exclusions =

* Separate **Browser Cache TTL** and **Edge Cache TTL** controls, plus Cloudflare's Caching Level setting.
* Exclude entire page types from caching, including the front page, posts page, archives, feeds, search results, 404s, and AMP pages.
* Bypass **AJAX** (admin-ajax.php), **REST API** (wp-json), sitemap.xml, robots.txt, and URLs with query strings.
* Exclude cookies (e.g. WooCommerce/EDD cart cookies) and custom URL parameters from the cache key.
* Exclude specific URLs such as `/cart/` or `/checkout/`, with wildcard support.

= Purging =

* **Selective purging**: only the changed URL is purged when a post, page, or comment is updated — keeping the rest of your cache warm.
* Optional purging of the homepage, archive pages, feeds, and AMP URLs alongside the affected post.
* **Automatic full purge** after a theme or plugin update.
* **Custom Purge URLs** for anything the automatic rules don't cover.
* Purges are queued and processed by a single coalesced cron job, reducing API requests during bulk edits and helping avoid Cloudflare rate limits.
* **Remote Purge** via a secret Cron URL for triggering purges from outside WP-Cron.
* Role-based permissions control who can purge the cache.
* One-click Purge **Latest Post**, **Custom Purge**, and **Purge Entire Cache** from the Quick Actions panel.

= Preloader =

* Automatically **re-warms the cache after a purge** so visitors don't hit a cold page.
* Optional **scheduled preloading** of your latest posts and any sitemaps you list, on a cron schedule you control.
* "Run Preloader Now" for an on-demand warm-up.

= Cloudflare Optimization =

Toggle **Cloudflare's performance, protocol, and security features** directly from WordPress: Always Online, Early Hints, Crawler Hints, Rocket Loader, IPv6 Compatibility, TLS 1.3, HTTP/3 (with QUIC), 0-RTT Connection Resumption, Browser Integrity Check, IP Geolocation, and Hotlink Protection.

= Visibility & Control =

* Dashboard with **live cache status**, connection status, and quick actions.
* **Activity Log** of purge and cache events, with optional Debug/Logging mode for troubleshooting.
* Import/Export your settings between sites, plus one-click Reset Settings to safe defaults.
* Uninstalling removes all plugin data by default; a "Keep Data on Uninstall" toggle lets you preserve settings and credentials for next time.

= How Does the Plugin Work? =

1. Connect your Cloudflare account using an **API Token** or **Global API Key**.
2. Select your domain and finish the setup.
3. Save your settings — that's it.

The plugin creates and keeps the required Cloudflare Cache Rule in sync automatically — no manual Cache Rule configuration required.

= API Token permissions =

For the recommended API Token setup, grant:

* Zone > Zone > Read
* Zone > Zone Settings > Edit
* Zone > Cache Rules > Edit
* Zone > Cache Purge > Purge
* Zone > Analytics > Read

A Global API Key also works if you'd rather use that.

= SuperFlare Pro =

Take Cloudflare caching further with SuperFlare Pro. Get advanced analytics, image optimization, advanced cache controls, performance optimizations, deeper WooCommerce/EDD controls, cache integrations, and priority email support.

* **Advanced analytics** and Edge Cache Status.
* **Image optimization** with automatic WebP delivery.
* **Advanced Preloader** with sitemap, menu, and content discovery.
* **Cache Reserve, Tiered Cache, and stale-while-revalidate.**
* **Advanced purging** and cache integrations.
* **Granular WooCommerce and EDD controls.**
* **JavaScript optimization**, link prefetching, DNS prefetch, and preconnect.
* **Heartbeat Control**, Server-Side Excludes, and marketing-parameter handling.
* Sync with other caching plugins and host caches.
* **Priority email support.**

[Learn more about SuperFlare Pro](https://superflare.pro/)

= Need more information? =

[Official Website](https://superflare.pro/) | [Documentation](https://superflare.pro/docs/) | [Contact & Support](https://superflare.pro/contact/) | [GitHub Repository](https://github.com/jumedeenkhan/wp-cloudflare-cache)

== Installation ==

1. Upload and activate SuperFlare Cache from the WordPress admin.
2. Go to the plugin's Setup Wizard.
3. Connect Cloudflare using an API Token or Global API Key.
4. Select your domain and finish the setup.
5. Review Cache Settings, Purge Cache, and Optimization to fit your site, then save.

The required Cloudflare Cache Rule is created and kept in sync automatically.

== Frequently Asked Questions ==

= Does this plugin work with the Cloudflare Free plan? =

Yes. The plugin uses a Cloudflare Cache Rule for full-page caching, which is available on the Free plan, and it's also works with Cloudflare premium plans.

= Do I need to manually create or edit a Cache Rule? =

No. The plugin creates, updates, and removes its own Cache Rule automatically as needed — including when you disable Full-Page Caching, disconnect, or change the connected domain.

= What happens if I delete the Cache Rule from my Cloudflare dashboard by mistake? =

It comes back automatically the next time you save any setting on the plugin's Settings page, as long as Full-Page Caching is still turned on.

= Will this ever touch other Cache Rules on my zone? =

No. The plugin only ever creates, updates, or removes the single rule it manages, identified by its own description. Any other rules on the zone are left exactly as they are, in their original order.

= Does the plugin cache logged-in users? =

No. Logged-in users, comment authors, and password-protected pages are bypassed at Cloudflare's edge.

= Does the plugin purge my entire cache on every update? =

No. Normal content updates use selective purging so unaffected cached pages stay in the cache. A full purge only happens after a theme/plugin update or when you trigger it manually.

= Can I exclude specific URLs, page types, or cookies from caching? =

Yes — Cache Exclude URLs, Exclude by Page Type (front page, archives, search, 404s, feeds, AMP, and more), and cookie/query-parameter exclusions are all available on the Cache Settings tab.

= Can I control Cloudflare features like Always Online or Rocket Loader from here? =

Yes, from the Optimization tab, without needing to log into the Cloudflare dashboard.

= Should I use an API Token or Global API Key? =

An API Token is recommended because it can be scoped to only the permissions the plugin needs. A Global API Key also works.

= Do I still need a separate caching plugin, or a Redis/Nginx page cache? =

No — Cloudflare's edge is your full-page cache once this plugin is active, so a separate origin-level page cache is redundant and best turned off. An object cache (like Redis Object Cache) for database queries is unrelated and fine to keep running.

= Is my Cloudflare API Token or Key stored securely? =

Yes. Credentials are stored in your site's own database like any other WordPress setting, are never exposed on the front end, and are only sent to Cloudflare's API over HTTPS.

= Does this work with WooCommerce or other e-commerce plugins? =

Yes. Use the Exclude Cookies setting to bypass cart/session cookies (e.g. `woocommerce_*`) so carts, checkouts, and account pages are never served from cache.

= What happens if Cloudflare's API is temporarily unreachable? =

The plugin fails safely: it never overwrites or removes existing Cache Rules on a failed or unconfirmed API response, and your site keeps working normally either way.

= Does deactivating the plugin affect my live Cloudflare cache? =

Deactivating removes the managed Cache Rule from Cloudflare so caching stops immediately. Reactivating recreates it right away if you're already connected, and takes you back to Settings; otherwise it opens the Setup Wizard.

== Screenshots ==

1. SuperFlare Cache settings page.
2. SuperFlare Cache dashboard with live analytics, and activity log.
3. Cache Control and Exclusions settings.
4. Cache Purging settings and options.
5. Cloudflare CDN Optimization control.
6. Cloudflare plugin Advanced settings.
7. Cloudflare connection and API setup.

== Upgrade Notice ==

= 2.0.0 =

Cache Rule reliability, security, and uninstall-behavior improvements, including a fix for Cache Rule creation failing on brand-new Cloudflare zones. Recommended update for all sites.

== Changelog ==

= 2.0.0 =

* Improved Cloudflare Cache Rule reliability and automatic synchronization.
* Improved cache bypass and security handling for admin, REST API, AJAX, and query-string requests.
* Improved selective purging and reduced redundant Cloudflare API requests.
* Fixed cache purging for permanently deleted posts.
* Improved activation, deactivation, and uninstall behavior.
* Improved Browser Cache TTL management through the Cache Rule.
* General security, performance, and UI improvements.

= 1.8.0 =

* Fixed duplicate Cloudflare syncs and Browser Cache TTL issues.
* Fixed Reset Settings defaults and improved settings reliability.

= 1.7.0 =

* Added broader cookie bypass support and REST API (/wp-json) bypass.

= 1.6.0 =

* Improved settings security, cache purging, domain detection, and Cloudflare request handling.
* Fixed deactivation, reset, admin bar, and API Token issues.
* Improved sitemap and cache bypass handling.

= 1.5 =

* Added Cloudflare API Token authentication and automatic domain selection.
* Migrated full-page caching from Page Rules to Cloudflare Cache Rules.
* Added logged-in user, cookie, URL, sitemap, and robots.txt cache exclusions.
* Added Browser and Edge Cache TTL controls and improved cache purging.
* Added connection status, Reset Settings, and improved security and error handling.

= 1.2.1 =

* Updated license details.

= 1.2 =

* Added homepage, custom URL, latest post, and full cache purge options.
* Added Page Rule creation, Browser Cache TTL, and block-editor purge support.
* Improved cache exclusions, comment purging, and WordPress compatibility.

= 1.0.3 =

* Tested up to WordPress 5.8.

= 1.0.1 =

* Updated max-age behavior for Cloudflare caching.

= 1.0 =

* Initial release.
