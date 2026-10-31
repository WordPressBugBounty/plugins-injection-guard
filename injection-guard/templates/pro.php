<?php if ( ! defined( 'ABSPATH' ) ) exit;

if($ig_pro){
	do_action('ig_settings_pro_tab');
	return;
}
?>
<div class="ig-pro-box">
	<h3><?php esc_html_e('Injection Guard Pro', 'injection-guard'); ?></h3>
	<p><?php esc_html_e('Go from "log and block" to an active defence that reacts on its own:', 'injection-guard'); ?></p>
	<ul class="ig-pro-list">
		<li><strong><?php esc_html_e('Automatic IP ban', 'injection-guard'); ?></strong> &mdash; <?php esc_html_e('visitors who keep triggering blocks are shut out for a time you choose.', 'injection-guard'); ?></li>
		<li><strong><?php esc_html_e('Email alerts', 'injection-guard'); ?></strong> &mdash; <?php esc_html_e('throttled notices about blocked attacks, so you do not have to watch the log.', 'injection-guard'); ?></li>
		<li><strong><?php esc_html_e('New administrator watch', 'injection-guard'); ?></strong> &mdash; <?php esc_html_e('an immediate email when an account is created with, or promoted to, administrator, including changes made straight in the database.', 'injection-guard'); ?></li>
		<li><strong><?php esc_html_e('Extended signature set', 'injection-guard'); ?></strong> &mdash; <?php esc_html_e('more SQL, template and log4j-style patterns, plus known scanner user agents.', 'injection-guard'); ?></li>
		<li><strong><?php esc_html_e('IP allowlist', 'injection-guard'); ?></strong> &mdash; <?php esc_html_e('your office, monitoring service or payment gateway is never blocked.', 'injection-guard'); ?></li>
		<li><strong><?php esc_html_e('Capability audit CSV export', 'injection-guard'); ?></strong> &mdash; <?php esc_html_e('hand a full privileged-account report to your security team.', 'injection-guard'); ?></li>
	</ul>
	<p><a class="button button-primary" href="<?php echo esc_url($ig_pro_link); ?>" target="_blank" rel="noopener"><?php esc_html_e('Get Injection Guard Pro', 'injection-guard'); ?></a></p>
</div>
