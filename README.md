# SuperFlare Cache – Cloudflare Full-Page Cache & CDN Optimization

Speed up your WordPress website with Cloudflare CDN, full-page cache, automatic cache purging, and performance optimization.

SuperFlare Cache lets you use **Cloudflare Full-Page Cache** on the free Cloudflare plan while safely bypassing logged-in users, administrators, comment authors, password-protected pages, and other requests that should never be cached.

## Features

### 🚀 Full-Page Caching

- Use **Cloudflare Full-Page Cache** with a single self-managed Cloudflare Cache Rule.
- No manual Cache Rule configuration is required.
- The plugin automatically creates, updates, and removes its own Cache Rule as needed.
- Existing Cloudflare Cache Rules are preserved and never reordered or overwritten.
- Automatically bypasses:
  - Logged-in users
  - Comment authors
  - Password-protected pages
  - Admin requests
  - Other excluded requests
- Non-2xx responses, including redirects and errors, are not cached.

Works with the **Cloudflare Free plan** and scales with Pro, Business, and Enterprise plans.

### 🎛️ Cache Control & Exclusions

- Separate **Browser Cache TTL** and **Edge Cache TTL** controls.
- Cloudflare Caching Level control.
- Exclude specific page types from caching:
  - Front page
  - Posts page
  - Archives
  - Feeds
  - Search results
  - 404 pages
  - AMP pages
- Automatically bypass:
  - AJAX requests (`admin-ajax.php`)
  - REST API (`wp-json`)
  - `sitemap.xml`
  - `robots.txt`
  - URLs containing query strings
- Exclude specific cookies from caching, including WooCommerce and EDD cart/session cookies.
- Exclude custom URL parameters from the cache key.
- Exclude specific URLs with wildcard support, such as:
  - `/cart/`
  - `/checkout/`

### 🧹 Selective Cache Purging

SuperFlare Cache avoids unnecessary full-cache purges.

- Purge only the affected URL when a post or page is updated.
- Purge affected content when comments are added or updated.
- Optionally purge:
  - Homepage
  - Archive pages
  - Feeds
  - AMP URLs
- Automatically performs a full purge after a theme or plugin update.
- Add **Custom Purge URLs** for content not covered by the automatic rules.
- Purge requests are queued and processed through a single coalesced cron job to reduce unnecessary Cloudflare API requests.
- **Remote Purge** through a secure secret Cron URL.
- Role-based permissions for cache purging.
- Quick Actions for:
  - Purge Latest Post
  - Custom Purge
  - Purge Entire Cache

### 🔥 Cache Preloader

Keep your cache warm after purging.

- Automatically re-warm pages after a purge.
- Schedule preloading of latest posts.
- Preload URLs discovered from selected sitemaps.
- Configure your preferred cron schedule.
- Run the preloader manually with **Run Preloader Now**.

### ⚡ Cloudflare Optimization

Control Cloudflare performance, protocol, and security features directly from WordPress:

- Always Online
- Early Hints
- Crawler Hints
- Rocket Loader
- IPv6 Compatibility
- TLS 1.3
- HTTP/3 with QUIC
- 0-RTT Connection Resumption
- Browser Integrity Check
- IP Geolocation
- Hotlink Protection

### 📊 Visibility & Control

- Dashboard with live cache status.
- Cloudflare connection status.
- Quick Actions.
- Activity Log for cache and purge events.
- Optional Debug/Logging mode for troubleshooting.
- Import and Export settings between sites.
- One-click Reset Settings.
- Uninstall behavior can be configured:
  - Remove plugin data by default.
  - Keep settings and credentials using **Keep Data on Uninstall**.

## How Does It Work?

1. Connect your Cloudflare account using an **API Token** or **Global API Key**.
2. Select your domain.
3. Finish the setup.
4. Save your settings.

That's it.

SuperFlare Cache automatically creates and maintains the required Cloudflare Cache Rule. No manual Cache Rule configuration is required.

## API Token Permissions

For the recommended Cloudflare API Token setup, grant:

- `Zone > Zone > Read`
- `Zone > Zone Settings > Edit`
- `Zone > Cache Rules > Edit`
- `Zone > Cache Purge > Purge`
- `Zone > Analytics > Read`

A **Global API Key** is also supported.

## SuperFlare Pro

Take Cloudflare caching and WordPress performance further with **SuperFlare Pro**.

SuperFlare Pro adds:

- Advanced analytics and Edge Cache Status
- Image optimization with automatic WebP delivery
- Advanced Preloader with sitemap, menu, and content discovery
- Cache Reserve
- Tiered Cache
- Stale-while-revalidate
- Advanced cache purging
- Cache integrations
- Granular WooCommerce and EDD controls
- JavaScript optimization
- Link prefetching
- DNS prefetch
- Preconnect
- Heartbeat Control
- Server-Side Excludes
- Marketing-parameter handling
- Synchronization with other caching plugins and host caches
- Priority email support

