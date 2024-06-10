<?php
/**
 * Drupal class.
 * To interact with Drupal database.
 *
 * @package drupal-discovery
 */

namespace DrupalDiscovery;


use wpdb;

/**
 * Drupal class.
 */
class Drupal {

	/**
	 * Database object.
	 *
	 * @var wpdb
	 */
	protected $database = null;

	/**
	 * Constructor.
	 */
	protected function __construct() {
		// Get the Drupal database object.
		$this->database = get_drupal_database();
	}

	/**
	 * Get plugin instance.
	 *
	 * @return Drupal
	 */
	public static function get_instance(): self {
		// Create a static variable to store the instance.
		static $instance = null;

		// If the instance is not set, create a new instance.
		if ( null === $instance ) {
			$instance = new self();
		}

		// Return the instance.
		return $instance;
	}

	/**
	 * Get Drupal version.
	 *
	 * @return string Drupal version.
	 */
	public function get_version(): string {
		// Database query.
		$query = "SELECT * FROM system WHERE filename LIKE '%/field.module'";

		// Get the version.
		$result = $this->database->get_row( $query, ARRAY_A );
		$info   = $result['info'] ?? '';
		$info   = maybe_unserialize( $info );

		// Return the version.
		return $info['version'] ?? '';
	}

	/**
	 * Get node types.
	 *
	 * @return array{}|array{
	 *     array{
	 *         type: string,
	 *         count: int
	 *     }
	 * }
	 */
	function get_node_types(): array {
		// Database query.
		$query = "SELECT `type`, count(1) AS `count` FROM node GROUP BY `type`;";

		// Get the result.
		$result = $this->database->get_results( $query, ARRAY_A );

		// Output.
		$output = [];

		// Loop through the result.
		foreach ( $result as $item ) {
			$output[ $item['type'] ] = [
				'type'  => $item['type'],
				'count' => $item['count'],
			];
		}

		// Return the output.
		return $output;
	}

	/**
	 * Get taxonomies.
	 *
	 * @return array{}|array{
	 *     array{
	 *         type: string,
	 *         count: int
	 *    }
	 * }
	 */
	function get_taxonomies(): array {
		// Database query.
		$query = "SELECT machine_name AS `type`, ( SELECT count(1) FROM taxonomy_term_data WHERE taxonomy_term_data.`vid` = taxonomy_vocabulary.`vid` ) AS `count` FROM taxonomy_vocabulary;";
		// Get the result.
		$result = $this->database->get_results( $query, ARRAY_A );

		// Output.
		$output = [];

		// Loop through the result.
		foreach ( $result as $item ) {
			$output[ $item['type'] ] = [
				'type'  => $item['type'],
				'count' => $item['count'],
			];
		}

		// Return the output.
		return $output;
	}
}
