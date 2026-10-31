<?php if ( ! defined( 'ABSPATH' ) ) exit;

/*
 * Hard limits. They exist so that an attacker who floods the site with random
 * query strings can not make the plugin's own log grow without bound.
 */
if ( ! defined( 'IG_MAX_PARAMS_PER_REQUEST' ) ) define( 'IG_MAX_PARAMS_PER_REQUEST', 50 );
if ( ! defined( 'IG_MAX_PARAMS_PER_URI' ) )     define( 'IG_MAX_PARAMS_PER_URI', 200 );
if ( ! defined( 'IG_MAX_PARAM_LENGTH' ) )       define( 'IG_MAX_PARAM_LENGTH', 64 );
if ( ! defined( 'IG_MAX_URI_LENGTH' ) )         define( 'IG_MAX_URI_LENGTH', 191 );
if ( ! defined( 'IG_LOG_TOUCH_INTERVAL' ) )     define( 'IG_LOG_TOUCH_INTERVAL', 300 ); // seconds between "last seen" refreshes (avoids a DB write on every hit)

if ( ! function_exists( 'sanitize_ig_data' ) ) {
	function sanitize_ig_data($input, $depth = 0) {

		if ($depth > 10) {
			return null; // prevent deep recursion
		}

		if (is_array($input)) {

			$new_input = array();

			foreach ($input as $key => $val) {

				// sanitize array key
				$clean_key = sanitize_key($key);

				// sanitize value (arrays recurse, scalars are cleaned)
				$new_input[$clean_key] = sanitize_ig_data($val, $depth + 1);
			}

		} else {

			if ( ! is_scalar( $input ) ) {
				return '';
			}

			$input = sanitize_text_field(wp_unslash((string) $input));

			if (is_email($input)) {
				$input = sanitize_email($input);
			}

			if (wp_http_validate_url($input)) {
				$input = esc_url_raw($input);
			}

			$new_input = $input;
		}

		return $new_input;
	}
}

/**
 * Normalises a query-string parameter name so that logging, blacklisting and
 * blocking all compare the very same value (case-insensitive, safe characters only).
 */
if ( ! function_exists( 'ig_normalize_param' ) ) {
	function ig_normalize_param( $key ) {
		if ( ! is_scalar( $key ) ) {
			return '';
		}
		return substr( sanitize_key( (string) $key ), 0, IG_MAX_PARAM_LENGTH );
	}
}

/**
 * Cleans a "URI index" (the path used as a key in the log / blacklist).
 */
if ( ! function_exists( 'ig_clean_uri_index' ) ) {
	function ig_clean_uri_index( $uri ) {
		if ( ! is_scalar( $uri ) ) {
			return '';
		}
		$uri = preg_replace( '/[\x00-\x1F\x7F]/', '', (string) $uri );
		$uri = wp_strip_all_tags( $uri );
		return substr( trim( $uri ), 0, IG_MAX_URI_LENGTH );
	}
}

####################################
###### INJECTION GUARD CLASS #######
####################################
############### BY #################
####################################
##### FAHAD@ANDROIDBUBBLES.COM #####
####################################

interface guard_base{
	public function init();
	public function update_log();
	public function get_requests_log();
	public function get_requests_log_updated($var=array());
	public function get_blacklisted();
}
class guard_plugins implements guard_base{

	protected $request;
	protected $request_uri;
	protected $request_uri_cleaned;
	protected $query_string = '';
	protected $param_to_blacklist;
	protected $blacklist_action;
	protected $log_changed = false;

	public function init(){
		$this->request = $_REQUEST;
		$uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '';
		$this->request_uri = substr(preg_replace('/[\x00-\x1F\x7F]/', '', (string) $uri), 0, 8192);
		$qs = isset($_SERVER['QUERY_STRING']) ? wp_unslash($_SERVER['QUERY_STRING']) : '';
		$this->query_string = substr(preg_replace('/[\x00-\x1F\x7F]/', '', (string) $qs), 0, 8192);
		$this->request_uri_cleaned = $this->cleaned_uri();
	}

	public function update_log(){
		//WILL DIFFER IN WP, JOOMLA and DRUPAL etc.

	}

	public function get_blacklisted(){

	}

	/**
	 * Path part of the request, normalised: decoded, dot-segments resolved,
	 * duplicate slashes removed, leading "index.php" ignored.
	 */
	private function cleaned_uri(){
		$path = $this->request_uri;
		$pos  = strpos($path, '?');
		if($pos !== false){
			$path = substr($path, 0, $pos);
		}
		$path = rawurldecode($path);
		$path = preg_replace('/[\x00-\x1F\x7F]/', '', $path);
		$path = wp_strip_all_tags($path);

		$segments = array();
		foreach(explode('/', $path) as $segment){
			if($segment === '' || $segment === '.'){
				continue;
			}
			if($segment === '..'){
				array_pop($segments);
				continue;
			}
			$segments[] = $segment;
		}

		if(!empty($segments) && strtolower($segments[0]) === 'index.php'){
			array_shift($segments);
		}

		$ret = !empty($segments) ? implode('/', $segments) : '/';

		return substr($ret, 0, IG_MAX_URI_LENGTH);
	}

	/**
	 * All parameter names of the current query string, normalised.
	 */
	public function get_query_keys(){
		if($this->query_string === ''){
			return array();
		}
		parse_str($this->query_string, $parsed);
		$keys = array();
		if(is_array($parsed)){
			foreach(array_keys($parsed) as $k){
				$nk = ig_normalize_param((string)$k);
				if($nk !== ''){
					$keys[$nk] = $nk;
				}
			}
		}
		return array_values($keys);
	}

	public function get_raw_query(){
		return $this->query_string;
	}

	public function get_requests_log(){

	}

