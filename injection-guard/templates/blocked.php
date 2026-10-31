<?php if ( ! defined( 'ABSPATH' ) ) exit;

	$ig_reason_labels = array(
		'blacklist'        => __('Blacklisted parameter', 'injection-guard'),
		'global-blacklist' => __('Global blacklist', 'injection-guard'),
		'signature'        => __('Attack signature', 'injection-guard'),
		'banned-ip'        => __('Temporarily banned IP', 'injection-guard'),
		'scanner-agent'    => __('Scanner user agent', 'injection-guard'),
	);
	$ig_reason_labels = apply_filters('ig_reason_labels', $ig_reason_labels);
?>
<div class="row mt-3">
	<div class="col-md-12">
		<div class="ms-3 h4"><?php esc_html_e('Blocked and suspicious requests', 'injection-guard'); ?>:</div>
		<p class="description"><?php esc_html_e('Newest first. Under heavy attack only a sample of requests is recorded, so the database is never flooded.', 'injection-guard'); ?></p>
	</div>
</div>

<div class="ig_toolbar">
	<a class="button" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=ig_export&type=blocked'), 'ig_export')); ?>"><?php esc_html_e('Export CSV', 'injection-guard'); ?></a>
	<?php if(!empty($ig_blocked)): ?>
	<button type="button" class="button ig_clear" data-what="blocked"><?php esc_html_e('Clear list', 'injection-guard'); ?></button>
	<?php endif; ?>
</div>

<?php if(empty($ig_blocked)): ?>
	<p><?php esc_html_e('Nothing has been blocked yet. That is good news.', 'injection-guard'); ?></p>
<?php else: ?>
<table class="widefat striped ig-blocked-table">
	<thead><tr>
		<th><?php esc_html_e('Time', 'injection-guard'); ?></th>
		<th><?php esc_html_e('URL', 'injection-guard'); ?></th>
		<th><?php esc_html_e('Parameter / signature', 'injection-guard'); ?></th>
		<th><?php esc_html_e('Reason', 'injection-guard'); ?></th>
		<th><?php esc_html_e('IP', 'injection-guard'); ?></th>
		<th><?php esc_html_e('Result', 'injection-guard'); ?></th>
	</tr></thead>
	<tbody>
	<?php foreach($ig_blocked as $ig_b):
		$ig_reason = isset($ig_b['reason']) ? $ig_b['reason'] : '';
	?>
		<tr>
			<td><?php echo esc_html(wp_date('Y-m-d H:i:s', (int)$ig_b['t'])); ?></td>
			<td><?php echo esc_html($ig_b['uri']); ?></td>
			<td><code><?php echo esc_html($ig_b['param']); ?></code></td>
			<td><?php echo esc_html(isset($ig_reason_labels[$ig_reason]) ? $ig_reason_labels[$ig_reason] : $ig_reason); ?></td>
			<td><?php echo esc_html($ig_b['ip']); ?></td>
			<td><?php echo !empty($ig_b['blocked']) ? '<span class="ig-badge bad">'.esc_html__('Blocked', 'injection-guard').'</span>' : '<span class="ig-badge warn">'.esc_html__('Recorded', 'injection-guard').'</span>'; ?></td>
		</tr>
	<?php endforeach; ?>
	</tbody>
</table>
<?php endif; ?>
