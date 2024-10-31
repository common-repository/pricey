<?php
namespace Pricey;

if ( ! defined( 'ABSPATH' ) ) exit;

  function add_plugin_page_settings_link( $links ) {
    $setting_page_url = home_url( '/wp-admin/admin.php?page=pricey-affiliate-settings' );
	  $setting_page_link = '<a href="' . $setting_page_url . '">設定</a>';
    array_unshift( $links, $setting_page_link );
	  return $links;
  }
?>
