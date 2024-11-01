<?php

namespace src;

if (!defined('ABSPATH')) exit;

class Main
{
    protected $wdbopg_wc_functions,$settings;

    public function __construct()
    {
        $this->wdbopg_wc_functions = new wdbopgWcFunctions();
        $this->settings = new settings();
    }

    /**
     * register_services
     */
    public function register_services()
    {
        $this->settings->admin_settings();
        $this->settings->admin_enqueue();
        add_filter('plugin_action_links_' . WDBOPG_PLUGIN_BASE_FILE, array( $this, 'wdbopg_action_links' ));
        add_action('woocommerce_review_order_before_cart_contents', array( $this, 'wdbopg_shipping_method_discount' ));
        add_filter('woocommerce_get_shop_coupon_data', array( $this, 'wdbopg_add_virtual_coupon_for_discount' ), 10, 2);
        add_action('woocommerce_cart_calculate_fees', array( $this, 'wdbopg_cart_calculate_fees' ) );
        add_filter('woocommerce_gateway_title', array( $this, 'wdbopg_payment_method_title_with_description' ), 10, 2 );
        add_filter('woocommerce_cart_totals_coupon_label', array( $this, 'wdbopg_woocommerce_cart_totals_coupon_label'), 10, 2 );
        add_action('woocommerce_review_order_before_payment', array($this, 'wdbopg_refresh_payment_methods'));
    }

    /**
     * Add settings link
     * @param $links
     * @return array
     */
    function wdbopg_action_links($links)
    {
        $action_links = array(
            'settings' => '<a href="' . admin_url('admin.php?page=flycart-coupon-configuration') . '">' . __('Settings', WDBOPG_TEXT_DOMAIN) . '</a>',
        );
        return array_merge($action_links, $links);
    }

    /**
     * creates virtual coupon
     * @param $response - coupon response from woocommerce
     * @param $coupon_code
     * @return array
     */
    function wdbopg_add_virtual_coupon_for_discount($response, $coupon_code)
    {
        global $woocommerce;

        $discount_groups = $this->get_discount_groups();
        $chosen_payment_method =  $this->wdbopg_wc_functions->get_payment_method($woocommerce);
        $i = 0;
        foreach ($discount_groups as $setting) {
            $i++;
            $amount = (!empty($setting['rabbit_discount_discount_value'])) ? $setting['rabbit_discount_discount_value'] : 0;
            $discount_type = ($setting['rabbit_discount_rabbit_radio'] == "flate_amount") ? 'fixed_cart' : 'percent';
            $coupon_description = (!empty($setting['rabbit_discount_rabbit_description'])) ? sanitize_text_field($setting['rabbit_discount_rabbit_description']) : __('Discount', WDBOPG_TEXT_DOMAIN);
            $current_coupon_code = $coupon_description.$i;
            $current_coupon_code = apply_filters('woocommerce_coupon_code', $current_coupon_code);
            if ($chosen_payment_method == $setting['rabbit_discount_rabbit_discount_gateway'] && $coupon_code == $current_coupon_code) {
                $coupon = array(
                    'code' => $coupon_code,
                    'amount' => $amount,
                    'date_created' => null,
                    'date_modified' => null,
                    'date_expires' => null,
                    'discount_type' => $discount_type,
                    'description' => $coupon_description,
                    'usage_count' => '',
                    'individual_use' => false,
                    'product_ids' => array(),
                    'excluded_product_ids' => array(),
                    'usage_limit' => '',
                    'usage_limit_per_user' => '',
                    'limit_usage_to_x_items' => '',
                    'expiry_date' => '',
                    'apply_before_tax' => 'yes',
                    'free_shipping' => false,
                    'product_categories' => array(),
                    'excluded_product_categories' => array(),
                    'exclude_sale_items' => false,
                    'minimum_amount' => (!empty($setting['rabbit_discount_rabbit_minimum_order'])) ? number_format($setting['rabbit_discount_rabbit_minimum_order'], 2, ".", "") : '',
                    'maximum_amount' => (!empty($setting['rabbit_discount_rabbit_maximum_order'])) ? number_format($setting['rabbit_discount_rabbit_maximum_order'], 2, ".", "") : '',
                    'customer_email' => array(),
                    'email_restrictions' => array(),
                    'used_by' => array(),
                );
                return $coupon;
            }
        }
        return $response;
    }

