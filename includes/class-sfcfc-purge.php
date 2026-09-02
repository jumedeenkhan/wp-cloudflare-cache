<?php
/**
 * Handles all Cloudflare API calls: cache purging, cache rule setup, zone lookups.
 *
 * @package SuperFlare
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Handles all Cloudflare API calls: cache purging, cache rule setup, zone lookups.
 */
class SFCFC_Purge {

	private $instance = null;

	/**
	 * @param SFCFC_Cache $instance
	 */
	public function __construct( $instance ) {
		$this->init();
		$this->instance = $instance;

		register_activation_hook( SFCFC_PLUGIN_FILE, array( $this, 'activate_plugin' ) );
		register_deactivation_hook( SFCFC_PLUGIN_FILE, array( $this, 'deactivate_plugin' ) );
		add_action( 'admin_init', array( $this, 'maybe_redirect_after_activation' ) );
	}

	/**
	 * Registers this class's hooks.
	 */
	private function init() {
		
		add_action( 'wp_ajax_sfcfc_purge_everything', array( $this, 'sfcfc_purge_everything' ) );
		add_action( 'wp_ajax_sfcfc_custom_purge', array( $this, 'ajax_custom_purge' ) );
		add_action( 'wp_ajax_sfcfc_purge_test_config', array( $this, 'sfcfc_purge_test_config' ) );
		add_action( 'wp_ajax_sfcfc_clear_activity_log', array( $this, 'ajax_clear_activity_log' ) );
		add_action( 'wp_ajax_sfcfc_regenerate_purge_key', array( $this, 'ajax_regenerate_purge_key' ) );
		add_action( 'wp_ajax_sfcfc_get_analytics', array( $this, 'ajax_get_analytics' ) );
		add_action( 'wp_ajax_sfcfc_regenerate_preload_key', array( $this, 'ajax_regenerate_preload_key' ) );
		add_action( 'wp_ajax_sfcfc_run_preloader', array( $this, 'ajax_run_preloader' ) );
		add_action( 'init', array( $this, 'maybe_handle_remote_purge' ) );
		add_action( 'init', array( $this, 'maybe_handle_remote_preload' ) );
		
        add_action( 'transition_comment_status', array($this, 'purge_cache_on_approved'), PHP_INT_MAX, 3 );
        add_action( 'comment_post',              array($this, 'purge_cache_on_added'), PHP_INT_MAX, 3 );
        add_action( 'delete_comment',            array($this, 'purge_cache_on_deleted'), PHP_INT_MAX );
        add_action( 'upgrader_process_complete', array($this, 'purge_cache_on_theme_plugin_update'), PHP_INT_MAX, 2 );

        add_filter( 'cron_schedules', array( $this, 'register_purge_cron_interval' ) );
        add_action( 'sfcfc_process_purge_queue', array( $this, 'process_purge_queue' ) );
		
		/**
		 * Uses before_delete_post, not deleted_post, since the post row (and its permalink) is
		 * already gone by the latter. Split into two groups so Purge Homepage / Purge Archives
		 * can distinguish an update from a trash/delete.
		 */
        foreach ( array( 'clean_post_cache', 'edit_post', 'publish_to_draft' ) as $action ) {
            add_action( $action, array( $this, 'sfcfc_purge_cache_via_id' ), PHP_INT_MAX, 2 );
        }

        foreach ( array( 'before_delete_post', 'wp_trash_post', 'delete_attachment' ) as $action ) {
            add_action( $action, array( $this, 'sfcfc_purge_cache_via_id_trash' ), PHP_INT_MAX, 2 );
        }
		
		add_action('wp_insert_post', array($this, 'sfcfc_wp_insert_post'), PHP_INT_MAX, 3);
		add_action('transition_post_status', array($this, 'sfcfc_post_updated'), PHP_INT_MAX, 3);
	}
	
	/**
	 * @param int     $post_ID
	 * @param WP_Post $post
	 * @param bool    $update
	 */
	public function sfcfc_wp_insert_post( $post_ID, $post, $update ) {
		if ( wp_is_post_revision( $post_ID ) || wp_is_post_autosave( $post_ID ) ) {
			return;
		}
		$this->queue_purge_for_post_deduped( $post, 'update' );
	}
	
	/**
	 * @param string  $new_status
	 * @param string  $old_status
	 * @param WP_Post $post
	 */
	public function sfcfc_post_updated( $new_status, $old_status, $post ) {
		$this->queue_purge_for_post_deduped( $post, 'trash' === $new_status ? 'trash' : 'update' );
	}
	
	/**
	 * @param int $post_id
	 */
	public function sfcfc_purge_cache_via_id( $post_id ) {
		$this->queue_purge_for_post_deduped( get_post( $post_id ), 'update' );
	}

	/**
	 * @param int $post_id
	 */
	public function sfcfc_purge_cache_via_id_trash( $post_id ) {
		$this->queue_purge_for_post_deduped( get_post( $post_id ), 'trash' );
	}
	
	/**
	 * @param int   $comment_ID
	 * @param mixed $comment_approved
	 * @param array $commentdata
	 */
	public function purge_cache_on_added( $comment_ID, $comment_approved, $commentdata ) {

		if ( 1 === (int) $comment_approved && 'on' === $this->instance->get( 'purge_on_comment_approved' ) ) {
			$this->queue_purge_for_post_deduped( get_post( $commentdata['comment_post_ID'] ), 'update' );
		}
	}
	
	/**
	 * @param string     $new_status
	 * @param string     $old_status
	 * @param WP_Comment $comment
	 */
	public function purge_cache_on_approved( $new_status, $old_status, $comment ) {
		$setting = 'approved' === $new_status ? 'purge_on_comment_approved' : 'purge_on_comment_deleted';

		if ( 'on' === $this->instance->get( $setting ) ) {
			$this->queue_purge_for_post_deduped( get_post( $comment->comment_post_ID ), 'update' );
		}
	}
	
	/**
	 * @param int $comment_ID
	 */
	public function purge_cache_on_deleted( $comment_ID ) {

		if ( 'on' === $this->instance->get( 'purge_on_comment_deleted' ) ) {
			$comment = get_comment( $comment_ID );
			$this->queue_purge_for_post_deduped( get_post( $comment->comment_post_ID ), 'update' );
		}
	}

	public function purge_cache_on_theme_plugin_update( $upgrader, $hook_extra ) {
		if ( 'on' !== $this->instance->get( 'purge_on_theme_plugin_update' ) ) {
			return;
		}

		if ( ! in_array( $hook_extra['type'] ?? '', array( 'plugin', 'theme' ), true ) ) {
			return;
		}

		$this->queue_purge_all();
		$this->log_activity( 'Theme/Plugin Update', true, 'Entire cache queued for purge after a ' . $hook_extra['type'] . ' update.' );
	}
	
