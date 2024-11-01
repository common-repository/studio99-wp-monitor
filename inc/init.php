<?php
/**
 * @package S99WPMon
 */

class s99wpmon_Init {

	public static function registerServices() {
		include_once(S99WPMON_PLUGIN_PATH . "/classes/s99wpmon.php");
		if (class_exists("S99WPMon")) {
			$s99wpmon = new S99WPMon();
			$s99wpmon->register();
			add_action("s99_wpmon_schedule_event", array($s99wpmon, "gatherInfosAndStore"));
			add_action("wp_ajax_execute_cron", array($s99wpmon, "gatherInfosAndStoreCron"));
			add_action("wp_ajax_nopriv_execute_cron", array($s99wpmon, "gatherInfosAndStoreCron"));
			add_action("wp_ajax_get_gathered_datas", array($s99wpmon, "returnAJAXCallResults"));
			add_action("wp_ajax_nopriv_get_gathered_datas", array($s99wpmon, "returnAJAXCallResults"));
			register_activation_hook(S99WPMON_PLUGIN_FILE, array($s99wpmon, "activate"));
			register_deactivation_hook(S99WPMON_PLUGIN_FILE, array($s99wpmon, "deactivate"));
		} else {
			die("Something went wrong pal!");
		}
	}
}