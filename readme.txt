=== WP Cloudflare Cache ===
Contributors: jumedeenkhan
Tags: cloudflare, cache, pagespeed, performance, cdn
Donate link: https://buymeacoffee.com/jumedeenkhan
Requires at least: 5.8
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.5.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Speed up your WordPress website via Cloudflare CDN with full-page caching – improve SEO, performance, and Core Web Vitals.

== Description ==

WP Cloudflare Cache adds real full-page caching to your WordPress site using a Cloudflare Cache Rule — including on the Cloudflare Free plan.

It automatically creates and manages a single Cache Rule that caches your public pages while safely bypassing logged-in users, comment authors, password-protected pages, WordPress admin requests, and any URLs you exclude.

When content changes, the plugin purges only the affected URL instead of clearing your entire Cloudflare cache.

= Key Features =

* Full-page HTML caching with a single Cloudflare Cache Rule.
* Works with the Cloudflare Free plan.
* Bypasses logged-in users, comment authors, and password-protected pages at Cloudflare's edge.
* Automatically excludes WordPress admin and login requests.
* Selective cache purging when posts, pages, or comments change.
* Optional homepage and Custom Purge URLs.
* Cache Exclude URLs for paths such as `/cart/` and `/checkout/`.
* Separate Browser Cache TTL and Edge Cache TTL controls.
* Cache-Control `max-age` and `s-maxage` controls.
* Optional bypass for `sitemap.xml` and `robots.txt`.
* Connect using a scoped API Token or Global API Key.
* Automatically loads available Cloudflare domains.
* Warns when the selected domain is not proxied through Cloudflare.
* One-click Purge All Cache, Purge Latest Post, and Reset Settings.
* Automatically migrates saved credentials from older versions.

= Why use WP Cloudflare Cache? =

By default, Cloudflare mainly caches static assets such as CSS, JavaScript, and images. Your WordPress HTML pages may still reach your server.

WP Cloudflare Cache creates a Cache Rule that allows Cloudflare to cache eligible public pages while keeping requests that must remain dynamic outside the cache.

Instead of purging your entire cache whenever content changes, only the affected URL is purged. Other cached pages remain available from Cloudflare.

= How it works =

1. Connect your Cloudflare account using an API Token or Global API Key.
2. Select your domain.
3. Save your settings.
4. The plugin automatically creates and manages the required Cloudflare Cache Rule.

No manual Cache Rule configuration is required.

= API Token permissions =

For the recommended API Token setup, grant the permissions required by the plugin:

* Zone > Cache Purge
* Zone > Cache Rules
* Zone > Zone Settings

You can also use a Global API Key.

= Upgrade from older versions =

Older versions used a Global API Key. Your saved Cloudflare credentials are migrated automatically when updating.

If your existing credentials cannot be verified, the plugin will display a notice asking you to reconnect Cloudflare.

= More information =

* [GitHub Repository](https://github.com/jumedeenkhan/wp-cloudflare-cache)
* [Setup Guide](https://www.mozedia.com/cloudflare-cache-everything-for-wordpress/)
* [Contact & Support](https://www.mozedia.com/contact/)

== Installation ==

1. Upload and activate **WP Cloudflare Cache** from the WordPress admin.
2. Go to the plugin settings page.
3. Connect Cloudflare using an API Token or Global API Key.
4. Select your domain.
5. Configure your cache settings and save.

The required Cloudflare Cache Rule will be created automatically.

== Frequently Asked Questions ==

= Does this work with the Cloudflare Free plan? =

Yes. The plugin uses a Cloudflare Cache Rule to enable full-page caching and is designed to work with the Cloudflare Free plan.

= Do I need to manually create a Cache Rule? =

No. The plugin automatically creates and updates the required Cache Rule when you save your settings.

= Does the plugin cache logged-in users? =

No. Logged-in users, comment authors, and password-protected pages are bypassed at Cloudflare's edge.

= What happens when I update a post or page? =

The affected URL is purged automatically. Optional homepage and Custom Purge URLs can also be purged.

= Does the plugin purge my entire cache on every update? =

No. Normal content updates use selective purging so unaffected cached pages can remain in the cache.

= Can I exclude specific URLs from caching? =

Yes. Add paths such as `/cart/` or `/checkout/` to Cache Exclude URLs.

= Can I bypass sitemap.xml or robots.txt? =

Yes. Both `sitemap.xml` and `robots.txt` have separate bypass options.

= Should I use an API Token or Global API Key? =

An API Token is recommended because it can be limited to only the permissions required by the plugin. A Global API Key also works.

= Will my existing settings work after upgrading? =

Saved Cloudflare credentials from older versions are migrated automatically when possible.

== Screenshots ==

1. WP Cloudflare Cache settings page.
2. Cloudflare installation guide.

== Upgrade Notice ==

= 1.5.0 =

Major update introducing Cloudflare Cache Rules, API Token authentication, edge-level cache bypass, TTL controls, Cache Exclude URLs, and improved security and settings management.

== Changelog ==

= 1.5.0 =

* Added Cloudflare API Token authentication alongside Global API Key support.
* Added automatic Cloudflare domain selection.
* Changed full-page caching from legacy Page Rules to a Cloudflare Cache Rule.
* Added edge-level bypass for logged-in users, comment authors, and password-protected pages.
* Added Browser Cache TTL and Edge Cache TTL controls.
* Added Cache-Control `max-age` and `s-maxage` settings.
* Added Cache Exclude URLs.
* Added separate bypass options for `sitemap.xml` and `robots.txt`.
* Added a warning when the selected domain is not proxied through Cloudflare.
* Added connection status and Reset Settings.
* Added automatic migration for saved credentials from older versions.
* Improved selective cache purging.
* Improved AJAX permission and nonce protection.
* Fixed zone detection for apex domains.
* Fixed settings sanitization and escaping issues.
* Fixed PHP warnings caused by failed Cloudflare API or network requests.
* Removed an unrelated filter that disabled the block editor.
* Updated the license to GPLv2 or later.

= 1.2.1 =

* Updated license details.

= 1.2 =

* Added homepage purge.
* Added Custom Purge URLs.
* Added Purge Latest Post and Purge All Cache actions.
* Added Page Rule creation.
* Added Browser Cache TTL settings.
* Added support for cache purging from the block editor.
* Excluded search and 404 pages from caching.
* Added cache purging for comment activity.
* Improved compatibility with PHP 8.3.
* Fixed compatibility issues with newer WordPress versions.
* Tested up to WordPress 6.5.3.

= 1.0.3 =

* Tested up to WordPress 5.8.

= 1.0.1 =

* Updated the `max-age` behavior for Cloudflare caching.

= 1.0 =

* Initial release.
