=== WP Cloudflare Cache ===
Contributors: jumedeenkhan
Tags: wp cloudflare cache, cloudflare cache, cloudflare, cloudflare page cache, cloudflare cache everything, cloudflare cache purge, cloudflare html cache, cloudflare purge, cache for cloudflare, cloudflare full page cache.
Donate link: https://www.paypal.me/iamjdk
Requires at least: 4.0
Tested up to: 5.3
Requires PHP: 5.6
Stable tag: 1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

WP Cloudflare cache plugin build for control cloudflare cache and purge only single page cache when post publish, updated and new comment are added.

== Description ==
WP Cloudflare cache plugin control HTML cache and purge specific page cache for new post, comment author and post update automatically.

Normally, Cloudflare cache only static content (Image, CSS, JS etc.) only and not cached to full pages. So we build this plugin to cache everything and bypass for logged in users and comment author even on cloudflare free plan.

Using this plugin you can improve your website performance by enabling page caching on Cloudlfare with free plan. It is compatible with every themes and WordPress versions.

= What this plugin can do for you =

This plugin offer you use cloudflare full page cache without affect logged in user and comment visitors.

= Automatic cache purge =

You no need clear cache after publish post or update post, because when you update any old post plugin purge their cache automatically.

Also when you publish new post, plugin purge Homepage and Blog page automatically. It's not enough plugin also clear cache when any comment are added, replied and deleted.

= Don't cache for logged in users =

This plugin especially builed for stop cloudflare to cache logged in users, also not cached for comment authors. that means your wordpress blog comment system not affected.

= Improve your site performance =

Normally cloudflare purge all entire cache and after that all pages are slow down, beause cloudflare revalidate cache again for them.

But this plugin purge only specific page cache. For example, your site have three post A,B,C and now you updated page A then cache only clear for page A not for B,C and others.

That means your all page are not affect and cloudflare revalidated only purge page cache. It's mean your site loaded fast always.

Here are plugin explained features.

= Additional features =

* Automatic cache purge.
* Purge specific page cache only.
* Control HTML page cache.
* Never cache logged in users.
* Purge cache when new post publish.
* Purge cache when post/page updated.
* Purge cache for comment author.
* Purge Homepage, blog page cache for new post.
* Only specific page cache purged not all cache.
* Of course, available on [GitHub](https://github.com/jumedeenkhan/wp-cloudflare-cache)<br />

= Missing something? =
If you need more additional feature, [let me know](https://www.mozedia.com/contact/)

== Installation ==

= Installing this plugin - Simple =
1. In your WordPress admin panel, go to *Plugins > New Plugin*, search for **WP Cloudflare Cache** and click "*Install now*"
2. Otherwise, download plugin and upload to your plugins directory, which usually is `/wp-content/plugins/`.
3. Activate plugin, All Done!.

= Not enough, you need more =

After active the plugin you need create a page rule on cloudflare.
Exam:- `https://example.com/*` and then setting cache level "cache everything".

For more help read our [setup guide here](https://www.mozedia.com/cloudflare-cache-everything-for-wordpress/),

Plugin setup guide also available in [Hindi Language](https://www.supportmeindia.com/cloudflare-cache-everything-for-wordpress/).

== Frequently Asked Questions ==
= A question that someone might have =
An answer to that question.

= Why I use this plugin? =
Because it offer you use cloudflare full page cache with cache purging.

= Does this plugin cache logged in user? =
No, this plugin make bypass all logged in user.

= Does this affect wordpress commenting system? =
No, this plugin clear clear for comments users.

= How this plugin work and clear cache? =
This plugin make bypass all logged in user, comment, author and cache pages only for visitors. When you publish new post and update any post, their specific page/post cache will be clear not all cache.

So this plugin offer you use cloudflare full cache without affecting site new post update, comments etc. and make your site super fast. Your site loaded under 100ms in worldwide.

== Screenshots ==

1. WP Cloudflare Cache Plugin Settings

== Changelog ==
= 1.0 =
* Initial release.

== Upgrade Notice ==
= 1.0 =
This is the first version of this plugin.