	/**
	 * Builds the purge URL list for a post: its own permalink, plus the homepage, its archive
	 * pages, its feeds, and any Custom Purge URLs, per each setting and whether $reason is an
	 * update or a trash/delete.
	 *
	 * @param WP_Post $post
	 * @param string  $reason 'update' or 'trash'.
	 * @return array<int, string>
	 */
	private function resolve_post_purge_urls( $post, $reason = 'update' ) {
		$urls = [];

		/**
		 * Passes the post object, not its ID, so the permalink still resolves after deletion.
		 */
		$page_url = get_permalink( $post );

		if ( filter_var( $page_url, FILTER_VALIDATE_URL ) ) {
			$urls[] = substr( $page_url, -1 ) === '/' ? substr( $page_url, 0, -1 ) : $page_url;
		}

		$homepage_setting = 'trash' === $reason ? 'purge_homepage_on_trash' : 'purge_homepage_on_update';

		if ( 'on' === $this->instance->get( $homepage_setting ) ) {
			$home_url     = home_url();
			$homepage_url = substr( $home_url, -1 ) === '/' ? substr( $home_url, 0, -1 ) : $home_url;

			$urls[] = trim( $homepage_url );
		}

		$archives_setting = 'trash' === $reason ? 'purge_archives_on_trash' : 'purge_archives_on_update';

		if ( 'on' === $this->instance->get( $archives_setting ) ) {
			$urls = array_merge( $urls, $this->resolve_post_archive_urls( $post ) );
		}

		if ( 'on' === $this->instance->get( 'purge_feeds' ) ) {
			$urls = array_merge( $urls, $this->resolve_post_feed_urls( $post ) );
		}

		if ( 'on' === $this->instance->get( 'purge_amp_urls' ) ) {
			$amp_url = $this->resolve_post_amp_url( $post );

			if ( $amp_url ) {
				$urls[] = $amp_url;
			}
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

		return array_unique( $urls );
	}

	/**
	 * Archive URLs the post appears in: its taxonomy term archives, its author archive, and its
	 * year archive. Capped at 20 so it leaves room, alongside the post's own URL, under Cloudflare's
	 * 30-URLs-per-request purge limit.
	 *
	 * @param WP_Post $post
	 * @return array<int, string>
	 */
	private function resolve_post_archive_urls( $post ) {
		$urls = array();

		foreach ( get_object_taxonomies( $post->post_type ) as $taxonomy ) {
			foreach ( wp_get_post_terms( $post->ID, $taxonomy ) as $term ) {
				$term_url = get_term_link( $term );

				if ( ! is_wp_error( $term_url ) ) {
					$urls[] = $term_url;
				}
			}
		}

		$author_url = get_author_posts_url( $post->post_author );

		if ( filter_var( $author_url, FILTER_VALIDATE_URL ) ) {
			$urls[] = $author_url;
		}

		$year_url = get_year_link( mysql2date( 'Y', $post->post_date ) );

		if ( filter_var( $year_url, FILTER_VALIDATE_URL ) ) {
			$urls[] = $year_url;
		}

		return array_slice( array_unique( $urls ), 0, 20 );
	}

	/**
	 * The post's comment feed plus the site's main feed.
	 *
	 * @param WP_Post $post
	 * @return array<int, string>
	 */
	private function resolve_post_feed_urls( $post ) {
		return array_unique( array_filter( array(
			get_bloginfo( 'rss2_url' ),
			get_post_comments_feed_link( $post->ID ),
		) ) );
	}

	/**
	 * The post's AMP URL, via amp_get_permalink() when available, else the conventional /amp/ suffix.
	 *
	 * @param WP_Post $post
	 * @return string
	 */
	private function resolve_post_amp_url( $post ) {
		if ( function_exists( 'amp_get_permalink' ) ) {
			return (string) amp_get_permalink( $post->ID );
		}

		$page_url = get_permalink( $post );

		if ( ! filter_var( $page_url, FILTER_VALIDATE_URL ) ) {
			return '';
		}

		return trailingslashit( $page_url ) . 'amp/';
	}

	/**
	 * Queues a post's purge URLs (resolved immediately, while post data is still available) for
	 * the next queue-drain cron run, deduped per post+reason so the same post is only resolved once
	 * per reason per request even though several hooks can fire for the same save or trash.
	 *
	 * @param WP_Post|null $post
	 * @param string       $reason 'update' or 'trash'.
	 */
	private function queue_purge_for_post_deduped( $post, $reason = 'update' ) {
		static $processed = array();

		if ( ! ( $post instanceof WP_Post ) || wp_is_post_revision( $post->ID ) || wp_is_post_autosave( $post->ID ) ) {
			return;
		}

		$dedup_key = $post->ID . ':' . $reason;

		if ( isset( $processed[ $dedup_key ] ) ) {
			return;
		}
		$processed[ $dedup_key ] = true;

		if ( 'on' === $this->instance->get( 'auto_purge_whole' ) ) {
			$this->queue_purge_all();
			return;
		}

		$this->queue_purge_urls( $this->resolve_post_purge_urls( $post, $reason ) );
	}

	/**
	 * Appends URLs to the sfcfc_purge_queue option. A no-op once purge_all is already queued.
	 *
	 * @param array<int, string> $urls
	 */
	private function queue_purge_urls( array $urls ) {
		if ( empty( $urls ) ) {
			return;
		}

		$queue = get_option( 'sfcfc_purge_queue', array( 'purge_all' => false, 'urls' => array() ) );

		if ( ! empty( $queue['purge_all'] ) ) {
			return;
		}

		$queue['urls'] = array_slice( array_unique( array_merge( $queue['urls'], $urls ) ), 0, 30 );
		update_option( 'sfcfc_purge_queue', $queue );
		$this->schedule_queue_cron_check();
	}

	/**
	 * Flags the queue for a full purge_everything on the next drain, short-circuiting any URLs.
	 */
	public function queue_purge_all() {
		update_option( 'sfcfc_purge_queue', array( 'purge_all' => true, 'urls' => array() ) );
		$this->schedule_queue_cron_check();
	}

	/**
	 * Registers the shutdown check only on requests that actually touch the purge queue, instead
	 * of on every single front-end and admin request site-wide, since the vast majority never do.
	 */
	private function schedule_queue_cron_check() {
		if ( ! has_action( 'shutdown', array( $this, 'maybe_start_queue_cron' ) ) ) {
			add_action( 'shutdown', array( $this, 'maybe_start_queue_cron' ), PHP_INT_MAX );
		}
	}

	/**
	 * Registers the short recurring interval used to drain the purge queue.
	 *
	 * @param array $schedules
	 * @return array
	 */
	public function register_purge_cron_interval( $schedules ) {
		$schedules['sfcfc_purge_interval'] = array(
			'interval' => 30,
			'display'  => __( 'Every 30 seconds (SuperFlare Cache queue)', 'wp-cloudflare-cache' ),
		);
		return $schedules;
	}

	/**
	 * Idempotently starts or stops the queue-drain cron: only runs while there's something
	 * queued, so an idle site never carries a recurring cron event it doesn't need.
	 */
	public function maybe_start_queue_cron() {
		$queue    = get_option( 'sfcfc_purge_queue', array( 'purge_all' => false, 'urls' => array() ) );
		$has_work = ! empty( $queue['purge_all'] ) || ! empty( $queue['urls'] );

		if ( ! $has_work ) {
			if ( wp_next_scheduled( 'sfcfc_process_purge_queue' ) ) {
				wp_clear_scheduled_hook( 'sfcfc_process_purge_queue' );
			}
			return;
		}

		if ( ! wp_next_scheduled( 'sfcfc_process_purge_queue' ) ) {
			wp_schedule_event( time(), 'sfcfc_purge_interval', 'sfcfc_process_purge_queue' );
		}
	}

	/**
	 * Cron callback: drains the queue in one API call, logs failures since there's no AJAX
	 * response to show them in, then re-checks the schedule so the recurring event stops as
	 * soon as the queue is empty instead of continuing to fire.
	 */
	public function process_purge_queue() {
		$queue = get_option( 'sfcfc_purge_queue', array( 'purge_all' => false, 'urls' => array() ) );

		if ( empty( $queue['purge_all'] ) && empty( $queue['urls'] ) ) {
			return;
		}

		$context = ! empty( $queue['purge_all'] ) ? 'Scheduled Purge (All)' : 'Scheduled Purge (URLs)';
		$result  = $this->purge_cache( ! empty( $queue['purge_all'] ) ? 'all' : $queue['urls'] );

		update_option( 'sfcfc_purge_queue', array( 'purge_all' => false, 'urls' => array() ) );

		if ( is_wp_error( $result ) ) {
			$this->log_activity( $context, false, $result->get_error_message() );

			if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- gated behind WP_DEBUG_LOG, the only way to surface a silent background (cron) purge failure.
				error_log( 'SuperFlare Cache: queued purge failed - ' . $result->get_error_message() );
			}

			$this->debug_log( 'Queued purge failed - ' . $result->get_error_message() );
		} else {
			$this->log_activity( $context, true, $result->message );

			if ( empty( $queue['purge_all'] ) && $this->preloader_should_run_on_purge() ) {
				$this->warm_urls( $queue['urls'] );
			}
		}

		$this->maybe_start_queue_cron();
	}

