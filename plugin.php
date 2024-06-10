<?php
/**
 * Plugin Name: Drupal Discovery
 * Description: Discovery for Drupal,
 *
 * @package drupal-discovery
 */

namespace DrupalDiscovery;

require_once __DIR__ . '/inc/namespace.php';

// Kick it off.
add_action( 'plugins_loaded', __NAMESPACE__ . '\\bootstrap' );
