<?php
namespace WPGraphQL;
use GraphQL\Error\FormattedError;
use GraphQL\GraphQL;
use GraphQL\Schema;

/**
 * Class Router
 *
 * This sets up the /graphql endpoint
 *
 * @package WPGraphQL
 * @since 0.0.1
 */
class Router {

	/**
	 * route
	 *
	 * Sets the route to use as the endpoint
	 *
	 * @var string
	 */
	public $route = 'graphql';

	/**
	 * Router constructor.
	 *
	 * @since 0.0.1
	 * @access public
	 */
	public function __construct() {

		/**
		 * Pass the route through a filter in case the endpoint /graphql should need to be changed
		 *
		 * @since 0.0.1
		 */
		$this->route = apply_filters( 'graphql_endpoint', 'graphql' );

		/**
		 * Create the rewrite rule for the route
		 *
		 * @since 0.0.1
		 */
		add_action( 'init', array( $this, 'add_rewrite_rule' ), 10 );

		/**
		 * Add the query var for the route
		 *
		 * @since 0.0.1
		 */
		add_filter( 'query_vars', array( $this, 'add_query_var' ), 10, 1 );

		/**
		 * Redirects the route to the graphql processor
		 *
		 * @since 0.0.1
		 */
		add_action( 'template_redirect', array( $this, 'resolve_http_request' ), 10 );

	}

	/**
	 * Adds rewrite rule for the route endpoint
	 *
	 * @since 0.0.1
	 * @access public
	 */
	public function add_rewrite_rule() {

		add_rewrite_rule(
			$this->route . '/?$',
			'index.php?' . $this->route . '=true',
			'top'
		);

	}

	/**
	 * Adds the query_var for the route
	 *
	 * @since 0.0.1
	 *
	 * @param $query_vars
	 *
	 * @return array
	 */
	public function add_query_var( $query_vars ) {

		$query_vars[] = $this->route;

		return $query_vars;

	}

	/**
	 * resolve_http_request
	 *
	 * This resolves the http request and ensures that WordPress can respond with the appropriate
	 * JSON response instead of responding with a template from the standard
	 * WordPress Template Loading process
	 *
	 * @since 0.0.1
	 */
	public function resolve_http_request() {

		/**
		 * Access the $wp_query object
		 */
		global $wp_query;

		/**
		 * Ensure we're on the registered route for graphql route
		 */
		if ( ! $wp_query->get( $this->route ) ) {
			return;
		}

		/**
		 * Set is_home to false
		 */
		$wp_query->is_home = false;

		/**
		 * Process the GraphQL query Request
		 */
		$this->process_http_request();

		return;

	}

	/**
	 * Set the response headers
	 *
	 * @since 0.0.1
	 */
	public function set_headers( $http_status ) {

		header( 'Access-Control-Allow-Origin: *' );
		header( 'Access-Control-Allow-Headers: content-type' );
		header( 'Content-Type: application/json', true, $http_status );

	}

	/**
	 * This processes the graphql requests that come into the /graphql endpoint via an HTTP request
	 *
	 * @todo: This needs to be re-worked to be a little more robust. Probably would be good to check out the REST API implementation on processing and responding to HTTP requests.
	 * @since 0.0.1
	 * @access public
	 * @return mixed
	 */
	public function process_http_request() {

		/**
		 * This action can be hooked to to enable various debug tools,
		 * such as enableValidation from the GraphQL Config.
		 * @since 0.0.4
		 */
		do_action( 'graphql_process_http_request' );

		try {

			/**
			 * Configure the app_context which gets passed down
			 * to all the resolvers.
			 *
			 * @since 0.0.4
			 */
			$app_context = new AppContext();
			$app_context->viewer = wp_get_current_user();
			$app_context->root_url = get_bloginfo( 'url' );
			$app_context->request = $_REQUEST;

			if ( isset( $_SERVER['CONTENT_TYPE'] ) && strpos( $_SERVER['CONTENT_TYPE'], 'application/json' ) !== false ) {
				$raw = file_get_contents( 'php://input' ) ?: '';
				$data = json_decode( $raw, true );
			} else {
				$data = $_REQUEST;
			}

			$data += [ 'query' => null, 'variables' => null ];

			if ( null === $data['query'] ) {
				$data['query'] = '{hello}';
			}

			/**
			 * Generate the Schema
			 */
			$schema = new Schema([
				'query' => Types::root_query(),
			]);

			$result = GraphQL::execute(
				$schema,
				$data['query'],
				null,
				$app_context,
				(array) $data['variables']
			);

			/**
			 * Run an action. This is a good place for debug tools to hook in
			 * to log things, etc.
			 * @since 0.0.4
			 */
			do_action( 'graphql_execute', $result, $schema, $data );

			/**
			 * Set the status code to 200
			 */
			$http_status = 200;

		} catch ( \Exception $error ) {

			/**
			 * If there are errors, set the status to 500
			 * and format the captured errors to be output properly
			 * @since 0.0.4
			 */
			$http_status = 500;
			if ( defined( 'GRAPHQL_DEBUG' ) && true === GRAPHQL_DEBUG ) {
				$result['extensions']['exception'] = FormattedError::createFromException( $error );
			} else {
				$result['errors'] = [ FormattedError::create( 'Unexpected Error' ) ];
			}
		}

		/**
		 * Set the response headers
		 */
		$this->set_headers( $http_status );
		wp_send_json( $result );

	}

}
