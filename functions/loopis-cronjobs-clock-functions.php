<?php
/**
 * Extra functions for raffle cronjob.
 *
 * Included for cronjob + admin in manual raffle starting.
 */
 
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function loopis_cronjobs_clock(){
    // runs each hour, fetch hour
    $current_hour = (int) current_time('H');
    $day_of_week = (int) current_time('w');
    loopis_log_function_start('loopis_cronjobs_clock');
    // if block for each run :
    // - raffle at 12 
    // - reminders at 0,5,10,15,20
    // - archive at sundays 13:00
    switch ($current_hour){
        case 0 :
            loopis_log_level1('Its ' . $current_hour . "-o'clock, running reminders!");
            loopis_cronjobs_reminders_network();
            break;
        case 5 :
            loopis_log_level1('Its ' . $current_hour . "-o'clock, running reminders!");
            loopis_cronjobs_reminders_network();
            break;
        case 12 :
            loopis_log_level1('Its ' . $current_hour . "-o'clock, running raffle!");
            loopis_cronjobs_raffle_network();
            break;
        case 13:
            if ($day_of_week===0){
                loopis_log_level1('Its ' . $current_hour . "-o'clock on a sunday, running archive!");
                loopis_cronjobs_archive_network();
            }
            break;
        case 15 :
            loopis_log_level1('Its ' . $current_hour . "-o'clock, running reminders!");
            loopis_cronjobs_reminders_network();
            break;
        case 20 :
            loopis_log_level1('Its ' . $current_hour . "-o'clock, running reminders!");
            loopis_cronjobs_reminders_network();
            break;
        default:
            loopis_log_level1('Its ' . $current_hour . "-o'clock, i've got nothing to do!");
            break;
    }
    loopis_log_function_success('loopis_cronjobs_clock');
}
