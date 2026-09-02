<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class CFCA_Purge
 *
 * Handles all Cloudflare API calls: cache purging, cache rule setup, zone lookups.
 *
 * @package		CloudflareCache
 * @subpackage	Classes/CFCA_Purge
 * @author		Jumedeen Khan
 * @since		1.2
 */
class CFCA_Purge {
	
	private $instance   = null;
    private $email        = '';
    private $api_key      = '';
    private $zone_id      = '';
    private $page_rule_id = '';

	function __construct($cf_email, $cf_api_key, $instance) {
		$this->init();
		$this->instance = $instance;
		
        $this->email         = $cf_email;
        $this->api_key       = $cf_api_key;
		
		register_deactivation_hook( CFCA_PLUGIN_FILE, array( $this, 'deactivate_plugin' ) );
		
	}

	private function init() {
		
		add_action( 'wp_ajax_cfca_purge_everything', array( $this, 'cfca_purge_everything' ) );
		add_action( 'wp_ajax_cfca_purge_test_config', array( $this, 'cfca_purge_test_config' ) );
		
        add_action( 'transition_comment_status', array($this, 'purge_cache_on_approved'), PHP_INT_MAX, 3 );
        add_action( 'comment_post',              array($this, 'purge_cache_on_added'), PHP_INT_MAX, 3 );
        add_action( 'delete_comment',            array($this, 'purge_cache_on_deleted'), PHP_INT_MAX );
		
		$purge_actions = array(
            // before_delete_post, not deleted_post: by the time deleted_post fires the row is already
            // gone from wp_posts, so get_post() below returns null and there's no permalink left to purge.
            'before_delete_post',
            'wp_trash_post',
            'clean_post_cache',
            'edit_post',
            'publish_to_draft',
            'delete_attachment',
        );

        foreach ($purge_actions as $action) {
            add_action( $action, array($this, 'cfca_purge_cache_via_id'), PHP_INT_MAX, 2 );
        }
		
		add_action('wp_insert_post', array($this, 'cfca_wp_insert_post'), PHP_INT_MAX, 3);
		add_action('transition_post_status', array($this, 'cfca_post_updated'), PHP_INT_MAX, 3);
		
		add_action('cfca_purge_cache', array($this, 'cfca_run_scheduled_purge'), 10, 1);
	}
	
	public function cfca_wp_insert_post( $post_ID, $post, $update ) {
		if ( wp_is_post_revision( $post_ID ) || wp_is_post_autosave( $post_ID ) ) {
			return;
		}
		wp_schedule_single_event( time() + 2, 'cfca_purge_cache', [ $post ] );
	}
	
	public function cfca_post_updated( $new_status, $old_status, $post ) {
		wp_schedule_single_event( time() + 2, 'cfca_purge_cache', [ $post ] );
	}
	
	public  function cfca_purge_cache_via_id( $post_id ) {
		$post = get_post( $post_id );
		wp_schedule_single_event( time() + 2, 'cfca_purge_cache', [ $post ] );
	}
	
	public function purge_cache_on_added( $comment_ID, $comment_approved, $commentdata ) {
		
		if ( $this->instance->get('purge_on_comment') == 'on' ) {
			$post_id = $commentdata['comment_post_ID'];
			$post = get_post( $post_id );
			
			wp_schedule_single_event( time() + 2, 'cfca_purge_cache', [ $post ] );
		}
	}
	
	public function purge_cache_on_approved($new_status, $old_status, $comment) {
		
		if ( $this->instance->get('purge_on_comment') == 'on' ) {
			$post_id = $comment->comment_post_ID;
			$post = get_post( $post_id );
			
			wp_schedule_single_event( time() + 2, 'cfca_purge_cache', [ $post ] );
		}
		
	} 
	
	public function purge_cache_on_deleted( $comment_ID ) {
		
		if ( $this->instance->get('purge_on_comment') == 'on' ) {
			$comment = get_comment( $comment_ID );
			$post_id = $comment->comment_post_ID;
			$post = get_post( $post_id );
			
			wp_schedule_single_event( time() + 2, 'cfca_purge_cache', [ $post ] );
		}
	}

