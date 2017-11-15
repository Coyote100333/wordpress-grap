<?php
/**
 * WPGraphQL Test settings Queries
 *
 * Test the WPGraphQL settings queries. These tests address all
 * of the default settings returned by the "get_registered_settings" method
 * in a vanilla WP core install
 *
 * @package WPGraphQL
 *
 */
class WP_GraphQL_Test_Settings_Queries extends WP_UnitTestCase {

	/**
	 * This function is run before each method
	 *
	 * @access public
	 * @return void
	 */
	public function setUp() {

		parent::setUp();

		$this->admin = $this->factory->user->create( [
			'role' => 'administrator',
		] );

		$this->editor = $this->factory->user->create( [
			'role' => 'editor',
		] );
	}

	/**
	 * This function is run after each method
	 *
	 * @access public
	 * @return void
	 */
	public function tearDown() {
		parent::tearDown();
	}

	/**
	 * Method for testing whether a user can query settings
	 * if they don't have the 'manage_options' capability
	 *
	 * They should not be able to query for the admin email
	 * so we should receive an error back
	 *
	 * @access public
	 * @return void
	 */
	public function testSettingsQueryAsEditor() {
		/**
		 * Set the editor user
		 * Set the query
		 * Make the request
		 * Validate the request has errors
		 */
		wp_set_current_user( $this->editor );
		$query = "
			query {
				allSettings {
				    generalSettingsEmail
				}
		    }
	    ";
		$actual = do_graphql_request( $query );

		$this->assertArrayHasKey( 'errors', $actual );

	}

	/**
	 * Method for testing the generalSettings
	 *
	 * @access public
	 * @return void
	 */
	public function testGeneralSettingQuery() {

		/**
		 * Set the admin user
		 * Set the query
		 * Make the request
		 * Validate the request
		 */
		wp_set_current_user( $this->admin );

		$mock_options = [
			'default_comment_status' => 'closed',
			'default_ping_status' => 'closed',
			'date_format' => 'test date format',
			'blogdescription' => 'test description',
			'admin_email' => 'test@test.com',
			'start_of_week' => 0,
			'time_format' => 'test_time_format',
			'timezone_string' => 'UTC',
			'blogname' => 'test_title',
			'siteurl' => 'http://test.com',
			'posts_per_page' => 20,
			'default_category' => 2,
			'default_post_format' => 'quote',
			'use_smilies' => 0,
			'points' => 5.5,
		];

		foreach ( $mock_options as $mock_option_key => $mock_value ) {
			update_option( $mock_option_key, $mock_value );
		}

		if ( is_multisite() ) {
			update_network_option( 1, 'admin_email', 'test email' );
		}

		if ( true === is_multisite() ) {
			$query = "
				query {
					allSettings {
					    discussionSettingsDefaultCommentStatus
					    discussionSettingsDefaultPingStatus
					    generalSettingsDateFormat
					    generalSettingsDescription
					    generalSettingsLanguage
					    generalSettingsStartOfWeek
					    generalSettingsTimeFormat
					    generalSettingsTimezone
					    generalSettingsTitle
					    generalSettingsUrl
					    readingSettingsPostsPerPage
					    writingSettingsDefaultCategory
					    writingSettingsDefaultPostFormat
					    writingSettingsUseSmilies
					    zoolSettingsPoints
					}
				}
			";
		} else {
			$query = "
				query {
					allSettings {
					    discussionSettingsDefaultCommentStatus
					    discussionSettingsDefaultPingStatus
					    generalSettingsDateFormat
					    generalSettingsDescription
					    generalSettingsEmail
					    generalSettingsLanguage
					    generalSettingsStartOfWeek
					    generalSettingsTimeFormat
					    generalSettingsTimezone
					    generalSettingsTitle
					    generalSettingsUrl
					    readingSettingsPostsPerPage
					    writingSettingsDefaultCategory
					    writingSettingsDefaultPostFormat
					    writingSettingsUseSmilies
					    zoolSettingsPoints
					}
				}
			";
		}

		$actual = do_graphql_request( $query );

		$allSettings = $actual['data']['allSettings'];

		$this->assertNotEmpty( $allSettings );
		$this->assertEquals( $mock_options['default_comment_status'], $allSettings['discussionSettingsDefaultCommentStatus'] );
		$this->assertEquals( $mock_options['default_ping_status'], $allSettings['discussionSettingsDefaultPingStatus'] );
		$this->assertEquals( $mock_options['date_format'], $allSettings['generalSettingsDateFormat'] );
		$this->assertEquals( $mock_options['blogdescription'], $allSettings['generalSettingsDescription'] );
		if ( ! is_multisite() ) {
			$this->assertEquals( $mock_options['admin_email'], $allSettings['generalSettingsEmail'] );
		}
		$this->assertEquals( 'en_US', $allSettings['generalSettingsLanguage'] );
		$this->assertEquals( $mock_options['start_of_week'], $allSettings['generalSettingsStartOfWeek'] );
		$this->assertEquals( $mock_options['time_format'], $allSettings['generalSettingsTimeFormat'] );
		$this->assertEquals( $mock_options['timezone_string'], $allSettings['generalSettingsTimezone'] );
		$this->assertEquals( $mock_options['blogname'], $allSettings['generalSettingsTitle'] );
		if ( ! is_multisite() ) {
			$this->assertEquals( $mock_options['siteurl'], $allSettings['generalSettingsUrl'] );
		}
		$this->assertEquals( $mock_options['posts_per_page'], $allSettings['readingSettingsPostsPerPage'] );
		$this->assertEquals( $mock_options['default_category'], $allSettings['writingSettingsDefaultCategory'] );
		$this->assertEquals( $mock_options['default_post_format'], $allSettings['writingSettingsDefaultPostFormat'] );
		$this->assertEquals( $mock_options['use_smilies'], $allSettings['writingSettingsUseSmilies'] );
		$this->assertEquals( $mock_options['points'], $allSettings['zoolSettingsPoints'] );
	}

}
