<?php
/**
 * @package S99WPMon
 */

if (!defined("WP_UNINSTALL_PLUGIN")) {
	die("What are you trying to uninstall pal?");
}

global $wpdb;

$tablename = "{$wpdb->prefix}s99wpmon_config"; 
$wpdb->query("DROP TABLE IF EXISTS $tablename;");