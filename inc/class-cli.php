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
	 * Get the commands.
	 *
	 * ## EXAMPLES
	 *
	 *     wp drupal get_commands
	 *
	 * @return void
	 */
	public function get_commands() {
		// Get the Drupal instance.
		$drupal = Drupal::get_instance();

		// Get Info
		echo PHP_EOL . '```shell' . PHP_EOL;
		echo 'wp drupal get_info  > docs/A.md' . PHP_EOL;
		echo PHP_EOL .'```' . PHP_EOL;

		// Node Types.
		$node_types = array_keys( $drupal->get_node_types() );
		$directory  = '1.node';

		echo PHP_EOL . $directory . PHP_EOL . PHP_EOL;
		echo '```shell' . PHP_EOL;
		foreach ( $node_types as $entity_name ) {
			printf( 'wp drupal get_entity_fields %s > docs/%s/%s.md' . PHP_EOL, $entity_name, $directory, $entity_name );
		}
		echo PHP_EOL .'```' . PHP_EOL;

		// Taxonomies.
		$taxonomies = array_keys( $drupal->get_taxonomies() );
		$directory  = '2.taxonomy';

		echo PHP_EOL . PHP_EOL . $directory . PHP_EOL . PHP_EOL;
		echo '```shell' . PHP_EOL;
		foreach ( $taxonomies as $entity_name ) {
			printf( 'wp drupal get_entity_fields %s > docs/%s/%s.md' . PHP_EOL, $entity_name, $directory, $entity_name );
		}
		echo PHP_EOL .'```' . PHP_EOL;
	}

	/**
	 * Get the info of Drupal.
	 *
	 * ## EXAMPLES
	 *
	 *     wp drupal get_info
	 *
	 * @return void
	 */
	public function get_info(): void {
		// Get the Drupal instance.
		$drupal = Drupal::get_instance();

		// Get the version.
		$version = $drupal->get_version();

		// Output the version.
		WP_CLI::log( PHP_EOL . "Drupal version: {$version}" . PHP_EOL . PHP_EOL );

		// Get node types.
		$node_types = $drupal->get_node_types();

		// Output the node types.
		echo "### Node Types \n\n";
		echo $this->array_to_markdown_table( array_values( $node_types ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		// Get taxonomy vocabularies.
		$taxonomies = $drupal->get_taxonomies();

		// Output the taxonomies.
		echo "\n\n\n### Taxonomies \n\n";
		echo $this->array_to_markdown_table( array_values( $taxonomies ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		// Get Media Types.
		$media_types = $drupal->get_media_types();

		// Output the media types.
		echo "\n\n\n### Media Types \n\n";
		echo $this->array_to_markdown_table( array_values( $media_types ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped


		$tital_media = wp_list_pluck( $media_types, 'count' );
		$tital_media = array_sum( $tital_media );
		echo "\n#### Total Media Types : $tital_media \n";

		// Get languages.
		$languages = $drupal->get_languages();

		$languages_list = [];
		foreach ( $languages as $item ) {
			$languages_list[] = [
				'domain '     => $item['domain'],
				'language'    => $item['language'],
				'wp_site'     => '',
				'description' => '',
			];
		}

		// Output the languages.
		echo "\n\n\n### Languages \n\n";
		echo $this->array_to_markdown_table( $languages_list ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Get the fields of a node type.
	 *
	 * ## EXAMPLES
	 *
	 *     wp drupal get_entity_fields article
	 *
	 * @param string[] $args Args
	 * @param array<string, string|int> $assoc_args Assoc Args
	 *
	 * @return void
	 */
	public function get_entity_fields( array $args = [], array $assoc_args = [] ) {
		$node_type = ( ! empty( $args[0] ) ) ? trim( strtolower( $args[0] ) ) : '';

		// Get the Drupal instance.
		$drupal = Drupal::get_instance();

		$field_data       = $drupal->get_entity_fields( $node_type );
		$database_queries = $drupal->get_entity_database_query( $node_type );

		echo "## $node_type\n";

		echo "\n#### Fields\n";
		echo $this->array_to_markdown_table( array_values( $field_data ) );

		echo "\n\n#### Main Query\n";

		if ( ! empty( $database_queries['main'] ) ) {
			foreach ( $database_queries['main'] as $query ) {
				echo "\n```sql\n";
				echo $query;
				echo "\n```\n";
			}
		}

		if ( ! empty( $database_queries['multiple'] ) ) {
			echo "\n#### Multi selection field query : \n";

			foreach ( $database_queries['multiple'] as $field_name => $query ) {

				echo "\n##### $field_name\n";
				echo "\n```sql\n";
				echo $query;
				echo "\n```\n";
			}
		}
	}

	public function get_taxonomy_fields( array $args = [], array $assoc_args = [] ) {
		$entity_type = ( ! empty( $args[0] ) ) ? trim( strtolower( $args[0] ) ) : '';

		// Get the Drupal instance.
		$drupal = Drupal::get_instance();

		$field_data = $drupal->get_entity_fields( $entity_type );

		echo "## $entity_type\n";

		echo "\n#### Fields\n";
		echo $this->array_to_markdown_table( array_values( $field_data ) );



	}

	/**
	 * Generate markdown from array.
	 *
	 * @param <string, string|array> $array Array to convert to markdown.
	 *
	 * @return string Markdown table.
	 */
	private function array_to_markdown_table( array $array = [] ): string {
		// Convert arrays to strings.
		foreach ( $array as $row_index => $row ) {
			foreach ( $row as $col_index => $column ) {
				if ( is_array( $column ) ) {
					$array[ $row_index ][ $col_index ] = implode( ', ', $column );
				}
			}
		}

		// Get the heading.
		$heading = array_keys( $array[0] );

		// Capitalize the heading.
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

		// Initialize markdown.
		$markdown = '';

		// Heading.
		foreach ( $heading as $index => $item ) {
			$markdown .= '| ' . str_pad( $item, $widths[ $index ], ' ' ) . ' ';
		}
		$markdown .= "|\n";
		foreach ( $heading as $index => $item ) {
			$markdown .= '|-' . str_pad( '', $widths[ $index ], '-' ) . '-';
		}
		$markdown .= "|\n";

		// Rows.
		foreach ( $array as $row ) {
			// Add row.
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
	 * @param array<int, string|array> $array Array to convert to CSV.
	 *
	 * @return string CSV string.
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

		// Convert array to CSV.
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
