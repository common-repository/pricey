<?php
// メニュー追加
namespace Pricey;
if (!defined('ABSPATH')) exit;

add_action('admin_menu', '\Pricey\set_plugin_sub_menu');


function set_plugin_sub_menu()
{
  add_menu_page(
    'プライシー管理', // ページタイトル
    'プライシー管理', // メニュータイトル
    'manage_options', // 権限
    'pricey-products', // path
    '\Pricey\show_products_index_page', // 関数
    'dashicons-admin-generic', // アイコン
    99 // 表示位置
  );

  add_submenu_page(
    'pricey-products',
    'アフィリエイト設定',           /* メニュータイトル */
    'アフィリエイト設定',
    'manage_options',
    'pricey-affiliate-settings',    /* ページを開いたときのURL */
    '\Pricey\show_affiliate_settings_page',       /* メニューに紐づく画面を描画するcallback関数 */
  );

  add_submenu_page(
    'pricey-products',
    '商品登録', /* メニュータイトル */
    '商品登録',
    'manage_options',
    'pricey-register-products',    /* ページを開いたときのURL */
    '\Pricey\show_register_product_page',    /* メニューに紐づく画面を描画するcallback関数 */
  );
}

function add_custom_menu_page()
{
?>
  <div class="wrap">
    <h2>設定画面</h2>
  </div>
<?php
}

function show_affiliate_settings_page()
{
  wp_enqueue_style(
    'pricey-register-product-page-stylesheet',
    PRICEY_URL . '/build/react.css',
    [],
    '1.0.0'
  );

  wp_enqueue_script(
    'pricey-register-product-page-script',
    plugins_url('../build/js/react.js', __FILE__),
    ['wp-element', 'wp-components'],
    time(),
    true
  );

  echo '<div id="pricey-affiliate-setting"></div>';
}

function show_register_product_page()
{
  wp_enqueue_style(
    'pricey-register-product-page-stylesheet',
    PRICEY_URL . '/build/react.css',
    [],
    '1.0.0'
  );

  wp_enqueue_script(
    'pricey-register-product-page-script',
    plugins_url('../build/js/react.js', __FILE__),
    ['wp-element', 'wp-components'],
    time(),
    true
  );

  echo '<div id="pricey-register-products"></div>';
}


function show_products_index_page()
{
  wp_enqueue_style(
    'pricey-register-product-page-stylesheet',
    PRICEY_URL . '/build/react.css',
    [],
    '1.0.0'
  );

  wp_enqueue_script(
    'pricey-register-product-page-script',
    plugins_url('../build/js/react.js', __FILE__),
    ['wp-element', 'wp-components'],
    time(),
    true
  );

  echo '<div id="pricey-product-index"></div>';
}
