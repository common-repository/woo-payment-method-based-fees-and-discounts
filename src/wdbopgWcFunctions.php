<?php

namespace src;

if (!defined('ABSPATH')) exit;

class wdbopgWcFunctions
{
    /**
     * Payment Method
     */
    public function get_payment_method($woocommerce)
    {
        if(method_exists($woocommerce->session,'get')) {
            return $woocommerce->session->get('chosen_payment_method');
        }else{
            return;
        }
    }

    /**
     * $sub_total_exclude_tax
     */
    public function sub_total_exclude_tax($woocommerce)
    {
        return $woocommerce->cart->subtotal_ex_tax;
    }
}
