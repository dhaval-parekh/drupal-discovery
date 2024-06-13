<?php
/**
 * Namespace functions.
 *
 * @package drupal-discovery
 */

namespace DrupalDiscovery;

use Exception;

use wpdb;

// Minimum should be "1" OR Maximum should be "60".
define( 'CHUNK_LIMIT', 60 );

//define( 'DRUPAL_DB_USER', LEBOAT_MIGRATION_DB_USER );
//define( 'DRUPAL_DB_PASSWORD', LEBOAT_MIGRATION_DB_PASSWORD );
//define( 'DRUPAL_DB_NAME', LEBOAT_MIGRATION_DB_NAME );
//define( 'DRUPAL_DB_HOST', LEBOAT_MIGRATION_DB_HOST );
//define( 'DRUPAL_MEDIA_PATH', LEBOAT_MIGRATION_MEDIA_PATH );

/**
 * Bootstrap plugin.
 *
 * @throws Exception Exception.
 *
 * @return void
 */
function bootstrap(): void {
	// Import files.
	require_once __DIR__ . '/class-drupal.php';

	// Register CLI commands.
	if ( defined( 'WP_CLI' ) && true === WP_CLI ) {
		// Import files.
		require_once __DIR__ . '/class-cli.php';

		// Register commands.
		\WP_CLI::add_command( 'drupal', CLI::class );
	}
}

/**
 * Get the Drupal database object.
 *
 * @return wpdb|null
 */
function get_drupal_database(): wpdb|null {
	// If the constants are not defined, return null.
	if (
		! defined( 'DRUPAL_DB_USER' ) ||
		! defined( 'DRUPAL_DB_PASSWORD' ) ||
		! defined( 'DRUPAL_DB_NAME' ) ||
		! defined( 'DRUPAL_DB_HOST' )
	) {
		return null;
	}

	// Create a static variable to store the database object.
	static $drupal_db = null;

	// If the database object is not set, create a new wpdb object.
	if ( null === $drupal_db ) {
		$drupal_db = new wpdb(
			DRUPAL_DB_USER,
			DRUPAL_DB_PASSWORD,
			DRUPAL_DB_NAME,
			DRUPAL_DB_HOST,
		);
	}

	// Return the database object.
	return $drupal_db;
}
