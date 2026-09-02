<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class WP_Cloudflare_Cache_Purge
 *
 * Thats where we bring the plugin to life
 *
 * @package		WPCC
 * @subpackage	Classes/WP_Cloudflare_Cache_Purge
 * @author		Jumedeen Khan
 * @since		1.2
 */
class WP_Cloudflare_Cache_Purge {
	
	private $instance   = null;
    private $email        = '';
    private $api_key      = '';
    private $zone_id      = '';
    private $page_rule_id = '';

	/**
	 * Our WP_Cloudflare_Cache_Purge constructor 
	 * to run the plugin logic.
	 *
	 * @since 1.2
	 */
	function __construct($cf_email, $cf_api_key, $instance) {
		$this->init();
		$this->instance = $instance;
		
        $this->email         = $cf_email;
        $this->api_key       = $cf_api_key;
		
		register_deactivation_hook( WPCC_PLUGIN_FILE, array( $this, 'deactivate_plugin' ) );
		
	}

	/**
	 * Registers all WordPress and plugin related hooks
	 *
	 * @access	private
	 * @since	1.2
	 * @return	void
	 */

	private function init() {
		
		add_action( 'wp_ajax_wpcc_purge_everything', array( $this, 'wpcc_purge_everything' ) );
		add_action( 'wp_ajax_wpcc_purge_test_config', array( $this, 'wpcc_purge_test_config' ) );
		add_action( 'wp_ajax_wpcc_create_page_rule', array( $this, 'wpcc_create_page_rule' ) );
		add_action( 'wp_ajax_wpcc_set_browser_ttl', array( $this, 'wpcc_set_browser_ttl' ) );
		
        // Purge cache on comments
        add_action( 'transition_comment_status', array($this, 'purge_cache_on_approved'), PHP_INT_MAX, 3 );
        add_action( 'comment_post',              array($this, 'purge_cache_on_added'), PHP_INT_MAX, 3 );
        add_action( 'delete_comment',            array($this, 'purge_cache_on_deleted'), PHP_INT_MAX );
		
		$purge_actions = array(
            'deleted_post',
            'wp_trash_post',
            'clean_post_cache',
            'edit_post',
            'publish_to_draft',
            'delete_attachment',
        );

        foreach ($purge_actions as $action) {
            add_action( $action, array($this, 'wpcc_purge_cache_via_id'), PHP_INT_MAX, 2 );
        }
		
		add_action('wp_insert_post', array($this, 'wpcc_wp_insert_post'), PHP_INT_MAX, 3);
		add_action('transition_post_status', array($this, 'wpcc_post_updated'), PHP_INT_MAX, 3);
		
		add_action('wpcc_purge_cache', array($this, 'purge_cache'), 10, 1);
	}
	
	public function wpcc_wp_insert_post( $post_ID, $post, $update ) {
		if ( wp_is_post_revision( $post_ID ) || wp_is_post_autosave( $post_ID ) ) {
			return;
		}
		wp_schedule_single_event( time() + 2, 'wpcc_purge_cache', [ $post ] );
	}
	
	public function wpcc_post_updated( $new_status, $old_status, $post ) {
		wp_schedule_single_event( time() +2, 'wpcc_purge_cache', [ $post ] );
	}
	
	public  function wpcc_purge_cache_via_id( $post_id ) {
		$post = get_post( $post_id );
		wp_schedule_single_event( time() +2, 'wpcc_purge_cache', [ $post ] );
	}
	
	public function purge_cache_on_added( $comment_ID, $comment_approved, $commentdata ) {
		
		if ( $this->instance->get('purge_on_comment') == 'on' ) {
			$post_id = $commentdata['comment_post_ID'];
			$post = get_post( $post_id );
			
			wp_schedule_single_event( time() +2, 'wpcc_purge_cache', [ $post ] );
		}
	}
	
	public function purge_cache_on_approved($new_status, $old_status, $comment) {
		
		if ( $this->instance->get('purge_on_comment') == 'on' ) {
			$post_id = $comment->comment_post_ID;
			$post = get_post( $post_id );
			
			wp_schedule_single_event( time() +2, 'wpcc_purge_cache', [ $post ] );
		}
		
	} 
	
	public function purge_cache_on_deleted( $comment_ID ) {
		
		if ( $this->instance->get('purge_on_comment') == 'on' ) {
			$comment = get_comment( $comment_ID );
			$post_id = $comment->comment_post_ID;
			$post = get_post( $post_id );
			
			wp_schedule_single_event( time() +2, 'wpcc_purge_cache', [ $post ] );
		}
	}

