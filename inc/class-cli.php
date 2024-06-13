<?php
/**
 * WP CLI command for Drupal Discovery.
 *
 * @package drupal-discovery
 */

namespace DrupalDiscovery;

use WP_CLI;

/**
 * WP CLI class.
 */
class CLI {

	/**
	 * Get the version of Drupal.
	 *
	 * ## EXAMPLES
	 *
	 *     wp drupal version
	 *
	 * @return void
	 */
	public function version(): void {
		// Get the Drupal instance.
		$drupal = Drupal::get_instance();

		// Get the version.
		$version = $drupal->get_version();

		// Output the version.
		WP_CLI::log( "Drupal version: {$version}" );
	}

	/**
	 * Get the info of Drupal.
	 *
	 * ## EXAMPLES
	 *
	 *     wp drupal info
	 *
	 * @return void
	 */
	public function get_info(): void {
		// Get the Drupal instance.
		$drupal = Drupal::get_instance();

		// Get node types.
		$node_types = $drupal->get_node_types();

		// Output the node types.
		echo "### Node Types \n";
		echo $this->array_to_markdown_table( array_values( $node_types ) );

		// Get taxonomy vocabularies.
		$taxonomies = $drupal->get_taxonomies();

		// Output the taxonomies.
		echo "\n\n\n### Taxonomies \n";
		echo $this->array_to_markdown_table( array_values( $taxonomies ) );
	}

	public function get_entity_fields( array $args = [], array $assoc_args = [] ) {
		$node_type = ( ! empty( $args[0] ) ) ? trim( strtolower( $args[0] ) ) : '';

		// Get the Drupal instance.
		$drupal = Drupal::get_instance();

		$field_data = $drupal->get_entity_fields( $node_type );
		$database_queries = $drupal->get_entity_database_query( $node_type );

		$this->array_to_markdown_table( array_values($field_data) );
		print_r( $database_queries );
	}

	/**
	 * Generate markdown from array.
	 *
	 * @param <string, string|array> $array
	 *
	 * @return string
	 */
	private function array_to_markdown_table( array $array = [] ): string {
		foreach ( $array as $row_index => $row ) {
			foreach ( $row as $col_index => $column ) {
				if ( is_array( $column ) ) {
					$array[ $row_index ][ $col_index ] = implode( ', ', $column );
				}
			}
		}

		$heading = array_keys( $array[0] );
		foreach ( $heading as $index => $item ) {
			$heading[ $index ] = ucwords( str_replace( '_', ' ', $item ) );
		}

		// Find the longest string in each column.
		$cols = array_merge(
			[ $heading ],
			$array
		);
		array_unshift( $cols, null );
		$cols   = call_user_func_array( 'array_map', $cols );
		$widths = array_map(
			function ( $col ) {
				return max( array_map( 'strlen', $col ) );
			},
			$cols
		);

		$markdown = '';

		// Heading.
		foreach ( $heading as $index => $item ) {
			$markdown .= '| ' . str_pad( $item, $widths[ $index ], ' ' ) . ' ';
		}
		$markdown .= "|\n";
		foreach ( $heading as $index => $item ) {
			$markdown .= '| ' . str_pad( '', $widths[ $index ], '-' ) . ' ';
		}
		$markdown .= "|\n";

		foreach ( $array as $row ) {
			$markdown .= '| ' . implode(
					' | ',
					array_map(
						function ( $cell, $width ) {
							return str_pad( $cell, $width );
						},
						$row,
						$widths
					)
				) . " |\n";
		}

		return $markdown;
	}

	/**
	 * Array to CSV.
	 *
	 * @param array<string, string|array> $array
	 *
	 * @return string
	 */
	private function array_to_csv( $array ): string {
		// Convert arrays to strings.
		foreach ( $array as $row_index => $row ) {
			foreach ( $row as $col_index => $column ) {
				if ( is_array( $column ) ) {
					$array[ $row_index ][ $col_index ] = implode( ' - ', $column );
				}
			}
		}

		$csv     = '';
		$heading = array_keys( $array[0] );
		$csv     .= implode( ',', $heading ) . "\n";
		foreach ( $array as $row ) {
			foreach ( $row as $i => $col ) {
				$row[ $i ] = '"' . $col . '"';
			}

			$csv .= implode( ',', $row ) . "\n";
		}

		return $csv;
	}

}
