<?php

class AccessFunctionsTest extends \Tests\WPGraphQL\TestCase\WPGraphQLTestCase {

	public $admin;

	public function setUp(): void {
		parent::setUp(); // TODO: Change the autogenerated stub

		$this->admin = $this->factory()->user->create( [
			'role' => 'administrator'
		] );
		$settings = get_option( 'graphql_general_settings' );
		$settings['public_introspection_enabled'] = 'on';
		update_option( 'graphql_general_settings', $settings );
	}

	public function tearDown(): void {
		// your tear down methods here

		// then
		parent::tearDown();
		WPGraphQL::clear_schema();
	}

	public function testGraphQLPhpVersion() {

		$contents = file_get_contents( dirname( __FILE__, 3 ) . '/vendor/webonyx/graphql-php/CHANGELOG.md' );
		codecept_debug( $contents );

	}

	/**
	 * Tests whether custom scalars can be registered and used in the Schema
	 *
	 * @throws Exception
	 */
	public function testCustomScalarCanBeUsedInSchema() {

		$test_value = 'test';

		register_graphql_scalar( 'TestScalar', [
			'description'  => __( 'Test Scalar', 'wp-graphql' ),
			'serialize' => function( $value ) {
				return $value;
			},
			'parseValue' => function( $value ) {
				return $value;
			},
			'parseLiteral' => function( $valueNode, array $variables = null ) {
				return isset( $valueNode->value ) ? $valueNode->value : null;
			}
		] );

		register_graphql_field( 'RootQuery', 'testScalar', [
			'type'    => 'TestScalar',
			'resolve' => function() use ( $test_value ) {
				return $test_value;
			}
		] );

		$query    = '{
			__type(name: "TestScalar") {
			  	kind
			}
		}';
		$response = $this->graphql( compact( 'query' ) );

		$this->assertArrayNotHasKey( 'errors', $response );
		$this->assertEquals( 'SCALAR', $response['data']['__type']['kind'] );

