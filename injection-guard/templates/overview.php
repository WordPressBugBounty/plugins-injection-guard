<?php if ( ! defined( 'ABSPATH' ) ) exit;

	$ig_total_urls    = count($ig_logs);
	$ig_total_params  = 0;
	foreach($ig_logs as $ig_u => $ig_p){
		$ig_total_params += is_array($ig_p) ? count($ig_p) : 0;
	}
	$ig_total_blocked_params = 0;
	foreach($ig_blacklisted as $ig_u => $ig_p){
		$ig_total_blocked_params += is_array($ig_p) ? count($ig_p) : 0;
	}

	$ig_since = time() - DAY_IN_SECONDS;
	$ig_blocked_24 = 0;
	$ig_detected_24 = 0;
	foreach($ig_blocked as $ig_b){
		if(!empty($ig_b['t']) && (int)$ig_b['t'] >= $ig_since){
			if(!empty($ig_b['blocked'])){ $ig_blocked_24++; }else{ $ig_detected_24++; }
		}
	}

	$ig_modes = array(
		'off'   => __('Off', 'injection-guard'),
		'log'   => __('Detect & record', 'injection-guard'),
		'block' => __('Detect & block', 'injection-guard'),
	);
?>
<div class="ig-cards">
	<div class="ig-card">
		<span class="ig-card-num"><?php echo esc_html(number_format_i18n($ig_total_urls)); ?></span>
		<span class="ig-card-label"><?php esc_html_e('URLs with parameters', 'injection-guard'); ?></span>
	</div>
	<div class="ig-card">
		<span class="ig-card-num"><?php echo esc_html(number_format_i18n($ig_total_params)); ?></span>
		<span class="ig-card-label"><?php esc_html_e('Parameters logged', 'injection-guard'); ?></span>
	</div>
	<div class="ig-card">
		<span class="ig-card-num"><?php echo esc_html(number_format_i18n($ig_total_blocked_params)); ?></span>
		<span class="ig-card-label"><?php esc_html_e('Parameters blacklisted', 'injection-guard'); ?></span>
	</div>
	<div class="ig-card ig-card-alert">
		<span class="ig-card-num"><?php echo esc_html(number_format_i18n($ig_blocked_24)); ?></span>
		<span class="ig-card-label"><?php esc_html_e('Blocked in the last 24h', 'injection-guard'); ?></span>
	</div>
	<div class="ig-card">
		<span class="ig-card-num"><?php echo esc_html(number_format_i18n($ig_detected_24)); ?></span>
		<span class="ig-card-label"><?php esc_html_e('Suspicious (not blocked), 24h', 'injection-guard'); ?></span>
	</div>
</div>

<p class="ig-status">
	<span class="ig-chip <?php echo !empty($ig_cfg['logging']) ? 'on' : 'off'; ?>"><?php echo !empty($ig_cfg['logging']) ? esc_html__('Logging on', 'injection-guard') : esc_html__('Logging off', 'injection-guard'); ?></span>
	<span class="ig-chip <?php echo ($ig_cfg['heuristics'] === 'off') ? 'off' : 'on'; ?>"><?php echo esc_html(sprintf(/* translators: %s: scan mode */ __('Signature scan: %s', 'injection-guard'), isset($ig_modes[$ig_cfg['heuristics']]) ? $ig_modes[$ig_cfg['heuristics']] : '')); ?></span>
	<span class="ig-chip on"><?php echo esc_html(sprintf(/* translators: %d: HTTP status code */ __('Blocked requests get HTTP %d', 'injection-guard'), (int)$ig_cfg['block_status'])); ?></span>
	<a href="#" class="ig-tab-jump" data-tab="ig_options"><?php esc_html_e('Change settings', 'injection-guard'); ?></a>
</p>

<?php if(!empty($ig_blocked)): ?>
<h3><?php esc_html_e('Latest blocked or suspicious requests', 'injection-guard'); ?></h3>
<table class="widefat striped ig-mini-table">
	<thead><tr>
		<th><?php esc_html_e('Time', 'injection-guard'); ?></th>
		<th><?php esc_html_e('URL', 'injection-guard'); ?></th>
		<th><?php esc_html_e('Parameter / signature', 'injection-guard'); ?></th>
		<th><?php esc_html_e('Result', 'injection-guard'); ?></th>
	</tr></thead>
	<tbody>
	<?php foreach(array_slice($ig_blocked, 0, 5) as $ig_b): ?>
		<tr>
			<td><?php echo esc_html(wp_date('Y-m-d H:i', (int)$ig_b['t'])); ?></td>
			<td><?php echo esc_html($ig_b['uri']); ?></td>
			<td><code><?php echo esc_html($ig_b['param']); ?></code></td>
			<td><?php echo !empty($ig_b['blocked']) ? '<span class="ig-badge bad">'.esc_html__('Blocked', 'injection-guard').'</span>' : '<span class="ig-badge warn">'.esc_html__('Recorded', 'injection-guard').'</span>'; ?></td>
		</tr>
	<?php endforeach; ?>
	</tbody>
</table>
<?php endif; ?>

<h3><?php esc_html_e('Users', 'injection-guard'); ?></h3>
