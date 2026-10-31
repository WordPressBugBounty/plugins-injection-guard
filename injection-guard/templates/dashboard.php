<?php if ( ! defined( 'ABSPATH' ) ) exit;

	$result = count_users();
	if(!empty($result)){
?>
	<div class="row mt-3">
		<div class="col-md-12">

			<ul>
			<?php
					foreach($result as $k=>$v){
						if(!is_array($v)){
			?>

							<li><b><?php echo esc_html(ucwords(str_replace('_', ' ', $k))); ?>:</b> <?php echo esc_html($v); ?></li>

			<?php
						}else{
							foreach($v as $i=>$j){
			?>

							<li><b><?php echo esc_html(ucwords(str_replace('_', ' ', $i))); ?>:</b> <?php echo esc_html($j); ?></li>

			<?php
							}
						}
					}
			?>
			</ul>

		</div>
	</div>

<?php

	}

	// The customers table needs WooCommerce; without it the section is simply skipped.
	if(!function_exists('wc_get_orders') || !function_exists('get_woocommerce_currency_symbol')){
		return;
	}

	$ig_customer_id = isset($_GET['user_id']) ? absint($_GET['user_id']) : 0;
	$ig_show_all    = isset($_GET['all']);
	$ig_geo         = isset($_GET['location']);

	$args = array(
		'blog_id'      => $GLOBALS['blog_id'],
		'role__in'     => array('customer'),
		'include'      => $ig_customer_id ? array($ig_customer_id) : array(),
		'orderby'      => 'registered',
		'order'        => 'DESC',
		'number'       => $ig_show_all ? 500 : 1,
		'count_total'  => false,
		'fields'       => 'all',
	 );
	$all_customers = get_users( $args );

	if(!empty($all_customers)){

?>
 <div class="table-responsive">
	<table class="table">
    <thead>
      <tr>
      	<th><?php esc_html_e('#', 'injection-guard'); ?></th>
      	<th><?php esc_html_e('Amount (Orders)', 'injection-guard'); ?></th>
        <th><?php esc_html_e('Items', 'injection-guard'); ?></th>
        <th><?php esc_html_e('Customer Name', 'injection-guard'); ?></th>
        <th><?php esc_html_e('Email', 'injection-guard'); ?></th>
        <th><?php esc_html_e('Registered', 'injection-guard'); ?></th>
        <th><?php esc_html_e('Last Login', 'injection-guard'); ?></th>
        <th><?php esc_html_e('IP', 'injection-guard'); ?></th>
      </tr>
	</thead>
    <tfoot>
    <tr>
    	<th><?php esc_html_e('#', 'injection-guard'); ?></th>
    	<th><?php esc_html_e('Amount (Orders)', 'injection-guard'); ?></th>
        <th><?php esc_html_e('Items', 'injection-guard'); ?></th>
        <th><?php esc_html_e('Customer Name', 'injection-guard'); ?></th>
        <th><?php esc_html_e('Email', 'injection-guard'); ?></th>
        <th><?php esc_html_e('Registered', 'injection-guard'); ?></th>
        <th><?php esc_html_e('Last Login', 'injection-guard'); ?></th>
        <th><?php esc_html_e('IP', 'injection-guard'); ?></th>
    </tr>
    </tfoot>
	<tbody>
<?php
		$c = 0;
		foreach($all_customers as $customers){ $c++;

			$meta = get_user_meta($customers->ID);

			$last_login_ts = (isset($meta['last_login'][0]) && is_numeric($meta['last_login'][0])) ? (int)$meta['last_login'][0] : 0;
			$last_login    = $last_login_ts ? wp_date('Y-m-d H:i:s', $last_login_ts) : '—';
			$the_login_date = $last_login_ts ? human_time_diff($last_login_ts) : '—';
			$ip_logs = isset($meta['ip_logs'][0]) ? maybe_unserialize($meta['ip_logs'][0]) : array();

			$customer_total_order = ig_get_customer_total_order($customers->ID);

			list($orders, $amount, $products) = $customer_total_order;
			$products_list = '';
			$row_class = '';

			if(!empty($products)){

				$products_list .= '<ul>';

				foreach($products as $item){

					$products_list .= '<li>'.esc_html($item['download_count']).' - '.esc_html($item['product']).' x'.esc_html($item['qty']).'</li>';

					if($item['download_count']>10){
						$row_class = 'danger';
					}elseif($item['download_count']>0){
						$row_class = 'success';
					}else{
						$row_class = 'warning';
					}

				}

				$products_list .= '</ul>';

			}

			$ip_list = '';
			if(!empty($ip_logs) && is_array($ip_logs)){
				$ip_list .= '<ul>';
				foreach($ip_logs as $ip){
					$location = '';

					if($ig_geo && filter_var($ip, FILTER_VALIDATE_IP)){

						$cache_key = 'ig_geo_'.md5($ip);
						$location  = get_transient($cache_key);

						if($location === false){
							$location = '';
							$response = wp_remote_get( 'https://tools.keycdn.com/geo.json?host='.rawurlencode($ip), array('timeout' => 5) );

							if ( !is_wp_error($response) && 200 === wp_remote_retrieve_response_code($response) ) {
							  $body = json_decode(wp_remote_retrieve_body($response));
							  if(is_object($body) && isset($body->status) && $body->status === 'success' && isset($body->data->geo)){
								  $city    = isset($body->data->geo->city) ? $body->data->geo->city : '';
								  $country = isset($body->data->geo->country_name) ? $body->data->geo->country_name : '';
								  $location = sanitize_text_field($city.' / '.$country);
							  }
							}
							set_transient($cache_key, $location, WEEK_IN_SECONDS);
						}
					}
					$ip_list .= '<li>'.esc_html($ip).' - '.esc_html($location).'</li>';
				}
				$ip_list .= '</ul>';
			}

?>
		<tr class="<?php echo esc_attr($row_class); ?>">
        	<td><?php echo esc_html($c); ?></td>
        	<td><?php echo  esc_html(html_entity_decode(get_woocommerce_currency_symbol()).($amount).' ('.$orders.')'); ?></td>
            <td><?php echo wp_kses_post($products_list); ?></td>
        	<td><?php echo esc_html($customers->display_name).' - '.esc_html($customers->ID); ?></td>
            <td><?php echo esc_html($customers->user_email); ?></td>
            <td><?php echo esc_html($customers->user_registered); ?></td>
            <td><?php echo esc_html($last_login.' / '.$the_login_date); ?></td>
            <td><?php echo wp_kses_post($ip_list); ?></td>
        </tr>
<?php
		}
?>
	</tbody>
	</table>
</div>
<?php

	}
