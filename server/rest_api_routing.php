<?php
namespace Pricey;

if ( ! defined( 'ABSPATH' ) ) exit;
// ルーティング追加
 add_action('rest_api_init', function () {
  register_rest_route( 'pricey', '/posts', array(
    'methods' => 'GET',
    'callback' => function ($request) {
        $data = $request->get_query_params();
        $posts = get_posts_by_ids($data["local_post_ids"]);
        $response = new \WP_REST_Response($posts, 200);
        return $response;
    },
    'permission_callback' => function () {
		return true;
	},
  ));

  register_rest_route( 'pricey', '/posts/preview', array(
    'methods' => 'GET',
    'callback' => function ($request) {
      $params = $request->get_query_params();
      $block = add_pricey_short_code(['id' => $params["post_id"]]);
      return rest_ensure_response($block);
    },
    'permission_callback' => function () {
		return true;
	},
  ));
} );

?>
