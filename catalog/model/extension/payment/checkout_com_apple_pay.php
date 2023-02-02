<?php
class ModelExtensionPaymentCheckoutComApplePay extends Model {
    public function getMethod($address, $total) {
        $this->load->language('extension/payment/checkout_com');

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('payment_checkout_com_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");

        if ($this->config->get('payment_checkout_com_total') > $total) {
            $status = false;
        } elseif (!$this->config->get('payment_checkout_com_geo_zone_id')) {
            $status = true;
        } elseif ($query->num_rows) {
            $status = true;
        } else {
            $status = false;
        }

        if (!$this->config->get('payment_checkout_com_secret_key') || !$this->config->get('payment_checkout_com_public_key') || !$this->config->get('payment_checkout_com_apple_pay_merchant_id')) {
            $status = false;
        }

        $method_data = array();

        if ($status) {
            $method_data = array(
                'code'       => 'checkout_com_apple_pay',
                'title'      => $this->config->get('payment_checkout_com_apple_pay_payment_title') ? $this->config->get('payment_checkout_com_apple_pay_payment_title') : 'Apple Pay (by Checkout.com)',
                'terms'      => '',
                'sort_order' => $this->config->get('payment_checkout_com_apple_pay_sort_order')
            );
        }

        return $method_data;
    }
}
