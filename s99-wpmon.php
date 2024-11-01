<?php
/**
 * @package S99WPMon
 */
/*
Plugin Name: Studio99 WP Monitor
Plugin URI: https://www.studio99.sm/s99-wpmon
Version: 1.0.0
Description: This plugin provide to Studio99 Wordpress Monitoring Console general informations of the Wordpress's website where it's istalled.
Author: Studio99
Author URI: https://www.studio99.sm
License: GPLv2 or later
Text-Domain: s99-wpmon
 */

defined("ABSPATH") or die("What are you doing here pal?");

define("S99WPMON_PLUGIN_TABLE_NAME", "s99wpmon_config");
define("S99WPMON_PLUGIN_FILE", __FILE__);
define("S99WPMON_PLUGIN_NAME", plugin_basename(S99WPMON_PLUGIN_FILE));
define("S99WPMON_PLUGIN_PATH", rtrim(plugin_dir_path(S99WPMON_PLUGIN_FILE), "/"));
define("S99WPMON_PLUGIN_URL", rtrim(plugin_dir_url(S99WPMON_PLUGIN_FILE), "/"));
define("S99WPMON_PLUGIN_GATHER_INFO_CRON_TIME", "hourly"); //everyminute eventually

add_filter("cron_schedules", "s99wpmon_add_cron_interval");
function s99wpmon_add_cron_interval( $schedules ) {
	$schedules["everyminute"] = array(
            "interval"  => 60,
            "display"   => "Every Minute"
    );
    return $schedules;
}

require_once(S99WPMON_PLUGIN_PATH . "/inc/init.php");

if (class_exists("s99wpmon_Init")) {

	s99wpmon_Init::registerServices();
} else {
	die("Something went wrong pal!");
}