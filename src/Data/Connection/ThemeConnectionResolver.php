<?php
namespace WPGraphQL\Data\Connection;

/**
 * Class ThemeConnectionResolver
 *
 * @package WPGraphQL\Data\Resolvers
 * @since 0.5.0
 */
class ThemeConnectionResolver extends AbstractConnectionResolver {
	/**
	 * {@inheritDoc}
	 *
	 * @var array
	 */
	protected $query;

	/**
	 * Get the IDs from the source
	 *
	 * @return array|mixed|null
	 */
	public function get_ids() {

		$ids     = [];
		$queried = $this->get_query();

		if ( empty( $queried ) ) {
			return $ids;
		}

		foreach ( $queried as $key => $item ) {
			$ids[ $key ] = $item;
		}

		return $ids;

	}

	/**
	 * @return array
	 */
	public function get_query_args() {
		$query_args            = $this->query_args;
		$query_args['allowed'] = null;

		return $query_args;
	}


	/**
	 * Get the items from the source
	 *
	 * @return array
	 */
	public function get_query() {
		$query_args = $this->get_query_args();
		return array_keys( wp_get_themes( $query_args ) );
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_ids_for_nodes() {
		if ( empty( $this->ids ) ) {
			return [];
		}

		$ids = $this->ids;

		// If pagination is going backwards, revers the array of IDs
		$ids = ! empty( $this->args['last'] ) ? array_reverse( $ids ) : $ids;

		$cursor_offset = $this->get_offset_for_cursor( $this->args['after'] ?? ( $this->args['before'] ?? 0 ) );

		if ( ! empty( $cursor_offset ) ) {
			// Determine if the offset is in the array
			$key = array_search( $cursor_offset, $ids, true );
			if ( false !== $key ) {
				$key = absint( $key );
				if ( ! empty( $this->args['before'] ) ) {
					// Slice the array from the back.
					$ids = array_slice( $ids, 0, $key, true );
				} else {
					// Slice the array from the front.
					$key ++;
					$ids = array_slice( $ids, $key, null, true );
				}
			}
		}

		$ids = array_slice( $ids, 0, $this->query_amount, true );

		return $ids;
	}

	/**
	 * The name of the loader to load the data
	 *
	 * @return string
	 */
	public function get_loader_name() {
		return 'theme';
	}

	/**
	 * Determine if the offset used for pagination is valid
	 *
	 * @param mixed $offset
	 *
	 * @return bool
	 */
	public function is_valid_offset( $offset ) {
		$theme = wp_get_theme( $offset );
		return $theme->exists();
	}

	/**
	 * Determine if the query should execute
	 *
	 * @return bool
	 */
	public function should_execute() {
		return true;
	}

}
