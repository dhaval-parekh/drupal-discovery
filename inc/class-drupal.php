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
	protected $database;

	/**
	 * Constructor.
	 *
	 * @throws \Exception Exception.
	 */
	protected function __construct() {
		// Get the Drupal database object.
		$db_instance = get_drupal_database();

		// If the database object is empty, throw an exception.
		if ( empty( $db_instance ) ) {
			throw new \Exception( 'Failed to connect with Drupal database.' );
		}

		// Set the database object.
		$this->database = $db_instance;
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
	 * Get languages.
	 *
	 * @return array{}|string[]
	 */
	public function get_languages(): array {
		// Database query.
		$query = "SELECT * FROM languages;";

		// Get the result.
		$result = $this->database->get_results( $query, ARRAY_A );

		// If the result is empty or not an array, return an empty array.
		if ( empty( $result ) || ! is_array( $result ) ) {
			return [];
		}

		// Output.
		return $result;
	}

	/**
	 * Get node types.
	 *
	 * @return array{}|array{
	 *     array{
	 *         type: string,
	 *         count: int,
	 *         url: string
	 *     }
	 * }
	 */
	function get_node_types(): array {
		// Database query.
		$query = "SELECT nid, `type`, count(1) AS `count`, ( SELECT alias FROM url_alias WHERE source = CONCAT( 'node/', node.nid ) AND language='en' ) AS drupal_url FROM node GROUP BY `type`;";

		// Get the result.
		$result = $this->database->get_results( $query, ARRAY_A );

		// Output.
		$output = [];

		// Loop through the result.
		foreach ( $result as $item ) {
			$output[ $item['type'] ] = [
				'type'  => $item['type'],
				'count' => $item['count'],
				'url'   => $item['drupal_url'],
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

	/**
	 * Get media types.
	 *
	 * @return array{}|array{
	 *     array{
	 *         type: string,
	 *         count: int
	 *    }
	 * }
	 */
	function get_media_types(): array {
		// Database query.
		$query = "SELECT filemime as `type`, count(1) as `count` FROM file_managed GROUP BY filemime;";

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

	protected function get_field_config( string $field_name = '' ) {
		$field_config = $this->database->get_row(
			"SELECT * FROM field_config WHERE field_name='$field_name';",
			ARRAY_A
		);

		$field_config['data'] = maybe_unserialize( $field_config['data'] );

		return $field_config;
	}

	protected function get_fields_by_entity_type( string $entity_type = '' ) {
		$query      = "SELECT * FROM field_config_instance WHERE bundle='$entity_type';";
		$field_list = $this->database->get_results( $query, ARRAY_A );

		$field_count = count( $field_list );

		for ( $index = 0; $index < $field_count; $index ++ ) {
			$field_list[ $index ]['data'] = maybe_unserialize( $field_list[ $index ]['data'] );
		}

		return $field_list;
	}

	protected function get_table_list() {
		return $this->database->get_col( 'SHOW tables;' );
	}

	protected function get_column_list( $table ) {
		$result = $this->database->get_results( "DESCRIBE $table;", ARRAY_A );

		return wp_list_pluck( $result, 'Field' );
	}

	function get_multiple_fields( string $entity_type = '' ) {
		static $entity_multiple_fields = [];

		if ( isset( $entity_multiple_fields[ $entity_type ] ) ) {
			return $entity_multiple_fields[ $entity_type ];
		}

		$entity_multiple_fields[ $entity_type ] = [];

		$field_list              = $this->get_fields_by_entity_type( $entity_type );
		$list_of_database_tables = $this->get_table_list();


		foreach ( $field_list as $index => $field ) {
			$field_name = $field['field_name'];

			$database_table = 'field_data_' . $field_name;
			$database_table = in_array( $database_table, $list_of_database_tables, true ) ? $database_table : '';

			if ( empty( $database_table ) ) {
				continue;
			}

			$result = $this->database->get_row(
				$this->database->prepare(
					"SELECT entity_id, `language`, count(1) AS count
					FROM %i
					WHERE bundle = %s
					GROUP BY entity_id, `language`
					ORDER BY count DESC
					LIMIT 0, 1;",
					$database_table,
					$entity_type
				),
				ARRAY_A
			);

			if ( empty( $result ) ) {
				continue;
			}

			if ( 1 !== absint( $result['count'] ) ) {
				$entity_multiple_fields[ $entity_type ][] = $field_name;
			}
		}

		return $entity_multiple_fields[ $entity_type ];
	}

	public function get_entity_fields( string $entity_type = '' ): array {
		if ( empty( $entity_type ) ) {
			return [];
		}

		$entity_fields = [];

		$field_list              = $this->get_fields_by_entity_type( $entity_type );
		$list_of_database_tables = $this->get_table_list();
		$multiple_fields         = $this->get_multiple_fields( $entity_type );

		foreach ( $field_list as $index => $field ) {
			$field_name     = $field['field_name'];
			$field_config   = $this->get_field_config( $field_name );
			$field_settings = $field_config['data'];

			// Database table.
			$database_table = 'field_data_' . $field_name;
			$database_table = in_array( $database_table, $list_of_database_tables, true ) ? $database_table : '';

			// Default value.
			if ( ! empty( $field['data']['default_value'] ) && is_array( $field['data']['default_value'] ) ) {
				$field['data']['default_value'] = implode( ', ', $field['data']['default_value'] );
			}

			// Entity type and name.
			$entity_type = '';
			$entity_name = '';

			if ( 'entityreference' === strtolower( $field_config['type'] ) ) {
				$data = $field_config['data'];

				$entity_type = isset( $data['settings']['target_type'] ) ? $data['settings']['target_type'] : '';
				$entity_name = isset( $data['settings']['handler_settings']['target_bundles'] ) ? $data['settings']['handler_settings']['target_bundles'] : [ 'No Entity Name' ];
				$entity_name = implode( ', ', $entity_name );
			}

			$item = [
				'label'          => $field['data']['label'] ?? '',
				'name'           => $field_name,
				'Notes'          => '',
				'WP Field'       => '',
				'type'           => $field_config['type'],
				'entity_type'    => $entity_type,
				'entity_name'    => $entity_name,
				'database_table' => $database_table,
				'columns'        => [],
				'is_required'    => $field['data']['required'] ?? '',
				'default_value'  => $field['data']['default_value'] ?? '',
				'is_multiple'    => in_array( $field_name, $multiple_fields, true ) ? 'Yes' : 'No',
				'is_core_field'  => '',
			];

			if ( ! empty( $item['database_table'] ) ) {
				$column_list = $this->get_column_list( $item['database_table'] );
				$column_list = array_unique( $column_list );

				foreach ( $column_list as $column ) {
					if ( str_contains( $column, $field_name ) ) {
						$item['columns'][] = $column;
					}
				}
			}

			if ( 'taxonomy_term_reference' === $item['type'] ) {
				$item['entity_type'] = $item['type'];
				$item['entity_name'] = wp_list_pluck( $field_config['data']['settings']['allowed_values'], 'vocabulary' );
			}

			$entity_fields[] = $item;
		}

		return $entity_fields;
	}

	public function get_entity_database_query( string $entity_type = '' ): array {
		if ( empty( $entity_type ) ) {
			return '';
		}

		$fields_list     = $this->get_entity_fields( $entity_type );
		$fields_chunks   = array_chunk( $fields_list, CHUNK_LIMIT );
		$multiple_fields = $this->get_multiple_fields( $entity_type );

		$type = '';

		$node_types     = array_keys( $this->get_node_types() );
		$taxonomy_types = array_keys( $this->get_taxonomies() );

		$type_prefix = '';

		if ( in_array( $entity_type, $node_types, true ) ) {
			$type = 'node';
			$type_prefix = 'node';
		} elseif ( in_array( $entity_type, $taxonomy_types, true ) ) {
			$type = 'taxonomy';
			$type_prefix = 'terms';
		}

		if ( empty( $type ) ) {
			return [];
		}

		$default_query_segment = match ( $type ) {
			'node' => [
				'select' => [
					'node.nid, node.vid, node.language, node.type, node.status, node.title, node.created, node.changed, node.comment',
					"( SELECT count(1) FROM redirect WHERE redirect = CONCAT( 'node/', node.nid ) ) AS is_redirected",
					"( SELECT alias FROM url_alias WHERE source = CONCAT( 'node/', node.nid ) AND language='en' ) AS drupal_url",
				],
				'from'   => [
					'node',
				],
				'where'  => [
					"node.type = '$entity_type'",
				],
			],
			'taxonomy' => [
				'select' => [
					'terms.*',
					'vocabulary.`machine_name`',
					'term_hierarchy.`parent`',
					"( SELECT alias FROM url_alias WHERE source = CONCAT( '/taxonomy/term/', terms.tid ) LIMIT 0, 1 ) AS drupal_url",
				],
				'from'   => [
					'taxonomy_term_data AS terms',
					'LEFT JOIN taxonomy_vocabulary AS vocabulary ON vocabulary.vid = terms.vid',
					'LEFT JOIN taxonomy_term_hierarchy AS term_hierarchy ON term_hierarchy.`tid` = terms.tid',
				],
				'where'  => [
					"vocabulary.`machine_name` = '$entity_type'",
				],
				'order'  => [
					'term_hierarchy.`parent` ASC',
				],
			]
		};

		$queryies = [
			'main'     => [],
			'multiple' => [],
		];

		foreach ( $fields_chunks as $fields ) {
			$query_segment = $default_query_segment;
			foreach ( $fields as $field ) {
				if ( empty( $field['database_table'] ) ) {
					continue;
				}


				$field_name  = $field['name'];
				$is_taxonomy = ( 'taxonomy_term_reference' === $field['type'] );
				$is_multiple = (
					in_array( $field_name, $multiple_fields, true ) ||
					in_array( $field['database_table'], $multiple_fields, true )
				);

				$select = [];
				foreach ( $field['columns'] as $column ) {
					$as_column_name = str_replace( [
						'field_',
						'_value',
					], '', $column );
					$select[]       = "{$field['database_table']}.$column AS $as_column_name";

					if ( $is_taxonomy ) {
						$select[] = "( SELECT taxonomy_term_data.name FROM taxonomy_term_data WHERE taxonomy_term_data.tid={$field['database_table']}.$column ) AS {$as_column_name}_name";
					}
				}

				if ( ! $is_multiple ) {
					$query_segment['select'][] = implode( ",\n\t", $select );

					if ( 'node' === $type ) {
						$query_segment['from'][]   = "LEFT JOIN {$field['database_table']} ON node.nid = {$field['database_table']}.entity_id AND {$field['database_table']}.bundle = '$entity_type' AND {$field['database_table']}.language = node.language";
					} elseif ( 'taxonomy' === $type ) {
						$query_segment['from'][]   = "LEFT JOIN {$field['database_table']} ON terms.tid = {$field['database_table']}.entity_id AND {$field['database_table']}.bundle = '$entity_type' AND {$field['database_table']}.language IN ( terms.language, 'und' )";
					}

				} else {

					if ( 'node' === $type ) {
						$custom_field_query = "SELECT\n\tnode.nid,\n\t" . implode( ",\n\t", $select ) .
						                      "\nFROM\n\tnode\n\tINNER JOIN {$field['database_table']} ON node.nid = {$field['database_table']}.entity_id AND {$field['database_table']}.language IN ( node.language, 'und' )" .
						                      "\nWHERE\n\tnode.type = '$entity_type';";
					} elseif ( 'taxonomy' === $type ) {
						$custom_field_query = "SELECT\n\tterm.tid,\n\t" . implode( ",\n\t", $select ) .
						                      "\nFROM\n\ttaxonomy_term_data AS term\n\tINNER JOIN `$db_table` AS `$field_name` ON term.tid = $field_name.entity_id AND term.langcode = $field_name.langcode;";
					}

					$queryies['multiple'][ $field_name ] = $custom_field_query;
				}
			}

			$select_str = implode( ",\n\t", $query_segment['select'] );
			$from_str   = implode( "\n\t", $query_segment['from'] );
			$where_str  = implode( "\n\t", $query_segment['where'] );
			$query      = "SELECT\n\t$select_str\nFROM\n\t$from_str\nWHERE\n\t$where_str";
			if ( ! empty( $query_segment['order'] ) ) {
				$order_str = implode( "\n\t", $query_segment['order'] );
				$query     .= "\nORDER BY\n\t" . $order_str;
			}

			$queryies['main'][] = $query;
		}

		return $queryies;
	}
}
