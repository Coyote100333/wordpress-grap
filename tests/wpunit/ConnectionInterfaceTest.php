<?php

class ConnectionInterfaceTest extends \Tests\WPGraphQL\TestCase\WPGraphQLTestCase {

	public function setUp(): void {
		$this->clearSchema();
		parent::setUp();
	}

	public function tearDown(): void {
		$this->clearSchema();
		parent::tearDown();
	}

	public function interfaceQuery( string $name ) {

		return $this->graphql(
			[
				'query'     => '
				query GetTypeByName($name: String!) {
					__type(name: $name) {
						name
						interfaces {
							name
						}
					}
				}
			',
				'variables' => [
					'name' => $name,
				],
			]
		);
	}

	public function testCommentConnectionImplementsConnection() {

		$results = $this->interfaceQuery( 'CommentConnection' );

		$this->assertArrayNotHasKey( 'errors', $results );
		$this->assertQuerySuccessful(
			$results,
			[
				$this->expectedObject(
					'__type.interfaces',
					[
						'name' => 'Connection',
					]
				),
			]
		);
	}

	public function testCommentConnectionEdgeImplementsConnection() {

		$results = $this->interfaceQuery( 'CommentConnectionEdge' );

		$this->assertArrayNotHasKey( 'errors', $results );
		$this->assertQuerySuccessful(
			$results,
			[
				$this->expectedObject(
					'__type.interfaces',
					[
						'name' => 'Edge',
					]
				),
			]
		);
	}

	public function testCommentToCommenterConnectionEdgeImplementsConnection() {

		$results = $this->interfaceQuery( 'CommentToCommenterConnectionEdge' );

		$this->assertArrayNotHasKey( 'errors', $results );
		$this->assertQuerySuccessful(
			$results,
			[
				$this->expectedObject(
					'__type.interfaces',
					[
						'name' => 'OneToOneConnection',
					]
				),
			]
		);
	}

	public function testContentNodeConnectionImplementsConnection() {

		$results = $this->interfaceQuery( 'ContentNodeConnection' );

		$this->assertArrayNotHasKey( 'errors', $results );
		$this->assertQuerySuccessful(
			$results,
			[
				$this->expectedObject(
					'__type.interfaces',
					[
						'name' => 'Connection',
					]
				),
			]
		);
	}

	public function testContentNodeConnectionEdgeImplementsConnection() {

		$results = $this->interfaceQuery( 'ContentNodeConnectionEdge' );

		$this->assertArrayNotHasKey( 'errors', $results );
		$this->assertQuerySuccessful(
			$results,
			[
				$this->expectedObject(
					'__type.interfaces',
					[
						'name' => 'Edge',
					]
				),
			]
		);
	}

	public function testContentTypeConnectionImplementsConnection() {

		$results = $this->interfaceQuery( 'ContentTypeConnection' );

		$this->assertArrayNotHasKey( 'errors', $results );
		$this->assertQuerySuccessful(
			$results,
			[
				$this->expectedObject(
					'__type.interfaces',
					[
						'name' => 'Connection',
					]
				),
			]
		);
	}

	public function testContentTypeConnectionEdgeImplementsConnection() {

		$results = $this->interfaceQuery( 'ContentTypeConnectionEdge' );

		$this->assertArrayNotHasKey( 'errors', $results );
		$this->assertQuerySuccessful(
			$results,
			[
				$this->expectedObject(
					'__type.interfaces',
					[
						'name' => 'Edge',
					]
				),
			]
		);
	}

	public function testMenuConnectionImplementsConnection() {

		$results = $this->interfaceQuery( 'MenuConnection' );

		$this->assertArrayNotHasKey( 'errors', $results );
		$this->assertQuerySuccessful(
			$results,
			[
				$this->expectedObject(
					'__type.interfaces',
					[
						'name' => 'Connection',
					]
				),
			]
		);
	}

	public function testMenuConnectionEdgeImplementsConnection() {

		$results = $this->interfaceQuery( 'MenuConnectionEdge' );

		$this->assertArrayNotHasKey( 'errors', $results );
		$this->assertQuerySuccessful(
			$results,
			[
				$this->expectedObject(
					'__type.interfaces',
					[
						'name' => 'Edge',
					]
				),
			]
		);
	}

	public function testMenuItemConnectionImplementsConnection() {

		$results = $this->interfaceQuery( 'MenuItemConnection' );

		$this->assertArrayNotHasKey( 'errors', $results );
		$this->assertQuerySuccessful(
			$results,
			[
				$this->expectedObject(
					'__type.interfaces',
					[
						'name' => 'Connection',
					]
				),
			]
		);
	}

