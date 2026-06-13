<?php
/**
 * Plugin Name:       RDS Auth Bridge
 * Description:        Authenticate WordPress users against an external PostgreSQL database (AWS RDS) via the authenticate hook.
 * Version:           1.0.0
 * Author:            Nick Granados
 * Author URI:        https://nickgranados.com
 * License:           GPL-2.0-or-later
 * Requires PHP:      8.2
 */

if (! defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

\NickGranados\RdsAuthBridge\Plugin::boot();
