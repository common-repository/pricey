<?php
/*
  Plugin Name: プライシー
  Plugin URI: https://pricey.jp/
  Description: Amazonや楽天市場から商品を検索してアフィリエイトリンクを管理できるプラグイン
  Version: 1.0.6
  Author: wilico
  Text Domain: Pricey
  Author URI: https://www.pricey.jp/about
  License: GPLv3
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action('init', 'PriceyAffiliatePlugin::init');
register_activation_hook(__FILE__, ['PriceyAffiliatePluginInstaller', 'activate_plugin']);
add_action('upgrader_process_complete', ['PriceyAffiliatePluginInstaller', 'plugin_upgrade_completed']);


if ( ! function_exists( 'get_plugin_data' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
}


/**
 * Define
 */
define('PRICEY_URL', plugins_url('/', __FILE__));
define('PRICEY_PATH', plugin_dir_path(__FILE__));
define('PRICEY_BASENAME', plugin_basename(__FILE__));

if(wp_get_environment_type() === "production"){
    define('PRICEY_AFFILIATE_PLUGIN_EXTERNAL_API_ENDPOINT', 'https://xoon0odxf3.execute-api.ap-northeast-1.amazonaws.com/v1');
} else {
    define('PRICEY_AFFILIATE_PLUGIN_EXTERNAL_API_ENDPOINT', 'https://pqjbfbc1tl.execute-api.ap-northeast-1.amazonaws.com/v1');
}

if(wp_get_environment_type() === "production"){
    define('PRICEY_AFFILIATE_PLUGIN_EXTERNAL_WEB_PAGE_BASE_URL', 'https://product.pricey.jp');
} else {
    define('PRICEY_AFFILIATE_PLUGIN_EXTERNAL_WEB_PAGE_BASE_URL', 'https://stage.product.pricey.jp');
}

// エラーログの表示設定
if(wp_get_environment_type() === "production"){
    // 本番では非表示に
    error_reporting(0);
    @ini_set('display_errors', 0);
} else {
    error_reporting(E_ALL);
    @ini_set('display_errors', 1);
}


// ルーティング設定
require_once PRICEY_PATH . 'server/db.php';
// ルーティング設定
require_once PRICEY_PATH . 'server/rest_api_routing.php';
// ショートコードの設定
require_once PRICEY_PATH . 'server/register_shortcode.php';
// 投稿ステータスの変更フック
require_once PRICEY_PATH . 'server/add_post_status_hooks.php';

require_once PRICEY_PATH . 'server/add_setting_link_on_plugin_index_page.php';
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), '\Pricey\add_plugin_page_settings_link' );

class PriceyAffiliatePluginInstaller
{
    /* プラグイン有効化時に発火する関数 */
    static function activate_plugin()
    {
        // ユーザー作成APIを叩く
        PriceyAffiliatePluginInstaller::_create_wp_user_request();
    }

    // ユーザー作成リクエスト
    static function _create_wp_user_request()
    {
        $request_url  = PRICEY_AFFILIATE_PLUGIN_EXTERNAL_API_ENDPOINT . '/wp-plugins/users';

        // プラグインのバージョンの取得
        $plugin_data = get_plugin_data(__FILE__);

        $request_body = [
            'domainName' => get_home_url(),
            'pluginVersion' => $plugin_data['Version'],
        ];
        wp_remote_get($request_url, [
            'method'      => 'POST',
            'body'        => $request_body,
        ]);
    }

    // ユーザー更新リクエスト
    static function _update_wp_user_request()
    {
        $request_url  = PRICEY_AFFILIATE_PLUGIN_EXTERNAL_API_ENDPOINT . '/wp-plugins/users';

        // プラグインのバージョンの取得
        $plugin_data = get_plugin_data(__FILE__);

        $request_body = [
            'domainName' => get_home_url(),
            'version' => $plugin_data["Version"]
        ];
        wp_remote_get($request_url, [
            'method'      => 'PATCH',
            'body'        => $request_body,
        ]);
    }

    static function plugin_upgrade_completed(){
        // ユーザー更新APIを叩く
        PriceyAffiliatePluginInstaller::_update_wp_user_request();
    }
}

class PriceyAffiliatePlugin
{
    const VERSION           = '1.0.0';
    const PLUGIN_ID         = 'pricey';
    const CONFIG_MENU_SLUG  = self::PLUGIN_ID . '-config';
    const CREDENTIAL_ACTION = self::PLUGIN_ID . '-nonce-action';
    const CREDENTIAL_NAME   = self::PLUGIN_ID . '-nonce-key';
    const PLUGIN_DB_PREFIX  = self::PLUGIN_ID . '_';

    static function init()
    {
        return new self();
    }

    function __construct()
    {
        // ブロック追加
        register_block_type(
            __DIR__ . '/build',
            array(
                'render_callback' => [$this, 'render_block'],
                'attributes' => [
                    'wpProductVariantGroupId' => [
                        'type' => 'integer',
                    ],
                ]
            )
        );


        if (is_admin() && is_user_logged_in()) {
            // メニュー追加
            require_once PRICEY_PATH . 'server/add_menu.php';
        }


    }