	/**
	 * Runs the scheduled (cron-triggered) purge and logs any failure, since this happens in the background
	 * with no AJAX response for the user to see.
	 * @param mixed $post
	 */
	public function cfca_run_scheduled_purge( $post = null ) {
		$result = $this->purge_cache( $post );
		
		if ( is_wp_error( $result ) && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- gated behind WP_DEBUG_LOG, the only way to surface a silent background (cron) purge failure.
			error_log( 'WP Cloudflare Cache: scheduled purge failed - ' . $result->get_error_message() );
		}
	}
	
	public function purge_cache( $post = null ) {
		
		if ( empty( $post ) ) {
			return new WP_Error( 'no_post', __( 'Nothing to purge.', 'wp-cloudflare-cache' ), [ 'status' => 400 ] );
		}
		
		if ( ! $this->has_credentials() ) {
			return new WP_Error( 'rest_forbidden', __( 'Please fill Cloudflare details.', 'wp-cloudflare-cache' ), [ 'status' => 401 ] );
		}
		
		$cf_zone_id = $this->instance->get_single_config( 'cf_zone_id', '' );
		
		if ( empty( $cf_zone_id ) ) {
			return new WP_Error( 'rest_forbidden', __( 'Please select your Cloudflare domain in Settings first.', 'wp-cloudflare-cache' ), [ 'status' => 401 ] );
		}

		$urls = [];
		
		if ( $post && $post != 'all' ) {

			// get_permalink( $post ), not $post->ID: passing the object lets WP use its already-loaded fields
			// directly instead of re-querying by ID — the only way this still resolves for a post that has
			// been permanently deleted by the time this scheduled (2s-delayed) purge actually runs.
			$page_url = get_permalink( $post );
			
			if ( filter_var( $page_url, FILTER_VALIDATE_URL ) ) {
				$urls[] = substr( $page_url, -1 ) === '/' ? substr( $page_url, 0, -1 ) : $page_url;
			}
			
			if ( $this->instance->get( 'purge_homepage' ) == 'on' ) {
				$home_url = home_url();
				$homepage_url = substr( $home_url, -1 ) === '/' ? substr( $home_url, 0, -1 ) : $home_url;
				
				$urls[] = trim( $homepage_url );
			}
			
			$purge_urls = trim( (string) $this->instance->get( 'purge_urls' ) );
			
			if ( '' !== $purge_urls ) {
				foreach ( preg_split( '/\r\n|\n|\r/', $purge_urls ) as $path ) {
					$path = trim( str_replace( array( '/*', '*' ), '', trim( $path ) ) );
					
					if ( '' === $path ) {
						continue;
					}
					
					$url = home_url( '/' . ltrim( $path, '/' ) );
					
					if ( filter_var( $url, FILTER_VALIDATE_URL ) ) {
						$urls[] = $url;
					}
				}
			}
			
			// Cloudflare's purge_cache endpoint accepts at most 30 URLs per request.
			$data = [ 'files' => array_slice( array_unique( $urls ), 0, 30 ) ];
		}
		
		if ( $post === 'all' ) {
			$data = ['purge_everything' => true];
		}

		$cf_headers = $this->get_api_headers();
		$cf_headers['body'] = json_encode( $data );
		
		$response = wp_remote_post(
			esc_url_raw( "https://api.cloudflare.com/client/v4/zones/$cf_zone_id/purge_cache" ),
			$cf_headers
		);
		
		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'cloudflare_error', $response->get_error_message(), [ 'status' => 500 ] );
		}
		
		$response_data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $response_data['success'] ) ) {
			$error = $response_data['errors'][0] ?? [ 'message' => 'Unknown Cloudflare error.', 'code' => 500 ];
			return new WP_Error( 'cloudflare_error', $error['message'], [ 'status' => $error['code'] ] );
		}

		$purged = ! empty( $data['files'] ) ? implode( ', ', $data['files'] ) : 'everything';

		return (object) [
			'success' => $response_data['success'],
			'message' => "Cloudflare Cache purged for $purged successfully. Please allow up to 30 seconds for changes to take effect.",
		];
	}   	
	
	// Sends a purge_cache() result (WP_Error or {message}) as { success, data: { message, type } }.
	private function cfca_send_purge_result( $result ) {
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array(
				'message' => $result->get_error_message(),
				'type'    => 'error',
			) );
		}

		wp_send_json_success( array(
			'message' => $result->message,
			'type'    => 'success',
		) );
	}

	public function cfca_purge_everything() {
		check_ajax_referer( 'cfca_ajax_nonce', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array(
				'message' => __( 'You cannot edit posts.', 'wp-cloudflare-cache' ),
				'type'    => 'error',
			) );
		}
		
		$this->cfca_send_purge_result( $this->purge_cache( 'all' ) );
	}
	
	public function cfca_purge_test_config() {
		check_ajax_referer( 'cfca_ajax_nonce', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array(
				'message' => __( 'You cannot edit posts.', 'wp-cloudflare-cache' ),
				'type'    => 'error',
			) );
		}
		
		$latest_post = get_posts([
			'numberposts' => 1,
			'post_status' => 'publish',
		])[0] ?? null;
		
		if ( ! $latest_post ) {
			wp_send_json_error( array(
				'message' => __( 'No published post found to test.', 'wp-cloudflare-cache' ),
				'type'    => 'error',
			) );
		}
		
		$this->cfca_send_purge_result( $this->purge_cache( $latest_post ) );
	}
	
	
	/**
	 * Sets the zone-wide Browser Cache TTL (how long visitors' browsers keep the response), independent
	 * of the per-rule Edge Cache TTL.
	 * @param string $cf_zone_id
	 * @param int    $seconds 0 for "Respect Existing Headers".
	 * @param array  $overrides
	 * @return true|WP_Error
	 */
	public function set_browser_cache_ttl( $cf_zone_id, $seconds, $overrides = array() ) {
		$cf_headers           = $this->get_api_headers( $overrides );
		$cf_headers['method'] = 'PATCH';
		$cf_headers['body']   = json_encode( array( 'value' => (int) $seconds ) );
		
		$response = wp_remote_post(
			esc_url_raw( "https://api.cloudflare.com/client/v4/zones/$cf_zone_id/settings/browser_cache_ttl" ),
			$cf_headers
		);
		
		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'cloudflare_error', $response->get_error_message(), [ 'status' => 500 ] );
		}
		
		$response_data = json_decode( wp_remote_retrieve_body( $response ), true );
		
		if ( empty( $response_data['success'] ) ) {
			$error = $response_data['errors'][0] ?? [ 'message' => 'Unknown Cloudflare error.', 'code' => 500 ];
			return new WP_Error( 'cloudflare_error', $error['message'], [ 'status' => $error['code'] ] );
		}
		
		return true;
	}
	
	public function setup_cache_rules( $cf_zone_id, $overrides = array() ) {
		$marker   = 'CFCA - Cache Everything';
		$endpoint = "https://api.cloudflare.com/client/v4/zones/$cf_zone_id/rulesets/phases/http_request_cache_settings/entrypoint";

		$host    = wp_parse_url( home_url(), PHP_URL_HOST );
		$rules   = $this->get_foreign_cache_rules( $endpoint, array( $marker ), $overrides );
		$rules[] = array(
			'description'       => $marker,
			'expression'        => $this->build_cache_expression( $host, $overrides ),
			'action'            => 'set_cache_settings',
			'action_parameters' => $this->build_cache_action_parameters( $overrides ),
		);
		
		return $this->put_cache_rules( $endpoint, $rules, $overrides );
	}
	
	/**
	 * Builds the Cache Rule's action_parameters: cache on, plus an Edge Cache TTL override when the
	 * "Edge Cache TTL" setting isn't "Respect Existing Headers".
	 * @param array $overrides
	 * @return array
	 */
	private function build_cache_action_parameters( $overrides = array() ) {
		$edge_ttl = $overrides['cf_edge_ttl'] ?? $this->instance->get( 'cf_edge_ttl' );
		$params   = array( 'cache' => true );
		
		if ( ! empty( $edge_ttl ) ) {
			$params['edge_ttl'] = array(
				'mode'    => 'override_origin',
				'default' => (int) $edge_ttl,
			);
		} else {
			$params['edge_ttl'] = array( 'mode' => 'respect_origin' );
		}
		
		return $params;
	}
	
	private function build_cache_expression( $host, $overrides = array() ) {
		$get = function ( $key, $default = '' ) use ( $overrides ) {
			return $overrides[ $key ] ?? $this->instance->get( $key, $default );
		};
		
		$conditions   = array();
		$conditions[] = sprintf( 'http.host wildcard "%s*"', $host );
		$conditions[] = 'not http.cookie contains "wordpress_logged_in_"';
		$conditions[] = 'not http.cookie contains "comment_author_"';
		$conditions[] = 'not http.cookie contains "wp-postpass_"';
		$conditions[] = 'not http.request.uri.path contains "/wp-admin"';
		$conditions[] = 'not http.request.uri.path contains "/wp-login"';
		
		if ( 'on' === $get( 'bypass_sitemap', 'on' ) ) {
			$conditions[] = 'not http.request.uri.path contains ".xml"';
		}
		if ( 'on' === $get( 'bypass_robots', 'on' ) ) {
			$conditions[] = 'not http.request.uri.path contains "robots.txt"';
		}
		
		$exclude_urls = trim( (string) $get( 'cache_exclude_urls' ) );
		if ( '' !== $exclude_urls ) {
			foreach ( preg_split( '/\r\n|\n|\r/', $exclude_urls ) as $path ) {
				$path = trim( str_replace( array( '/*', '*' ), '', trim( $path ) ) );
				if ( '' !== $path ) {
					$conditions[] = sprintf( 'not http.request.uri contains "%s"', $path );
				}
			}
		}
		
		return '(' . implode( ' and ', $conditions ) . ')';
	}
	
	/**
	 * Removes only the Cache Rule this plugin created (matched by description), leaving any others intact.
	 * @param string $cf_zone_id
	 * @return true|WP_Error
	 */
	public function remove_cache_rules( $cf_zone_id ) {
		$endpoint = "https://api.cloudflare.com/client/v4/zones/$cf_zone_id/rulesets/phases/http_request_cache_settings/entrypoint";
		$rules    = $this->get_foreign_cache_rules( $endpoint, array( 'CFCA - Cache Everything' ) );
		
		return $this->put_cache_rules( $endpoint, $rules );
	}
	
	/**
	 * Fetches the zone's cache-settings ruleset, minus any rules matching the given descriptions.
	 * @param string $endpoint
	 * @param array  $markers_to_strip Rule descriptions to exclude from the returned list.
	 * @return array
	 */
	private function get_foreign_cache_rules( $endpoint, $markers_to_strip, $overrides = array() ) {
		$existing = wp_remote_get( esc_url_raw( $endpoint ), $this->get_api_headers( $overrides ) );
		$rules    = array();
		
		if ( ! is_wp_error( $existing ) ) {
			$existing_data = json_decode( wp_remote_retrieve_body( $existing ), true );
			if ( ! empty( $existing_data['success'] ) && is_array( $existing_data['result']['rules'] ?? null ) ) {
				foreach ( $existing_data['result']['rules'] as $rule ) {
					if ( ! in_array( $rule['description'] ?? '', $markers_to_strip, true ) ) {
						$rules[] = $rule;
					}
				}
			}
		}
		
		return $rules;
	}
	
	private function put_cache_rules( $endpoint, $rules, $overrides = array() ) {
		$cf_headers           = $this->get_api_headers( $overrides );
		$cf_headers['method'] = 'PUT';
		$cf_headers['body']   = json_encode( array( 'rules' => $rules ) );
		
		$response = wp_remote_post( esc_url_raw( $endpoint ), $cf_headers );
		
		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'cloudflare_error', $response->get_error_message(), [ 'status' => 500 ] );
		}
		
		$response_data = json_decode( wp_remote_retrieve_body( $response ), true );
		
		if ( empty( $response_data['success'] ) ) {
			$error = $response_data['errors'][0] ?? [ 'message' => 'Unknown Cloudflare error.', 'code' => 500 ];
			return new WP_Error( 'cloudflare_error', $error['message'], [ 'status' => $error['code'] ] );
		}
		
		return true;
	}
	
	// Only the live Cloudflare Cache Rule is cleaned up here; local settings (cfca_config/cfca_options)
	// must survive a deactivate/reactivate cycle — uninstall.php is what wipes those, on actual removal.
	public function deactivate_plugin() {
		$cf_zone_id = $this->instance->get_single_config( 'cf_zone_id', '' );

		if ( ! empty( $cf_zone_id ) && $this->has_credentials() ) {
			$this->remove_cache_rules( $cf_zone_id );
		}
	}
	
	/**
	 * Zones list read from cache (stored in cfca_config) so the Settings page never blocks on a live
	 * Cloudflare request while rendering. Refreshed by refresh_zones_cache(), called on every settings save.
	 * @return array Zone ID => zone name.
	 */
	public function get_cached_zones() {
		return $this->instance->get_single_config( 'cf_zones_cache', array() );
	}
	
	/**
	 * Fetches the zones list live and stores it for get_cached_zones() to read without an API round-trip.
	 * @return array Zone ID => zone name.
	 */
	public function refresh_zones_cache( $overrides = array() ) {
		$zones = $this->list_zones( $overrides );
		$this->instance->set_single_config( 'cf_zones_cache', $zones );
		$this->instance->update_config();
		return $zones;
	}
	
	public function list_zones( $overrides = array() ) {
		if ( ! $this->has_credentials( $overrides ) ) {
			return array();
		}
		
		$response = wp_remote_get( 'https://api.cloudflare.com/client/v4/zones?per_page=50', $this->get_api_headers( $overrides ) );
		
		if ( is_wp_error( $response ) ) {
			return array();
		}
		
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		
		if ( empty( $data['success'] ) || ! is_array( $data['result'] ?? null ) ) {
			return array();
		}
		
		$zones = array();
		foreach ( $data['result'] as $zone ) {
			if ( isset( $zone['id'], $zone['name'] ) ) {
				$zones[ $zone['id'] ] = $zone['name'];
			}
		}
		
		return $zones;
	}
	
	/**
	 * Whether the given hostname has at least one DNS record (A/AAAA/CNAME) proxied through Cloudflare
	 * (the orange cloud). Caching cannot work at all if traffic isn't routed through Cloudflare's proxy.
	 * @param string $cf_zone_id
	 * @param string $hostname
	 * @return bool|null True/false when known, null when it couldn't be determined.
	 */
	public function is_dns_proxied( $cf_zone_id, $hostname ) {
		$response = wp_remote_get(
			esc_url_raw( "https://api.cloudflare.com/client/v4/zones/$cf_zone_id/dns_records?name=" . rawurlencode( $hostname ) ),
			$this->get_api_headers()
		);
		
		if ( is_wp_error( $response ) ) {
			return null;
		}
		
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		
		if ( empty( $data['success'] ) || empty( $data['result'] ) ) {
			return null;
		}
		
		foreach ( $data['result'] as $record ) {
			if ( in_array( $record['type'] ?? '', array( 'A', 'AAAA', 'CNAME' ), true ) && ! empty( $record['proxied'] ) ) {
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Builds the request headers for the currently selected authentication method (API Token or Global API Key).
	 * @param array $overrides Values from an in-progress settings save, preferred over the (possibly stale) database.
	 * @return array
	 */
	public function get_api_headers( $overrides = array() ) {
		$get = function ( $key, $default = '' ) use ( $overrides ) {
			return $overrides[ $key ] ?? $this->instance->get( $key, $default );
		};

		if ( 'key' !== $get( 'cf_auth_method', 'token' ) ) {
			$headers = array(
				'Authorization' => 'Bearer ' . $get( 'cf_api_token' ),
				'Content-Type'  => 'application/json',
			);
		} else {
			$headers = array(
				'X-Auth-Email' => $get( 'cf_email' ),
				'X-Auth-Key'   => $get( 'cf_api_key' ),
				'Content-Type' => 'application/json',
			);
		}

		return array( 'headers' => $headers );
	}
	
	/**
	 * Whether enough Cloudflare credentials are filled in for the selected authentication method.
	 * @param array $overrides Values from an in-progress settings save, preferred over the (possibly stale) database.
	 * @return bool
	 */
	public function has_credentials( $overrides = array() ) {
		$get = function ( $key, $default = '' ) use ( $overrides ) {
			return $overrides[ $key ] ?? $this->instance->get( $key, $default );
		};

		if ( 'key' !== $get( 'cf_auth_method', 'token' ) ) {
			return ! empty( $get( 'cf_api_token' ) );
		}
		return ! empty( $get( 'cf_email' ) ) && ! empty( $get( 'cf_api_key' ) );
	}
}
