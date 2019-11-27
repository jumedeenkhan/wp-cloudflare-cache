<?php

/**
 * WP Cloudflare Cache Plugin
 * Created by Jumedeen khan.
 * User: jumedeenkhan
 */

//* WPCC Hooks
add_action('admin_init', 'wpcc_register_settings');
add_action('admin_menu', 'wpcc_register_options');
add_action('edit_post', 'wpcc_clearCache');
add_action('transition_post_status', 'wpcc_clearHome', 10, 3);
add_filter('plugin_action_links_' . WPCC_PLUGIN_BASENAME, 'wpcc_links');

// WPCC Expires Header
add_action('init', 'wpcc_sitemap_http_headers', 999);
add_action('template_redirect', 'wpcc_no_cache_for_login', 999);

//* Class for constructor
function wpcc_purgeCache($path, $zone_id) {
    $data       = array(
        "files" => $path
    );
    $result     = wp_remote_post("https://api.cloudflare.com/client/v4/zones/$zone_id/purge_cache", array(
        'body' => json_encode($data),
        'headers' => array(
            'X-Auth-Email' => get_option('cf_email_value'),
            'X-Auth-Key' => get_option('cf_key_value'),
            'Content-Type' => 'application/json'
        )
    ));
    $arr_result = json_decode($result['body'], true);
    if (isset($arr_result['success']));
}

function wpcc_getZoneID($domain) {
    $result = wp_remote_get("https://api.cloudflare.com/client/v4/zones", array(
        'headers' => array(
            'X-Auth-Email' => get_option('cf_email_value'),
            'X-Auth-Key' => get_option('cf_key_value'),
            'Content-Type' => 'application/json'
        )
    ));
    
    $arr_result = json_decode($result['body'], true);
    if (isset($arr_result['success'])) {
        foreach ($arr_result['result'] as $r) {
            if ($r['name'] == $domain) {
                return $r['id'];
            }
        }
        
        return false;
    }
    
    return false;
}

function wpcc_clearHome($new_status, $old_status, $post_id) {
    if ($new_status == 'trash' || $new_status == 'publish' && $old_status != 'publish') {
        $purge_paths = array();
        $post_url    = rtrim(get_permalink($post_id), '/');
        
        // cloudflare settings
        $cf_key_value   = get_option('cf_key_value');
        $cf_email_value = get_option('cf_email_value');
        
        $arr_url = parse_url($post_url);
        $domain  = str_replace('www.', '', $arr_url['host']);
        
        array_push($purge_paths, get_site_url() . "/");
        array_push($purge_paths, get_site_url());
        
        array_push($purge_paths, get_site_url() . "/blog/");
        array_push($purge_paths, get_site_url());
        
        if (strlen($cf_key_value) > 0 && strlen($cf_email_value) > 0) {
            $zone_id = wpcc_getZoneID($domain);
            if (wpcc_purgeCache($purge_paths, $zone_id)) {
                //
            }
        }
    }
}

function wpcc_clearCache($post_id) {
    $purge_paths = array();
    $post_url    = rtrim(get_permalink($post_id), '/');
    
    // check post type to avoid calling twice
    if ((wp_is_post_revision($post_id) || wp_is_post_autosave($post_id))) {
        return false;
    }
    
    // cloudflare settings
    $cf_key_value   = get_option('cf_key_value');
    $cf_email_value = get_option('cf_email_value');
    
    $cf_maxage_value = get_option('cf_maxage_value');
    
    $arr_url = parse_url($post_url);
    $domain  = str_replace('www.', '', $arr_url['host']);
    
    array_push($purge_paths, $post_url);
    array_push($purge_paths, $post_url . "/");
    
    if (strlen($cf_key_value) > 0 && strlen($cf_email_value) > 0) {
        $zone_id = wpcc_getZoneID($domain);
        if (wpcc_purgeCache($purge_paths, $zone_id)) {
            //
        }
    }
}

function wpcc_register_settings() {
    // save cloudflare settings
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        
        $cf_key_value   = isset($_POST['cf_key_value']) ? sanitize_text_field($_POST['cf_key_value']) : null;
        $cf_email_value = isset($_POST['cf_email_value']) ? sanitize_text_field($_POST['cf_email_value']) : null;
        
        $cf_maxage_value = isset($_POST['cf_maxage_value']) ? sanitize_text_field($_POST['cf_maxage_value']) : null;
        
        add_option('cf_key_value', $cf_key_value);
        register_setting('cachepurger_options_group', 'cf_key_value');
        add_option('cf_email_value', $cf_email_value);
        register_setting('cachepurger_options_group', 'cf_email_value');
        
        add_option('cf_maxage_value', $cf_maxage_value);
        register_setting('cachepurger_options_group', 'cf_maxage_value');
    }
}

