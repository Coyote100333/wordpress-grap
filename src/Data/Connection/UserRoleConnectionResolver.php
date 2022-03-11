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
	 * @return mixed
	 */
	public function get_offset() {
		$offset = null;
		if ( ! empty( $this->args['after'] ) ) {
			$offset = substr( base64_decode( $this->args['after'] ), strlen( 'arrayconnection:' ) );
		} elseif ( ! empty( $this->args['before'] ) ) {
			$offset = substr( base64_decode( $this->args['before'] ), strlen( 'arrayconnection:' ) );
		}

		return $offset;
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
	 * @return array|mixed
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

		if ( ! empty( $this->get_offset() ) ) {
			// Determine if the offset is in the array
			$key = array_search( $this->get_offset(), $ids, true );
			if ( false !== $key ) {
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
		return true;
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