	public function purge_cache( $post = null ) {
		
		// cloudflare settings
		$cf_api_key = $this->api_key;
		$cf_email   = $this->email;
		
		$cf_zone_id =  $this->instance->get_single_config('cf_zone_id', '');
		
		if ( empty( $cf_zone_id ) ) {
			$domain = $this->instance->get_only_domain();
			$cf_zone_id     = $this->getZoneID( $domain );
			
			// Set cache for zone id
			$this->instance->set_single_config( 'cf_zone_id', $cf_zone_id );
			$this->instance->update_config();
		}
		
		if ( ! ( $cf_api_key || $cf_email || $cf_zone_id ) ) {
			return new WP_Error( 'rest_forbidden', __( 'Please fill Cloudflare details.', 'wp-cloudflare-cache' ), [ 'status' => 401 ] );
		}

		$urls = [];
		
		if ( $post && $post != 'all' ) {

			$page_url = get_permalink( $post->ID );
			
			if ( filter_var( $page_url, FILTER_VALIDATE_URL ) ) {
				$urls[] = substr( $page_url, -1 ) === '/' ? substr( $page_url, 0, -1 ) : $page_url;
			}
			
			if ( $this->instance->get( 'purge_homepage' ) == 'on' ) {
				$home_url = get_site_url();
				$homepage_url = substr( $home_url, -1 ) === '/' ? substr( $home_url, 0, -1 ) : $home_url;
				
				$urls[] = trim( $homepage_url );
			}
			
			if ( $this->instance->get( 'purge_urls' ) != '' ) {
				$purge_urls = trim( $this->instance->get( 'purge_urls' ) );
				$purge_urls = preg_split( '/\r\n|\n|\r/', $purge_urls );
				
				if ( $purge_urls ) {
					foreach ( $purge_urls as $url ) {
						$url = home_url('') . $url;
						if ( filter_var( $url, FILTER_VALIDATE_URL ) ) {
							$urls[] = $url;
						}
					}
				}
			}

			$data = [ 'files' => $urls ];
		}
		
		if ( $post = 'all' ) {
			$data = ['purge_everything' => true];
		}

		$cf_headers = $this->get_api_headers();
		$cf_headers['body'] = json_encode( $data );
		
		$response = wp_remote_post(
			esc_url_raw( "https://api.cloudflare.com/client/v4/zones/$cf_zone_id/purge_cache" ),
			$cf_headers
		);
		
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		$response_data = ( ! is_wp_error( $response ) ) ? $body : null;		

		if ( ! $response_data['success'] ) {
			$error = $response_data['errors'][0];
			return new WP_Error( 'cloudflare_error', $error['message'], [ 'status' => $error['code'] ] );
		}

		$purged = $urls ? implode( ', ', $urls ) : 'everything';

		return (object) [
			'success' => $response_data['success'],
			'message' => "Cloudflare Cache purged for $purged successfully. Please allow up to 30 seconds for changes to take effect.",
		];
	}   	
	
