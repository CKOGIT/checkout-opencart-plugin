<?php
class ModelExtensionPaymentCheckoutComApm extends Model {
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

        if (!$this->config->get('payment_checkout_com_secret_key') || !$this->config->get('payment_checkout_com_public_key')) {
            $status = false;
        }

        $payment_methods = $this->getPaymentMethods();

        if (!$payment_methods) {
            $status = false;
        } else {
            $selected = array();

            foreach ($payment_methods as $payment_method) {
                $selected[] = $this->language->get('text_' . $payment_method);
            }
        }

        $method_data = array();

        if ($status) {
            if ($this->config->get('payment_checkout_com_apm_payment_title')) {
                $title = $this->config->get('payment_checkout_com_apm_payment_title');
            } else {
                $title = sprintf($this->language->get('text_title'), implode(' / ', $selected));
            }

            $method_data = array(
                'code'       => 'checkout_com_apm',
                'title'      => $title,
                'terms'      => '',
                'sort_order' => $this->config->get('payment_checkout_com_apm_sort_order')
            );
        }

        return $method_data;
    }

    public function getPaymentMethods() {
        $payment_methods = ['alipay', 'paypal', 'bancontact', 'knet', 'qpay'];

        $data = [];

        foreach ($payment_methods as $payment_method) {
            if (is_array($this->config->get('payment_checkout_com_apm_payment_method')) && in_array($payment_method, $this->config->get('payment_checkout_com_apm_payment_method'))) {
                if ($payment_method == 'bancontact' && strtoupper($this->session->data['currency']) != 'EUR') {
                    continue;
                } elseif ($payment_method == 'knet' && strtoupper($this->session->data['currency']) != 'KWD') {
                    continue;
                } elseif ($payment_method == 'qpay' && strtoupper($this->session->data['currency']) != 'QAR') {
                    continue;
                } elseif ($payment_method == 'alipay' && strtoupper($this->session->data['currency']) != 'USD') {
                    continue;
                } elseif ($payment_method == 'paypal') {
                    $currencies = array(
                        'AUD',
                        'BRL',
                        'CAD',
                        'CZK',
                        'DKK',
                        'EUR',
                        'HKD',
                        'HUF',
                        'ILS',
                        'JPY',
                        'MYR',
                        'MXN',
                        'NOK',
                        'NZD',
                        'PHP',
                        'PLN',
                        'GBP',
                        'RUB',
                        'SGD',
                        'SEK',
                        'CHF',
                        'TWD',
                        'THB',
                        'TRY',
                        'USD'
                    );

                    if (!in_array(strtoupper($this->session->data['currency']), $currencies)) {
                        continue;
                    }
                }

                $data[] = $payment_method;
            }
        }

        return $data;
    }
}