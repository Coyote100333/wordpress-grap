<?php
namespace WPGraphQL\Data\Connection;

/**
 * Class TaxonomyConnectionResolver
 *
 * @package WPGraphQL\Data\Connection
 */
class TaxonomyConnectionResolver extends AbstractConnectionResolver {
	/**
	 * {@inheritDoc}
	 *
	 * @var string[]
	 */
	protected $query;

	/**
	 * {@inheritDoc}
	 */
	public function get_ids_from_query() {
		$ids     = [];
		$queried = $this->query;

		if ( empty( $queried ) ) {
			return $ids;
		}

		foreach ( $queried as $item ) {
			$ids[] = $item;
		}

		return $ids;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function prepare_query_args( array $args ): array {
		// If any args are added to filter/sort the connection.
		return [];
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return string[]
	 */
	public function get_query() {
		$query_args = $this->get_query_args();

		if ( isset( $query_args['name'] ) ) {
			return [ $query_args['name'] ];
		}

		if ( isset( $query_args['in'] ) ) {
			return is_array( $query_args['in'] ) ? $query_args['in'] : [ $query_args['in'] ];
		}

		return \WPGraphQL::get_allowed_taxonomies( 'names', $query_args );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function loader_name(): string {
		return 'taxonomy';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param string $offset The offset (taxonomy name) to check.
	 */
	public function is_valid_offset( $offset ) {
		return (bool) get_taxonomy( $offset );
	}
}
