<?php
// Build paths relative to this file location
$wp_root = dirname(__DIR__, 4);
$plugin_dir = dirname(__DIR__, 1);
$theme_dir = $wp_root . '/wp-content/themes/loopis-theme';

// Load WordPress
require_once $wp_root . '/wp-load.php';

// Include the necessary files
include_once $plugin_dir . '/loopis-cronjobs.php';
loopis_cron_include_files();

include_once $theme_dir . '/includes/functions/user/admin-post-comment.php';
include_once $theme_dir . '/includes/functions/user/admin-notification.php';
include_once $theme_dir . '/includes/functions/user/get-locker.php';

// Start the custom cron job
loopis_cron_clock();