**[Learn more about SuperFlare Pro](https://superflare.pro/)**

## Installation

1. Upload and activate **SuperFlare Cache** from the WordPress admin.
2. Open the plugin's **Setup Wizard**.
3. Connect Cloudflare using an API Token or Global API Key.
4. Select your domain and finish the setup.
5. Review **Cache Settings**, **Purge Cache**, and **Optimization** settings.
6. Save your settings.

The required Cloudflare Cache Rule is created and kept synchronized automatically.

## Frequently Asked Questions

### Does this plugin work with the Cloudflare Free plan?

Yes. SuperFlare Cache uses a Cloudflare Cache Rule for full-page caching, which is available on the Cloudflare Free plan. It also works with Cloudflare premium plans.

### Do I need to manually create or edit a Cache Rule?

No. The plugin automatically creates, updates, and removes its own Cache Rule when required, including when you disable Full-Page Caching, disconnect Cloudflare, or change the connected domain.

### What happens if I delete the Cache Rule from my Cloudflare dashboard?

If Full-Page Caching is still enabled, the plugin recreates its managed Cache Rule the next time you save a setting on the plugin's Settings page.

### Will this plugin touch my other Cloudflare Cache Rules?

No. The plugin only manages its own Cache Rule. Existing rules are preserved in their original order.

### Does the plugin cache logged-in users?

No. Logged-in users, comment authors, and password-protected pages are bypassed at Cloudflare's edge.

### Does the plugin purge the entire cache on every update?

No. Normal content updates use selective purging so unaffected cached pages remain cached.

A full purge is performed after a theme or plugin update, or when you manually trigger a full purge.

### Can I exclude specific URLs, page types, cookies, or parameters?

Yes. You can configure:

- Cache Exclude URLs
- Page Type exclusions
- Cookie exclusions
- Query-parameter exclusions

### Can I control Cloudflare features from WordPress?

Yes. The **Optimization** tab lets you control supported Cloudflare performance, protocol, and security features without logging into the Cloudflare dashboard.

### Should I use an API Token or Global API Key?

An **API Token is recommended** because it can be restricted to only the permissions required by the plugin.

A Global API Key is also supported.

### Do I still need another full-page caching plugin?

No. Once SuperFlare Cache is active, Cloudflare's edge becomes your full-page cache, so a separate origin-level page cache is generally redundant and should be disabled.

An object cache such as Redis Object Cache is different and can still be used for database queries.

### Is my Cloudflare API Token or Key stored securely?

Credentials are stored in your site's own WordPress database, are not exposed on the front end, and are only sent to Cloudflare's API over HTTPS.

### Does this work with WooCommerce and other e-commerce plugins?

Yes. Use the **Exclude Cookies** setting to bypass cart and session cookies such as `woocommerce_*`.

Cart, checkout, and account pages should be excluded from full-page caching.

### What happens if Cloudflare's API is temporarily unavailable?

The plugin fails safely. It does not overwrite or remove existing Cache Rules when an API operation fails or cannot be confirmed.

Your website continues to operate normally.

### What happens when the plugin is deactivated?

Deactivating the plugin removes its managed Cache Rule from Cloudflare so full-page caching stops.

When the plugin is reactivated:

- If Cloudflare is already connected, the Cache Rule is recreated.
- Otherwise, the Setup Wizard is opened.

## Screenshots

1. SuperFlare Cache settings page.
2. SuperFlare Cache dashboard with live analytics and activity log.
3. Cache Control and Exclusions settings.
4. Cache Purging settings and options.
5. Cloudflare CDN Optimization controls.
6. Advanced plugin settings.
7. Cloudflare connection and API setup.

## Requirements

- WordPress: **5.8 or higher**
- PHP: **7.4 or higher**
- Cloudflare account

## License

SuperFlare Cache is licensed under the **GPLv2 or later**.

See the [GNU General Public License v2.0](https://www.gnu.org/licenses/gpl-2.0.html).

## Links

- **Website:** https://superflare.pro/
- **Documentation:** https://superflare.pro/docs/
- **Support:** https://superflare.pro/contact/
- **GitHub:** https://github.com/jumedeenkhan/wp-cloudflare-cache/
- **SuperFlare Pro:** https://superflare.pro/
- **Donate:** https://buymeacoffee.com/jumedeenkhan

## Changelog

### 2.0.0

- Improved Cloudflare Cache Rule reliability and automatic synchronization.
- Improved cache bypass and security handling for admin, REST API, AJAX, and query-string requests.
- Improved selective purging and reduced redundant Cloudflare API requests.
- Fixed cache purging for permanently deleted posts.
- Improved activation, deactivation, and uninstall behavior.
- Improved Browser Cache TTL management through the Cache Rule.
- General security, performance, and UI improvements.

### 1.8.0

- Fixed duplicate Cloudflare syncs and Browser Cache TTL issues.
- Fixed Reset Settings defaults and improved settings reliability.

### 1.7.0

- Added broader cookie bypass support.
- Added REST API (`/wp-json`) bypass.

### 1.6.0

- Improved settings security, cache purging, domain detection, and Cloudflare request handling.
- Fixed deactivation, reset, admin bar, and API Token issues.
- Improved sitemap and cache bypass handling.

### 1.5

- Added Cloudflare API Token authentication and automatic domain selection.
- Migrated full-page caching from Page Rules to Cloudflare Cache Rules.
- Added logged-in user, cookie, URL, sitemap, and `robots.txt` cache exclusions.
- Added Browser and Edge Cache TTL controls.
- Improved cache purging.
- Added connection status and Reset Settings.
- Improved security and error handling.

### 1.2.1

- Updated license details.

### 1.2

- Added homepage, custom URL, latest post, and full cache purge options.
- Added Page Rule creation and Browser Cache TTL.
- Added block-editor purge support.
- Improved cache exclusions, comment purging, and WordPress compatibility.

### 1.0.3

- Tested up to WordPress 5.8.

### 1.0.1

- Updated max-age behavior for Cloudflare caching.

### 1.0

- Initial release.