	public function get_requests_log_updated($updated_log=array()){

		if(!is_array($updated_log)){
			$updated_log = array();
		}

		$keys = $this->get_query_keys();

		if(function_exists('ig_safe_params') && function_exists('ig_remove_matching')){
			$keys = ig_remove_matching($keys, ig_safe_params());
		}

		$keys = array_slice(array_values($keys), 0, IG_MAX_PARAMS_PER_REQUEST);

		if(empty($keys)){
			return $updated_log;
		}

		$uri   = $this->request_uri_cleaned;
		$entry = (isset($updated_log[$uri]) && is_array($updated_log[$uri])) ? $updated_log[$uri] : array();
		$time  = time();
		$dirty = false;

		foreach($keys as $key){
			$key = (string)$key;
			if(!isset($entry[$key])){
				if(count($entry) >= IG_MAX_PARAMS_PER_URI){
					continue;
				}
				$entry[$key] = $time;
				$dirty = true;
			}elseif(($time - (int)$entry[$key]) >= IG_LOG_TOUCH_INTERVAL){
				$entry[$key] = $time;
				$dirty = true;
			}
		}

		if($dirty){
			$updated_log[$uri] = $entry;
			$this->log_changed = true;
		}

		return $updated_log;
	}

	public function get_blacklisted_updated($blacklisted=array(), $uri_index=''){

		if(!is_array($blacklisted)){
			$blacklisted = array();
		}

		$uri_index = ig_clean_uri_index($uri_index);

		if($uri_index === '' || $this->param_to_blacklist === '' || $this->param_to_blacklist === null){
			return $blacklisted;
		}

		$list = (isset($blacklisted[$uri_index]) && is_array($blacklisted[$uri_index])) ? $blacklisted[$uri_index] : array();

		if($this->blacklist_action){
			if(!in_array($this->param_to_blacklist, $list, true)){
				$list[] = $this->param_to_blacklist;
			}
		}else{
			$key = array_search($this->param_to_blacklist, $list, true);
			while($key !== false){
				unset($list[$key]);
				$key = array_search($this->param_to_blacklist, $list, true);
			}
		}

		$list = array_values(array_unique($list));

		if(empty($list)){
			unset($blacklisted[$uri_index]);
		}else{
			$blacklisted[$uri_index] = $list;
		}

		return $blacklisted;
	}
}
class guard_wordpress extends guard_plugins{

	/**
	 * Raw read of the log (no capability check): the front-end needs it to append new entries.
	 */
	protected function load_log(){
		$log = get_option('ig_requests_log');
		return is_array($log) ? $log : array();
	}

	/**
	 * Keeps the log inside its configured limits.
	 */
	protected function trim_log($log){

		$settings  = function_exists('ig_get_settings') ? ig_get_settings() : array();
		$limit     = isset($settings['log_limit']) ? max(10, (int)$settings['log_limit']) : 500;
		$retention = isset($settings['log_retention_days']) ? max(0, (int)$settings['log_retention_days']) : 0;

		if($retention > 0){
			$cutoff = time() - ($retention * DAY_IN_SECONDS);
			foreach($log as $uri => $params){
				if(!is_array($params)){
					unset($log[$uri]);
					continue;
				}
				foreach($params as $param => $seen){
					if((int)$seen < $cutoff){
						unset($log[$uri][$param]);
					}
				}
				if(empty($log[$uri])){
					unset($log[$uri]);
				}
			}
		}

		if(count($log) > $limit){
			$latest = array();
			foreach($log as $uri => $params){
				$latest[$uri] = !empty($params) ? max(array_map('intval', $params)) : 0;
			}
			arsort($latest);
			$keep = array_slice(array_keys($latest), 0, $limit);
			$log  = array_intersect_key($log, array_flip($keep));
		}

		return $log;
	}

	/**
	 * Clean structure before it is stored: uri => (param => timestamp)
	 */
	protected function sanitize_log($log){
		$clean = array();
		foreach((array)$log as $uri => $params){
			$uri = ig_clean_uri_index($uri);
			if($uri === '' || !is_array($params)){
				continue;
			}
			foreach($params as $param => $seen){
				$param = ig_normalize_param((string)$param);
				if($param === ''){
					continue;
				}
				$clean[$uri][$param] = absint($seen);
			}
		}
		return $clean;
	}

	public function update_log(){

		if(function_exists('ig_get_settings')){
			$settings = ig_get_settings();
			if(empty($settings['logging'])){
				return;
			}
		}

		$this->log_changed = false;

		$updated_log = $this->load_log();

		$updated_log = $this->get_requests_log_updated($updated_log);

		if(!$this->log_changed){
			return; // nothing new: no database write
		}

		$updated_log = $this->trim_log($updated_log);

		update_option( 'ig_requests_log', $this->sanitize_log($updated_log), false );

	}

	public function update_blacklisted($val, $uri_index, $block_this=true){

		$val       = ig_normalize_param($val);
		$uri_index = ig_clean_uri_index($uri_index);

		if($val === '' || $uri_index === ''){
			return false;
		}

		$updated_bl = $this->get_blacklisted();

		$this->param_to_blacklist = $val;
		$this->blacklist_action   = (bool)$block_this;

		$updated_bl = $this->get_blacklisted_updated($updated_bl, $uri_index);

		update_option( 'ig_blacklisted', $updated_bl );

		return true;
	}

	public function get_requests_log(){
		if (!current_user_can('manage_options')) {
			return;
		}
		return $this->load_log();
	}

	public function get_blacklisted(){
		$bl = get_option('ig_blacklisted');
		return is_array($bl) ? $bl : array();
	}

	public function wp_uri_cleaned(){
		return $this->request_uri_cleaned;
	}

	public function available_uri_strings(){
		return $this->get_query_keys();
	}
}
