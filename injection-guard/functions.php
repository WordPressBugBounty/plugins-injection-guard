<?php if ( ! defined( 'ABSPATH' ) ) exit;

	require_once( dirname( __FILE__ ) . '/guard.php' );

	//FOR QUICK DEBUGGING (administrators only)

	if(!function_exists('pre')){

		function pre($data){

			if(isset($_GET['debug']) && function_exists('current_user_can') && current_user_can('manage_options')){

				pree($data);

			}

		}

	}

	if(!function_exists('pree')){

		function pree($data){

			if(!function_exists('current_user_can') || !current_user_can('manage_options')){
				return;
			}

			echo '<pre>';

			echo esc_html(print_r($data, true));

			echo '</pre>';

		}

	}

	/* ------------------------------------------------------------------
	 * SETTINGS
	 * ---------------------------------------------------------------- */

	if(!function_exists('ig_default_settings')){

		function ig_default_settings(){
			return apply_filters('ig_default_settings', array(
				'logging'            => 1,       // record query-string parameters
				'block_status'       => 404,     // 404 (default, looks like "not found") or 403
				'heuristics'         => 'log',   // off | log | block (signature scan of the query string)
				'exempt_admins'      => 1,       // logged-in administrators are never blocked/logged
				'log_blocked'        => 1,       // keep a list of blocked / detected requests
				'anonymize_ip'       => 1,       // store anonymised IPs in the blocked list
				'ip_header'          => 'REMOTE_ADDR',
				'log_limit'          => 500,     // max number of URLs kept in the log
				'log_retention_days' => 0,       // 0 = keep until limit is reached
				'blocked_limit'      => 200,     // max entries in the blocked list
				'safe_params'        => '',      // never logged, never blocked (supports prefix*)
				'global_blacklist'   => '',      // blocked on every URL (supports prefix*)
			));
		}

	}

	if(!function_exists('ig_get_settings')){

		function ig_get_settings(){
			$saved = get_option('ig_settings');
			$saved = is_array($saved) ? $saved : array();
			return wp_parse_args($saved, ig_default_settings());
		}

	}

	if(!function_exists('ig_parse_list')){

		/**
		 * "utm_*, fbclid\nGCLID" => array('utm_*', 'fbclid', 'gclid')
		 */
		function ig_parse_list($raw){
			$out = array();
			if(is_array($raw)){
				$raw = implode("\n", $raw);
			}
			$parts = preg_split('/[\s,]+/', (string)$raw, -1, PREG_SPLIT_NO_EMPTY);
			foreach((array)$parts as $part){
				$wild = (substr($part, -1) === '*');
				$name = ig_normalize_param(rtrim($part, '*'));
				if($name === ''){
					continue;
				}
				if($wild && strlen($name) < 2){
					continue; // a bare "a*" would be far too broad
				}
				$out[$name . ($wild ? '*' : '')] = $name . ($wild ? '*' : '');
				if(count($out) >= 500){
					break;
				}
			}
			return array_values($out);
		}

	}

	if(!function_exists('ig_safe_params')){
		function ig_safe_params(){
			$s = ig_get_settings();
			return apply_filters('ig_safe_params', ig_parse_list($s['safe_params']));
		}
	}

	if(!function_exists('ig_global_blacklist')){
		function ig_global_blacklist(){
			$s = ig_get_settings();
			return apply_filters('ig_global_blacklist', ig_parse_list($s['global_blacklist']));
		}
	}

	if(!function_exists('ig_param_matches')){
		function ig_param_matches($param, $patterns){
			foreach((array)$patterns as $p){
				$p = (string)$p;
				if($p === (string)$param){
					return true;
				}
				if(substr($p, -1) === '*'){
					$prefix = substr($p, 0, -1);
					if($prefix !== '' && strpos((string)$param, $prefix) === 0){
						return true;
					}
				}
			}
			return false;
		}
	}

	if(!function_exists('ig_remove_matching')){
		function ig_remove_matching($keys, $patterns){
			$out = array();
			foreach((array)$keys as $k){
				if(!ig_param_matches($k, $patterns)){
					$out[] = $k;
				}
			}
			return $out;
		}
	}

	if(!function_exists('ig_find_matching')){
		function ig_find_matching($keys, $patterns){
			$out = array();
			foreach((array)$keys as $k){
				if(ig_param_matches($k, $patterns)){
					$out[] = $k;
				}
			}
			return $out;
		}
	}

	if(!function_exists('ig_sanitize_settings')){

		function ig_sanitize_settings($in){

			$in  = is_array($in) ? wp_unslash($in) : array();
			$def = ig_default_settings();

			$clean = array();

			foreach(array('logging', 'exempt_admins', 'log_blocked', 'anonymize_ip') as $flag){
				$clean[$flag] = empty($in[$flag]) ? 0 : 1;
			}

			$clean['block_status'] = (isset($in['block_status']) && (int)$in['block_status'] === 403) ? 403 : 404;

			$clean['heuristics'] = (isset($in['heuristics']) && in_array($in['heuristics'], array('off', 'log', 'block'), true)) ? $in['heuristics'] : $def['heuristics'];

			$headers = array_keys(ig_ip_header_choices());
			$clean['ip_header'] = (isset($in['ip_header']) && in_array($in['ip_header'], $headers, true)) ? $in['ip_header'] : 'REMOTE_ADDR';

			$clean['log_limit']          = isset($in['log_limit']) ? min(5000, max(10, (int)$in['log_limit'])) : $def['log_limit'];
			$clean['log_retention_days'] = isset($in['log_retention_days']) ? min(3650, max(0, (int)$in['log_retention_days'])) : 0;
			$clean['blocked_limit']      = isset($in['blocked_limit']) ? min(1000, max(20, (int)$in['blocked_limit'])) : $def['blocked_limit'];

			$clean['safe_params']      = implode("\n", ig_parse_list(isset($in['safe_params']) ? $in['safe_params'] : ''));
			$clean['global_blacklist'] = implode("\n", ig_parse_list(isset($in['global_blacklist']) ? $in['global_blacklist'] : ''));

			return apply_filters('ig_sanitize_settings', $clean, $in);
		}

	}

	/* ------------------------------------------------------------------
	 * IP HELPERS
	 * ---------------------------------------------------------------- */

	if(!function_exists('ig_ip_header_choices')){
		function ig_ip_header_choices(){
			return array(
				'REMOTE_ADDR'            => __('Direct connection (REMOTE_ADDR) - recommended', 'injection-guard'),
				'HTTP_CF_CONNECTING_IP'  => __('Cloudflare (CF-Connecting-IP)', 'injection-guard'),
				'HTTP_X_REAL_IP'         => __('Reverse proxy (X-Real-IP)', 'injection-guard'),
				'HTTP_X_FORWARDED_FOR'   => __('Reverse proxy (X-Forwarded-For)', 'injection-guard'),
			);
		}
	}

	if(!function_exists('ig_get_ip')){

		/**
		 * Visitor IP. Only REMOTE_ADDR is trusted unless the administrator explicitly
		 * says the site sits behind a proxy (forwarded headers can be forged by anyone).
		 */
		function ig_get_ip()
		{
			$settings = ig_get_settings();
			$header   = isset($settings['ip_header']) ? $settings['ip_header'] : 'REMOTE_ADDR';
			$allowed  = ig_ip_header_choices();

			if(!isset($allowed[$header])){
				$header = 'REMOTE_ADDR';
			}

			$ip = '';

			if(isset($_SERVER[$header])){
				$ip = sanitize_text_field(wp_unslash($_SERVER[$header]));
				if($header === 'HTTP_X_FORWARDED_FOR' && strpos($ip, ',') !== false){
					$parts = explode(',', $ip);
					$ip    = trim($parts[0]);
				}
			}

			if(!filter_var($ip, FILTER_VALIDATE_IP) && isset($_SERVER['REMOTE_ADDR'])){
				$ip = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']));
			}

			$ip = apply_filters('ig_client_ip', $ip);

			return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '';
		}

	}

	if(!function_exists('ig_get_logged_ip')){
		function ig_get_logged_ip(){
			$ip = ig_get_ip();
			$s  = ig_get_settings();
			if($ip !== '' && !empty($s['anonymize_ip']) && function_exists('wp_privacy_anonymize_ip')){
				$ip = wp_privacy_anonymize_ip($ip);
			}
			return $ip;
		}
	}

	/* ------------------------------------------------------------------
	 * REQUEST INSPECTION
	 * ---------------------------------------------------------------- */

	if(!function_exists('ig_signatures')){

		/**
		 * Conservative attack signatures (id => regex). Kept deliberately narrow to avoid false positives.
		 * Extend through the "ig_signatures" filter.
		 */
		function ig_signatures(){
			$sigs = array(
				'xss-script-tag'   => '/<\s*script\b/i',
				'xss-js-uri'       => '/javascript\s*:/i',
				'xss-event-attr'   => '/\bon(?:error|load|click|mouseover|focus)\s*=/i',
				'sqli-union'       => '/union(?:\s|\+|\/\*.{0,50}?\*\/)+(?:all(?:\s|\+)+)?select/i',
				'sqli-schema'      => '/information_schema/i',
				'sqli-time'        => '/\b(?:sleep|benchmark)\s*\(\s*\d/i',
				'sqli-tautology'   => '/[\'"]\s*(?:or|and)\s*[\'"]?\d+[\'"]?\s*=\s*[\'"]?\d+/i',
				'path-traversal'   => '/(?:\.\.[\/\\\\]){2,}/',
				'null-byte'        => '/\x00/',
				'php-exec'         => '/\b(?:base64_decode|eval|assert|system|passthru|shell_exec|proc_open)\s*\(/i',
				'stream-wrapper'   => '/(?:php|expect|phar|data|file):\/\//i',
				'sensitive-file'   => '/(?:wp-config\.php|\/etc\/passwd|\/proc\/self)/i',
			);
			return apply_filters('ig_signatures', $sigs);
		}

	}

	if(!function_exists('ig_scan_request')){

		/**
		 * Returns the id of the first matching signature, or '' if the string looks fine.
		 */
		function ig_scan_request($raw){

			$raw = (string)$raw;

			if($raw === ''){
				return '';
			}

			$raw = substr($raw, 0, 4096);

			$once  = rawurldecode(str_replace('+', ' ', $raw));
			$twice = rawurldecode($once);

			$variants = array_unique(array($raw, $once, $twice));

			foreach(ig_signatures() as $id => $regex){
				foreach($variants as $variant){
					if(@preg_match($regex, $variant)){
						return (string)$id;
					}
				}
			}

			return '';
		}

	}

	if(!function_exists('ig_is_exempt')){

		function ig_is_exempt(){

			if(defined('DOING_CRON') && DOING_CRON){
				return true;
			}

			$settings = ig_get_settings();
			$exempt   = false;

			if(!empty($settings['exempt_admins']) && function_exists('is_user_logged_in') && is_user_logged_in() && current_user_can('manage_options')){
				$exempt = true;
			}

			return (bool)apply_filters('ig_is_exempt', $exempt);
		}

	}

	if(!function_exists('ig_deny')){

		function ig_deny($code = 404){

			$code = ((int)$code === 403) ? 403 : 404;

			nocache_headers();

			if($code === 404 && function_exists('wp_is_block_theme') && !wp_is_block_theme() && locate_template('404.php')){
				global $wp_query;
				if($wp_query){
					$wp_query->set_404();
				}
				status_header(404);
				get_template_part('404');
				exit();
			}

			wp_die(
				esc_html($code === 404 ? __('The requested page could not be found.', 'injection-guard') : __('This request was blocked by Injection Guard.', 'injection-guard')),
				esc_html($code === 404 ? __('Not found', 'injection-guard') : __('Request blocked', 'injection-guard')),
				array('response' => $code)
			);
		}

	}

	if(!function_exists('ig_register_block')){

		/**
		 * Records a blocked (or just detected) request. Rate-limited so that an attack can
		 * not turn the log itself into a database write flood.
		 */
		function ig_register_block($uri, $param, $reason, $blocked = true){

			$uri    = ig_clean_uri_index($uri);
			$param  = substr(sanitize_text_field((string)$param), 0, IG_MAX_PARAM_LENGTH);
			$reason = sanitize_key($reason);

			do_action('ig_request_blocked', $uri, $param, $reason, (bool)$blocked);

			$settings = ig_get_settings();

			if(empty($settings['log_blocked'])){
				return;
			}

			if(get_transient('ig_blocked_write_lock')){
				return;
			}

			set_transient('ig_blocked_write_lock', 1, 2);

			$log = get_option('ig_blocked_log');
			$log = is_array($log) ? $log : array();

			array_unshift($log, array(
				't'       => time(),
				'uri'     => $uri,
				'param'   => $param,
				'reason'  => $reason,
				'ip'      => ig_get_logged_ip(),
				'blocked' => $blocked ? 1 : 0,
			));

			$log = array_slice($log, 0, max(20, (int)$settings['blocked_limit']));

			update_option('ig_blocked_log', $log, false);
		}

	}

	if(!function_exists('ig_start')){

		function ig_start(){

			if(ig_is_exempt()){
				return;
			}

			$settings  = ig_get_settings();
			$guard_obj = new guard_wordpress;
			$guard_obj->init();

			do_action('ig_before_guard', $guard_obj, $settings);

			$guard_obj->update_log();

			$uri  = $guard_obj->wp_uri_cleaned();
			$aus  = $guard_obj->available_uri_strings();

			if(empty($aus)){
				return;
			}

			$check = ig_remove_matching($aus, ig_safe_params());

			// 1) blocked on every URL
			$hit = ig_find_matching($check, ig_global_blacklist());
			if(!empty($hit)){
				ig_register_block($uri, reset($hit), 'global-blacklist', true);
				ig_deny($settings['block_status']);
			}

			// 2) blocked on this URL (path compared case-insensitively)
			$ig_blacklisted = $guard_obj->get_blacklisted();

			foreach($ig_blacklisted as $bl_uri => $bl_params){

				if(strtolower((string)$bl_uri) !== strtolower($uri) || !is_array($bl_params)){
					continue;
				}

				$bl_norm = array_map('ig_normalize_param', array_map('strval', $bl_params));
				$diff    = array_values(array_intersect($check, $bl_norm));

				if(!empty($diff)){
					ig_register_block($uri, reset($diff), 'blacklist', true);
					ig_deny($settings['block_status']);
				}
			}

			// 3) signature scan
			if($settings['heuristics'] !== 'off'){
				$sig = ig_scan_request($guard_obj->get_raw_query());
				if($sig !== ''){
					$block = ($settings['heuristics'] === 'block');
					ig_register_block($uri, $sig, 'signature', $block);
					if($block){
						ig_deny(403);
					}
				}
			}

		}

	}

	/* ------------------------------------------------------------------
	 * AJAX / ADMIN-POST HANDLERS
	 * ---------------------------------------------------------------- */

	if(!function_exists('ig_ajax_check')){

		function ig_ajax_check(){
			if(!check_ajax_referer('ig_nonce_action', 'ig_nonce', false)){
				wp_send_json_error(array('message' => __('Sorry, your nonce did not verify.', 'injection-guard')), 403);
			}
			if(!is_super_admin() || !current_user_can('manage_options')){
				wp_send_json_error(array('message' => __('Unauthorized action.', 'injection-guard')), 403);
			}
		}

	}

	if(!function_exists('ig_update')){
		function ig_update(){

			ig_ajax_check();

			$val  = isset($_POST['val']) ? ig_normalize_param(wp_unslash($_POST['val'])) : '';

			$type = (isset($_POST['type']) && $_POST['type'] === 'whitelist') ? 'whitelist' : 'blacklist';

			$uri  = isset($_POST['uri_index']) ? ig_clean_uri_index(wp_unslash($_POST['uri_index'])) : '';

			if($val === '' || $uri === ''){
				wp_send_json_error(array('message' => __('Invalid request.', 'injection-guard')), 400);
			}

			$guard_obj = new guard_wordpress;

			$guard_obj->update_blacklisted($val, $uri, ($type === 'blacklist'));

			wp_send_json_success(array('status' => true));
		}
	}

	if (!function_exists('ig_update_bulk_backlist')) {
		function ig_update_bulk_backlist() {

			ig_ajax_check();

			$ig_type     = (isset($_POST['ig_type']) && $_POST['ig_type'] === 'whitelist') ? 'whitelist' : 'blacklist';
			$ig_post_obj = (isset($_POST['ig_post_obj']) && is_array($_POST['ig_post_obj'])) ? wp_unslash($_POST['ig_post_obj']) : array();

			$guard_obj = new guard_wordpress;

			$count = 0;

			foreach ($ig_post_obj as $uri => $val_array) {
				if (!is_array($val_array)) {
					continue;
				}
				foreach ($val_array as $val) {
					if ($count >= 1000) {
						break 2;
					}
					if ($guard_obj->update_blacklisted($val, $uri, ($ig_type === 'blacklist'))) {
						$count++;
					}
				}
			}

			wp_send_json_success(array('message' => __('Updated successfully.', 'injection-guard'), 'count' => $count));
		}
	}

	if(!function_exists('ig_clear_data')){
		function ig_clear_data(){

			ig_ajax_check();

			$what = isset($_POST['what']) ? sanitize_key(wp_unslash($_POST['what'])) : '';

			if($what === 'requests'){
				delete_option('ig_requests_log');
			}elseif($what === 'blocked'){
				delete_option('ig_blocked_log');
			}else{
				wp_send_json_error(array('message' => __('Invalid request.', 'injection-guard')), 400);
			}

			wp_send_json_success(array('message' => __('Cleared.', 'injection-guard')));
		}
	}

	if(!function_exists('ig_save_settings')){
		function ig_save_settings(){

			if(!current_user_can('manage_options') || !is_super_admin()){
				wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'injection-guard'), '', array('response' => 403));
			}

			check_admin_referer('ig_save_settings', 'ig_settings_nonce');

			$posted = isset($_POST['ig']) && is_array($_POST['ig']) ? $_POST['ig'] : array();

			update_option('ig_settings', ig_sanitize_settings($posted));

			wp_safe_redirect(add_query_arg(array('page' => 'ig_settings', 'tab' => 'ig_options', 'ig_notice' => 'saved'), admin_url('options-general.php')));
			exit;
		}
	}

	if(!function_exists('ig_csv_cell')){
		// neutralises spreadsheet formula injection
		function ig_csv_cell($v){
			$v = (string)$v;
			if($v !== '' && preg_match('/^[=+\-@\t\r]/', $v)){
				$v = "'" . $v;
			}
			return $v;
		}
	}

	if(!function_exists('ig_export_csv')){
		function ig_export_csv(){

			if(!current_user_can('manage_options')){
				wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'injection-guard'), '', array('response' => 403));
			}

			check_admin_referer('ig_export');

			$type = (isset($_GET['type']) && $_GET['type'] === 'blocked') ? 'blocked' : 'log';

			nocache_headers();
			header('Content-Type: text/csv; charset=utf-8');
			header('Content-Disposition: attachment; filename="injection-guard-' . $type . '-' . gmdate('Y-m-d') . '.csv"');

			$out = fopen('php://output', 'w');

			if($type === 'blocked'){
				$rows = get_option('ig_blocked_log');
				fputcsv($out, array('time', 'uri', 'param_or_signature', 'reason', 'ip', 'blocked'), ',', '"', '\\');
				foreach((array)$rows as $r){
					fputcsv($out, array(
						gmdate('Y-m-d H:i:s', (int)$r['t']),
						ig_csv_cell($r['uri']),
						ig_csv_cell($r['param']),
						ig_csv_cell($r['reason']),
						ig_csv_cell($r['ip']),
						!empty($r['blocked']) ? 'yes' : 'no',
					), ',', '"', '\\');
				}
			}else{
				$rows = get_option('ig_requests_log');
				$bl   = get_option('ig_blacklisted');
				$bl   = is_array($bl) ? $bl : array();
				fputcsv($out, array('uri', 'param', 'last_seen', 'blacklisted'), ',', '"', '\\');
				foreach((array)$rows as $uri => $params){
					foreach((array)$params as $param => $seen){
						$is_bl = isset($bl[$uri]) && in_array((string)$param, array_map('strval', (array)$bl[$uri]), true);
						fputcsv($out, array(ig_csv_cell($uri), ig_csv_cell($param), gmdate('Y-m-d H:i:s', (int)$seen), $is_bl ? 'yes' : 'no'), ',', '"', '\\');
					}
				}
			}

			fclose($out);
			exit;
		}
	}

	function ig_plugin_links($links) {

		global $ig_pro, $ig_pro_link;

		$extra = array('<a href="' . esc_url(admin_url('options-general.php?page=ig_settings')) . '">'.esc_html__('Settings', 'injection-guard').'</a>');

		if(!$ig_pro){
			$extra[] = '<a href="'.esc_url($ig_pro_link).'" title="'.esc_attr__('Go Premium', 'injection-guard').'" target="_blank" rel="noopener">'.esc_html__('Go Premium', 'injection-guard').'</a>';
		}

		return array_merge($extra, $links);

	}

	/* ------------------------------------------------------------------
	 * USER ACTIVITY (login history, sessions)
	 * ---------------------------------------------------------------- */

	function ig_user_last_login( $user_login, $user ) {
		update_user_meta( $user->ID, 'last_login', time() );
		$ips = get_user_meta( $user->ID, 'ip_logs',  true);
		$ips = is_array($ips)?$ips:array();
		$ip  = ig_get_ip();
		if($ip !== ''){
			$ips[] = $ip;
		}
		$ips = array_slice(array_values(array_unique($ips)), -20); // keep the latest 20 only
		update_user_meta( $user->ID, 'ip_logs', $ips);

	}
	add_action( 'wp_login', 'ig_user_last_login', 10, 2 );

	function ig_get_customer_total_order($user_id=0) {
		global $wpdb;

		// WooCommerce is optional
		if(!function_exists('wc_get_orders') || !function_exists('wc_get_order')){
			return array(0, 0, array());
		}

		$customer_orders = wc_get_orders( array(
			'customer_id' => $user_id?$user_id:get_current_user_id(),
			'status'      => array( 'completed' ),
			'limit'       => -1,
			'return'      => 'objects',
		) );

		$total = 0;
		$products = array();
		if(!empty($customer_orders)){
			foreach ( $customer_orders as $order ) {
				if(empty($order) || !is_object($order)){
					continue;
				}
				if(count($order->get_items())>0){

					$download_count = 0;
					$permissions_query = $wpdb->prepare( "
							SELECT * FROM {$wpdb->prefix}woocommerce_downloadable_product_permissions
							WHERE order_id = %d ORDER BY product_id
						", $order->get_id() );
					$download_permissions = $wpdb->get_results($permissions_query);
					if(!empty($download_permissions)){
						$for_download_count = current($download_permissions);
						$download_count = $for_download_count->download_count;
					}

					foreach ($order->get_items() as $item_id => $item_data) {
						$product = $item_data->get_product();
						if(!empty($product)){

							$product_name = $product->get_name();
							$item_quantity = $item_data->get_quantity();

							$products[] = array('qty'=>$item_quantity, 'product'=>$product_name, 'download_count'=>$download_count);
						}
					}
				}
				$total += (float)$order->get_total();
			}
		}

		return array(count($customer_orders), $total, $products);
	}

	add_action( 'wp_login', function( $user_login, $user ) {
		update_user_meta( $user->ID, 'ig_last_login', time() );
		update_user_meta( $user->ID, 'ig_session_start', time() );
	}, 10, 2 );

	add_action( 'wp_logout', function() {
		$user_id = get_current_user_id();
		if ( $user_id ) {
			$start = get_user_meta( $user_id, 'ig_session_start', true );
			$end   = time();

			if ( $start ) {
				$duration = $end - $start;
				update_user_meta( $user_id, 'ig_last_session_duration', $duration );
				update_user_meta( $user_id, 'ig_last_logout', $end );
			}
		}
	});

	add_filter( 'manage_users_columns', function( $columns ) {
		$columns['ig_last_login']     = __( 'Last Login', 'injection-guard' );
		$columns['ig_last_logout']    = __( 'Last Logout', 'injection-guard' );
		$columns['ig_session_length'] = __( 'Session Duration', 'injection-guard' );
		return $columns;
	} );

	add_filter( 'manage_users_custom_column', function( $value, $column_name, $user_id ) {
		if ( $column_name === 'ig_last_login' ) {
			$login  = get_user_meta( $user_id, 'ig_last_login', true );
			$start  = get_user_meta( $user_id, 'ig_session_start', true );
			$display = $login ?: $start;
			return $display ? esc_html( wp_date( 'Y-m-d H:i:s', (int)$display ) ) : '—';
		}

		if ( $column_name === 'ig_last_logout' ) {
			$logout = get_user_meta( $user_id, 'ig_last_logout', true );
			return $logout ? esc_html( wp_date( 'Y-m-d H:i:s', (int)$logout ) ) : '—';
		}

		if ( $column_name === 'ig_session_length' ) {
			$start     = get_user_meta( $user_id, 'ig_session_start', true );
			$last_seen = get_user_meta( $user_id, 'ig_last_seen', true );
			if ( $start && $last_seen && $last_seen > $start ) {
				$duration = $last_seen - $start;
				return gmdate( 'H:i:s', $duration );
			}
			return '—';
		}

		return $value;
	}, 10, 3 );

	add_action( 'init', 'ig_track_user_session_ping', 1 );
	add_action( 'admin_init', 'ig_track_user_session_ping', 1 );

	function ig_track_user_session_ping() {
		if ( ! is_user_logged_in() ) return;

		$user_id = get_current_user_id();
		$now     = time();

		$start     = get_user_meta( $user_id, 'ig_session_start', true );
		$last_seen = get_user_meta( $user_id, 'ig_last_seen', true );

		if ( empty( $start ) || ! is_numeric( $start ) || $start > $now ) {
			update_user_meta( $user_id, 'ig_session_start', $now );
			update_user_meta( $user_id, 'ig_last_seen', $now );
			update_user_meta( $user_id, 'ig_last_session_duration', 0 );
			return;
		}

		if ( empty( $last_seen ) || ( $now - intval( $last_seen ) ) >= 300 ) {
			update_user_meta( $user_id, 'ig_last_seen', $now );
			update_user_meta( $user_id, 'ig_last_session_duration', $now - intval( $start ) );
		}
	}

	/* ------------------------------------------------------------------
	 * CAPABILITY AUDIT
	 * ---------------------------------------------------------------- */

	if(!function_exists('ig_audit_flag_caps')){
		function ig_audit_flag_caps(){
			return apply_filters('ig_audit_flag_caps', array( 'manage_options', 'edit_users', 'install_plugins', 'delete_users', 'edit_plugins', 'edit_themes' ));
		}
	}

	if(!function_exists('ig_audit_get_rows')){

		/**
		 * One page of user rows for the capability audit (shared by the screen and the Pro CSV export).
		 */
		function ig_audit_get_rows($offset = 0, $per_page = 100){

			global $wpdb;

			$offset   = max(0, (int)$offset);
			$per_page = min(200, max(1, (int)$per_page));
			$cap_key  = $wpdb->prefix . 'capabilities';

			$results = $wpdb->get_results( $wpdb->prepare("
				SELECT u.ID as user_id, u.user_login, u.user_email, um.meta_value as caps
				FROM {$wpdb->users} u
				INNER JOIN {$wpdb->usermeta} um ON um.user_id = u.ID
				WHERE um.meta_key = %s
				ORDER BY u.ID ASC
				LIMIT %d OFFSET %d
			", $cap_key, $per_page, $offset) );

			$rows = array();

			if(empty($results)){
				return $rows;
			}

			update_meta_cache('user', wp_list_pluck($results, 'user_id'));

			$flag_caps = ig_audit_flag_caps();

			foreach ( $results as $row ) {

				$caps = is_serialized($row->caps) ? @unserialize($row->caps, array('allowed_classes' => false)) : array();
				$cap_list = array();
				$suspicious = false;

				foreach ( (array) $caps as $cap => $value ) {
					if ( $value ) {
						$cap_list[] = (string)$cap;
						if ( in_array( $cap, $flag_caps, true ) ) {
							$suspicious = true;
						}
					}
				}

				$last = get_user_meta($row->user_id, 'ig_last_login', true);

				$rows[] = array(
					'id'         => (int)$row->user_id,
					'login'      => $row->user_login,
					'email'      => $row->user_email,
					'caps'       => $cap_list,
					'suspicious' => ($suspicious || trim($row->user_login, '-') === ''),
					'last_login' => $last ? (int)$last : 0,
				);
			}

			return $rows;
		}

	}

	function ig_capability_audit_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to access this page.', 'injection-guard' ) );
		}
		?>
		<div class="wrap ig_settings ig_audit">
			<h1><?php esc_html_e( 'Capability Audit', 'injection-guard' ); ?> &#128462;</h1>
			<p class="description"><?php esc_html_e( 'Accounts holding powerful capabilities are highlighted. Review any account you do not recognise.', 'injection-guard' ); ?></p>
			<p class="ig_audit_tools">
				<input type="search" id="ig_audit_filter" placeholder="<?php esc_attr_e( 'Filter by username, email or capability…', 'injection-guard' ); ?>" class="regular-text">
				<label><input type="checkbox" id="ig_audit_flagged_only"> <?php esc_html_e( 'Show flagged accounts only', 'injection-guard' ); ?></label>
				<?php do_action( 'ig_capability_audit_toolbar' ); ?>
			</p>
			<table id="cap-audit-table" class="widefat striped">
				<thead>
				  <tr>
					<th><?php esc_html_e( 'ID', 'injection-guard' ); ?></th>
					<th><?php esc_html_e( 'Username', 'injection-guard' ); ?></th>
					<th><?php esc_html_e( 'Email', 'injection-guard' ); ?></th>
					<th><?php esc_html_e( 'Last Login', 'injection-guard' ); ?></th>
					<th><?php esc_html_e( 'Capabilities', 'injection-guard' ); ?></th>
				  </tr>
				</thead>

				<tbody id="cap-audit-results"></tbody>
			</table>
			<p id="cap-audit-status" class="description"><?php esc_html_e( 'Loading…', 'injection-guard' ); ?></p>
		</div>
		<?php
	}

	add_action('wp_ajax_ig_load_capability_audit', function() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
		}

		check_ajax_referer( 'ig_nonce_action', 'security' );

		$offset   = isset($_REQUEST['offset']) ? absint($_REQUEST['offset']) : 0;
		$per_page = isset($_REQUEST['per_page']) ? absint($_REQUEST['per_page']) : 100;

		$rows = ig_audit_get_rows($offset, $per_page);

		ob_start();

		foreach ( $rows as $row ) {

			$user_link = admin_url('user-edit.php?user_id='.(int)$row['id']);
			$class     = $row['suspicious'] ? 'flag' : '';
			$last      = $row['last_login'] ? wp_date('Y-m-d H:i', $row['last_login']) : '—';

			echo '<tr class="' . esc_attr($class) . '">';
			echo '<td><a href="' . esc_url($user_link) . '" target="_blank" rel="noopener">' . esc_html($row['id']) . '</a></td>';
			echo '<td>' . esc_html($row['login']) . '</td>';
			echo '<td>' . esc_html($row['email']) . '</td>';
			echo '<td>' . esc_html($last) . '</td>';
			echo '<td>' . esc_html(implode(', ', !empty($row['caps']) ? $row['caps'] : array('—'))) . '</td>';
			echo '</tr>';
		}

		wp_send_json_success( array( 'rows' => ob_get_clean(), 'count' => count($rows) ) );
	});

	/* ------------------------------------------------------------------
	 * HOUSEKEEPING (upgrade routine, privacy policy text)
	 * ---------------------------------------------------------------- */

	if(!function_exists('ig_maybe_upgrade')){
		function ig_maybe_upgrade(){

			if(!defined('IG_VERSION') || get_option('ig_version') === IG_VERSION){
				return;
			}

			update_option('ig_version', IG_VERSION);

			if(false === get_option('ig_settings')){
				add_option('ig_settings', ig_default_settings());
			}

			// the request log can get large: make sure it is not autoloaded on every page view
			$log = get_option('ig_requests_log');
			if(is_array($log)){
				delete_option('ig_requests_log');
				add_option('ig_requests_log', $log, '', 'no');
			}

			// remove a credential dump file that older dashboard code could create
			if(defined('IG_DIR')){
				$stray = IG_DIR . 'ab-hash.dat';
				if(is_file($stray)){
					@unlink($stray);
				}
			}
		}
	}

	if(!function_exists('ig_privacy_policy_content')){
		function ig_privacy_policy_content(){
			if(!function_exists('wp_add_privacy_policy_content')){
				return;
			}
			wp_add_privacy_policy_content(
				'Injection Guard',
				wp_kses_post(wpautop(__('Injection Guard stores the names of query-string parameters seen on your site (never their values). When a request is blocked it may store the requested path and the visitor IP address (anonymised by default) in the blocked-requests list. For logged-in users it stores login and logout times and the IP addresses used to log in. This data stays in your own WordPress database.', 'injection-guard')))
			);
		}
	}
	add_action('admin_init', 'ig_privacy_policy_content');
