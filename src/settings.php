<?php
namespace src;

if (!defined('ABSPATH')) exit;

class settings
{
    /**
     * Define the metabox and field configurations.
     */
    public function admin_settings()
    {
        add_action( 'cmb2_admin_init', array($this, 'cmb2_flycart_discount_and_fee_metaboxes'));
        add_action( is_admin() ? 'admin_footer' : 'wp_footer', array($this, 'cmb2_repeatable_fields_titles_show_in_header'));
    }

    /**
     * Define the metabox and field configurations.
     */
    public function cmb2_flycart_discount_and_fee_metaboxes()
    {

        $gateway_options = array();
        if (is_array(WC()->payment_gateways->payment_gateways())) {
            $gateways = WC()->payment_gateways->payment_gateways();
            foreach ((array)$gateways as $gateway) {
                if (is_object($gateway) && $gateway->enabled == 'yes') {
                    $gateway_options[$gateway->id] = ($gateway->title)?$gateway->title:'';
                }
            }
        }
        $cmb_coupon = new_cmb2_box(array(
            'id' => WDBOPG_FIELD_PREFIX.'rabbit_coupon_metabox',
            'title' => __('Payment Methods based Discount & Fees', WDBOPG_TEXT_DOMAIN),
            'object_types' => array('options-page'),
            'option_key' => 'flycart-coupon-configuration',
            'tab_group' => 'flycart-payment-discount-and-fee-configuration',
            'parent_slug' => 'woocommerce',
            'capability' => 'edit_posts',
            'tab_title' => __('Discount Settings', WDBOPG_TEXT_DOMAIN),
            'save_button' => __('Save', WDBOPG_TEXT_DOMAIN),
        ));

        $rabbit_discount_group_id = $cmb_coupon->add_field(array(
            'id' => WDBOPG_FIELD_PREFIX.'rabbit_coupon_groups',
            'type' => 'group',
            'repeatable' => true,
            'options' => array(
                'group_title' => __('Payment Gateway Group {#}', WDBOPG_TEXT_DOMAIN),
                'add_button' => __('Add Row', WDBOPG_TEXT_DOMAIN),
                'remove_button' => __('Remove Row', WDBOPG_TEXT_DOMAIN),
                'closed' => true,
                'sortable' => true,
            ),
        ));

        $cmb_coupon->add_group_field($rabbit_discount_group_id, array(
            'name' => __('Choose the Payment Gateway', WDBOPG_TEXT_DOMAIN),
            'id' => WDBOPG_FIELD_PREFIX.'rabbit_discount_gateway',
            'type' => 'select',
            'show_option_none' => false,
            'options' => $gateway_options,
        ));
        $cmb_coupon->add_group_field($rabbit_discount_group_id, array(
            'name' => __('Discount Type', WDBOPG_TEXT_DOMAIN),
            'id' => WDBOPG_FIELD_PREFIX . 'rabbit_radio',
            'type' => 'radio_inline',
            'default' => 'percentage_amount',
            'options' => array(
                'percentage_amount' => __('Percentage', WDBOPG_TEXT_DOMAIN),
                'flate_amount' => __('Flat Amount', WDBOPG_TEXT_DOMAIN),
            ),
        ));
        $cmb_coupon->add_group_field($rabbit_discount_group_id, array(
            'name' => __('Discount Amount', WDBOPG_TEXT_DOMAIN),
            'desc' => __('Enter discount amount Eg: 10.25', WDBOPG_TEXT_DOMAIN),
            'id' => WDBOPG_FIELD_PREFIX . 'discount_value',
            'type' => 'text_small',
            'attributes' => array(
                'type' => 'number',
                'min' => '0',
                'max' => '1000000000',
                'step' => '0.01',
            ),
        ));
        $cmb_coupon->add_group_field($rabbit_discount_group_id, array(
            'name' => __("Discount Label", WDBOPG_TEXT_DOMAIN),
            'desc' => __('Label to show in the totals column in the checkout', WDBOPG_TEXT_DOMAIN),
            'id' => WDBOPG_FIELD_PREFIX . 'rabbit_description',
            'type' => 'text_medium',
        ));
        $cmb_coupon->add_group_field($rabbit_discount_group_id, array(
            'name' => __('Minimum Spent (Optional) ', WDBOPG_TEXT_DOMAIN),
            'desc' => __('Apply the discount if order subtotal is above the value set in this field Eg: 200', WDBOPG_TEXT_DOMAIN),
            'id' => WDBOPG_FIELD_PREFIX . 'rabbit_minimum_order',
            'type' => 'text_small',
            'attributes' => array(
                'type' => 'number',
                'min' => '0',
                'max' => '1000000000',
                'step' => '0.01',
            ),
        ));
        $cmb_coupon->add_group_field($rabbit_discount_group_id, array(
            'name' => __('Maximum Spent (optional)', WDBOPG_TEXT_DOMAIN),
            'desc' => __('Apply the discount if the order subtotal is less than the value set in this field. Example: 500', WDBOPG_TEXT_DOMAIN),
            'id' => WDBOPG_FIELD_PREFIX . 'rabbit_maximum_order',
            'type' => 'text_small',
            'attributes' => array(
                'type' => 'number',
                'min' => '0',
                'max' => '1000000000',
                'step' => '0.01',
            ),
        ));
        $cmb_coupon->add_group_field($rabbit_discount_group_id, array(
            'name' => __("Message to show next to the payment gateway name (during the checkout)", WDBOPG_TEXT_DOMAIN),
            'desc' => __('The message will show next to the selected payment gateway. Example: Direct Bank Transfer (your message)', WDBOPG_TEXT_DOMAIN),
            'id' => WDBOPG_FIELD_PREFIX . 'rabbit_custom_message',
            'type' => 'textarea',
        ));

        $cmb_fee = new_cmb2_box(array(
            'id' => WDBOPG_FIELD_PREFIX . 'rabbit_fee_metabox',
            'title' => __('Payment Methods based Discount & Fees', WDBOPG_TEXT_DOMAIN),
            'object_types' => array('options-page'),
            'option_key' => 'flycart-fee-configuration',
            'tab_group' => 'flycart-payment-discount-and-fee-configuration',
            'parent_slug' => 'flycart-coupon-configuration',
            'capability' => 'edit_posts',
            'tab_title' => __('Fee Settings', WDBOPG_TEXT_DOMAIN),
            'save_button' => __('Save', WDBOPG_TEXT_DOMAIN)
        ));

        $rabbit_fee_group_id = $cmb_fee->add_field(array(
            'id' => WDBOPG_FIELD_PREFIX.'rabbit_fee_groups',
            'type' => 'group',
            'repeatable' => true,
            'options' => array(
                'group_title' => __('Payment Gateway Group {#}', WDBOPG_TEXT_DOMAIN),
                'add_button' => __('Add Row', WDBOPG_TEXT_DOMAIN),
                'remove_button' => __('Remove Row', WDBOPG_TEXT_DOMAIN),
                'closed' => true,
                'sortable' => true,
            ),
        ));

        $cmb_fee->add_group_field($rabbit_fee_group_id, array(
            'name' => __('Choose the Payment Gateway', WDBOPG_TEXT_DOMAIN),
            'id' => WDBOPG_FIELD_PREFIX.'rabbit_fee_gateway',
            'type' => 'select',
            'show_option_none' => false,
            'options' => $gateway_options,
        ));
        $cmb_fee->add_group_field($rabbit_fee_group_id, array(
            'name' => __('Fee Type', WDBOPG_TEXT_DOMAIN),
            'id' => WDBOPG_FIELD_PREFIX . 'rabbit_fee_radio',
            'type' => 'radio_inline',
            'default' => 'percentage_amount',
            'options' => array(
                'percentage_amount' => __('Percentage', WDBOPG_TEXT_DOMAIN),
                'flate_amount' => __('Flat Amount', WDBOPG_TEXT_DOMAIN),
            ),
        ));
        $cmb_fee->add_group_field($rabbit_fee_group_id, array(
            'name' => __('Fee Amount', WDBOPG_TEXT_DOMAIN),
            'desc' => __('Enter fee amount Eg: 10.25', WDBOPG_TEXT_DOMAIN),
            'id' => WDBOPG_FIELD_PREFIX . 'discount_fee_value',
            'type' => 'text_small',
            'attributes' => array(
                'type' => 'number',
                'min' => '0',
                'max' => '1000000000',
                'step' => '0.01',
            ),
        ));
        $cmb_fee->add_group_field($rabbit_fee_group_id, array(
            'name' => __("Fee Label", WDBOPG_TEXT_DOMAIN),
            'desc' => __('Label to show in the totals column in the checkout', WDBOPG_TEXT_DOMAIN),
            'id' => WDBOPG_FIELD_PREFIX . 'rabbit_fee_description',
            'type' => 'text_medium',
        ));
        $cmb_fee->add_group_field($rabbit_fee_group_id, array(
            'name' => 'Include Taxes',
            'desc' => 'Is taxable?',
            'id'   => WDBOPG_FIELD_PREFIX. 'taxes_checkbox',
            'type' => 'checkbox',
        ) );
        $cmb_fee->add_group_field($rabbit_fee_group_id, array(
            'name' => 'Hide fees on cart page',
            'desc' => 'Hide?',
            'id'   => WDBOPG_FIELD_PREFIX. 'cart_hide_checkbox',
            'type' => 'checkbox',
        ) );
        $cmb_fee->add_group_field($rabbit_fee_group_id, array(
            'name' => __('Minimum Spent (Optional) ', WDBOPG_TEXT_DOMAIN),
            'desc' => __('Apply the fee if order subtotal is above the value set in this field Eg: 200', WDBOPG_TEXT_DOMAIN),
            'id' => WDBOPG_FIELD_PREFIX . 'rabbit_minimum_fee_order',
            'type' => 'text_small',
            'attributes' => array(
                'type' => 'number',
                'min' => '0',
                'max' => '1000000000',
                'step' => '0.01',
            ),
        ));
        $cmb_fee->add_group_field($rabbit_fee_group_id, array(
            'name' => __('Maximum Spent (optional)', WDBOPG_TEXT_DOMAIN),
            'desc' => __('Apply the fee if the order subtotal is less than the value set in this field. Example: 500', WDBOPG_TEXT_DOMAIN),
            'id' => WDBOPG_FIELD_PREFIX . 'rabbit_maximum_fee_order',
            'type' => 'text_small',
            'attributes' => array(
                'type' => 'number',
                'min' => '0',
                'max' => '1000000000',
                'step' => '0.01',
            ),
        ));
        $cmb_fee->add_group_field($rabbit_fee_group_id, array(
            'name' => __("Message to show next to the payment gateway name (during the checkout)", WDBOPG_TEXT_DOMAIN),
            'desc' => __('The message will show next to the selected payment gateway. Example: Direct Bank Transfer (your message)', WDBOPG_TEXT_DOMAIN),
            'id' => WDBOPG_FIELD_PREFIX . 'rabbit_custom_fee_message',
            'type' => 'textarea',
        ));
    }