    /**
     * create discount for current selected payment gateway
     */
    public function wdbopg_shipping_method_discount()
    {
        if (is_admin() && !defined('DOING_AJAX')) return;

        global $woocommerce;
        $chosen_payment_method =  $this->wdbopg_wc_functions->get_payment_method($woocommerce);
        $cart_total =  $this->wdbopg_wc_functions->sub_total_exclude_tax($woocommerce);
        if (is_object($woocommerce->cart) && !empty($chosen_payment_method) && isset($cart_total) && $cart_total > 0) {

            $discount_groups = $this->get_discount_groups();
            $sub_total_exclude_tax = floatval($cart_total);
            if(!did_action('woocommerce_before_calculate_totals')){
                do_action('woocommerce_before_calculate_totals', $woocommerce->cart);
            }
            $i = 0;
            foreach ($discount_groups as $setting) {
                $i++;
                $amount = (!empty($setting['rabbit_discount_discount_value'])) ? $setting['rabbit_discount_discount_value'] : 0;
                $minimum_order_value = (!empty($setting['rabbit_discount_rabbit_minimum_order'])) ? floatval($setting['rabbit_discount_rabbit_minimum_order']) : '0';
                $maximum_order_value = (!empty($setting['rabbit_discount_rabbit_maximum_order'])) ? floatval($setting['rabbit_discount_rabbit_maximum_order']) : '9999999999';
                $coupon_description = (!empty($setting['rabbit_discount_rabbit_description'])) ? sanitize_text_field($setting['rabbit_discount_rabbit_description']) : __('Discount', WDBOPG_TEXT_DOMAIN);
                $coupon_code = $coupon_description.$i;
                $coupon_code = apply_filters('woocommerce_coupon_code', $coupon_code);
                $woocommerce->cart->remove_coupon($coupon_code);
                if ($chosen_payment_method == $setting['rabbit_discount_rabbit_discount_gateway'] && $coupon_code != '' && !$woocommerce->cart->has_discount($coupon_code) && $sub_total_exclude_tax <= $maximum_order_value && $sub_total_exclude_tax >= $minimum_order_value && !empty($amount) && $amount > 0) {
                    $woocommerce->cart->applied_coupons[] = $coupon_code;
                    do_action('woocommerce_applied_coupon', $coupon_code);
                }
            }
        }
    }

    /**
     * get metadata
     */
    public function get_discount_groups()
    {

        $settings = (get_option('flycart-coupon-configuration'))?get_option('flycart-coupon-configuration'):'';

        if (isset($settings['rabbit_discount_rabbit_coupon_groups']) && is_array($settings['rabbit_discount_rabbit_coupon_groups'])) {
            return $settings['rabbit_discount_rabbit_coupon_groups'];
        }
        return array();
    }

    /**
     * wdbopg_cart_calculate_fees
     */
    public function wdbopg_cart_calculate_fees() {
        global $woocommerce;

        if ( is_admin() && ! defined( 'DOING_AJAX' ) )
            return;

        $wdbopg_fee_groups = $this->wdbopg_get_fee_groups();

        foreach ($wdbopg_fee_groups as $fee_setting) {

            $hide_cart_page = (!empty($fee_setting['rabbit_discount_cart_hide_checkbox'])) ? $fee_setting['rabbit_discount_cart_hide_checkbox'] : '';
            if($hide_cart_page == 'on' && is_cart())
                return false;

            $cart_total =  $this->wdbopg_wc_functions->sub_total_exclude_tax($woocommerce);
            $wdbopg_miminum_amount = (!empty( $fee_setting['rabbit_discount_rabbit_minimum_fee_order'] )) ? $fee_setting['rabbit_discount_rabbit_minimum_fee_order'] : 0;
            $wdbopg_maximum_amount = (!empty( $fee_setting['rabbit_discount_rabbit_maximum_fee_order'] )) ? $fee_setting['rabbit_discount_rabbit_maximum_fee_order'] : 9999999999;
            $wdbopg_fee_type = (!empty( $fee_setting['rabbit_discount_rabbit_fee_radio'] )) ? $fee_setting['rabbit_discount_rabbit_fee_radio'] : '';
            $is_taxable_wdbopg_fees = (!empty( $fee_setting['rabbit_discount_taxes_checkbox'] )) ? $fee_setting['rabbit_discount_taxes_checkbox'] : '';
            $wdbopg_fee_amount = (!empty($fee_setting['rabbit_discount_discount_fee_value'])) ? $fee_setting['rabbit_discount_discount_fee_value'] : '';
            $wdbopg_fee_label = (!empty($fee_setting['rabbit_discount_rabbit_fee_description'])) ? $fee_setting['rabbit_discount_rabbit_fee_description'] : '';
            $chosen_payment_method =  $this->wdbopg_wc_functions->get_payment_method($woocommerce);
            if($chosen_payment_method == $fee_setting['rabbit_discount_rabbit_fee_gateway'] && !empty($wdbopg_fee_label) && $wdbopg_miminum_amount <= $cart_total && $wdbopg_maximum_amount >= $cart_total){
                switch ($wdbopg_fee_type) {
                    case "percentage_amount":
                        if($is_taxable_wdbopg_fees == 'on' && !empty($wdbopg_fee_amount)){
                            $percentage = ( $fee_setting['rabbit_discount_discount_fee_value'] <= 100 ) ? $fee_setting['rabbit_discount_discount_fee_value'] : 100;
                            $surcharge = ($percentage/100) * $cart_total;
                            $woocommerce->cart->add_fee( $wdbopg_fee_label, (float)$surcharge, true, '' );
                        }else{
                            $percentage = ( $fee_setting['rabbit_discount_discount_fee_value'] <= 100 ) ? $fee_setting['rabbit_discount_discount_fee_value'] : 100;
                            $surcharge = ($percentage/100) * $cart_total;
                            $woocommerce->cart->add_fee( $wdbopg_fee_label, (float)$surcharge, false, '' );
                        }
                        break;
                    case "flate_amount":
                    default:
                        if($is_taxable_wdbopg_fees == 'on' && !empty($wdbopg_fee_amount)){
                            $woocommerce->cart->add_fee( $wdbopg_fee_label, (float)$wdbopg_fee_amount, true, '' );
                        }else{
                            $woocommerce->cart->add_fee( $wdbopg_fee_label, (float)$wdbopg_fee_amount, false, '' );
                        }
                        break;
                }
            }
        }
    }

