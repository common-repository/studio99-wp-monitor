<?php
/**
 * @package S99WPMon
 */

class s99wpmon_InfosGatherer {

        function __construct() { }

        public function getEverythingYouGot() {
            if (!function_exists("get_core_updates")) {
                include_once(ABSPATH . "wp-admin/includes/update.php");
            }
            if (!function_exists('get_site_transient')) {
                require_once ABSPATH . 'wp-admin/includes/option.php';
            }

            global $wp_version;

            $pluginUpdates = json_decode(json_encode(get_site_transient("update_plugins")), true);
            $pluginUpdates = $pluginUpdates["response"];

            $coreInfos = json_decode(json_encode(get_core_updates()), true);
            $wpTheme = wp_get_theme();

            $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD);

            return [
                "system_infos" => $this->getSystemInfos(),
                "hostname" => gethostname(),
                "apache" => [
                    "php_version" => phpversion(),
                    "upload_max_filesize" => ini_get("upload_max_filesize"),
                    "memory_limit" => ini_get("memory_limit"),
                    "max_input_vars" => ini_get("max_input_vars"),
                    "max_execution_time" => ini_get("max_execution_time"),
                    "maxlifetime" => ini_get("session.gc_maxlifetime")
                ],
                "mySQL" => [
                    "version" => $mysqli->server_info
                ],
                "wordpress" => [
                    "name" => get_bloginfo("name"),
                    "description" => get_bloginfo("description"),
                    "seo_discuraged" => intval(get_option("blog_public")) === 0 ? false : true,
                    "core_version" => $wp_version,
                    "core_new_version" => $coreInfos[0]["current"],
                    "core_version_update" => $coreInfos[0]["response"],
                    "overridden_memory_limit" => defined(WP_MEMORY_LIMIT) ? WP_MEMORY_LIMIT : "not set",
                    "debug_mode" => WP_DEBUG,
                    "plugins" => $this->getPluginsInfos($pluginUpdates),
                    "theme" => [
                        "name" => $wpTheme->get("Name"),
                        "version" => $wpTheme->get("Version"),
                        "status" => $wpTheme->get("Status"),
                        "child_active" => !empty($wpTheme->parent()),
                        "father" => empty($wpTheme->parent()) ? null : ["name" => $wpTheme->parent()->get("Name"),"version" => $wpTheme->parent()->get("Version"),"status" => $wpTheme->parent()->get("Status")]
                    ]
                ]
            ];
        } 

        private function getPluginsInfos($updates) {
            if (!function_exists("get_plugins")) {
                include_once(ABSPATH . "wp-admin/includes/plugin.php");
            }

            $rawPlugins = get_plugins();
            $plugins = [];
            foreach ($rawPlugins as $key => $rp) {
                $updateRequired = isset($updates[$key]) ? true : false;
                $newVersion = isset($updates[$key]) ? $updates[$key]["new_version"] : "";
                $plugins[] = [
                    "unique" => $key,
                    "active" => is_plugin_active($key),
                    "name" => $rp["Name"],
                    "version" => $rp["Version"],
                    "update_required" => $updateRequired,
                    "new_version" => $newVersion,
                    "wp_version_required" => $rp["RequiresWP"],
                    "description" => strip_tags($rp["Description"])
                ];
            }
            return $plugins;
        }

        private function getSystemInfos() {
            $rootPath = dirname(ABSPATH . "..");

            $memInfoArray = $this->getSystemMemInfo();
            $memTotal = number_format(intval(str_replace(" kB", "", $memInfoArray["MemTotal"])) / 1024 / 1024, 2);
            $memUsed = number_format(intval(str_replace(" kB", "", $memInfoArray["MemAvailable"])) / 1024 / 1024, 2);
            $memFree = number_format(intval(str_replace(" kB", "", $memInfoArray["MemFree"])) / 1024 / 1024, 2);
            $memPecent = number_format($memUsed / $memTotal * 100, 2);

            $diskTotal = number_format(disk_total_space($rootPath) / 1024 / 1024 / 1024, 2);
            $diskFree = number_format(disk_free_space($rootPath) / 1024 / 1024 / 1024, 2);
            $diskUsed = number_format($diskTotal - $diskFree, 2);
            $diskPecent = number_format($diskUsed / $diskTotal * 100, 2);

            $cpuInfoArray = $this->getCPUInfo();

            return [
                    "cpu_cores" => $cpuInfoArray["cores"],
                    "cpu_vendor" => $cpuInfoArray["vendor"],
                    "cpu_model" => $cpuInfoArray["model"],
                    "cpu_ghz" => $cpuInfoArray["cpu"],
                    "mem_percent" => $memPecent,
                    "mem_total" => $memTotal,
                    "mem_used" => $memUsed,
                    "mem_free" => $memFree,
                    "hdd_free" => $diskFree,
                    "hdd_total" => $diskTotal,
                    "hdd_used" => $diskUsed,
                    "hdd_percent" => $diskPecent
            ];
        }

        private function getSystemMemInfo() {
            $data = explode("\n", file_get_contents("/proc/meminfo"));
            $meminfo = array();
            foreach ($data as $line) {
                list($key, $val) = explode(":", $line);
                $meminfo[$key] = trim($val);
            }
            return $meminfo;
        }

        private function getMySQLVersion() { 
            $output = shell_exec('mysql -V'); 
            preg_match('@[0-9]+\.[0-9]+\.[0-9]+@', $output, $version); 
            return $version[0]; 
        }

        private function getCPUInfo() {
            $cpuFileInfo = file_get_contents('/proc/cpuinfo');
            preg_match_all("/(processor)/", $cpuFileInfo, $cores);
            preg_match_all("/vendor[\s\t\-_]id[\t\s]+:[\t\s]+(.+)/", $cpuFileInfo, $vendors);
            preg_match_all("/model[\s\t\-_]name[\t\s]+:[\t\s]+(.+)/", $cpuFileInfo, $models);
            preg_match_all("/cpu[\t\s\-_]MHz[\t\s]+:[\t\s]+(.+)/", $cpuFileInfo, $strenghts);
            $result = array($cores, $vendors, $models, $strenghts);

            return array(
                "cores" => isset($cores[1]) ? count($cores[1]) : "undefines",
                "vendor" => isset($vendors[1]) ? $vendors[1][0] : "undefines",
                "model" => isset($models[1]) ? $models[1][0] : "undefines",
                "cpu" => isset($strenghts[1]) ? number_format(floatval($strenghts[1][0]) / 1024, 2) : "undefined", 
            );
        }
        
}