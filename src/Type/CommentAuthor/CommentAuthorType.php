<?php

namespace WPGraphQL\Type\CommentAuthor;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQLRelay\Relay;
use WPGraphQL\AppContext;
use WPGraphQL\Type\WPObjectType;
use WPGraphQL\Types;

class CommentAuthorType extends WPObjectType {

	/**
	 * Holds the type name
	 *
	 * @var string $type_name
	 */
	private static $type_name;

	/**
	 * Holds the $fields definition for the CommentAuthorType
	 *
	 * @var $fields
	 */
	private static $fields;

	/**
	 * CommentAuthorType constructor.
	 *
	 * @since 0.0.5
	 */
	public function __construct() {

		/**
		 * Set the type_name
		 *
		 * @since 0.0.5
		 */
		self::$type_name = 'CommentAuthor';

		$config = [
			'name'        => self::$type_name,
			'description' => __( 'A Comment Author object', 'wp-graphql' ),
			'fields' => function() {
				return self::fields();
			},
			'interfaces'  => [ self::node_interface() ],
		];

		parent::__construct( $config );

	}

	/**
	 * This defines the fields that make up the CommentAuthorType
	 *
	 * @return mixed
	 * @since 0.0.5
	 */
	protected static function fields() {

		if ( null === self::$fields ) :
			self::$fields = [
				'id'    => [
					'type'        => Types::non_null( Types::id() ),
					'description' => __( 'The globally unique identifier for the Comment Author user', 'wp-graphql' ),
					'resolve'     => function( array $comment_author, $args, AppContext $context, ResolveInfo $info ) {
						return ! empty( $comment_author['comment_author_email'] ) ? Relay::toGlobalId( 'commentAuthor', $comment_author['comment_author_email'] ) : null;
					},
				],
				'name'  => [
					'type'        => Types::string(),
					'description' => __( 'The name for the comment author.', 'wp-graphql' ),
					'resolve'     => function( array $comment_author, $args, AppContext $context, ResolveInfo $info ) {
						return ! empty( $comment_author['comment_author'] ) ? $comment_author['comment_author'] : '';
					},
				],
				'email' => [
					'type'        => Types::string(),
					'description' => __( 'The email for the comment author', 'wp-graphql' ),
					'resolve'     => function( array $comment_author, $args, AppContext $context, ResolveInfo $info ) {
						return ! empty( $comment_author['comment_author_email'] ) ? $comment_author['comment_author_email'] : '';
					},
				],
				'url'   => [
					'type'        => Types::string(),
					'description' => __( 'The url the comment author.', 'wp-graphql' ),
					'resolve'     => function( array $comment_author, $args, AppContext $context, ResolveInfo $info ) {
						return ! empty( $comment_author['comment_author_url'] ) ? $comment_author['comment_author_url'] : '';
					},
				],
			];
		endif;

		return self::$fields;
	}

}
