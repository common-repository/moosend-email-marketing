<?php

/**
 * Plugin Name: Moosend Website Tracking
 * Plugin URI: http://moosend.com/wordpress-plugin
 * Description: Track your store activity and send them to Moosend platform.
 * Version: 1.0.190
 * Author: Moosend
 * Author URI: http://moosend.com
 * Requires at least: 4.1
 *
 * Text Domain: moosend-email-marketing
 *
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

const MO_SITE_ID = 'moo_site_id';
const MOO_TEXT_DOMAIN = 'moosend-email-marketing';

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/vendor/moosend/website-tracking/index.php';

$mooTracker = new MooTracker(new \Moosend\TrackerFactory(), get_option(MO_SITE_ID));
