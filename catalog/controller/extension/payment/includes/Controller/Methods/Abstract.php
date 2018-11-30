<?php

define('PLUGIN_VERSION', '1.0.0');

abstract class Controller_Methods_Abstract extends Controller
{   
    public function index(){  
        $this->language->load('extension/payment/checkoutcom');
        $data = $this->getData();
        return $this->load->view ( 'extension/payment/checkoutapi/checkoutcom',$data);
    }

    public function  getIndex(){ 
        $this->index();
    }

    public function setMethodInstance($methodInstance){
        $this->_methodInstance = $methodInstance;
    }

    public function getMethodInstance(){
        return $this->_methodInstance;
    }

    public function send(){
        $this->_placeorder();
    }

    protected function _placeorder(){
        $this->load->model('checkout/order');

        if(empty($this->session->data['order_id'])){
            $redirectUrl = $this->url->link('checkout/checkout', '', 'SSL');
            header("Location: ".$redirectUrl);
        }

        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        //building charge
        $responseCharge = (array) $this->_createCharge($order_info);

        // Verify if payment is 3DS
        if(isset($responseCharge['3ds'])){

            if(isset($responseCharge['3ds']->enrolled )){

                $redirect = $responseCharge['_links']->redirect;
                $redirectUrl = $redirect->href;

                // Redirect to 3DS bank to complete payment.
                $json['redirect'] = $redirectUrl;

            } elseif ($responseCharge['status'] == 'Authorised' && isset($responseCharge['3ds']->downgraded)) {

                // Add 3D downgraded message to order
                $message = '3DS payment has been downgraded to non 3DS. Payment has been authorized. Payment Id : ' . $responseCharge['id'];
                $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], 1, $message, true);

                // Redirect to success page
                $json['redirect'] = $this->url->link('checkout/success', '', 'SSL');

            } elseif ($responseCharge['status'] == 'Declined'){

                $errorMessage = '3DS payment failed. Payment Id : ' . $responseCharge['id'] .' - OrderId : '. $this->session->data['order_id'];
                $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], 10, $errorMessage, true);
                $this->log->write($errorMessage);
                $this->session->data['fail_transaction'] = true;

                $json['error'] = 'An error has occurred while processing your payment. Please check your card details or try with a different card'  ;
            }

        } else {
            // Process non 3DS payment.

            // Verify if risk settings was triggered
            if($responseCharge['risk']->flagged){

                // Add flagged message to order
                $message = 'Payment has been flagged. Payment Id : ' . $responseCharge['id'];

                // Add message to order and set order status to "Pending"
                $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], 1, $message, true);

                $json['redirect'] = $this->url->link('checkout/success', '', 'SSL');

            } elseif ($responseCharge['status'] == 'Authorized') {

                // Process normal payment flow
                $message = 'Payment has been successfully authorized with Payment Id : ' . $responseCharge['id'];

                // Add message to order and set order status to "Pending"
                $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], 1, $message, true);

                $json['redirect'] = $this->url->link('checkout/success', '', 'SSL');

            } elseif ($responseCharge['status'] == 'Declined'){

                $errorMessage = 'Payment failed. Payment Id : ' . $responseCharge['id'] .' - OrderId : '. $this->session->data['order_id'];
                $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], 10, $errorMessage, true);
                $this->log->write($errorMessage);
                $this->session->data['fail_transaction'] = true;

                $json['error'] = 'An error has occurred while processing your payment. Please check your card details or try with a different card'  ;
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));

    }

    protected function _createCharge($order_info){
        $config = array();
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $productsLoad= $this->cart->getProducts();
        $scretKey = $this->config->get('payment_checkoutcom_secret_key');
        $orderId = $this->session->data['order_id'];
        $amountCents = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false) * 100;
        $config['authorization'] = $scretKey  ;
        $config['mode'] = $this->config->get('payment_checkoutcom_test_mode');

        if($this->config->get('payment_checkoutcom_payment_action') =='capture') {
            $config = array_merge($config, $this->_captureConfig());
        }else {
            $config = array_merge($config,$this->_authorizeConfig());
        }

        $is3D = $this->config->get('payment_checkoutcom_3D_secure');
        $chargeMode = 1;

        if($is3D == 'yes'){
            $chargeMode = 2;
        }

        $integrationType = $this->config->get('payment_checkoutcom_integration_type');

        $products = array();
        foreach ($productsLoad as $item ) {

            $products[] = array (
                'name'       =>     $item['name'],
                'sku'        =>     $item['product_id'],
                'price'      =>     $item['price'],
                'quantity'   =>     $item['quantity']
            );
        }

        $billingAddressConfig = array(
            'addressLine1'   =>  $order_info['payment_address_1'],
            'addressLine2'   =>  $order_info['payment_address_2'],
            'postcode'       =>  $order_info['payment_postcode'],
            'country'        =>  $order_info['payment_iso_code_2'],
            'city'           =>  $order_info['payment_city'],
            'phone'          =>  array('number' => $order_info['telephone']),

        );

        $shippingAddressConfig = array(
            'addressLine1'   =>  $order_info['shipping_address_1'],
            'addressLine2'   =>  $order_info['shipping_address_2'],
            'postcode'       =>  $order_info['shipping_postcode'],
            'country'        =>  $order_info['shipping_iso_code_2'],
            'city'           =>  $order_info['shipping_city'],
            'phone'          =>  array('number' => $order_info['telephone']),
            'recipientName'	 =>   $order_info['firstname']. ' '. $order_info['lastname']

        );

        $config['postedParam'] = array_merge($config['postedParam'],array (
            'email'              =>  $order_info['email'],
            'customerName'       =>  $order_info['firstname']. ' '. $order_info['lastname'],
            'value'              =>  $amountCents,
            'trackId'            =>  $orderId,
            'currency'           =>  $order_info['currency_code'],
            'description'        =>  "Order number::$orderId",
            'chargeMode'         =>  $chargeMode,
            'shippingDetails'    =>  $shippingAddressConfig,
            'billingDetails'     =>  $billingAddressConfig,
            'products'           =>  $products,
            // 'card'               =>  array(),
            'metadata'           => array(
                                        'server'            => $this->config->get('config_url'),
                                        'quote_id'          => $orderId,
                                        'oc_version'        => VERSION,
                                        'plugin_version'    => PLUGIN_VERSION,
//                                        'lib_version'       => CheckoutApi_Client_Constant::LIB_VERSION,
                                        'integration_type'  => $integrationType,
                                        'time'              => date('Y-m-d H:i:s')
                                    )
        ));


        return $config;
    }

    protected function _captureConfig(){

        $autoCapTime = $this->config->get('payment_checkoutcom_autocapture_delay');
        if(empty($autoCapTime)){
            $autoCapTime = 0;
        }

        $to_return['postedParam'] = array (
//            'autoCapture' => CheckoutApi_Client_Constant::AUTOCAPUTURE_CAPTURE,
            'autoCapTime' => $this->config->get('payment_checkoutcom_autocapture_delay')
        );

        return $to_return;
    }

    protected function _authorizeConfig(){
        $to_return['postedParam'] = array (
//            'autoCapture' => CheckoutApi_Client_Constant::AUTOCAPUTURE_AUTH,
            'autoCapTime' => 0
        );

        return $to_return;
    }

    protected function _getCharge($config){
        $Api = CheckoutApi_Api::getApi(array('mode'=> $this->config->get('payment_checkoutcom_test_mode')));

        return $Api->createCharge($config);
    }

    private function _saveCard($respondCharge,$customerId,$saveCardCheck){
        if (empty($respondCharge)) {
            return false;
        }

        if($saveCardCheck != 1){
            return false;
        }

        $last4      = $respondCharge->getCard()->getLast4();
        $cardId     = $respondCharge->getCard()->getId();
        $cardType   = $respondCharge->getCard()->getPaymentMethod();
        $ckoCustomerId = $respondCharge->getCard()->getCustomerId();

        if (empty($last4) || empty($cardId) || empty($cardType) || empty($ckoCustomerId)) {
            return false;
        }

        if ($this->_cardExist($customerId, $cardId, $cardType)) {
           return false;
        }


        $this->db->query("INSERT INTO " . DB_PREFIX . "checkout_customer_cards SET customer_id = '" . (int)$customerId . "', card_id = '" . $cardId . "', card_number = '" . $last4 . "', card_type = '" . $cardType . "', card_enabled = '" . $saveCardCheck. "', cko_customer_id = '" . $ckoCustomerId. "'");

        return true;
    }

    private function _cardExist($customerId,$cardId,$cardType){
        $sql = "SELECT * FROM ".DB_PREFIX."checkout_customer_cards WHERE customer_id = '".$customerId."' AND card_type = '".$cardType."' AND card_id = '".$cardId."'";

        $query = $this->db->query($sql);

        if($query->rows){
            return true;
        } 

        return false;
    }
}