    /**
     * get metadata
     */
    public function wdbopg_get_fee_groups()
    {

        $settings = (get_option('flycart-fee-configuration'))?get_option('flycart-fee-configuration'):'';

        if (isset($settings['rabbit_discount_rabbit_fee_groups']) && is_array($settings['rabbit_discount_rabbit_fee_groups'])) {
            return $settings['rabbit_discount_rabbit_fee_groups'];
        }
        return array();
    }

    /**
     * wdbopg_payment_method_title_with_description
     * @param $title
     * @param $id
     * @return string
     */
    public function wdbopg_payment_method_title_with_description($title, $id)
    {

        if ( ! is_checkout() && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
            return $title;
        }
        /**
         * coupon payment_method_title_with_description
         */
        $discount_groups = $this->get_discount_groups();
        foreach ($discount_groups as $coupon_setting) {
            if ($id == $coupon_setting['rabbit_discount_rabbit_discount_gateway'] && isset( $coupon_setting['rabbit_discount_discount_value'] ) && 0 < $coupon_setting['rabbit_discount_discount_value'] ) {
                $wdbopg_custom_discount_label = (!empty($coupon_setting['rabbit_discount_rabbit_custom_message']))?$coupon_setting['rabbit_discount_rabbit_custom_message']:'';
                if($wdbopg_custom_discount_label != ''){
                    $title .= '(' .  __( $wdbopg_custom_discount_label, WDBOPG_TEXT_DOMAIN ) . ')';
                }
            }
        }

        /**
         * fee payment_method_title_with_description
         */
        $wdbopg_fee_groups = $this->wdbopg_get_fee_groups();
        foreach ($wdbopg_fee_groups as $fee_setting) {
            if ($id == $fee_setting['rabbit_discount_rabbit_fee_gateway'] && isset( $fee_setting['rabbit_discount_discount_fee_value'] ) && 0 < $fee_setting['rabbit_discount_discount_fee_value'] ) {
                $wdbopg_custom_discount_label = (!empty($fee_setting['rabbit_discount_rabbit_custom_fee_message']))?$fee_setting['rabbit_discount_rabbit_custom_fee_message']:'';
                if($wdbopg_custom_discount_label != ''){
                    $title .= '(' .  __( $wdbopg_custom_discount_label, WDBOPG_TEXT_DOMAIN ) . ')';
                }
            }
        }

        return $title;
    }

    /**
     * woocommerce_custom_coupon_label
     * @param $label
     * @param $coupon
     * @return string|void
     */
    public function wdbopg_woocommerce_cart_totals_coupon_label($label, $coupon)
    {
        $description = $coupon->get_description();
        return (!empty($description)) ? sprintf( esc_html__( 'Coupon: %s', WDBOPG_TEXT_DOMAIN ), $description ) : __( $label, WDBOPG_TEXT_DOMAIN );
    }

    /**
     * refresh payment methods
     */
    public function wdbopg_refresh_payment_methods()
    {
        // jQuery code
        if (!is_cart()) {
            ?>
            <script type="text/javascript">
                (function ($) {
                    $('form.checkout').on('change', 'input[name^="payment_method"]', function () {
                        $('body').trigger('update_checkout');
                    });
                })(jQuery);
            </script>
            <?php
        }
    }
}

?>
