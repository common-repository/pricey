<?php
namespace Pricey;

if ( ! defined( 'ABSPATH' ) ) exit;

function get_posts_by_ids($post_ids) {
    $args = array(
    'post_type' => 'post',
    'post__in' => array_map('intval', $post_ids),
    'post_status' => array('publish', 'draft'),
    'order'=> 'desc',
    'orderby'=> 'modified'
    );
    $query = new \WP_Query($args);
    if ($query->have_posts()) { // 取得した記事データが存在するかしないかの判定
        while ($query->have_posts()) { // 記事データが存在する場合、その記事データに対してループ処理を実行
            $query->the_post(); // 次の記事データへと進める
            $posts[] = [
                'id' => get_the_ID(),
                'url' => get_permalink(), // パーマリンクを取得
                'title' => get_the_title(),
                'imageUrl' => get_the_post_thumbnail_url() ? get_the_post_thumbnail_url() : '', // アイキャッチ画像URLを取得
                'lastModifiedAt' => get_the_modified_date('Y/m/d H:i:s') // 更新日時を取得
            ];
        }
    }

    return $posts;
}

?>