    // ブロックをレンダリング
    function fetch_prices($groupId)
    {
        $apiURL = PRICEY_AFFILIATE_PLUGIN_EXTERNAL_API_ENDPOINT . "/wp-plugins/products/fetch-prices";
        $headers = array(
            'Content-Type' => 'application/json',
            'domain' => get_home_url()
        );

        $body = [
            'wpProductVariantGroupId' => $groupId,
        ];

        $response = wp_remote_post($apiURL, array(
            'method'  => 'POST',
            'headers' => $headers,
            'body'    => json_encode($body),
        ));

        return $response;
    }

    function products_updated_at($products)
    {
        $timeArr = array();
        for ($i = 0; $i < count($products); $i++) {
            array_push($timeArr, $products[$i]->productVariantHistory->createdAt);
        }
        $minTime = min($timeArr);
        $dateTime = new DateTime($minTime, new DateTimeZone('UTC'));
        $dateTime->setTimezone(new DateTimeZone('Asia/Tokyo'));
        return $dateTime->format('Y/m/d H:i');
    }

    function postage_text($postage)
    {
        if ($postage === null) {
            return "+送料";
        } elseif ($postage == 0) {
            return "送料無料";
        } else {
            return "送料{$postage}円";
        }
    }

    // ブロックをレンダリング
    function render_block($attr, $contents)
    {
        if (!isset($attr["wpProductVariantGroupId"])) return '';

        $groupId = json_decode($attr["wpProductVariantGroupId"]);
        // TODO: エラーハンドリング
        // groupIdを元に最新価格の取得リクエスト
        $response = $this->fetch_prices($groupId);
        $response_body = json_decode($response["body"]);
        // エラーがある場合
        if (isset($response_body->code)) {
            return "";
        }
        $products = $response_body;
        ob_start();
?>

        <div class="flex pricey-affiliate-block">
            <div class="product-image-box">
                <?php
                echo "<img class='product-image' src=\"" . esc_attr($products[0]->image) . "\">";
                ?>
            </div>
            <div class="product-detail-container">
                <h3 class="product-title-label">
                    <?php
                    $content = $products[0]->title ?? '';
                    $content = esc_html($content);
                    echo esc_html(mb_strimwidth($content, 0, 60, '…', 'UTF-8'));
                    ?>
                </h3>
                <ul class="affliate-link-button-list">
                    <li>
                        <a class="flex price-history-container" href='<?php echo esc_url($this->make_pricey_web_url($products[0])); ?>' target="_blank">
                            <p>価格推移を見る</p>
                            <div class="img-box">
                                <img src="<?php echo esc_url(PRICEY_URL); ?>assets/fi_chevron-down.png" alt="chevron-down">
                            </div>
                        </a>
                    </li>
                    <?php for ($i = 0; $i < count($products); $i++) { ?>
                        <li>
                            <a class="flex site-button <?php echo esc_attr($products[$i]->site->name ?? ''); ?>" href=<?php echo esc_url($products[$i]->affiliateUrl ?? ''); ?> target="_blank">
                            <?php if ($products[$i]->site->name == 'amazon') : ?>
                                <p>Amazonで見る</p>
                            <?php elseif ($products[$i]->site->name == 'rakuten') : ?>
                                <p>楽天市場で見る</p>
                            <?php elseif ($products[$i]->site->name == 'yahoo') : ?>
                                <p>ヤフーで見る</p>
                            <?php endif; ?>
                            <div class="price-container">
                                <?php if ($products[$i]->productVariantHistory->price > 0) : ?>
                                <div class="price-label">
                                合計
                                <span class="number"> <?php echo esc_html(number_format($products[$i]->productVariantHistory->price)); ?></span>
                                円
                                </div>
                                <p class="postage">
                                    <?php echo esc_html($this->postage_text($products[$i]->productVariantHistory->postage)); ?>
                                </p>
                                <?php else : ?>
                                    <p>在庫なし</p>
                                <?php endif; ?>
                            </div>
                            </a>
                        </li>
                    <?php } ?>
                    <li class="flex additional-data">
                        <div>
                            <p><?php echo esc_html($this->products_updated_at($products)) ?> 更新</p>
                        </div>
                        <div class="flex site-info">
                            <p>Powered by</p>
                            <div class="img-box">
                                <a href="https://pricey.onelink.me/SGOd/cctmnsn4" target="_blank">
                                    <img src="<?php echo esc_url(PRICEY_URL); ?>assets/pricey-logo.png" alt="Priceyロゴ">
                                </a>
                            </div>
                        </div>
                    </li>
                </ul>

            </div>
        </div>

<?php
        $output = ob_get_contents();
        ob_end_clean();
        return $output;
    }

    function make_pricey_web_url($product)
    {
        return PRICEY_AFFILIATE_PLUGIN_EXTERNAL_WEB_PAGE_BASE_URL . '/wpp/products/' . $product->wpProductVariantUid;
    }
}
?>