	public function wpcc_purge_everything() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			$response = new WP_Error( 'rest_forbidden', __( 'You cannot edit posts.', 'wp-cloudflare-cache' ), [ 'status' => 401 ] );
		}
		
		$response = $this->purge_cache( 'all' );
		wp_send_json( $response );
		wp_die();
	}
	
	public function wpcc_purge_test_config() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			$response = new WP_Error( 'rest_forbidden', __( 'You cannot edit posts.', 'wp-cloudflare-cache' ), [ 'status' => 401 ] );
		}
		
		$latest_post = get_posts([
			'numberposts' => 1,
			'post_status' => 'publish',
		])[0];
		
		$response = $this->purge_cache( $latest_post );
		
		wp_send_json( $response );
		wp_die();
	}
	
	public function wpcc_set_browser_ttl() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			$response = new WP_Error( 'rest_forbidden', __( 'You cannot edit posts.', 'wp-cloudflare-cache' ), [ 'status' => 401 ] );
		}
		
		$response = $this->wpcc_change_browser_ttl();
		
		wp_send_json( $response );
		wp_die();
	}
	
	public function wpcc_create_page_rule() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			$response = new WP_Error( 'rest_forbidden', __( 'You cannot edit posts.', 'wp-cloudflare-cache' ), [ 'status' => 401 ] );
		}
		
		$response = $this->wpcc_add_page_rule();
		
		wp_send_json( $response );
		wp_die();
	}
	
	public function wpcc_add_page_rule() {
		
        // delete old page rule, if exist
        if( $this->instance->get_single_config('cf_page_rule_id', '') != '' ) {
			$this->delete_page_rule( $this->instance->get_single_config('cf_page_rule_id', '') );
        }
		
		$cf_headers = $this->get_api_headers();	
		$cf_zone_id =  $this->instance->get_single_config('cf_zone_id', '');;
		
		if ( empty( $cf_zone_id ) ) {
			$domain = $this->instance->get_only_domain();
			$cf_zone_id     = $this->getZoneID( $domain );
			
			// Set cache for zone id
			$this->instance->set_single_config( 'cf_zone_id', $cf_zone_id );
			$this->instance->update_config();
		}
		
		$url = home_url('/*');
		
		$cf_headers['method'] = 'POST';
		$cf_headers['body'] = json_encode( array('targets' => array(array('target' => 'url', 'constraint' => array('operator' => 'matches', 'value' => $url))), 'actions' => array(array('id' => 'cache_level', 'value' => 'cache_everything')), 'priority' => 1, 'status' => 'active') );
		
		$response = wp_remote_post(
			esc_url_raw( "https://api.cloudflare.com/client/v4/zones/$cf_zone_id/pagerules" ),
			$cf_headers
		);
		
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		$response_data = ( ! is_wp_error( $response ) ) ? $body : null;		

		if ( ! $response_data['success'] ) {
			$error = $response_data['errors'][0];
			return new WP_Error( 'cloudflare_error', $error['message'], [ 'status' => $error['code'] ] );
		}

        if ( isset($response_data['result']) && is_array($response_data['result']) && isset($response_data['result']['id']) ) {
			$id = $response_data['result']['id'];
			$this->instance->set_single_config( 'cf_page_rule_id', $id);
			$this->instance->update_config();
        }

		return (object) [
			'success' => $response_data['success'],
			'message' => "Cloudflare cache everything page rule created successfully.",
		];

        return false;
    }
	
	public function wpcc_change_browser_ttl() {
		
		$cf_headers = $this->get_api_headers();	
		$cf_zone_id =  $this->instance->get_single_config('cf_zone_id', '');;
		
		if ( empty( $cf_zone_id ) ) {
			$domain = $this->instance->get_only_domain();
			$cf_zone_id     = $this->getZoneID( $domain );
			
			// Set cache for zone id
			$this->instance->set_single_config( 'cf_zone_id', $cf_zone_id );
			$this->instance->update_config();
		}
		
		$url = home_url('/*');
		
        $cf_headers['method'] = 'PATCH';
        $cf_headers['body']   = json_encode( array('value' => 0) );
		
		$response = wp_remote_post(
            esc_url_raw( "https://api.cloudflare.com/client/v4/zones/$cf_zone_id/settings/browser_cache_ttl" ),
			$cf_headers
		);
		
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		$response_data = ( ! is_wp_error( $response ) ) ? $body : null;		

		if ( ! $response_data['success'] ) {
			$error = $response_data['errors'][0];
			return new WP_Error( 'cloudflare_error', $error['message'], [ 'status' => $error['code'] ] );
		}

		return (object) [
			'success' => $response_data['success'],
			'message' => "Cloudflare browser cache ttl changed successfully.",
		];

        return false;
    }
	
	function delete_page_rule( $page_rule_id = false) {
		
		$cf_headers = $this->get_api_headers();	
		$cf_zone_id =  $this->instance->get_single_config('cf_zone_id', '');
		
		if ( empty( $cf_zone_id ) ) {
			$domain = $this->instance->get_only_domain();
			$cf_zone_id     = $this->getZoneID( $domain );
		}
		
		$cf_headers['method'] = 'DELETE';
		
		$response = wp_remote_post(
            esc_url_raw( "https://api.cloudflare.com/client/v4/zones/$cf_zone_id/pagerules/$page_rule_id" ),
			$cf_headers
		);
		
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		$response_data = ( ! is_wp_error( $response ) ) ? $body : null;		

		if ( ! $response_data['success'] ) {
			$error = $response_data['errors'][0];
			return new WP_Error( 'cloudflare_error', $error['message'], [ 'status' => $error['code'] ] );
		}
		
		$this->instance->set_single_config('cf_page_rule_id', '');
		$this->instance->update_config();

		return (object) [
			'success' => $response_data['success'],
			'message' => "Cloudflare cache everything page rule created successfully.",
		];
		
		return true;	
	}
	
	public function deactivate_plugin() {
		$this->delete_page_rule( $this->instance->get_single_config('cf_page_rule_id', '') );
		delete_option('wpcc_config');
	}
	
	public function getZoneID( $domain ) {
		
		// cloudflare settings
		$cf_api_key = $this->instance->get( 'cf_api_key' );
		$cf_email   = $this->instance->get( 'cf_email' );
		
		$result = wp_remote_get("https://api.cloudflare.com/client/v4/zones", array(
			'headers' => array(
				'X-Auth-Email' => $cf_email,
				'X-Auth-Key' => $cf_api_key,
				'Content-Type' => 'application/json'
			)
		));
		
		$arr_result = json_decode($result['body'], true);
		
		if ( isset( $arr_result['success'] ) ) {
			foreach ($arr_result['result'] as $r) {
				if ($r['name'] == $domain) {
					return $r['id'];
				}
			}
			return false;
		}
		return false;
	}
	
	public function get_api_headers( $api_cache = false ) {

        $cf_headers = array();
		
		$cf_headers = array(
			'headers' => array(
				'X-Auth-Email' => $this->email,
				'X-Auth-Key' => $this->api_key,
				'Content-Type' => 'application/json'
			)
		);
		return $cf_headers;
		
	}
}