		$query     = '{
			__schema {
				queryType {
					fields {
						name
						type {
							name
							kind
						}
					}
				}
			}
		}';
		$response = $this->graphql( compact( 'query' ) );

		$fields = $response['data']['__schema']['queryType']['fields'];

		$test_scalar = array_filter( $fields, function( $field ) {
			return $field['type']['name'] === 'TestScalar' && $field['type']['kind'] === 'SCALAR' ? $field : null;
		} );

		$this->assertNotEmpty( $test_scalar );

		$query    = '{ testScalar }';
		$response = $this->graphql( compact( 'query' ) );

		$this->assertEquals( $test_value, $response['data']['testScalar'] );

	}

	// tests
	public function testMe() {
		$actual   = graphql_format_field_name( 'This is some field name' );
		$expected = 'thisIsSomeFieldName';
		self::assertEquals( $expected, $actual );
	}

	public function testRegisterFieldStartingWithNumberOutputsDebugMessage() {
		register_graphql_field( 'RootQuery', '123TestField', [
			'type' => 'String',
		]);

		$query    = '{
			posts(first:1) {
				nodes {
					id
				}
			}
		}';
		$response = $this->graphql( compact( 'query' ) );

		$this->assertArrayHasKey( 'debug', $response['extensions'] );

		$has_debug_message = null;

		foreach ( $response['extensions']['debug'] as $debug_message ) {
			if (
				'123TestField' === $debug_message['field_name'] &&
				'RootQuery' === $debug_message['type_name'] &&
				'INVALID_FIELD_NAME' === $debug_message['type']
			) {
				$has_debug_message = true;
			}
		}

		$this->assertTrue( $has_debug_message );

	}

	public function testRegisterInputField() {

		/**
		 * Register Test CPT
		 */
		register_post_type( 'test_cpt', [
			"label"               => __( 'Test CPT', 'wp-graphql' ),
			"labels"              => [
				"name"          => __( 'Test CPT', 'wp-graphql' ),
				"singular_name" => __( 'Test CPT', 'wp-graphql' ),
			],
			"description"         => __( 'test-post-type', 'wp-graphql' ),
			"supports"            => [ 'title' ],
			"show_in_graphql"     => true,
			"graphql_single_name" => 'TestCpt',
			"graphql_plural_name" => 'TestCpts',
		] );

		/**
		 * Register a GraphQL Input Field to the connection where args
		 */
		register_graphql_field(
			'RootQueryToTestCptConnectionWhereArgs',
			'testTest',
			[
				'type'        => 'String',
				'description' => 'just testing here'
			]
		);

		/**
		 * Introspection query to query the names of fields on the Type
		 */
		$query = '{
			__type( name: "RootQueryToTestCptConnectionWhereArgs" ) { 
				inputFields {
					name
				}
			} 
		}';

		$response = $this->graphql( compact( 'query' ) );

		/**
		 * Get an array of names from the inputFields
		 */
		$names = array_column( $response['data']['__type']['inputFields'], 'name' );

		/**
		 * Assert that `testTest` exists in the $names (the field was properly registered)
		 */
		$this->assertTrue( in_array( 'testTest', $names ) );

		/**
		 * Cleanup
		 */
		deregister_graphql_field( 'RootQueryToTestCptConnectionWhereArgs', 'testTest' );
		unregister_post_type( 'action_monitor' );
		WPGraphQL::clear_schema();

	}

	/**
	 * Test to make sure "testInputField" doesn't exist in the Schema already
	 * @throws Exception
	 */
	public function testFilteredInputFieldDoesntExistByDefault() {
		/**
		 * Query the "RootQueryToPostConnectionWhereArgs" Type
		 */
		$query = '
		{
		  __type(name: "RootQueryToPostConnectionWhereArgs") {
		    name
		    kind
		    inputFields {
		      name
		    }
		  }
		}
		';

		$response = $this->graphql( compact( 'query') );

		/**
		 * Map the names of the inputFields to be an array so we can properly
		 * assert that the input field is there
		 */
		$field_names = array_map( function( $field ) {
			return $field['name'];
		}, $response['data']['__type']['inputFields'] );

		codecept_debug( $field_names );

		/**
		 * Assert that there is no `testInputField` on the Type already
		 */
		$this->assertArrayNotHasKey( 'errors', $response );
		$this->assertNotContains( 'testInputField', $field_names );
	}

	/**
	 * Test to make sure filtering in "testInputField" properly adds the input to the Schema
	 * @throws Exception
	 */
	public function testFilterInputFields() {

		/**
		 * Query the "RootQueryToPostConnectionWhereArgs" Type
		 */
		$query = '
		{
		  __type(name: "RootQueryToPostConnectionWhereArgs") {
		    name
		    kind
		    inputFields {
		      name
		    }
		  }
		}
		';

		/**
		 * Filter in the "testInputField"
		 */
		add_filter( 'graphql_input_fields', function( $fields, $type_name, $config, $type_registry ) {
			if ( isset( $config['queryClass'] ) && 'WP_Query' === $config['queryClass'] ) {
				$fields['testInputField'] = [
					'type' => 'String'
				];
			}
			return $fields;
		}, 10, 4 );

		$response = $this->graphql( compact( 'query' ) );

		/**
		 * Map the names of the inputFields to be an array so we can properly
		 * assert that the input field is there
		 */
		$field_names = array_map( function( $field ) {
			return $field['name'];
		}, $response['data']['__type']['inputFields'] );

		codecept_debug( $field_names );

		$this->assertArrayNotHasKey( 'errors', $response );
		$this->assertContains( 'testInputField', $field_names );

	}

	public function testRenameGraphQLFieldName() {

		rename_graphql_field( 'RootQuery', 'user', 'wpUser' );

		$query = '{ __type(name: "RootQuery") { fields { name } } }';
		$response = $this->graphql( compact( 'query' ) );

		$this->assertQuerySuccessful(
			$response,
			array(
				$this->not()->expectedNode( '__type.fields', array( 'name' => 'user' ) ),
				$this->expectedNode( '__type.fields', array( 'name' => 'wpUser' ) )
			)
		);
	}

	public function testRenameGraphQLType() {

		rename_graphql_type( 'User', 'WPUser' );
		rename_graphql_type( 'AvatarRatingEnum', 'ImageRatingEnum' );
		rename_graphql_type( 'PostObjectUnion', 'CPTUnion' );
		rename_graphql_type( 'ContentNode', 'PostNode' );

		$query    = '{ __schema { types { name } } }';
		$response = $this->graphql( compact( 'query' ) );

		$this->assertQuerySuccessful(
			$response,
			array(
				$this->not()->expectedNode( '__schema.types', array( 'name' => 'User' ) ),
				$this->not()->expectedNode( '__schema.types', array( 'name' => 'AvatarRatingEnum' ) ),
				$this->not()->expectedNode( '__schema.types', array( 'name' => 'PostObjectUnion' ) ),
				$this->not()->expectedNode( '__schema.types', array( 'name' => 'ContentNode' ) ),
				$this->expectedNode( '__schema.types', array( 'name' => 'WPUser' ) ),
				$this->expectedNode( '__schema.types', array( 'name' => 'ImageRatingEnum' ) ),
				$this->expectedNode( '__schema.types', array( 'name' => 'CPTUnion' ) ),
				$this->expectedNode( '__schema.types', array( 'name' => 'PostNode' ) )
			)
		);

	}

	/**
	 * @throws Exception
	 */
	public function testRenamedGraphQLTypeCanBeReferencedInFieldRegistration() {

		// Rename the User type
		rename_graphql_type( 'User', 'RenamedUser' );

		// Register a field referencing the "User" Type (this should still work)
		register_graphql_field( 'RootQuery', 'testUserField', [
			'type' => 'User',
		] );

		// Register a field referencing the "RenamedUser" Type (this should also work)
		register_graphql_field( 'RootQuery', 'testWpUserField', [
			'type' => 'RenamedUser',
		] );

		// Query for the RootQuery type
		$query = '
		{
		  __type( name:"RootQuery" ) {
		    fields {
		      name
		      type {
		        name
		      }
		    }
		  }
		}
		';

		$response = graphql([
			'query' => $query,
		]);

		// Both fields registered using the Original Type name and the Replaced Type Name
		// should be respected
		// should now be fields of the Type "RenamedUser"
		$this->assertQuerySuccessful(
			$response,
			[
				$this->expectedNode( '__type.fields', [ 'name' => 'testUserField', 'type' => [ 'name' => 'RenamedUser' ] ] ),
				$this->expectedNode( '__type.fields', [ 'name' => 'testWpUserField', 'type' => [ 'name' => 'RenamedUser' ] ] ),
			]
		);
    }

    public function testGraphqlFunctionWorksInResolvers() {

	    register_graphql_field(
		    'RootQuery',
		    'graphqlInResolver',
		    [
			    'type'        => 'String',
			    'description' => __( 'Returns an MD5 hash of the schema, useful in determining if the schema has changed.', 'wp-gatsby' ),
			    'resolve'     => function() {
				    $graphql = \graphql(
					    [
						    'query' => '{posts{nodes{id}}}',
					    ]
				    );

				    $json_string = \wp_json_encode( $graphql['data'] );
				    $md5         = md5( $json_string );

				    return $md5;
			    },
		    ]
	    );

	    $query = '
	   {
	     graphqlInResolver
	   }
	   ';

	    $actual = graphql([
	    	'query' => $query
	    ]);

	    codecept_debug( $actual );

	    $this->assertQuerySuccessful( $actual, [
	    	$this->expectedField( 'graphqlInResolver', self::NOT_NULL )
	    ]);

    }

	public function testGraphqlFunctionWorksInResolversForBatchQueries() {

		register_graphql_field(
			'RootQuery',
			'graphqlInResolver',
			[
				'type'        => 'String',
				'description' => __( 'Returns an MD5 hash of the schema, useful in determining if the schema has changed.', 'wp-gatsby' ),
				'resolve'     => function() {
					$graphql = \graphql(
						[
							'query' => '{posts{nodes{id}}}',
						]
					);

					$json_string = \wp_json_encode( $graphql['data'] );
					$md5         = md5( $json_string );

					return $md5;
				},
			]
		);

		$query = '
	   {
	     graphqlInResolver
	   }
	   ';

		$actual = graphql([
			[
				'query' => $query
			],
			[
				'query' => $query
			]
		]);

		$this->assertTrue( is_array( $actual ) );

		foreach ( $actual as $response ) {
			$this->assertTrue( is_array( $response ) );
			$this->assertQuerySuccessful( $response, [
				$this->expectedField( 'graphqlInResolver', self::NOT_NULL )
			]);
		}

		codecept_debug( $actual );

	}

	public function testSettingRootValueWhenCallingGraphqlFunction() {

		$value = uniqid( 'test-', true );

		register_graphql_field( 'RootQuery', 'someRootField', [
			'type' => 'String',
			'resolve' => function( $root ) {
				return isset ( $root['someRootField' ] ) ? $root['someRootField' ] : null;
			}
		] );

		$actual = graphql([
			'query' => '{someRootField}',
			'root_value' => [
				'someRootField' => $value
			]
		]);

		$this->assertSame( $value, $actual['data']['someRootField'] );


	}
}