	public function debug_log( $message ) {
		if ( 'on' !== $this->instance->get( 'debug_logging' ) ) {
			return;
		}

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- gated behind the Debug/Logging Mode setting, which exists precisely to surface this.
		error_log( 'SuperFlare Cache Debug: ' . $message );
	}

	private function log_activity( $type, $success, $message ) {
		if ( 'on' !== $this->instance->get( 'enable_activity_log', 'on' ) ) {
			return;
		}

		$log = get_option( 'sfcfc_activity_log', array() );

		array_unshift( $log, array(
			'time'    => time(),
			'type'    => $type,
			'success' => (bool) $success,
			'message' => $message,
		) );

		update_option( 'sfcfc_activity_log', array_slice( $log, 0, 20 ) );
	}

	/**
	 * @return array<int, array{time:int,type:string,success:bool,message:string}>
	 */
	public function get_activity_log() {
		return get_option( 'sfcfc_activity_log', array() );
	}

	/**
	 * AJAX handler: clears the Activity Log.
	 */
	public function ajax_clear_activity_log() {
		check_ajax_referer( 'sfcfc_ajax_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array(
				'message' => __( 'You do not have permission to do this.', 'wp-cloudflare-cache' ),
				'type'    => 'error',
			) );
		}

		delete_option( 'sfcfc_activity_log' );

		wp_send_json_success( array(
			'message' => __( 'Activity log cleared.', 'wp-cloudflare-cache' ),
			'type'    => 'success',
		) );
	}

	/**
	 * Auto-generated on first use so an external cron/webhook can trigger a purge without wp-admin login.
	 *
	 * @return string
	 */
	public function get_purge_secret_key() {
		$key = $this->instance->get_single_config( 'purge_secret_key', '' );
		if ( ! $key ) {
			$key = wp_generate_password( 20, false );
			$this->instance->set_single_config( 'purge_secret_key', $key );
			$this->instance->update_config();
		}
		return $key;
	}

	/**
	 * @return string
	 */
	public function build_purge_url() {
		return add_query_arg( array( 'sfcfc_purge' => 1, 'key' => $this->get_purge_secret_key() ), home_url( '/' ) );
	}

	/**
	 * Handles ?sfcfc_purge=1&key=... on any front-end request, so real system cron / uptime hooks can purge.
	 */
	/**
	 * Called by external cron/webhook requests with no WP session to carry a nonce; authenticated
	 * via the hash_equals() secret-key check below instead.
	 */
	public function maybe_handle_remote_purge() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- see docblock above.
		if ( empty( $_GET['sfcfc_purge'] ) || empty( $_GET['key'] ) ) {
			return;
		}

		if ( 'on' !== $this->instance->get( 'remote_purge_url_enabled', 'on' ) ) {
			wp_die( esc_html__( 'Purge via Cron URL is turned off in SuperFlare Cache settings.', 'wp-cloudflare-cache' ), '', array( 'response' => 403 ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- see docblock above.
		if ( ! hash_equals( $this->get_purge_secret_key(), sanitize_text_field( wp_unslash( $_GET['key'] ) ) ) ) {
			wp_die( esc_html__( 'Invalid purge key.', 'wp-cloudflare-cache' ), '', array( 'response' => 403 ) );
		}

		$result = $this->purge_cache( 'all' );
		$this->log_activity( 'Remote Purge', ! is_wp_error( $result ), is_wp_error( $result ) ? $result->get_error_message() : $result->message );

		wp_die( is_wp_error( $result ) ? esc_html( $result->get_error_message() ) : esc_html( $result->message ), '', array( 'response' => is_wp_error( $result ) ? 500 : 200 ) );
	}

	/**
	 * Purges by exact URL or by hostname, from the sidebar's Custom Purge modal.
	 *
	 * @param string   $type  'url' or 'hostname'.
	 * @param string[] $items
	 * @return object|WP_Error
	 */
	public function purge_custom( $type, $items ) {
		if ( ! $this->has_credentials() ) {
			return new WP_Error( 'missing_credentials', __( 'Please fill Cloudflare details.', 'wp-cloudflare-cache' ), [ 'status' => 401 ] );
		}

		$cf_zone_id = $this->instance->get_single_config( 'cf_zone_id', '' );

		if ( empty( $cf_zone_id ) ) {
			return new WP_Error( 'missing_zone', __( 'Please select your Cloudflare domain in Settings first.', 'wp-cloudflare-cache' ), [ 'status' => 401 ] );
		}

		if ( empty( $items ) ) {
			return new WP_Error( 'no_items', __( 'Add at least one URL or hostname to purge.', 'wp-cloudflare-cache' ), [ 'status' => 400 ] );
		}

		$field      = 'hostname' === $type ? 'hosts' : 'files';
		$chunk_size = 'hostname' === $type ? 100 : 30;
		$purged     = 0;

		foreach ( array_chunk( $items, $chunk_size ) as $chunk ) {
			$cf_headers         = $this->get_api_headers();
			$cf_headers['body'] = json_encode( [ $field => $chunk ] );

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

			$purged += count( $chunk );
		}

		return (object) [
			'success' => true,
			'message' => sprintf(
				/* translators: 1: number of items, 2: "URL(s)" or "hostname(s)" */
				__( 'Cloudflare cache purged for %1$d %2$s. Please allow up to 30 seconds for changes to take effect.', 'wp-cloudflare-cache' ),
				$purged,
				'hostname' === $type ? _n( 'hostname', 'hostnames', $purged, 'wp-cloudflare-cache' ) : _n( 'URL', 'URLs', $purged, 'wp-cloudflare-cache' )
			),
		];
	}

	/**
	 * AJAX handler for the sidebar's Custom Purge modal.
	 */
	public function ajax_custom_purge() {
		check_ajax_referer( 'sfcfc_ajax_nonce', 'nonce' );

		if ( ! $this->instance->can_current_user_purge_cache() ) {
			wp_send_json_error( array(
				'message' => __( 'You do not have permission to purge the cache.', 'wp-cloudflare-cache' ),
				'type'    => 'error',
			) );
		}

		$type = ( 'hostname' === sanitize_key( wp_unslash( $_POST['purge_type'] ?? '' ) ) ) ? 'hostname' : 'url';
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Each line is sanitized/validated below (sanitize_text_field() or esc_url_raw()) before use.
		$raw  = isset( $_POST['items'] ) ? wp_unslash( $_POST['items'] ) : '';

		$items = array();
		foreach ( preg_split( '/\r\n|\n|\r/', $raw ) as $line ) {
			$line = trim( $line );

			if ( '' === $line ) {
				continue;
			}

			if ( 'hostname' === $type ) {
				$items[] = sanitize_text_field( preg_replace( '#^https?://#i', '', untrailingslashit( $line ) ) );
			} else {
				$items[] = ( 0 === strpos( $line, 'http' ) ) ? esc_url_raw( $line ) : esc_url_raw( home_url( '/' . ltrim( $line, '/' ) ) );
			}
		}

		$items  = array_values( array_unique( array_filter( $items ) ) );
		$result = $this->purge_custom( $type, $items );

		$this->sfcfc_send_purge_result( $result, 'hostname' === $type ? 'Custom Purge (Hostnames)' : 'Custom Purge (URLs)' );
	}

	/**
	 * AJAX handler: regenerates the remote purge key, invalidating the old URL.
	 */
	public function ajax_regenerate_purge_key() {
		check_ajax_referer( 'sfcfc_ajax_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to do this.', 'wp-cloudflare-cache' ), 'type' => 'error' ) );
		}

		$this->instance->set_single_config( 'purge_secret_key', wp_generate_password( 20, false ) );
		$this->instance->update_config();

		wp_send_json_success( array(
			'message' => __( 'New purge key generated.', 'wp-cloudflare-cache' ),
			'type'    => 'success',
			'url'     => $this->build_purge_url(),
		) );
	}

	/**
	 * Fires non-blocking requests to re-prime the cache right after a URL purge, so the next real
	 * visitor doesn't hit a cold miss. Never used for a full purge_all (nothing specific to warm).
	 *
	 * @param array<int, string> $urls
	 */
	private function warm_urls( array $urls ) {
		foreach ( array_slice( $urls, 0, 30 ) as $url ) {
			wp_remote_get( $url, array( 'timeout' => 3, 'blocking' => false ) );
		}
	}

	/**
	 * Whether "Automatically preload the pages you have purged from cache" should fire: both the
	 * master Enable Preloader switch and this specific behaviour need to be on.
	 *
	 * @return bool
	 */
	private function preloader_should_run_on_purge() {
		return 'on' === $this->instance->get( 'enable_preloader' ) && 'on' === $this->instance->get( 'preloader_start_on_purge', 'on' );
	}

	/**
	 * Builds the URL list for a full preloader run, per the Preload Operation settings.
	 *
	 * @return array<int, string>
	 */
	public function build_preload_urls() {
		$urls = array();

		if ( 'on' === $this->instance->get( 'preload_latest_posts' ) ) {
			$latest_posts = get_posts( array(
				'numberposts' => 20,
				'post_status' => 'publish',
			) );

			foreach ( $latest_posts as $latest_post ) {
				$urls[] = get_permalink( $latest_post );
			}
		}

		if ( 'on' === $this->instance->get( 'preload_sitemap' ) ) {
			$urls = array_merge( $urls, $this->get_sitemap_urls() );
		}

		return array_unique( array_filter( $urls ) );
	}

	/**
	 * Fetches every sitemap listed in the Preload Sitemap URLs setting. Each one may either be a
	 * sitemap index (its own <sitemap> entries are followed one level deep) or a leaf sitemap
	 * (its <url> entries are collected directly).
	 *
	 * @return array<int, string>
	 */
	private function get_sitemap_urls() {
		$paths = trim( (string) $this->instance->get( 'preload_sitemap_paths', '' ) );

		if ( '' === $paths ) {
			$paths = '/wp-sitemap.xml';
		}

		$urls = array();

		foreach ( preg_split( '/[\r\n,]+/', $paths ) as $path ) {
			$path = trim( $path );

			if ( '' === $path ) {
				continue;
			}

			$urls = array_merge( $urls, $this->fetch_sitemap_entries( home_url( '/' . ltrim( $path, '/' ) ) ) );

			if ( count( $urls ) >= 200 ) {
				break;
			}
		}

		return array_slice( array_unique( $urls ), 0, 200 );
	}

	/**
	 * @param string $sitemap_url
	 * @param int    $depth   Recursion depth; stops following nested sitemap indexes past 1.
	 * @param array  $visited URLs already fetched in this call tree, to break reference cycles.
	 * @return array<int, string>
	 */
	private function fetch_sitemap_entries( $sitemap_url, $depth = 0, &$visited = array() ) {
		if ( $depth > 1 || isset( $visited[ $sitemap_url ] ) ) {
			return array();
		}
		$visited[ $sitemap_url ] = true;

		$response = wp_remote_get( esc_url_raw( $sitemap_url ), array( 'timeout' => 10 ) );

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return array();
		}

		libxml_use_internal_errors( true );
		$xml = simplexml_load_string( wp_remote_retrieve_body( $response ) );
		libxml_use_internal_errors( false );

		if ( ! $xml ) {
			return array();
		}

		$urls = array();

		foreach ( $xml->url ?? array() as $entry ) {
			$loc = (string) ( $entry->loc ?? '' );

			if ( '' !== $loc ) {
				$urls[] = $loc;
			}
		}

		/**
		 * A sitemap index, not a leaf sitemap: follow each <sitemap> entry one level deep.
		 */
		foreach ( $xml->sitemap ?? array() as $sub_sitemap ) {
			$sub_url = (string) ( $sub_sitemap->loc ?? '' );

			if ( '' !== $sub_url ) {
				$urls = array_merge( $urls, $this->fetch_sitemap_entries( $sub_url, $depth + 1, $visited ) );
			}
		}

		return $urls;
	}

	/**
	 * Runs a full preloader pass and logs it, used by the manual "Run Preloader Now" button and the
	 * preloader cronjob URL alike.
	 *
	 * @return int Number of URLs preloaded.
	 */
	public function run_preloader() {
		$urls = $this->build_preload_urls();
		$this->warm_urls( $urls );
		$this->log_activity( 'Preloader', true, sprintf( 'Preloaded %d URL(s).', count( $urls ) ) );

		return count( $urls );
	}

	/**
	 * AJAX handler: runs the preloader immediately from the "Run Preloader Now" button.
	 */
	public function ajax_run_preloader() {
		check_ajax_referer( 'sfcfc_ajax_nonce', 'nonce' );

		if ( ! $this->instance->can_current_user_purge_cache() ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to do this.', 'wp-cloudflare-cache' ), 'type' => 'error' ) );
		}

		if ( 'on' !== $this->instance->get( 'enable_preloader' ) ) {
			wp_send_json_error( array( 'message' => __( 'Enable Preloader first.', 'wp-cloudflare-cache' ), 'type' => 'error' ) );
		}

		$count = $this->run_preloader();

		wp_send_json_success( array(
			/* translators: %d: number of URLs preloaded */
			'message' => sprintf( __( 'Preloaded %d URL(s).', 'wp-cloudflare-cache' ), $count ),
			'type'    => 'success',
		) );
	}

	/**
	 * Auto-generated on first use, separate from the purge secret key, so regenerating one never
	 * invalidates the other's URL.
	 *
	 * @return string
	 */
	public function get_preload_secret_key() {
		$key = $this->instance->get_single_config( 'preload_secret_key', '' );
		if ( ! $key ) {
			$key = wp_generate_password( 20, false );
			$this->instance->set_single_config( 'preload_secret_key', $key );
			$this->instance->update_config();
		}
		return $key;
	}

	/**
	 * @return string
	 */
	public function build_preload_url() {
		return add_query_arg( array( 'sfcfc_preload' => 1, 'key' => $this->get_preload_secret_key() ), home_url( '/' ) );
	}

	/**
	 * Handles ?sfcfc_preload=1&key=... on any front-end request, so an external cronjob can start
	 * the preloader. Gated behind Start the preloader via Cronjob, same as Enable Preloader itself.
	 */
	public function maybe_handle_remote_preload() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- External cron/webhook request, authenticated via the hash_equals() secret-key check below instead.
		if ( empty( $_GET['sfcfc_preload'] ) || empty( $_GET['key'] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- External cron/webhook request, authenticated via the hash_equals() secret-key check below instead.
		if ( ! hash_equals( $this->get_preload_secret_key(), sanitize_text_field( wp_unslash( $_GET['key'] ) ) ) ) {
			wp_die( esc_html__( 'Invalid preload key.', 'wp-cloudflare-cache' ), '', array( 'response' => 403 ) );
		}

		if ( 'on' !== $this->instance->get( 'enable_preloader' ) || 'on' !== $this->instance->get( 'preloader_cronjob_enabled' ) ) {
			wp_die( esc_html__( 'The preloader cronjob is not enabled.', 'wp-cloudflare-cache' ), '', array( 'response' => 403 ) );
		}

		$count = $this->run_preloader();

		/* translators: %d: number of URLs preloaded */
		wp_die( esc_html( sprintf( __( 'Preloaded %d URL(s).', 'wp-cloudflare-cache' ), $count ) ), '', array( 'response' => 200 ) );
	}

	/**
	 * AJAX handler: regenerates the preloader cronjob key, invalidating the old URL.
	 */
	public function ajax_regenerate_preload_key() {
		check_ajax_referer( 'sfcfc_ajax_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to do this.', 'wp-cloudflare-cache' ), 'type' => 'error' ) );
		}

		$this->instance->set_single_config( 'preload_secret_key', wp_generate_password( 20, false ) );
		$this->instance->update_config();

		wp_send_json_success( array(
			'message' => __( 'New preloader cronjob key generated.', 'wp-cloudflare-cache' ),
			'type'    => 'success',
			'url'     => $this->build_preload_url(),
		) );
	}

	/**
	 * @param WP_Post|string|null $post A post to purge, or 'all' to purge everything.
	 * @return object|WP_Error
	 */
	public function purge_cache( $post = null ) {
		
		if ( empty( $post ) ) {
			return new WP_Error( 'no_post', __( 'Nothing to purge.', 'wp-cloudflare-cache' ), [ 'status' => 400 ] );
		}
		
		if ( ! $this->has_credentials() ) {
			return new WP_Error( 'missing_credentials', __( 'Please fill Cloudflare details.', 'wp-cloudflare-cache' ), [ 'status' => 401 ] );
		}
		
		$cf_zone_id = $this->instance->get_single_config( 'cf_zone_id', '' );
		
		if ( empty( $cf_zone_id ) ) {
			return new WP_Error( 'missing_zone', __( 'Please select your Cloudflare domain in Settings first.', 'wp-cloudflare-cache' ), [ 'status' => 401 ] );
		}

		if ( 'all' === $post ) {
			$data = [ 'purge_everything' => true ];
		} else {
			/**
			 * $post is either a WP_Post (resolve its URLs now) or an already-resolved array
			 * of URL strings (the queue-drain path, resolved earlier when each post changed).
			 */
			$urls = is_array( $post ) ? $post : $this->resolve_post_purge_urls( $post );

			if ( empty( $urls ) ) {
				return new WP_Error( 'no_urls', __( 'No purgeable URLs were found for this item.', 'wp-cloudflare-cache' ), [ 'status' => 400 ] );
			}

			/**
			 * Cloudflare's purge_cache endpoint accepts at most 30 URLs per request.
			 */
			$data = [ 'files' => array_slice( $urls, 0, 30 ) ];
		}

		$cf_headers = $this->get_api_headers();
		$cf_headers['body'] = json_encode( $data );
		
		$response = wp_remote_post(
			esc_url_raw( "https://api.cloudflare.com/client/v4/zones/$cf_zone_id/purge_cache" ),
			$cf_headers
		);
		
		if ( is_wp_error( $response ) ) {
			$this->debug_log( 'purge_cache request failed - ' . $response->get_error_message() );
			return new WP_Error( 'cloudflare_error', $response->get_error_message(), [ 'status' => 500 ] );
		}
		
		$response_data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $response_data['success'] ) ) {
			$error = $response_data['errors'][0] ?? [ 'message' => 'Unknown Cloudflare error.', 'code' => 500 ];
			$this->debug_log( 'purge_cache rejected by Cloudflare - ' . $error['message'] );
			return new WP_Error( 'cloudflare_error', $error['message'], [ 'status' => $error['code'] ] );
		}

		$purged = ! empty( $data['files'] ) ? implode( ', ', $data['files'] ) : 'everything';

		return (object) [
			'success' => $response_data['success'],
			'message' => "Cloudflare Cache purged for $purged successfully. Please allow up to 30 seconds for changes to take effect.",
		];
	}   	
	
	/**
	 * Sends a purge_cache() result as { success, data: { message, type } }, and logs it.
	 *
	 * @param object|WP_Error $result
	 * @param string          $context Label shown in the Activity Log, e.g. "Purge All Cache".
	 */
	private function sfcfc_send_purge_result( $result, $context = 'Purge' ) {
		if ( is_wp_error( $result ) ) {
			$this->log_activity( $context, false, $result->get_error_message() );
			wp_send_json_error( array(
				'message' => $result->get_error_message(),
				'type'    => 'error',
			) );
		}

		$this->log_activity( $context, true, $result->message );
		wp_send_json_success( array(
			'message' => $result->message,
			'type'    => 'success',
		) );
	}

	/**
	 * AJAX handler: purges the entire Cloudflare cache for this zone.
	 */
	public function sfcfc_purge_everything() {
		check_ajax_referer( 'sfcfc_ajax_nonce', 'nonce' );
		if ( ! $this->instance->can_current_user_purge_cache() ) {
			wp_send_json_error( array(
				'message' => __( 'You do not have permission to purge the cache.', 'wp-cloudflare-cache' ),
				'type'    => 'error',
			) );
		}
		
		$result = $this->purge_cache( 'all' );

		/**
		 * A full purge already covers anything still queued, so skip the redundant drain.
		 */
		if ( ! is_wp_error( $result ) ) {
			update_option( 'sfcfc_purge_queue', array( 'purge_all' => false, 'urls' => array() ) );
		}

		$this->sfcfc_send_purge_result( $result, 'Purge All Cache' );
	}
	
	/**
	 * AJAX handler for the Quick Actions "Purge Latest Post" button: purges only the latest
	 * published post's own URL plus the homepage, ignoring the Purge Options toggles (archives,
	 * feeds, AMP, Custom Purge URLs) that apply to automatic post/page-update purges. Use Custom
	 * Purge for anything beyond that.
	 */
	public function sfcfc_purge_test_config() {
		check_ajax_referer( 'sfcfc_ajax_nonce', 'nonce' );
		if ( ! $this->instance->can_current_user_purge_cache() ) {
			wp_send_json_error( array(
				'message' => __( 'You do not have permission to purge the cache.', 'wp-cloudflare-cache' ),
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

		$urls     = [];
		$page_url = get_permalink( $latest_post );

		if ( filter_var( $page_url, FILTER_VALIDATE_URL ) ) {
			$urls[] = untrailingslashit( $page_url );
		}

		$urls[] = untrailingslashit( home_url() );

		$this->sfcfc_send_purge_result( $this->purge_cache( array_unique( $urls ) ), 'Purge Latest Post' );
	}

	
	/**
	 * Pushes several generic zone settings in a single API call, via Cloudflare's bulk zone-settings
	 * endpoint, instead of one request per setting — used on every settings save, where looping a
	 * per-setting call would otherwise fire a dozen-plus sequential blocking requests.
	 *
	 * @param string $cf_zone_id
	 * @param array<string, mixed> $settings setting_id => value.
	 * @param array  $overrides
	 * @return true|WP_Error
	 */
	public function set_zone_settings_bulk( $cf_zone_id, array $settings, $overrides = array() ) {
		$items = array();

		foreach ( $settings as $setting_id => $value ) {
			$items[] = array( 'id' => $setting_id, 'value' => $value );
		}

		$cf_headers           = $this->get_api_headers( $overrides );
		$cf_headers['method'] = 'PATCH';
		$cf_headers['body']   = json_encode( array( 'items' => $items ) );

		$response = wp_remote_post(
			esc_url_raw( "https://api.cloudflare.com/client/v4/zones/$cf_zone_id/settings" ),
			$cf_headers
		);

		if ( is_wp_error( $response ) ) {
			$this->debug_log( 'set_zone_settings_bulk request failed - ' . $response->get_error_message() );
			return new WP_Error( 'cloudflare_error', $response->get_error_message(), [ 'status' => 500 ] );
		}

		$response_data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $response_data['success'] ) ) {
			$error = $response_data['errors'][0] ?? [ 'message' => 'Unknown Cloudflare error.', 'code' => 500 ];
			$this->debug_log( 'set_zone_settings_bulk rejected by Cloudflare - ' . $error['message'] );
			return new WP_Error( 'cloudflare_error', $error['message'], [ 'status' => $error['code'] ] );
		}

		return true;
	}

	public function sync_cache_rule_state( $cf_zone_id, $overrides = array() ) {
		$enabled = ( $overrides['cf_cache_enabled'] ?? $this->instance->get( 'cf_cache_enabled', 'on' ) ) === 'on';

		if ( ! $enabled ) {
			return $this->remove_cache_rules( $cf_zone_id );
		}

		return $this->setup_cache_rules( $cf_zone_id, $overrides );
	}
	
	/**
	 * Creates or updates this plugin's Cache Rule for the given zone.
	 *
	 * @param string $cf_zone_id
	 * @param array  $overrides
	 * @return true|WP_Error
	 */
	public function setup_cache_rules( $cf_zone_id, $overrides = array() ) {
		$marker   = '[DO NOT EDIT] Managed by SuperFlare Plugin';
		$endpoint = "https://api.cloudflare.com/client/v4/zones/$cf_zone_id/rulesets/phases/http_request_cache_settings/entrypoint";

		/**
		 * Recognizes this plugin's rule under its old CFCA_ prefix too (both the pre- and
		 * post-"[DO NOT EDIT]" formats), so sites upgrading from before the SuperFlare rename get
		 * their existing rule updated in place instead of a duplicate rule alongside it.
		 */
		$host = wp_parse_url( home_url(), PHP_URL_HOST );
		$path = wp_parse_url( home_url(), PHP_URL_PATH );
		$rule = array(
			'description'       => $marker,
			'expression'        => $this->build_cache_expression( $host, $path, $overrides ),
			'action'            => 'set_cache_settings',
			'action_parameters' => $this->build_cache_action_parameters( $overrides ),
		);
		$rules = $this->replace_cache_rule( $endpoint, array( $marker, '[DO NOT EDIT] SFCFC - Cache Everything', '[DO NOT EDIT] CFCA - Cache Everything', 'CFCA - Cache Everything' ), $rule, $overrides );

		if ( is_wp_error( $rules ) ) {
			return $rules;
		}
		
		return $this->put_cache_rules( $endpoint, $rules, $overrides );
	}
	
	private function replace_cache_rule( $endpoint, $markers, $rule, $overrides = array() ) {
		$existing = wp_remote_get( esc_url_raw( $endpoint ), $this->get_api_headers( $overrides ) );

		if ( is_wp_error( $existing ) ) {
			return $existing;
		}

		$existing_data = json_decode( wp_remote_retrieve_body( $existing ), true );

		if ( empty( $existing_data['success'] ) && 404 !== wp_remote_retrieve_response_code( $existing ) ) {
			$error = $existing_data['errors'][0] ?? array( 'message' => 'Unknown Cloudflare error.', 'code' => 500 );
			return new WP_Error( 'cloudflare_error', $error['message'], array( 'status' => $error['code'] ) );
		}

		$existing_rules = $existing_data['result']['rules'] ?? array();

		if ( ! is_array( $existing_rules ) ) {
			$existing_rules = array();
		}

		$rules    = array();
		$replaced = false;

		foreach ( $existing_rules as $existing_rule ) {
			if ( ! in_array( $existing_rule['description'] ?? '', $markers, true ) ) {
				$rules[] = $existing_rule;
				continue;
			}

			if ( ! $replaced ) {
				$rules[]  = $rule;
				$replaced = true;
			}
		}

		if ( ! $replaced ) {
			$rules[] = $rule;
		}

		return $rules;
	}
	
	/**
	 * Builds the Cache Rule's action_parameters (cache on, plus Edge Cache TTL override if set).
	 *
	 * @param array $overrides
	 * @return array
	 */
	private function build_cache_action_parameters( $overrides = array() ) {
		$edge_ttl    = $overrides['cf_edge_ttl'] ?? $this->instance->get( 'cf_edge_ttl' );
		$browser_ttl = $overrides['cf_browser_ttl'] ?? $this->instance->get( 'cf_browser_ttl' );
		$params      = array( 'cache' => true );
		
		if ( ! empty( $edge_ttl ) ) {
			$params['edge_ttl'] = array(
				'mode'    => 'override_origin',
				'default' => (int) $edge_ttl,
			);
		} else {
			$params['edge_ttl'] = array( 'mode' => 'respect_origin' );
		}
		
		if ( ! empty( $browser_ttl ) ) {
			$params['browser_ttl'] = array(
				'mode'    => 'override_origin',
				'default' => (int) $browser_ttl,
			);
		} else {
			$params['browser_ttl'] = array( 'mode' => 'respect_origin' );
		}
		
		return $params;
	}
	
	/**
	 * Builds the Cache Rule's match expression: this host, minus every bypass condition.
	 *
	 * @param string $host
	 * @param string $path
	 * @param array  $overrides
	 * @return string
	 */
	private function build_cache_expression( $host, $path = '', $overrides = array() ) {
		$get = function ( $key, $default = '' ) use ( $overrides ) {
			return $overrides[ $key ] ?? $this->instance->get( $key, $default );
		};
		
		$conditions   = array();
		$conditions[] = sprintf( 'http.host wildcard "%s*"', $host );

		$path = rtrim( (string) $path, '/' );
		if ( '' !== $path ) {
			$conditions[] = sprintf( '(http.request.uri.path eq "%1$s" or starts_with(http.request.uri.path, "%1$s/"))', str_replace( '"', '', $path ) );
		}
		$conditions[] = 'not http.cookie contains "wordpress"';
		$conditions[] = 'not http.cookie contains "comment_"';
		$conditions[] = 'not http.cookie contains "wp-postpass_"';
		$conditions[] = 'not http.request.uri contains "/wp-admin"';
		$conditions[] = 'not http.request.uri contains "/wp-login"';
		
		if ( 'on' === $get( 'bypass_sitemap', 'on' ) ) {
			$conditions[] = 'not http.request.uri.path contains ".xml"';
		}
		if ( 'on' === $get( 'bypass_robots', 'on' ) ) {
			$conditions[] = 'not http.request.uri.path contains "/robots.txt"';
		}
		if ( 'on' === $get( 'bypass_ajax', 'on' ) ) {
			$conditions[] = 'not http.request.uri contains "/wp-admin/admin-ajax.php"';
		}
		if ( 'on' === $get( 'bypass_rest_api', 'on' ) ) {
			$conditions[] = 'not http.request.uri contains "/wp-json"';
		}
		
		$exclude_urls = trim( (string) $get( 'cache_exclude_urls' ) );
		if ( '' !== $exclude_urls ) {
			foreach ( preg_split( '/\r\n|\n|\r/', $exclude_urls ) as $path ) {
				$path = trim( str_replace( array( '/*', '*' ), '', trim( $path ) ) );
				if ( '' !== $path ) {
					$conditions[] = sprintf( 'not http.request.uri contains "%s"', str_replace( '"', '', $path ) );
				}
			}
		}

		$exclude_cookies = trim( (string) $get( 'cache_exclude_cookies' ) );
		if ( '' !== $exclude_cookies ) {
			foreach ( preg_split( '/\r\n|\n|\r/', $exclude_cookies ) as $cookie ) {
				$cookie = trim( $cookie );
				if ( '' !== $cookie ) {
					$conditions[] = sprintf( 'not http.cookie contains "%s"', str_replace( '"', '', $cookie ) );
				}
			}
		}

		$exclude_query_params = trim( (string) $get( 'cache_exclude_query_params' ) );
		if ( '' !== $exclude_query_params ) {
			foreach ( preg_split( '/\r\n|\n|\r/', $exclude_query_params ) as $param ) {
				$param = trim( str_replace( '*', '', $param ) );
				if ( '' !== $param ) {
					$conditions[] = sprintf( 'not http.request.uri.query contains "%s"', str_replace( '"', '', $param ) );
				}
			}
		}
		
		return '(' . implode( ' and ', $conditions ) . ')';
	}
	
	/**
	 * Removes only the Cache Rule this plugin created (matched by description), leaving others intact.
	 *
	 * @param string $cf_zone_id
	 * @return true|WP_Error
	 */
	public function remove_cache_rules( $cf_zone_id ) {
		$endpoint = "https://api.cloudflare.com/client/v4/zones/$cf_zone_id/rulesets/phases/http_request_cache_settings/entrypoint";
		/**
		 * Matches both the current marker and every marker used before the SuperFlare rename, so
		 * deactivating on a site that's never re-saved its settings still removes its actual rule.
		 */
		$rules    = $this->get_foreign_cache_rules( $endpoint, array( '[DO NOT EDIT] Managed by SuperFlare Plugin', '[DO NOT EDIT] SFCFC - Cache Everything', '[DO NOT EDIT] CFCA - Cache Everything', 'CFCA - Cache Everything' ) );

		if ( is_wp_error( $rules ) ) {
			return $rules;
		}
		
		return $this->put_cache_rules( $endpoint, $rules );
	}
	
	/**
	 * Fetches the zone's cache-settings ruleset, minus any rules matching the given descriptions.
	 *
	 * @param string $endpoint
	 * @param array  $markers_to_strip Rule descriptions to exclude from the returned list.
	 * @param array  $overrides
	 * @return array|WP_Error
	 */
	private function get_foreign_cache_rules( $endpoint, $markers_to_strip, $overrides = array() ) {
		$existing = wp_remote_get( esc_url_raw( $endpoint ), $this->get_api_headers( $overrides ) );

		if ( is_wp_error( $existing ) ) {
			return $existing;
		}

		$existing_data = json_decode( wp_remote_retrieve_body( $existing ), true );

		if ( empty( $existing_data['success'] ) && 404 !== wp_remote_retrieve_response_code( $existing ) ) {
			$error = $existing_data['errors'][0] ?? array( 'message' => 'Unknown Cloudflare error.', 'code' => 500 );
			return new WP_Error( 'cloudflare_error', $error['message'], array( 'status' => $error['code'] ) );
		}

		$existing_rules = $existing_data['result']['rules'] ?? array();

		if ( ! is_array( $existing_rules ) ) {
			$existing_rules = array();
		}

		$rules = array();

		foreach ( $existing_rules as $rule ) {
			if ( ! in_array( $rule['description'] ?? '', $markers_to_strip, true ) ) {
				$rules[] = $rule;
			}
		}
		
		return $rules;
	}
	
	/**
	 * @param string $endpoint
	 * @param array  $rules
	 * @param array  $overrides
	 * @return true|WP_Error
	 */
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
	
	public function activate_plugin() {
		set_transient( 'sfcfc_activation_redirect', true, 30 );

		$cf_zone_id = $this->instance->get_single_config( 'cf_zone_id', '' );

		if ( ! empty( $cf_zone_id ) && $this->has_credentials() && 'on' === $this->instance->get( 'cf_cache_enabled', 'on' ) ) {
			$this->setup_cache_rules( $cf_zone_id );
		}
	}

	public function deactivate_plugin() {
		$cf_zone_id = $this->instance->get_single_config( 'cf_zone_id', '' );

		if ( ! empty( $cf_zone_id ) && $this->has_credentials() ) {
			$this->remove_cache_rules( $cf_zone_id );
		}

		wp_clear_scheduled_hook( 'sfcfc_process_purge_queue' );
	}

	public function maybe_redirect_after_activation() {
		if ( ! get_transient( 'sfcfc_activation_redirect' ) ) {
			return;
		}

		delete_transient( 'sfcfc_activation_redirect' );

		if ( wp_doing_ajax() || isset( $_GET['activate-multi'] ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$cf_zone_id = $this->instance->get_single_config( 'cf_zone_id', '' );
		$page       = ( ! empty( $cf_zone_id ) && $this->has_credentials() ) ? 'superflare-settings' : 'superflare-setup-wizard';

		wp_safe_redirect( admin_url( 'admin.php?page=' . $page ) );
		exit;
	}
	
	/**
	 * Zones list read from cache, so the Settings page never blocks on a live Cloudflare request.
	 *
	 * @return array Zone ID => zone name.
	 */
	public function get_cached_zones() {
		return $this->instance->get_single_config( 'cf_zones_cache', array() );
	}
	
	/**
	 * Fetches the zones list live and stores it for get_cached_zones() to read.
	 *
	 * @param array $overrides
	 * @return array Zone ID => zone name.
	 */
	public function refresh_zones_cache( $overrides = array() ) {
		$zones = $this->list_zones( $overrides );
		$this->instance->set_single_config( 'cf_zones_cache', $zones );
		$this->instance->update_config();
		return $zones;
	}
	
	/**
	 * @param array $overrides
	 * @return array Zone ID => zone name.
	 */
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
	 * Whether the hostname has at least one DNS record proxied through Cloudflare (the orange cloud).
	 *
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
	 * Builds the request headers for the currently selected authentication method (Token or Key).
	 * Sets an explicit 15s timeout — WP's 5s HTTP API default is too tight for Cloudflare's API
	 * under load, and these calls are blocking (fired synchronously during settings save).
	 *
	 * @param array $overrides
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

		return array( 'headers' => $headers, 'timeout' => 15 );
	}
	
	/**
	 * Whether enough Cloudflare credentials are filled in for the selected authentication method.
	 *
	 * @param array $overrides
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

	public function get_zone_analytics( $hours = 24 ) {
		$cf_zone_id = $this->instance->get_single_config( 'cf_zone_id', '' );

		if ( empty( $cf_zone_id ) || ! $this->has_credentials() ) {
			return new WP_Error( 'missing_credentials', __( 'Please connect Cloudflare first.', 'wp-cloudflare-cache' ) );
		}

		$cache_key = 'sfcfc_analytics_' . md5( $cf_zone_id );
		$cached    = get_transient( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		$since = gmdate( 'Y-m-d\TH:i:s\Z', time() - ( $hours * HOUR_IN_SECONDS ) );
		$until = gmdate( 'Y-m-d\TH:i:s\Z', time() );

		$query = 'query { viewer { zones(filter: { zoneTag: "' . str_replace( '"', '', $cf_zone_id ) . '" }) { '
			. 'httpRequests1hGroups(limit: 24, filter: { datetime_geq: "' . $since . '", datetime_leq: "' . $until . '" }, orderBy: [datetime_ASC]) { '
			. 'dimensions { datetime } '
			. 'sum { requests cachedRequests bytes cachedBytes threats } '
			. 'uniq { uniques } '
			. '} } } }';

		$cf_headers         = $this->get_api_headers();
		$cf_headers['body'] = json_encode( array( 'query' => $query ) );

		$response = wp_remote_post( esc_url_raw( 'https://api.cloudflare.com/client/v4/graphql' ), $cf_headers );

		if ( is_wp_error( $response ) ) {
			$this->debug_log( 'Analytics fetch failed: ' . $response->get_error_message() );
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! empty( $body['errors'] ) ) {
			$message = $body['errors'][0]['message'] ?? __( 'Unknown Cloudflare API error.', 'wp-cloudflare-cache' );
			$this->debug_log( 'Analytics fetch failed: ' . $message );
			return new WP_Error( 'sfcfc_api_error', $message );
		}

		$groups = $body['data']['viewer']['zones'][0]['httpRequests1hGroups'] ?? array();

		$totals = array(
			'requests'        => 0,
			'cached_requests' => 0,
			'cached_bytes'    => 0,
			'threats'         => 0,
			'uniques'         => 0,
		);

		$by_time = array();

		foreach ( $groups as $group ) {
			$requests = (int) ( $group['sum']['requests'] ?? 0 );
			$cached   = (int) ( $group['sum']['cachedRequests'] ?? 0 );

			$totals['requests']        += $requests;
			$totals['cached_requests'] += $cached;
			$totals['cached_bytes']    += (int) ( $group['sum']['cachedBytes'] ?? 0 );
			$totals['threats']         += (int) ( $group['sum']['threats'] ?? 0 );
			$totals['uniques']         += (int) ( $group['uniq']['uniques'] ?? 0 );

			$time = $group['dimensions']['datetime'] ?? '';

			$by_time[ $time ] = array(
				'time'           => $time,
				'requests'       => $requests,
				'cachedRequests' => $cached,
			);
		}

		$now    = time() - ( time() % HOUR_IN_SECONDS );
		$hourly = array();

		for ( $i = $hours - 1; $i >= 0; $i-- ) {
			$bucket_time = gmdate( 'Y-m-d\TH:00:00\Z', $now - ( $i * HOUR_IN_SECONDS ) );
			$hourly[]    = $by_time[ $bucket_time ] ?? array( 'time' => $bucket_time, 'requests' => 0, 'cachedRequests' => 0 );
		}

		$result = array(
			'requests'        => $totals['requests'],
			'cached_requests' => $totals['cached_requests'],
			'cached_pct'      => $totals['requests'] ? round( ( $totals['cached_requests'] / $totals['requests'] ) * 100, 1 ) : 0,
			'bandwidth'       => size_format( $totals['cached_bytes'] ),
			'visitors'        => $totals['uniques'],
			'threats'         => $totals['threats'],
			'hourly'          => $hourly,
		);

		set_transient( $cache_key, $result, 30 * MINUTE_IN_SECONDS );

		return $result;
	}

	public function ajax_get_analytics() {
		check_ajax_referer( 'sfcfc_ajax_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'wp-cloudflare-cache' ) ), 403 );
		}

		$result = $this->get_zone_analytics( 24 );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( $result );
	}
}
