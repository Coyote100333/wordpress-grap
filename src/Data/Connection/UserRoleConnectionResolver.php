<?php

namespace WPGraphQL\Data\Connection;

use Exception;
use GraphQL\Type\Definition\ResolveInfo;
use WPGraphQL\AppContext;
use WPGraphQL\Model\User;

/**
 * Class PluginConnectionResolver - Connects plugins to other objects
 *
 * @package WPGraphQL\Data\Resolvers
 * @since   0.0.5
 */
class UserRoleConnectionResolver extends AbstractConnectionResolver {
	/**
	 * {@inheritDoc}
	 *
	 * @var array
	 */
	protected $query;

	/**
	 * UserRoleConnectionResolver constructor.
	 *
	 * @param mixed       $source     source passed down from the resolve tree
	 * @param array       $args       array of arguments input in the field as part of the GraphQL query
	 * @param AppContext  $context    Object containing app context that gets passed down the resolve tree
	 * @param ResolveInfo $info       Info about fields passed down the resolve tree
	 *
	 * @throws Exception
	 */
	public function __construct( $source, array $args, AppContext $context, ResolveInfo $info ) {
		parent::__construct( $source, $args, $context, $info );
	}

	/**
	 * @return array
	 */
	public function get_ids() {

		// Given a list of role slugs
		if ( isset( $this->query_args['slugIn'] ) ) {
			return $this->query_args['slugIn'];
		}

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
		return is_array( $this->query_args ) && ! empty( $this->query_args ) ? $this->query_args : [];
	}

	/**
	 * @return array
	 */
	public function get_query() {
		$wp_roles = wp_roles();
		$roles    = ! empty( $wp_roles->get_names() ) ? array_keys( $wp_roles->get_names() ) : [];

		return $roles;
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
	 * @return string
	 */
	public function get_loader_name() {
		return 'user_role';
	}

	/**
	 * @param mixed $offset Whether the provided offset is valid for the connection
	 *
	 * @return bool
	 */
	public function is_valid_offset( $offset ) {
		return (bool) get_role( $offset );
	}

	/**
	 * @return bool
	 */
	public function should_execute() {

		if (
			current_user_can( 'list_users' ) ||
			(
				$this->source instanceof User &&
				get_current_user_id() === $this->source->userId
			)
		) {
			return true;
		}

		return false;
	}

}