	public function testMenuItemConnectionEdgeImplementsConnection() {

		$results = $this->interfaceQuery( 'MenuItemConnectionEdge' );

		$this->assertArrayNotHasKey( 'errors', $results );
		$this->assertQuerySuccessful(
			$results,
			[
				$this->expectedObject(
					'__type.interfaces',
					[
						'name' => 'Edge',
					]
				),
			]
		);
	}

	public function testMenuItemLinkableConnectionEdgeImplementsEdge() {

		$results = $this->interfaceQuery( 'MenuItemLinkableConnectionEdge' );

		$this->assertArrayNotHasKey( 'errors', $results );
		$this->assertQuerySuccessful(
			$results,
			[
				$this->expectedObject(
					'__type.interfaces',
					[
						'name' => 'Edge',
					]
				),
			]
		);
	}

	public function testMenuItemLinkableConnectionEdgeImplementsConnection() {

		$results = $this->interfaceQuery( 'MenuItemLinkableConnectionEdge' );

		$this->assertArrayNotHasKey( 'errors', $results );
		$this->assertQuerySuccessful(
			$results,
			[
				$this->expectedObject(
					'__type.interfaces',
					[
						'name' => 'Edge',
					]
				),
			]
		);
	}

	public function testTaxonomyConnectionImplementsConnection() {

		$results = $this->interfaceQuery( 'TaxonomyConnection' );

		$this->assertArrayNotHasKey( 'errors', $results );
		$this->assertQuerySuccessful(
			$results,
			[
				$this->expectedObject(
					'__type.interfaces',
					[
						'name' => 'Connection',
					]
				),
			]
		);
	}

	public function testTaxonomyEdgeConnectionImplementsConnection() {

		$results = $this->interfaceQuery( 'TaxonomyConnectionEdge' );

		$this->assertArrayNotHasKey( 'errors', $results );
		$this->assertQuerySuccessful(
			$results,
			[
				$this->expectedObject(
					'__type.interfaces',
					[
						'name' => 'Edge',
					]
				),
			]
		);
	}

	public function testTermNodeConnectionEdgeImplementsConnection() {

		$results = $this->interfaceQuery( 'TermNodeConnectionEdge' );

		$this->assertArrayNotHasKey( 'errors', $results );
		$this->assertQuerySuccessful(
			$results,
			[
				$this->expectedObject(
					'__type.interfaces',
					[
						'name' => 'Edge',
					]
				),
			]
		);
	}

	public function testTermNodeConnectionImplementsConnection() {

		$results = $this->interfaceQuery( 'TermNodeConnection' );

		$this->assertArrayNotHasKey( 'errors', $results );
		$this->assertQuerySuccessful(
			$results,
			[
				$this->expectedObject(
					'__type.interfaces',
					[
						'name' => 'Connection',
					]
				),
			]
		);
	}

	public function testUserConnectionImplementsConnection() {

		$results = $this->interfaceQuery( 'UserConnection' );

		$this->assertArrayNotHasKey( 'errors', $results );
		$this->assertQuerySuccessful(
			$results,
			[
				$this->expectedObject(
					'__type.interfaces',
					[
						'name' => 'Connection',
					]
				),
			]
		);
	}

	public function testUserConnectionEdgeImplementsEdge() {

		$results = $this->interfaceQuery( 'UserConnectionEdge' );

		$this->assertArrayNotHasKey( 'errors', $results );
		$this->assertQuerySuccessful(
			$results,
			[
				$this->expectedObject(
					'__type.interfaces',
					[
						'name' => 'Edge',
					]
				),
			]
		);
	}

	public function testPageInfoFragmentOnConnections() {

		$query = '
		{
			contentNodes {
				...PaginatedConnection
			}
			terms {
				...PaginatedConnection
			}
			users {
				...PaginatedConnection
			}
			mediaItems {
				...PaginatedConnection
			}
			comments {
				...PaginatedConnection
			}
			contentTypes {
				...PaginatedConnection
			}
			plugins {
				...PaginatedConnection
			}
			themes {
				...PaginatedConnection
			}
			menus {
				...PaginatedConnection
			}
			menuItems {
				...PaginatedConnection
			}
		}
		
		fragment PaginatedConnection on Connection {
			__typename
			pageInfo {
				startCursor
				endCursor
				hasNextPage
				hasPreviousPage
			}
			nodes {
				__typename
				id
			}
		}
		';

		$results = $this->graphql(
			[
				'query' => $query,
			]
		);

		$this->assertArrayNotHasKey( 'errors', $results );
	}
}
