<?php
/**
 * Plugin name: WooCommerce Payment Method based Fees and Discounts
 * Plugin URI: https://flycart.org
 * Description: Provide flat or percentage discounts and fees for cart sub total based on selected payment method.
 * Author: Flycart Technologies LLP
 * Author URI: https://www.flycart.org
 * Version: 1.0.0
 * Text Domain: payment-methods-based-discount-and-fees-for-woocommerce
 * Domain Path: /i18n/languages/
 * Plugin URI: https://www.flycart.org
 * Requires at least: 4.6.1
 * WC requires at least: 2.5
 * WC tested up to: 3.5
 */

namespace WDBOPG;

if (!defined('ABSPATH')) exit;

/**
 * Define the text domain
 */
if(!defined('WDBOPG_TEXT_DOMAIN')){
    define('WDBOPG_TEXT_DOMAIN','payment-methods-based-discount-and-fees-for-woocommerce');
}

/**
 * Set base file URL
 */
if (!defined('WDBOPG_PLUGIN_BASE_FILE')){
    define('WDBOPG_PLUGIN_BASE_FILE', plugin_basename(__FILE__));
}

/**
 * Check and abort PHP,Wordpress,Woocommerce versions
 */
register_activation_hook( __FILE__, function () {
    global $wp_version;

    $php = '5.6';
    $wp  = '4.6.1';
    $wc  = '2.5';

    if ( version_compare( phpversion(), $php, '<' ) ) {
        deactivate_plugins( basename( __FILE__ ) );
        exit('<p>' .
            sprintf(
                __( 'This plugin can not be activated because it requires a PHP version greater than %1$s.', WDBOPG_TEXT_DOMAIN ),
                $php
            )
            . '</p>'
        );
    }

    if ( version_compare( $wp_version, $wp, '<' ) ) {
        deactivate_plugins( basename( __FILE__ ) );
        exit('<p>' .
            sprintf(
                __( 'This plugin can not be activated because it requires a WordPress version greater than %1$s.', WDBOPG_TEXT_DOMAIN ),
                $wp
            )
            . '</p>'
        );
    }

    if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
        deactivate_plugins( basename( __FILE__ ) );
        exit('<p>' .
            sprintf(
                __( 'This plugin can not be activated because it requires a woocommerce plugin.', WDBOPG_TEXT_DOMAIN )
            )
            . '</p>'
        );
    }

    $plugin_folder = get_plugins( '/' . 'woocommerce' );
    $plugin_file = 'woocommerce.php';
    if ( isset( $plugin_folder[$plugin_file]['Version'] ) ) {
        $wc_version = $plugin_folder[$plugin_file]['Version'];
    }

    if ( version_compare( $wc_version, $wc, '<' ) ) {
        deactivate_plugins( basename( __FILE__ ) );
        exit('<p>' .
            sprintf(
                __( 'This plugin can not be activated because it requires a woocommerce version greater than %1$s.', WDBOPG_TEXT_DOMAIN ),
                $wc
            )
            . '</p>'
        );
    }
} );

/**
 * De-activate our plugin when Woocommerce gets deactivated
 */
add_action('deactivated_plugin', 'wdbopg_detect_plugin_deactivation', 10, 2);
function wdbopg_detect_plugin_deactivation ($plugin, $network_activation)
{
   if ($plugin == 'woocommerce/woocommerce.php') {
       wp_die('<p>'.
            sprintf(
                __( 'Please deactivat "<b>WP Discount</b>" plugin first. Thank you.',WDBOPG_TEXT_DOMAIN )
            ).' <a href="'.admin_url('plugins.php').'" >Go Back</a>'.'</p>'
        );
    }
}

/**
 * Define the plugin path
 */
if(!defined('WDBOPG_PLUGIN_PATH')){
    define('WDBOPG_PLUGIN_PATH',plugin_dir_path(__FILE__));
}

/**
 * Define the plugin url
 */
if(!defined('WDBOPG_PLUGIN_URL')){
    define('WDBOPG_PLUGIN_URL',plugin_dir_url(__FILE__));
}

/**
 * Define the cmb2 field prefix
 */
if(!defined('WDBOPG_FIELD_PREFIX')){
    define('WDBOPG_FIELD_PREFIX','rabbit_discount_');
}

/**
 * Current version of our app
 */
if (!defined('WDBOPG_VERSION'))
    define('WDBOPG_VERSION', '1.0.0');

/**
 *Package autoload
 */
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    return false;
}else{
    require __DIR__ . '/vendor/autoload.php';
}

use src\Main;

$discount_main = new Main();
$discount_main->register_services();
