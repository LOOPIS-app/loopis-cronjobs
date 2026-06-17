<?php
/**
 * Plugin Name: LOOPIS Cronjobs
 * Plugin URI:  https://github.com/LOOPIS-app/loopis-cronjobs
 * Description: Plugin for configuring the cronjobs of LOOPIS.app
 * Version:     0.2
 * Author:      The Develoopers
 * Author URI:  https://loopis.org
 * License:     GPL-3.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: loopis-cronjobs
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
define('LOOPIS_CRONJOBS_VERSION', '0.2');
define('LOOPIS_CRONJOBS_DIR', plugin_dir_path(__FILE__));  

function loopis_cronjobs_include_files() {
    $functions = LOOPIS_CRONJOBS_DIR . 'functions';
    foreach (glob($functions . '/*.php') as $file) {
        include $file;
    }
}
