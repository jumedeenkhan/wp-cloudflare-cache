=== Cloudflare Page Cache for WordPress ===
Contributors: jumedeenkhan
Tags: cloudflare, cloudflare cache, cloudflare page cache, improve speed, improve performance
Donate link: https://www.paypal.me/jumedeenkhan
Requires at least: 4.7
Tested up to: 6.5.3
Requires PHP: 5.6
Stable tag: 1.2.1
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

WP Cloudflare Cache plugin built for cache html pages on Cloudflare free plan and purge cache only when post or page updated.

== Description ==
WP Cloudflare Cache help you to use Cloudflare full page cache and purge specific page cache on update, new comment added, approved or deleted.

Normally, Cloudflare cache only static content (e.g. CSS, JS and Images) but not cached to HTML pages. So we build this plugin to cache everything and bypass for logged in users and comment author even on Cloudflare free plan.

This plugin specially build for Cloudflare Free Plan but you can also use it on Cloudflare Pro and Enterprise Plans.

= Additional Features =

* Automatic purge cache.
* Purge specific page cache only.
* Control HTML page cache.
* Never cache logged in users.
* Purge cache when new post publish.
* Purge cache when post/page updated.
* Purge cache for comment added, approved.
* Purge Homepage, blog page cache for new post.
* Purce cache for custom URLs pages.
* Only specific page cache purged not all cache.
* Of course, available on [GitHub](https://github.com/jumedeenkhan/wp-cloudflare-cache)<br />

Using this plugin you can improve your website performance and SEO by enabling page caching. It's compatible with every themes and WordPress versions.

This plugin will cache your site's webpages and static files to the Cloudflare CDN and make speed up your WordPress site on world wide content delivery network.

With [more than 200 CDN edge locations](https://www.cloudflare.com/network/) provided by Cloudflare, your website will be served from the nearest Cloudflare CDN location. This will reduce your website loading speed time and help to get higher ranking in search results.

= How does the plugin work? =
This plugin takes full advantage of Cloudflare Free Plan, so you don’t need a buy Cloudflare Pro Plan. But if you like to use features like Cloudflare image optimization, WAF (Web Application Firewall) etc. then you can buy Cloudflare Pro plan to enable those features in your Cloudflare account.
Here is a small summary of plugin features.

The free Cloudflare plan allows you to enable a page cache by entering the Cache Everything page rule, greatly improving response times but it's not support dynamic website update such as WordPress. You can use cache everything page rule but its not bypass logged in user, ajax requests and comments updates. But this plugin make all of this possible.

== Features and Advantages ==
* Developed to cache HTML pages on Cloudflare Free Plan.
* Takes full advantage of Cloudflare Cache Everything Page Rule.
* Automatically purge specific page cache on post/page update.
* Bypass logged-in users and purge on specific page or post update.
* Purge cache on new post insert, post edit and moving in draft or trash.
* Purge parent page cache on new comment added, approved or deleted.
* Purge homepage, category, tags, blog page on latest post published.
* Ability to select and customize what you want to cache and what you don’t (plugin settings).
* Ability to purge only HTML pages of your website rather than purging everything (like css, js, images).
* Ability to purge entire site cache via clicking on single button. (plugins settings).
* Ability to purge only latest post cache via clicking on single button. (plugins settings).
* Ability to exclude custom URLs pages from being cached on individual page/post bases
* Detailed FAQ section covering all kind of questions (plugin settings – FAQ tab)

== Improve your site performance ==

Normally Cloudflare purge all entire cache and after that all pages are slow down, because Cloudflare revalidate all webpages cache again for them.

But this plugin purge only specific page cache. For example, your site have three post A,B,C and now you updated page A then cache only clear for page A not for B,C and others.

That means your all page are not affect and Cloudflare revalidated only purge page cache. It's mean your site loaded fast always and its good for better rankings.

= Missing something? =
If you need more additional feature, [let me know](https://www.mozedia.com/contact/)

== Installation ==

= Installing this plugin - Simple =
1. In your WordPress admin panel, go to *Plugins > New Plugin*, search for **WP Cloudflare Cache** and click "*Install now*"
2. Otherwise, download plugin and upload to your plugins directory, which usually is `/wp-content/plugins/`.
3. Activate plugin, do some settings, All Done!.

= Not enough, you need more? =

After active the plugin you need create a page rule on Cloudflare.
Exam:- `https://example.com/*` and then create a "cache everything" page rule.

We have added "Installation Guide" section in plugin settings, you can follow installation steps to setup.

For more help you can see our [WP Cloudflare Cache Plugin Setup Guide](https://www.mozedia.com/cloudflare-cache-everything-for-wordpress/) documents.

== Frequently Asked Questions ==
= A question that someone might have =
An answer to that question.

= Why I use this plugin? =
Because it offer you use Cloudflare full page cache on free plan.

= Does this plugin cache logged in user? =
No, this plugin make bypass all logged in user.

= Does this affect WordPress commenting system? =
No, this plugin clear single post cache for comments users.

= How this plugin work and clear cache? =
This plugin make bypass all logged in user, comment author and cache pages only for visitors. When you publish new post and update any post, their specific page/post cache will be clear automatically, not all.

So this plugin offer you use Cloudflare cache for HTML pages without affecting site dynamic content like new post update, comments etc. and make your site super fast. Your site loaded under 100ms in worldwide.

== Screenshots ==

1. WP Cloudflare Cache Plugin Settings
2. Plugin Setup Installation Guide

== Changelog ==

= 1.2.1 =
* Change licence details

= 1.2 =
* Added new homepage purge button.
* Add option exclude custom URLs.
* Added purge latest post button.
* Added purge cache everything button.
* Added create page rule button.
* Added set browser cache ttl button.
* Added ability to purge on block editor.
* Don't cache for search and 404 page.
* Purge on comment added, approved, deleted.
* PHP 8.3 improvement for new version.
* Some bug fix for new WordPress version.
* Tested for WordPress 6.5.3.

= 1.0.3 =
* Tested for WordPress 5.8.

= 1.0.1 =
* Change max-age value to control only Cloudflare cache.

= 1.0 =
* Initial release.

== Upgrade Notice ==

= 1.2.1 =
Change licence details

= 1.2 =
Added new homepage purge button.
Add option exclude custom URLs.
Added purge latest post button.
Added purge cache everything button.
Added create page rule button.
Added set browser cache ttl button.
Added ability to purge on block editor.
Don't cache for search and 404 page.
Purge on comment added, approved, deleted.
PHP 8.3 improvement for new version.
Some bug fix for new WordPress version.
Tested for WordPress 6.5.3.

= 1.0 =
This is the first version of this plugin.
