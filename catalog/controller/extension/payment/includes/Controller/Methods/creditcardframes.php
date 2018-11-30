<?php
class Controller_Methods_creditcardframes extends Controller_Methods_Abstract implements Controller_Interface
{
    public function getData(){
        $this->language->load('extension/payment/checkoutcom');
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $config['debug'] = false;
        $config['email'] = $order_info['email'];
        $config['name'] = $order_info['firstname'] . ' ' . $order_info['lastname'];
        $config['amount'] = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false) * 100;
        $config['currency'] = $order_info['currency_code'];
        $config['widgetSelector'] = '.widget-container';
        $mode = $this->config->get('payment_checkoutcom_test_mode');
        $paymentMode = $this->config->get('payment_checkoutcom_payment_mode');
        $cancelUrl = $this->config->get('config_url').'index.php?route=extension/payment/checkoutcom/failPage';
        $redirectUrl = $this->config->get('config_url').'index.php?route=extension/payment/checkoutcom/send';
        $theme = $this->config->get('payment_checkoutcom_frames_theme');


        $data = array(
            'publicKey'      => $this->config->get('payment_checkoutcom_public_key'),
            'theme'          => $theme,
            'redirectUrl'    => $redirectUrl,
            'button_confirm' => $this->language->get('button_confirm'),
            'integrationType'=> 'framesJs',
            'action'         => $this->url->link('extension/payment/checkoutcom/send', '', true),
            'save_card'      => $this->config->get('payment_checkoutcom_save_card'),
        );

        if($this->config->get('payment_checkoutcom_save_card') == 'yes'){
            if($this->customer->getId()){
                $data['customer_login'] = 'yes';

                $cardList = $this->getCustomerCardList($this->customer->getId());
                $data['cardLists'] = '';
                
                if(!empty($cardList)){
                    foreach ($cardList as $key) {
                        $test[] = $key;
                    }

                    $data['cardLists'] = $test;
                }
            } else {
                $data['customer_login'] = 'no';
            }
        }

        return $data;
    }

    /**
     * @param $order_info
     * @return array|void
     */
    protected function _createCharge($order_info){
        $config = parent::_createCharge($order_info);

        $config = array();
        $secretKey = $this->config->get('payment_checkoutcom_secret_key');

        $config = $this->getConfigData();
        $config['source']['type'] = 'token';

        if($this->request->post['cko-payment'] == 'new_card' && !empty($this->request->post['cko-card-token'])){
            $config['cardToken'] = $this->request->post['cko-card-token'];

        } elseif($this->request->post['cko-payment'] == 'saved_card' && !empty($this->request->post['entity_id'])){
            $getCardId = $this->getCardId($this->request->post['entity_id']);

            $config['cardId'] = $getCardId[0]['card_id'];
        } else {
            $config['source']['token'] = $this->request->post['cko-card-token'];
        }

        $endPointMode = $this->config->get('payment_checkoutcom_test_mode');
        $createChargeUrl = "https://api.sandbox.checkout.com/payments";

        if($endPointMode == 'live'){
            $createChargeUrl = "https://api.checkout.com/payments";
        }

        // curl to process charge with card token
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$createChargeUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: '.$secretKey,'Content-Type:application/json;charset=UTF-8'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($config));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        $server_output = curl_exec($ch);
        curl_close ($ch);

        $response = json_decode($server_output);

        return $response;
    }

    public function getConfigData(){
        $config = array();
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $orderId = $this->session->data['order_id'];
        $amountCents = ($this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false))*100;
        $enable3D = $this->config->get('payment_checkoutcom_3D_secure') == 'yes' ? true : false;
        $attemptN3d = $this->config->get('payment_checkoutcom_attempt_non3D') == 'yes' ? true : false;
        $capture = $this->config->get('payment_checkoutcom_payment_action') == 'yes' ? true : false;
        $skipRiskCheck = $this->config->get('payment_checkoutcom_skip_risk_check') == 'yes' ? false : true;

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        $paymentIp = $ip;

        $config = array();

        $billingAddressConfig = array(
            'address_line1' =>  $order_info['payment_address_1'],
            'address_line2' =>  $order_info['payment_address_2'],
            'zip'           =>  $order_info['payment_postcode'],
            'country'       =>  $order_info['payment_iso_code_2'],
            'city'          =>  $order_info['payment_city'],
        );

        $shippingAddressConfig = array(
            'address_line1' =>  $order_info['shipping_address_1'],
            'address_line2' =>  $order_info['shipping_address_2'],
            'zip'           =>  $order_info['shipping_postcode'],
            'country'       =>  $order_info['shipping_iso_code_2'],
            'city'          =>  $order_info['shipping_city'],
        );

        $config = array(
            'source'    => array(
                'billing_address' => $billingAddressConfig
            ),
            'customer'  =>  array(
                'email' =>  $order_info['email'],
                'name'  =>  $order_info['payment_firstname']. ' ' .$order_info['payment_lastname']
            ),
            '3ds' => array(
                'enabled'       => $enable3D,
                'attempt_n3d'   => $attemptN3d
            ),
            'risk'    => array(
                "enabled" => $skipRiskCheck
            ),
            'payment_type'       =>  'REGULAR',
            'payment_ip'         =>  $paymentIp,
            'capture'            =>  $capture,
            'amount'             =>  $amountCents,
            'reference'          =>  $orderId,
            'currency'           =>  $order_info['currency_code'],
            'description'        =>  "Order number::$orderId",
            'shipping'    =>  array(
                'address' =>   $shippingAddressConfig,
                'phone'   => array(
                    'number' =>  $order_info['telephone']
                )
            ),
            'metadata'           => array(
                'server'            => $this->config->get('config_url'),
                'orderId'           => $orderId,
                'opencart_version'  => VERSION,
                'plugin_version'    => PLUGIN_VERSION,
                'integration_type'  => 'FramesJs',
                'time'              => date('Y-m-d H:i:s')
            ),
        );

        return $config;
    }

    public function getCustomerCardList($customerId) {
        $sql = "SELECT * FROM ".DB_PREFIX."checkout_customer_cards WHERE customer_id = '".$customerId."' AND card_enabled = '1'";

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getCardId($entityId){
        $sql = 'SELECT card_id FROM '.DB_PREFIX."checkout_customer_cards WHERE entity_id = '".$entityId."'";

        $query = $this->db->query($sql);

        return $query->rows;
    }
}