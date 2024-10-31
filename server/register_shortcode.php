<?php
namespace Pricey;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * ショートコードの登録
 * ex.) [pricey id=123]
 */
add_shortcode( 'pricey', '\Pricey\add_pricey_short_code' );
function add_pricey_short_code( $attr ) {
	if ( ! isset( $attr['id'] ) ) return '';
	return render_block( [
    'blockName' => "create-block/pricey",
    "attrs" => [
      'wpProductVariantGroupId' => $attr['id']
    ]
  ], null );
}

?>
