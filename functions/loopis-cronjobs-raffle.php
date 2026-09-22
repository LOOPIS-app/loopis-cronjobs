<?php
/**
 * Main functions for the daily raffle cronjob.
 *
 * Triggered by the LOOPIS clock at 12.00 daily.
 * Loops through all subsites in the network.
 * Loops through all posts from yesterday and performs the raffle logic.
 * Sends a daily report to the subsite admins after the raffle is done.
 * 
 * Complemented by extra functions in separate file.
 */
 
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Do the daily raffle on current subsite.
 * And then send the daily report to the subsite admins.
 */
function loopis_cronjobs_raffle() {
	// Set start time
	$start_time = new DateTime(current_time('mysql'));
	
	// Calculate the start and end of yesterday
	$now_time = new DateTime(current_time('mysql'));
	$yesterday = clone $now_time;
	$yesterday->modify('-1 day');
	$yesterday_start = $yesterday->format('Y-m-d 00:00:00');
	$yesterday_end = $yesterday->format('Y-m-d 23:59:59');
	
	// Get new posts
	$args = array( 
		'post_type' => 'post',
		'posts_per_page' => -1,
		'date_query' => array(
			array(
				'after'     => $yesterday_start,
				'before'    => $yesterday_end,
				'inclusive' => true,
			),
		),
	);
	$the_query = new WP_Query( $args );
	$new_count = $the_query->found_posts;
	
	// Initialize count variables
	$available_count = 0;
	$booked_count = 0;
	$erased_count = 0;
	$email_count = 0;
	$happy_count = 0;
	$sad_count = 0;
		
	// Start post loop
	if( $the_query->have_posts() ):
		while( $the_query->have_posts() ) : 
		$the_query->the_post(); 
	
	// Get post meta variables
		$post_id = get_the_ID();
		loopis_log_level2('Raffle-ing post: ' . $post_id);
		$location = get_post_meta($post_id, 'location', true);
		$participants = get_post_meta($post_id, 'participants', true); 
		if (is_array($participants) && !empty($participants)) {
			$participants = array_filter($participants);   /* remove gaps  */
			$participants = array_values($participants) ;}  /* re-index */
		if (is_array($participants)) { $tickets = count($participants); } else { $tickets = 0; } 
	
	// Start raffle
	if (in_category( 'new' )) {
	
	// Post with no participants
	if ($tickets == 0) { admin_action_switch($post_id); $available_count++; }
	
	// Post with 1 participant
	if ($tickets == 1 && $location == 'Skåpet') { $winner_id = $participants[0]; admin_action_book_locker($winner_id, $post_id); $booked_count++; $email_count += 2; $happy_count += 2; }
	
	// Post with 1 participant & custom location
	if ($tickets == 1 && $location != 'Skåpet') { $winner_id = $participants[0]; admin_action_book_custom($winner_id, $post_id); $booked_count++; $email_count += 2; $happy_count += 2; }
	
	// Post with 1+ participants
	if ($tickets > 1 && $location == 'Skåpet') { admin_action_raffle_locker($participants, $tickets, $post_id); $booked_count++; $email_count += $tickets + 1; $happy_count += 2; $sad_count += $tickets - 1; } 
	
	// Post with 1+ participants & custom location
	if ($tickets > 1 && $location != 'Skåpet') { admin_action_raffle_custom($participants, $tickets, $post_id); $booked_count++; $email_count += $tickets + 1; $happy_count += 2; $sad_count += $tickets - 1; }
	
	// Post removed
	} elseif (in_category('removed')) { admin_action_erase($post_id); $erased_count++; } 
				
	endwhile;
	wp_reset_postdata();
	endif;

	// Calculate % booked
	$raffle_count = $new_count - $erased_count;
	if ($raffle_count < 1) { $booked_percentage = 0; } else {
	$booked_percentage = round(($booked_count / $raffle_count) * 100); }
	
	// Calculate % happy/sad emails
	if ($happy_count < 1) { $happy_percentage = 0; } else {
	$happy_percentage = round(($happy_count / $email_count) * 100); }
	if ($sad_count < 1) { $sad_percentage = 0; } else {
	$sad_percentage = round(($sad_count / $email_count) * 100); }
		
	// Count stuff currently in locker
    $locker_args = array(
        'post_type'      => 'post',
        'posts_per_page' => -1,
        'tax_query'      => array(
            array(
                'taxonomy' => 'category',
                'field'    => 'term_id',
                'terms'    => loopis_cat('locker'),
            ),
        ),
    );
    $locker_query = new WP_Query($locker_args);
    $locker_count = $locker_query->found_posts;
	
	// Count stuff coming to the locker
    $coming_args = array(
        'post_type'      => 'post',
        'posts_per_page' => -1,
        'tax_query'      => array(
            array(
                'taxonomy' => 'category',
                'field'    => 'term_id',
                'terms'    => loopis_cat('booked'),
            ),
        ),
    );
    $coming_query = new WP_Query($coming_args);
    $coming_count = $coming_query->found_posts;

	// Check locker warning setting
	$locker_warning_value = loopis_get_setting('locker_warning', '0');
	if ($locker_warning_value === '0') { $locker_warning = '✅ Varning för skåp ej aktiv';
	} else { $locker_warning = '<b>⚠ Varning för skåp aktiv!</b>'; }

	// Count active 'support' posts
    $active_term = get_term_by('slug', 'active', 'support-category');
	$support_args = array(
        'post_type'      => 'support',
        'posts_per_page' => -1,
        'tax_query'      => array(
            array(
                'taxonomy' => 'support-category',
                'field'    => 'term_id',
                'terms'    => $active_term ? $active_term->term_id : 0,
            ),
        ),
    );
    $support_query = new WP_Query($support_args);
    $support_current = $support_query->found_posts;

	$support_symbol = $support_current > 0 ? '🔴' : '🟢';

	// Count number of members
	$role_counts = count_users()['avail_roles'];
	$member_count = $role_counts['member'] ?? 0;
	$member_pending_count = $role_counts['member_pending'] ?? 0;
	
	// Count new users added yesterday
	$new_users_yesterday = count(get_users([
    'date_query' => [
        [
            'after'     => $yesterday_start,
            'before'    => $yesterday_end,
            'inclusive' => true,
        ],
    ],
    'fields' => 'ID',
	]));

	// Count new members added yesterday
	$new_members_yesterday = count(get_users([
	'role' => 'member',
    'date_query' => [
        [
            'after'     => $yesterday_start,
            'before'    => $yesterday_end,
            'inclusive' => true,
        ],
    ],
    'fields' => 'ID',
	]));
	
	// Get manager emails
	$manager_emails = get_users(array(
		'role'   => 'manager',
		'fields' => array('user_email')
	));
	$manager_emails = wp_list_pluck($manager_emails, 'user_email');

	// Set email details
	$blog_name = get_bloginfo('name');
	$date = current_time('d/m');
	$weekday = current_time('l');

	// Calculate execution time
	$end_time = new DateTime(current_time('mysql'));
	$interval = $start_time->diff($end_time);
	$execution_time = $interval->format('%s');
	
	// Prepare email
	$to = "admin@loopis.app," . implode(', ', $manager_emails);
	$subject = "🌈 " . $blog_name  . " (" . $weekday . " " . $date . ")";
	$message = "
	<p>📊 Här är dagens rapport från LOOPIS " . $blog_name . "</p>
	
	<h3>🎲 Lottning</h3>
	<hr>
	🎁 {$raffle_count} nya annonser skapades igår<br>
	❤ {$booked_count} paxades vid dagens lottning ({$booked_percentage}%)<br>
	🟢 {$available_count} blev först till kvarn<br>
	🔥 {$erased_count} annonser togs bort i förtid<br>
	✉ {$email_count} email skickades<br>
	😃 {$happy_count} glada besked ({$happy_percentage}%)<br>
	☹️ {$sad_count} tråkiga besked ({$sad_percentage}%)<br>

	<h3>⏹ Skåpet</h3>
	<hr>
	⏺️ {$locker_count} saker finns i skåpet just nu<br>
	▶ {$coming_count} saker är på väg till skåpet<br>
	{$locker_warning}<br>

	<h3>🛟 Support</h3>
	<hr>
	{$support_symbol} {$support_current} aktiva support-trådar<br>
	
	<h3>👤 Medlemmar</h3>
	<hr>
	📋 {$new_users_yesterday} nya registreringar igår<br>
	🎉 {$new_members_yesterday} nya medlemmar igår<br>
	⏳ {$member_pending_count} ofärdiga registreringar<br>
	👥 {$member_count} medlemmar totalt<br>

	<h3>🤖 Övrigt</h3>
	<hr>
	⏱ Tidsåtgång: {$execution_time} sekunder (" . $start_time->format('H:i:s') . " → " . $end_time->format('H:i:s') . ")<br>
	💌 Mottagare: {$to}<br>
	";

	$headers = array(
		'From: info@loopis.app',
		'Content-Type: text/html; charset=UTF-8',
		'X-Emoji-Service: twemoji'
			);
	
	// Send email
	wp_mail($to, $subject, $message, $headers);	
}

/**
 * Our cronjob clock triggers this function at 12.00 daily.
 * It will loop through all subsites and execute the function above.
 */
function loopis_cronjobs_raffle_network() {
	if (is_multisite(  )){
		$sites = get_sites(['fields' => 'ids']);
		$exclude = get_option('loopis_excluded_raffle') ?? false;
		foreach ($sites as $site){
			if ($exclude){
				if (in_array($site, (array) $exclude)){
					continue;
				}
			}
			switch_to_blog($site);
			loopis_cronjobs_raffle();
			restore_current_blog();
		}
	} else {
		loopis_cronjobs_raffle();
	}
}