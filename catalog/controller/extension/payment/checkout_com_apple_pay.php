<?php
class ControllerExtensionPaymentCheckoutComApplePay extends Controller {
    public function index() {
        $data = $this->load->language('extension/payment/checkout_com');

        $this->load->model('extension/payment/checkout_com');

        $data['testmode'] = $this->config->get('payment_checkout_com_test');
        $data['public_key'] = $this->config->get('payment_checkout_com_public_key');
        $data['merchant_id'] = $this->config->get('payment_checkout_com_apple_pay_merchant_id');
        $data['validation_url'] = $this->url->link('extension/payment/checkout_com_apple_pay/paymentSession', '', true);

        $data['text_testmode'] = $this->language->get('text_testmode');
        $data['button_confirm'] = $this->language->get('button_confirm');

        $button_style = 'cursor: pointer; -webkit-appearance: -apple-pay-button; ';

        if ($this->config->get('payment_checkout_com_apple_pay_button_theme') == 'white') {
            $button_style .= '-apple-pay-button-style: white; '; 
        } elseif ($this->config->get('payment_checkout_com_apple_pay_button_theme') == 'white_outline') {
            $button_style .= '-apple-pay-button-style: white-outline; ';
        } else {
            $button_style .= '-apple-pay-button-style: black; ';
        }

        if ($this->config->get('payment_checkout_com_apple_pay_button_type') == 'buy') {
            $button_style .= '-apple-pay-button-type: buy;';
        } elseif ($this->config->get('payment_checkout_com_apple_pay_button_type') == 'checkout') {
            $button_style .= '-apple-pay-button-type: checkout;';
        } else {
            $button_style .= '-apple-pay-button-type: plain;';
        }

        $data['button_style'] = trim($button_style);

        $this->load->model('checkout/order');

        if (isset($this->session->data['order_id'])) {
            $order_id = $this->session->data['order_id'];
        } else {
            $order_id = 0;
        }

        $order_info = $this->model_checkout_order->getOrder($order_id);

        if ($order_info) {
            $this->load->model('localisation/country');

            $country_info = $this->model_localisation_country->getCountry($order_info['payment_country_id']);

            $checkout_fields = array(
                'countryCode'          => $country_info['iso_code_2'],
                'currencyCode'         => $order_info['currency_code'],
                'merchantCapabilities' => array('supports3DS', 'supportsDebit', 'supportsCredit'),
                'supportedNetworks'    => array('visa', 'masterCard', 'amex', 'discover'),
                'total'                => array(
                    'label'                   => $this->config->get('config_name'),
                    'type'                    => 'final',
                    'amount'                  => $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false)
                )
            );

            if ($this->config->get('payment_checkout_com_apple_pay_mada_status')) {
                $checkout_fields['countryCode'] = 'SA';
                array_push($checkout_fields['supportedNetworks'], 'mada');
            } else {
                array_push($checkout_fields['merchantCapabilities'], 'supportsEMV');
            }

            $data['checkout_fields'] = json_encode($checkout_fields);

            return $this->load->view('extension/payment/checkout_com_apple_pay', $data);
        }
    }

    public function paymentSession() {
        $json = array();

        if ($this->request->server['REQUEST_METHOD'] == 'POST' && isset($this->request->post['url']) && isset($this->request->post['domain'])) {
            $payload = array(
                'merchantIdentifier' => $this->config->get('payment_checkout_com_apple_pay_merchant_id'),
                'displayName'        => $this->config->get('config_name'),
                'domainName'         => $this->request->post['domain']
            );

            $curl = curl_init();

            curl_setopt($curl, CURLOPT_URL, $this->request->post['url']);
            curl_setopt($curl, CURLOPT_SSLCERT, $this->config->get('payment_checkout_com_apple_pay_certificate'));
            curl_setopt($curl, CURLOPT_SSLKEY, $this->config->get('payment_checkout_com_apple_pay_certificate_key'));
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

            $response = curl_exec($curl);

            if ($response === false) {
                $this->log->write('Checkout.com Apple Pay Payment Session curlError: ' . curl_error($curl));
            } else {
                $json = $response;
            }

            curl_close($curl);
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}