function wpcc_register_options() {
    add_options_page('WPCC Settings', 'WP Cloudflare Cache', 'manage_options', 'wpcc-cache', 'wpcc_options');
}

function wpcc_links($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=wpcc-cache') . '">' . __('Settings') . '</a>';
    array_unshift($links, $settings_link);
    
    return $links;
}

function wpcc_options() {
    
    global $wpdb;
    global $wpcc_tablename_log;
    
    // get tablename
    $table_name = $wpdb->prefix . $wpcc_tablename_log;
    
    // check if user has permission
    if (current_user_can('activate_plugins') == false && current_user_can('edit_theme_options') == false && current_user_can('manage_options') == false) {
        print('<div class="alert notice"><p><span style="color:red">Access Denied:</span> Unfortunately, you are does not allow to access this plugin page. Please use an administrator account to make this changes</p></div>');
    } else {
?>
<div class="wrap">
   <h2>WP CloudFlare Cache Settings</h2>
    <div class="notice notice-success is-dismissible">
            <p>You will need to enter your Email Address and API key from your <a target="_blank" href="https://www.cloudflare.com/my-account.html">Cloudflare Account</a></p>
            <button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>
   <form method="post" action="options.php">
      <?php
        settings_fields('cachepurger_options_group');
?>
     <table class="form-table">
          <tbody>
         <tr>
            <th scope="row">E-mail Address:</th>
            <td>
                <input class="regular-text code" type="text" id="email" name="cf_email_value" value="<?php
        echo get_option('cf_email_value');
?>" required>
             </td>
         </tr>
         <tr>
            <th scope="row">Global API Key:</th>
             <td>
                <input autosuggest="true" class="regular-text code" type="password" id="API_key" name="cf_key_value" value="<?php
        echo get_option('cf_key_value');
?>" required>
            <button id="visibility_toggle" type="button" class="button button-secondary wp-hide-pw hide-if-no-js" data-toggle="0" data-label="Show">
                <span class="dashicons dashicons-visibility"></span>
                <span class="text">Show</span>
            </button>'
             </td>
         </tr>
          </tbody>
      </table>
   <h2>Add Expires Header Value</h2>
      <table class="form-table">
          <tbody>
         <tr>
            <th scope="row">Cache-Control max-age:</th>
            <td>
                <input class="regular-text code" type="number" id="wpcc-expire" name="cf_maxage_value" min="300" value="<?php
        echo get_option('cf_maxage_value');
?>" required>
                <p class="description" id="tagline-description"><?php
        _e('Value will be in seconds. Example:- 7 days = 604800 seconds', 'wp-cloudflare-super-page-cache');
?></p>
             </td>
         </tr>
          </tbody>
      </table>

      <?php
        submit_button();
?>
  </form>
</div>
<div class="wpcc-lrs">
	<p><b>Note:- </b>After setup this plugin, you will need to create "Cache everything" page rule on cloudflare.</p>
	<p>If you need help in setup plugin, please read our guideline at <a href="https://www.mozedia.com/cloudflare-cache-everything-for-wordpress/" target="_blank">Mozedia.com</a> [English] or <a href="https://www.supportmeindia.com/cloudflare-cache-everything-for-wordpress/" target="_blank">SupportMeIndia.com</a> [Hindi].</p>
</div>
<?php
    }
}

// Enable Browser Cache for sitemap
function wpcc_sitemap_http_headers() {
    // Bypass sitemap
    if (strcasecmp($_SERVER['REQUEST_URI'], "/sitemap_index.xml") == 0 || preg_match("/[a-zA-Z0-9]-sitemap.xml$/", $_SERVER['REQUEST_URI'])) {
        header("Cache-Control: max-age=5");
    }
}

// Enable Browser Cache for HTML Pages
function wpcc_no_cache_for_login() {
    $wpcc_maxage = get_option('cf_maxage_value');
    
    header_remove("Cache-Control");
    header("Cache-Control: max-age=$wpcc_maxage");
    
    if (is_admin() || is_user_logged_in() || is_feed()) {
        header("Cache-Control: no-cache, must-revalidate, max-age=0");
    }
    
}
