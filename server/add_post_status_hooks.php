<?php
// 投稿のステータス更新を監視
namespace Pricey;

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'transition_post_status', '\Pricey\on_changed_post_status', 10, 3 );

// publish: 公開保存
// draft: 下書き保存
// trash: ゴミ箱へ
function on_changed_post_status( $new_status, $old_status, $post ) {
    // 既に同じステータスになっている場合は何もしない
    if ( $new_status === $old_status ) {
        return;
    }

    if ( $new_status == 'publish' || $new_status == 'draft' || $new_status == 'trash' ) {
        $response = changePostStatus($post->ID, $new_status);
    }
}

function changePostStatus($ID, $status) {
    $page = get_post($ID);
    $blocks = parse_blocks($page->post_content);

    $wpProductVariantGroupIds = [];
    foreach ($blocks as $block){
        if ($block["blockName"] === 'create-block/pricey') {
            if($block["attrs"]){
                $wpProductVariantGroupIds[] = $block["attrs"]["wpProductVariantGroupId"];
            }
        } else if($block["blockName"] === 'core/shortcode') {
            // ショートコードからidを抽出
            $shortcode_atts = shortcode_parse_atts($block["innerHTML"]);
            $wpProductVariantGroupIds[] =  preg_replace('/[^0-9]/', '', $shortcode_atts["id"]);
        }
    }

    if(count($wpProductVariantGroupIds) === 0) {
        return;
    }

    $request_url = PRICEY_AFFILIATE_PLUGIN_EXTERNAL_API_ENDPOINT . "/wp-plugins/posts";
    $headers = [
        'domain' => get_home_url(),
    ];
    $body = [
        'wpProductVariantGroupIds' => $wpProductVariantGroupIds,
        'status'    => $status,
        'localPostId' => $ID,
    ];
     $response = wp_remote_post( $request_url, [
        'method'      => 'POST',
        'headers'     => $headers,
        'body'     => $body
    ] );

    $response_code = wp_remote_retrieve_response_code( $response );
    $response_body = json_decode(wp_remote_retrieve_body( $response ), true);

    // 現在400はショートコードのIDが不正な場合のみ想定
    if ( $response_code >= 400 && $response_code < 500 ) {
        $status_text = "";

        if ($status === "publish") {
            $status_text = "公開";
        } elseif ($status === "draft") {
            $status_text = "保存";
        } elseif ($status === "trash") {
            // 削除の場合はサーバー側でgroupIdsの検証を行わないので起こり得ないが一応
            $status_text = "削除";
        }

        $custom_error_message = "[プライシー] ショートコードのIDを確認してください 無効なID: " . json_encode($response_body['description']);
        wp_die( $custom_error_message );
    }
}
