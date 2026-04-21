<?php
/*
Plugin Name: LOOPIS Cronjobs
Plugin URI: https://github.com/LOOPIS-app/loopis-config
Description: Plugin for cronjob functionality on loopis page
Version: 0.1 (beta)
Author: The Develoopers
Author URI: https://loopis.org
*/

/*
 * Copyright (C) 2026 LOOPIS
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

// Prevent direct access
if (!defined('ABSPATH')) { 
    exit; 
}

// Define plugin version
define('LOOPIS_CRON_VERSION', '0.1');
define('LOOPIS_CRON_DIR', plugin_dir_path(__FILE__));  

function loopis_cron_include_files() {
    $functions = LOOPIS_CRON_DIR . '/functions/';
    foreach (glob($functions . '/*.php') as $file) {
        include_once $file;
    }
}



