<?php
/**
 * @package S99WPMon
 */

class s99wpmon_SettingsPage {

        function __construct() {
            $this->enqueue();
            require_once(S99WPMON_PLUGIN_PATH . "/template/backend/settings.php");
        }

        public function enqueue() {
            wp_enqueue_style("s99_wp_mon_style", S99WPMON_PLUGIN_URL . "/template/backend/layouts/css/style.css");
            wp_enqueue_script("s99_wp_mon_script", S99WPMON_PLUGIN_URL . "/template/backend/layouts/js/settings.js");
        }

        public function loadPageContent() {
            global $wpdb;
            $tablename = $wpdb->prefix . S99WPMON_PLUGIN_TABLE_NAME;
            $sql = "SELECT * FROM $tablename WHERE meta_key = 'last_reading'";
            $results = $wpdb->get_results($sql);

            if (count($results) == 0) {
                $sql = "INSERT INTO $tablename (meta_key, last_update, datas) VALUES ('last_reading', '2001-01-01 00:00:00', '-')"; 
                $wpdb->get_results($sql);
            }

            $now = new DateTime();
            $now->modify("-1 hour");
            $nowDBFormat = $now->format("Y-m-d H:i:s");

            $sql = "SELECT * FROM $tablename WHERE meta_key = 'last_reading' AND last_update < '$nowDBFormat'";
            $results = $wpdb->get_results($sql);

            $datas = null;
            $lastUpdate = new DateTime();
            if (count($results)) {
                require_once(S99WPMON_PLUGIN_PATH . "/classes/infos_gatherer.php");
                if (class_exists("s99wpmon_InfosGatherer")) {
                    $ig = new s99wpmon_InfosGatherer();
                    $datas = json_encode($ig->getEverythingYouGot());
                    $encodedDatas = base64_encode($datas);
                    $nowDBFormat = $lastUpdate->format("Y-m-d H:i:s");
                    $sql = "UPDATE $tablename SET last_update = '$nowDBFormat', datas = '$encodedDatas' WHERE meta_key = 'last_reading'";
                    $wpdb->get_results($sql);
                } else {
                    die("Something went wrong pal!");
                }
            } else {
                $sql = "SELECT * FROM $tablename WHERE meta_key = 'last_reading'";
                $results = json_decode(json_encode($wpdb->get_results($sql)), true);
                $datas = base64_decode($results[0]["datas"]);
                $lastUpdate = DateTime::createFromFormat("Y-m-d H:i:s", $results[0]["last_update"]);
            }

            $lastUpdate->setTimezone(new DateTimeZone("Europe/Rome"));

            echo "<code>0 * * * * curl -X POST -k -H 'Content-Type: application/x-www-form-urlencoded' -i '" . get_site_url() . "/wp-admin/admin-ajax.php' --data action=execute_cron > /dev/null 2>&1</code><br>";
            echo "<p><b>Ultimo Aggiornamento: {$lastUpdate->format("d/m/Y H:i:s")}</b></p>";
            echo "<pre id=\"s99-wpmon-code\">" . json_encode(json_decode($datas), JSON_PRETTY_PRINT) . "</pre>";

        }
        
}