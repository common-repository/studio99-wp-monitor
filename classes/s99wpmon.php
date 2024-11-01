<?php
/**
 * @package S99WPMon
 */

class S99WPMon {

	function __construct() {
		require_once(S99WPMON_PLUGIN_PATH . "/classes/infos_gatherer.php");
	}

	private function checkAndPrepareDataBase() {
    	global $wpdb;
		$tablename = $wpdb->prefix . S99WPMON_PLUGIN_TABLE_NAME;
    	$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $tablename (
  					id INTEGER NOT NULL AUTO_INCREMENT,
                    meta_key VARCHAR(100),
  					last_update DATETIME DEFAULT '2000-01-01 00:00:00' NOT NULL,
  					datas TEXT,
  					PRIMARY KEY (id)
		) $charset_collate;";
		require_once(ABSPATH . "wp-admin/includes/upgrade.php");
		dbDelta($sql);
    }

    private function setUpdateCron() {
    	if (!wp_next_scheduled("s99_wpmon_schedule_event")) {
			wp_schedule_event(time(), S99WPMON_PLUGIN_GATHER_INFO_CRON_TIME, "s99_wpmon_schedule_event");
		}
    }

    private function unsetUpdateCron() {
    	wp_clear_scheduled_hook("s99_wpmon_schedule_event");
    }

    public function gatherInfosAndStore() {
    	global $wpdb;
    	$tablename = $wpdb->prefix . S99WPMON_PLUGIN_TABLE_NAME;
		$lastUpdate = new DateTime();
        if (class_exists("s99wpmon_InfosGatherer")) {
            $ig = new s99wpmon_InfosGatherer();
            $datas = json_encode($ig->getEverythingYouGot());
            $encodedDatas = base64_encode($datas);
            $nowDBFormat = $lastUpdate->format("Y-m-d H:i:s");
            $sql = "UPDATE $tablename SET last_update = '$nowDBFormat', datas = '$encodedDatas' WHERE meta_key = 'last_reading'";
            $wpdb->get_results($sql);
        } else {
        	error_log("ERROR WP SCHEDULE!");
        }
    }

    public function gatherInfosAndStoreCron() {
    	if ($_SERVER['SERVER_ADDR'] == $this->getClientIPAddress()) {
    		$this->gatherInfosAndStore();
    		die("updated");
    	} else {
    		die("not updated");
    	}
    }

    public function returnAJAXCallResults() {
    	$wpconsoleIPAddress = gethostbyname("wpconsole.studio99.sm");
    	if ($this->getClientIPAddress() == $wpconsoleIPAddress || $this->getClientIPAddress() == "127.0.0.1" || true) {
	    	global $wpdb;
	        $tablename = $wpdb->prefix . S99WPMON_PLUGIN_TABLE_NAME;
			$sql = "SELECT * FROM $tablename WHERE meta_key = 'last_reading'";
	        $results = json_decode(json_encode($wpdb->get_results($sql)), true);
	        $datas = base64_decode($results[0]["datas"]);
	        header("Content-Type: application/json");
	        die($datas);
	    } else {
	    	die("Access denied!");
	    }
    }

	public function addAdminMenuLink() {
    	add_menu_page("S99-WPMon - Studio99 Wordpress Monnitor", "S99 WPMon", "manage_options", "s99_wp_mon", array($this, "loadAdminPage"), "dashicons-plugins-checked", 110);
    }

	public function loadAdminPage() {
		include_once(S99WPMON_PLUGIN_PATH . "/classes/settings_page.php");
		if (class_exists("s99wpmon_SettingsPage")) {
			$sp = new s99wpmon_SettingsPage();
			$sp->loadPageContent();
		}
    }

	public function register() {
    	add_action("admin_menu", array($this, "addAdminMenuLink"));
    	add_filter("plugin_action_links_" . S99WPMON_PLUGIN_NAME, array($this, "settingsLink"));
    }

    public function settingsLink($links) {
    	$links[] = "<a href=\"admin.php?page=s99_wp_mon\">Settings</a>";
    	return $links;
    }

	public function install() {
    	$this->checkAndPrepareDataBase();
    }

	public function activate() {
		$this->setUpdateCron();
    	$this->checkAndPrepareDataBase();
    }

	public function deactivate() {
    	$this->unsetUpdateCron(time(), "s99_wpmon_schedule_event");
    }

    private function getClientIPAddress() {
    	$ip = null;
    	if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
		    $ip = sanitize_text_field($_SERVER['HTTP_CLIENT_IP']);
		} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		    $ip = sanitize_text_field($_SERVER['HTTP_X_FORWARDED_FOR']);
		} else {
		    $ip = sanitize_text_field($_SERVER['REMOTE_ADDR']);
		}
		return $ip;
    }

}