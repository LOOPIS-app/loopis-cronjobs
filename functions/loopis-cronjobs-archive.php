<?php
/**
 * Function for archive cronjob.
 * 
 * Runs every sunday at 01.00.
 */
 
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/** CRON: ARCHIVE */
function loopis_cronjobs_archive() {
    $start_time = new DateTime(current_time('mysql'));
    $four_weeks_ago = new DateTime(current_time('mysql'));
    $four_weeks_ago->modify('-28 days');

    // Set category IDs
    $old_category_ids = loopis_cats(['old', 'paused']);
    $new_category_id = loopis_cat('archived');

    // Query for old posts in the specified categories
    $args = array(
        'category__in' => $old_category_ids,
        'posts_per_page' => -1,
        'fields' => 'ids',
    );
    $old_posts = new WP_Query($args);
    $checked_count = $old_posts->found_posts;

    // Initialize array to store user IDs of authors whose posts are archived
    $user_ids = array();
    $archived_count = 0;

    // Loop through old posts and archive if necessary
    if ($old_posts->have_posts()) {
        foreach ($old_posts->posts as $post_id) {
            // Initialize archive flag
            $do_archive = null;

            // Check if post life is extended
            $extend_date_meta = get_post_meta($post_id, 'extend_date', true);
            if ($extend_date_meta) {
                $extend_date = new DateTime($extend_date_meta);

                // If the extension date is more than four weeks ago, mark for archiving
                if ($extend_date < $four_weeks_ago) {
                    $do_archive = true;
                }
            } else {
                // If no extension date is set, check the original publish date
                $publish_date = new DateTime(get_the_date('Y-m-d H:i:s', $post_id));

                // If the publish date is more than four weeks ago, mark for archiving
                if ($publish_date < $four_weeks_ago) {
                    $do_archive = true;
                }
            }

            if ($do_archive) {
                wp_set_post_categories($post_id, array($new_category_id));
                update_post_meta($post_id, 'archive_date', current_time('Y-m-d H:i:s'));

                $archived_count++;

                $user_id = get_post_field('post_author', $post_id);
                if ($user_id && !in_array($user_id, $user_ids)) {
                    $user_ids[] = $user_id;
                }
            }
        }
    }
    // Count unique users affected (previously used in the removed email report)
    $unique_users = count($user_ids);

    // Email report to be removed after testing
    $end_time = new DateTime(current_time('mysql'));
    $execution_time = $start_time->diff($end_time)->s;

    $from = "ping@loopis.app";
    $to = "admin@loopis.app";
    $subject = "⭕ Arkivering";
    $message = "
    <b>⭕ {$archived_count} annonser har arkiverats</b><br>
    <hr>
    👤 {$unique_users} användare påverkades<br>
	<br>
	🎁 {$checked_count} annonser kontrollerades<br>
	<br>
    🐎 Processen tog {$execution_time} sekunder<br>
    ⏰ " . $start_time->format('H:i:s') . " → " . $end_time->format('H:i:s') . "<br>
    ";
    $headers = array(
        "From: {$from}",
        'Content-Type: text/html; charset=UTF-8',
        'X-Emoji-Service: twemoji',
    );

    wp_mail($to, $subject, $message, $headers);
}


/** CRON: ARCHIVE */
function loopis_cronjobs_archive_network() {
    if (is_multisite(  )) {
		$sites = get_sites(['fields' => 'ids']);
		$exclude = get_option('loopis_excluded_archive') ?? false;
		foreach ($sites as $site){
			if ($exclude) {
				if (in_array($site, (array) $exclude)){
					continue;
				}
			}
			switch_to_blog($site);
			loopis_cronjobs_archive();
			restore_current_blog();
		}
	}else{
		loopis_cronjobs_archive();
	}
}