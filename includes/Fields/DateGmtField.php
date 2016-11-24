<?php
namespace DFM\WPGraphQL\Fields;

use Youshido\GraphQL\Execution\ResolveInfo;
use Youshido\GraphQL\Field\AbstractField;
use Youshido\GraphQL\Type\Scalar\StringType;

/**
 * Class DateGmtField
 * @package DFM\WPGraphQL\Fields
 * @since 0.0.1
 */
class DateGmtField extends AbstractField {

	/**
	 * @return string
	 * @since 0.0.1
	 */
	public function getName() {
		return 'date_gmt';
	}

	/**
	 * @return StringType
	 * @since 0.0.1
	 */
	public function getType() {
		return new StringType();
	}

	/**
	 * @return mixed
	 * @since 0.0.1
	 */
	public function getDescription() {
		return __( 'The date the object was published, in GMT.', 'wp-graphql' );
	}

	/**
	 * @param $value
	 * @param array $args
	 * @param ResolveInfo $info
	 *
	 * @return mixed
	 * @since 0.0.1
	 */
	public function resolve( $value, array $args, ResolveInfo $info ) {
		return esc_html( $value->post_date_gmt );
	}

}