    /**
     * enqueue stylesheet and javascripts
     */
    public function admin_enqueue()
    {
        add_action('admin_enqueue_scripts', array($this, 'wp_discount_enqueue_script'));
    }

    /**
     * stylesheet and javascript enqueue callback method
     */
    function wp_discount_enqueue_script()
    {
        wp_register_style('stylesheet', WDBOPG_PLUGIN_URL.'src/assets/css/mystyle.css', false);
        wp_register_script('my-js', WDBOPG_PLUGIN_URL.'src/assets/js/myscript.js', array('jquery'), null, true);
        wp_register_script('cmb2-js', WDBOPG_PLUGIN_URL.'src/assets/js/cmb2-conditional-logic.js', array('jquery'), null, true);

        wp_enqueue_script( array('my-js','cmb2-js') );
        wp_enqueue_style ('stylesheet');
    }

    /**
     * show titles in cmb2 repeater filed head
     */
    function cmb2_repeatable_fields_titles_show_in_header()
    {
        ?>
        <script type="text/javascript">
            /**
             * Coupon Auto title generator
             */
            jQuery(function ($) {
                var $box = $(document.getElementById('rabbit_discount_rabbit_coupon_metabox'));
                var replaceTitles = function () {
                    $box.find('.cmb-group-title').each(function () {
                        var $this = $(this);
                        var txt = $this.next().find('[id$="rabbit_discount_rabbit_description"]').val()+' - '+$this.next().find('[id$="rabbit_discount_rabbit_discount_gateway"]    :selected').text();
                        var rowindex;
                        if (!txt) {
                            txt = $box.find('[data-grouptitle]').data('grouptitle');
                            if (txt) {
                                rowindex = $this.parents('[data-iterator]').data('iterator');
                                txt = txt.replace('{#}', ( rowindex + 1 ));
                            }
                        }
                        if (txt) {
                            $this.text(txt);
                        }
                    });
                };
                var replaceOnKeyUp = function (evt) {
                    var $this = $(evt.target);
                    var id = 'title';
                    if (evt.target.id.indexOf(id, evt.target.id.length - id.length) !== -1) {
                        $this.parents('.cmb-row.cmb-repeatable-grouping').find('.cmb-group-title').text($this.val());
                    }
                };
                $box
                    .on('cmb2_add_row cmb2_shift_rows_complete', replaceTitles)
                    .on('keyup', replaceOnKeyUp);
                replaceTitles();
            });
            /**
             *Fee Auto title generator
             */
            jQuery(function ($) {
                var $box = $(document.getElementById('rabbit_discount_rabbit_fee_metabox'));
                var replaceTitles = function () {
                    $box.find('.cmb-group-title').each(function () {
                        var $this = $(this);
                        var txt = $this.next().find('[id$="rabbit_discount_rabbit_fee_description"]').val()+' - '+$this.next().find('[id$="rabbit_discount_rabbit_fee_gateway"]    :selected').text();
                        var rowindex;
                        if (!txt) {
                            txt = $box.find('[data-grouptitle]').data('grouptitle');
                            if (txt) {
                                rowindex = $this.parents('[data-iterator]').data('iterator');
                                txt = txt.replace('{#}', ( rowindex + 1 ));
                            }
                        }
                        if (txt) {
                            $this.text(txt);
                        }
                    });
                };
                var replaceOnKeyUp = function (evt) {
                    var $this = $(evt.target);
                    var id = 'title';
                    if (evt.target.id.indexOf(id, evt.target.id.length - id.length) !== -1) {
                        $this.parents('.cmb-row.cmb-repeatable-grouping').find('.cmb-group-title').text($this.val());
                    }
                };
                $box
                    .on('cmb2_add_row cmb2_shift_rows_complete', replaceTitles)
                    .on('keyup', replaceOnKeyUp);
                replaceTitles();
            });
        </script>
        <?php
    }
}

?>
