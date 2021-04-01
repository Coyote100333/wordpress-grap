<?php

class CursorPaginationForPostsTest extends \Codeception\TestCase\WPTestCase {

	public function setUp(): void {
		parent::setUp();
	}

	public function tearDown(): void {
		parent::tearDown(); // TODO: Change the autogenerated stub
	}

	public function createPostObject( $args ) {

		/**
		 * Set up the $defaults
		 */
		$defaults = [
			'post_author'   => $this->admin,
			'post_content'  => 'test',
			'post_excerpt'  => 'Test excerpt',
			'post_status'   => 'publish',
			'post_title'    => 'Test Title',
			'post_type'     => 'post',
			'post_date'     => $this->current_date,
			'has_password'  => false,
			'post_password' => null,
		];

		/**
		 * Combine the defaults with the $args that were
		 * passed through
		 */
		$args = array_merge( $defaults, $args );

		/**
		 * Create the page
		 */
		$post_id = $this->factory->post->create( $args );

		/**
		 * Update the _edit_last and _edit_lock fields to simulate a user editing the page to
		 * test retrieving the fields
		 *
		 * @since 0.0.5
		 */
		update_post_meta( $post_id, '_edit_lock', $this->current_time . ':' . $this->admin );
		update_post_meta( $post_id, '_edit_last', $this->admin );

		/**
		 * Return the $id of the post_object that was created
		 */
		return $post_id;

	}

	/**
	 * Creates several posts (with different timestamps) for use in cursor query tests
	 *
	 * @param  int $count Number of posts to create.
	 * @return array
	 */
	public function create_posts() {

		$alphabet = range( 'A', 'Z' );

		// Create posts
		$created_posts = [];
		for ( $i = 0; $i <= count( $alphabet ) - 1; $i ++ ) {
			// Set the date 1 minute apart for each post
			$date                = date( 'Y-m-d H:i:s', strtotime( "-1 day -{$i} minutes" ) );
			$created_posts[ $i ] = $this->createPostObject(
				[
					'post_type'   => 'post',
					'post_date'   => $date,
					'post_status' => 'publish',
					'post_title'  => $alphabet[ ($i ) ],
				]
			);
		}

		return $created_posts;

	}

	public function delete_posts( $post_ids ) {
		foreach( $post_ids as $post_id ) {
			wp_delete_post( $post_id );
		}
	}

	public function get_nodes( $results ) {
		return $results['data']['posts']['nodes'];
	}

	public function get_edges( $results ) {
		return $results['data']['posts']['edges'];
	}

	public function testForwardPagination() {

		$post_ids = $this->create_posts();
		$query = '
		query TestForwardPaginationForPosts( $first: Int $after: String $last:Int $before: String ) {
		  posts( first:$first last:$last after:$after before:$before) {
		    pageInfo {
		      hasNextPage
		      hasPreviousPage
		      startCursor
		      endCursor
		    }
		    edges {
		      cursor
		    }
		    nodes {
		      databaseId
		      title
		    }
		  }
		}
		';

		$actual = graphql([
			'query' => $query,
			'variables' => [
				'first' => 5,
				'after' => null,
				'last' => null,
				'before' => null
			]
		]);

		$this->assertArrayNotHasKey( 'errors', $actual );
		codecept_debug( $actual );

		$nodes = $this->get_nodes( $actual );
		$titles = wp_list_pluck( $nodes, 'title' );

		$alphabet = range( 'A', 'Z' );
		$this->assertSame( $titles, array_slice( $alphabet, 0, 5 ) );

		// Page forward by 5
		$actual = graphql([
			'query' => $query,
			'variables' => [
				'first' => 5,
				'after' => $actual['data']['posts']['pageInfo']['endCursor'],
				'last' => null,
				'before' => null
			]
		]);

		codecept_debug( $actual );

		$this->assertArrayNotHasKey( 'errors', $actual );

		$nodes = $this->get_nodes( $actual );
		$titles = wp_list_pluck( $nodes, 'title' );

		$alphabet = range( 'A', 'Z' );
		$this->assertSame( $titles, array_slice( $alphabet, 5, 5 ) );

		codecept_debug( [ 'endCursor' => base64_decode( $actual['data']['posts']['pageInfo']['endCursor'] ) ]);

		// Ask for the first 5 items, with a before cursor established
		$actual = graphql([
			'query' => $query,
			'variables' => [
				'first' => 5,
				'after' => null,
				'last' => null,
				'before' => $actual['data']['posts']['pageInfo']['endCursor']
			]
		]);

		codecept_debug( $actual );

		$this->assertArrayNotHasKey( 'errors', $actual );

		$nodes = $this->get_nodes( $actual );
		$titles = wp_list_pluck( $nodes, 'title' );

		$alphabet = range( 'A', 'Z' );
		$this->assertSame( $titles, array_slice( $alphabet, 0, 5 ) );

		// Ask for the first 5 items, but within the bounds of a before and after cursor
		$actual = graphql([
			'query' => $query,
			'variables' => [
				'first' => 5,
				'after' => $this->get_edges( $actual )[1]['cursor'],
				'last' => null,
				'before' => $this->get_edges( $actual )[3]['cursor']
			]
		]);

		codecept_debug( $actual );

		$this->assertArrayNotHasKey( 'errors', $actual );

		$nodes = $this->get_nodes( $actual );
		$titles = wp_list_pluck( $nodes, 'title' );

		$alphabet = range( 'A', 'Z' );
		$this->assertSame( $titles, array_slice( $alphabet, 2, 1 ) );




		$this->delete_posts( $post_ids );

	}
}
