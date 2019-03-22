<?php
namespace WPGraphQL\Data\Connection;

use GraphQL\Type\Definition\ResolveInfo;
use WPGraphQL\AppContext;
use WPGraphQL\Types;

/**
 * Class UserConnectionResolver
 *
 * @package WPGraphQL\Data\Connection
 */
class UserConnectionResolver extends AbstractConnectionResolver {

	/**
	 * @return bool
	 */
	public function should_execute() {
		return true;
	}

	/**
	 * @return array
	 * @throws \Exception
	 */
	public function get_query_args() {
		/**
		 * Set the $query_args based on various defaults and primary input $args
		 */
		$query_args['count_total'] = false;
		$query_args['offset'] = $this->get_offset();
		$query_args['order'] = ! empty( $this->args['last'] ) ? 'ASC' : 'DESC';

		/**
		 * If "pageInfo" is in the fieldSelection, we need to calculate the pagination details, so
		 * we need to run the query with count_total set to true.
		 */
		$field_selection = $this->info->getFieldSelection( 2 );
		if ( ! empty( $field_selection['pageInfo'] ) ) {
			$query_args['count_total'] = true;
		}

		/**
		 * Set the number, ensuring it doesn't exceed the amount set as the $max_query_amount
		 */
		$query_args['number'] = $this->get_query_amount();

		/**
		 * Take any of the input $args (under the "where" input) that were part of the GraphQL query and map and
		 * sanitize their GraphQL input to apply to the WP_Query
		 */
		$input_fields = [];
		if ( ! empty( $this->args['where'] ) ) {
			$input_fields = $this->sanitize_input_fields( $this->args['where'] );
		}

		/**
		 * Merge the default $query_args with the $args that were entered in the query.
		 *
		 * @since 0.0.5
		 */
		if ( ! empty( $input_fields ) ) {
			$query_args = array_merge( $query_args, $input_fields );
		}

		/**
		 * Only query the IDs and let deferred resolution query the nodes
		 */
		$query_args['fields'] = 'ID';

		/**
		 * Filter the query_args that should be applied to the query. This filter is applied AFTER the input args from
		 * the GraphQL Query have been applied and has the potential to override the GraphQL Query Input Args.
		 *
		 * @param array       $query_args array of query_args being passed to the
		 * @param mixed       $source     source passed down from the resolve tree
		 * @param array       $args       array of arguments input in the field as part of the GraphQL query
		 * @param AppContext  $context    object passed down zthe resolve tree
		 * @param ResolveInfo $info       info about fields passed down the resolve tree
		 *
		 * @since 0.0.6
		 */
		$query_args = apply_filters( 'graphql_user_connection_query_args', $query_args, $this->source, $this->args, $this->context, $this->info );

		return $query_args;
	}

	/**
	 * @return mixed|\WP_User_Query
	 * @throws \Exception
	 */
	public function get_query() {
		return new \WP_User_Query( $this->get_query_args() );
	}

	/**
	 * @return array
	 * @throws \Exception
	 */
	public function get_items() {
		$results = $this->get_query()->get_results();
		return ! empty( $results ) ? $results : [];
	}

	/**
	 * This sets up the "allowed" args, and translates the GraphQL-friendly keys to WP_User_Query
	 * friendly keys.
	 *
	 * There's probably a cleaner/more dynamic way to approach this, but this was quick. I'd be
	 * down to explore more dynamic ways to map this, but for now this gets the job done.
	 *
	 * @param array       $args     The query "where" args
	 *
	 * @since  0.0.5
	 * @return array
	 * @access protected
	 */
	protected function sanitize_input_fields( array $args ) {

		$arg_mapping = [
			'roleIn'            => 'role__in',
			'roleNotIn'         => 'role__not_in',
			'searchColumns'     => 'search_columns',
			'hasPublishedPosts' => 'has_published_posts',
			'nicenameIn'        => 'nicename__in',
			'nicenameNotIn'     => 'nicename__not_in',
			'loginIn'           => 'login__in',
			'loginNotIn'        => 'login__not_in',
		];

		/**
		 * Map and sanitize the input args to the WP_User_Query compatible args
		 */
		$query_args = Types::map_input( $args, $arg_mapping );

		/**
		 * Filter the input fields
		 *
		 * This allows plugins/themes to hook in and alter what $args should be allowed to be passed
		 * from a GraphQL Query to the get_terms query
		 *
		 * @param array       $query_args The mapped query args
		 * @param array       $args       The query "where" args
		 * @param mixed       $source     The query results of the query calling this relation
		 * @param array       $all_args   Array of all the query args (not just the "where" args)
		 * @param AppContext  $context    The AppContext object
		 * @param ResolveInfo $info       The ResolveInfo object
		 *
		 * @since 0.0.5
		 * @return array
		 */
		$query_args = apply_filters( 'graphql_map_input_fields_to_wp_comment_query', $query_args, $args, $this->source, $this->args, $this->context, $this->info );

		return ! empty( $query_args ) && is_array( $query_args ) ? $query_args : [];

	}
}
