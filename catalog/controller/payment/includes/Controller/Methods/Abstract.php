<?php

define('PLUGIN_VERSION', '1.2.0');

abstract class Controller_Methods_Abstract extends Controller
{

    protected function index()
    {
        $this->language->load('payment/checkoutapipayment');
        $data = $this->getData();
        foreach ($data as $key=>$val) {

            $this->data[$key] = $val;
        }

        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/checkoutapi/checkoutapipayment.tpl')) {
            $this->template = $this->config->get('config_template') . '/template/payment/checkoutapi/checkoutapipayment.tpl';
        } else {
            $this->template = 'default/template/payment/checkoutapi/checkoutapipayment.tpl';
        }

    }

    public function  getIndex()
    {
        $this->index();
    }

    public function setMethodInstance($methodInstance)
    {
        $this->_methodInstance = $methodInstance;
    }

    public function getMethodInstance()
    {
        return $this->_methodInstance;
    }

    public function send()
    {
        $this->_placeorder();
    }

    protected function _placeorder()
    {
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $amount = ($this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false))*100;

        $toValidate = array(
            'currency' => $this->currency->getCode(),
            'value' => $amount,
            'trackId' => $this->session->data['order_id'],
        );

        //building charge
        $respondCharge = $this->_createCharge($order_info);
        $Api = CheckoutApi_Api::getApi(array('mode'=> $this->config->get('test_mode')));
        $validateRequest = $Api::validateRequest($toValidate,$respondCharge);

        if( $respondCharge->isValid()) {

            if($respondCharge->getChargeMode() == 2){
                if(!empty($respondCharge->getRedirectUrl())){
                    $json['success'] = $respondCharge->getRedirectUrl();
                } else {
                    $Payment_Error = 'Transaction failed : '.$respondCharge->getErrorMessage(). ' with response code : '.$respondCharge->getResponseCode();

                    if(!isset($this->session->data['fail_transaction']) || $this->session->data['fail_transaction'] == false) {
                        $this->model_checkout_order->confirm($this->session->data['order_id'], $this->config->get('checkout_failed_order'), $Payment_Error, true);
                    }
                    if(isset($this->session->data['fail_transaction']) && $this->session->data['fail_transaction']) {
                        $this->model_checkout_order->update($this->session->data['order_id'], $this->config->get('checkout_failed_order'), $Payment_Error, true);
                    }
                    $json['error'] = 'We are sorry, but you transaction could not be processed. Please verify your payment information and try again.'  ;
                    $this->session->data['fail_transaction'] = true;

                    $redirectUrl = $this->url->link('checkout/checkout', '', 'SSL');
                    $json['success'] = $this->url->link('checkout/checkout', '', 'SSL');

                }


            }elseif($respondCharge->getChargeMode() == 3){
                $localPayment = $respondCharge->getLocalPayment();
                $paymentUrl = $localPayment->getPaymentUrl();

                if(!$paymentUrl){
                    $Payment_Error = 'Transaction failed : '.$respondCharge->getErrorMessage(). ' with response code : '.$respondCharge->getResponseCode();

                    if(!isset($this->session->data['fail_transaction']) || $this->session->data['fail_transaction'] == false) {
                        $this->model_checkout_order->confirm($this->session->data['order_id'], $this->config->get('checkout_failed_order'), $Payment_Error, true);
                    }
                    if(isset($this->session->data['fail_transaction']) && $this->session->data['fail_transaction']) {
                        $this->model_checkout_order->update($this->session->data['order_id'], $this->config->get('checkout_failed_order'), $Payment_Error, true);
                    }
                    $json['error'] = 'We are sorry, but you transaction could not be processed. Please verify your payment information and try again.'  ;
                    $this->session->data['fail_transaction'] = true;

                    $redirectUrl = $this->url->link('checkout/checkout', '', 'SSL');
                    $json['redirect'] = $this->url->link('checkout/checkout', '', 'SSL');
                } else {
                    $json['success'] = $paymentUrl;
                }


            } elseif (preg_match('/^1[0-9]+$/', $respondCharge->getResponseCode())) {
                $Message = 'Your transaction has been  ' .strtolower($respondCharge->getStatus()) .' with transaction id : '.$respondCharge->getId();

                if(!$validateRequest['status']){
                    foreach($validateRequest['message'] as $errormessage){
                        $Message .= '. '.$errormessage . '. ';
                    }
                }

                if(!isset($this->session->data['fail_transaction']) || $this->session->data['fail_transaction'] == false) {
                    $this->model_checkout_order->confirm($this->session->data['order_id'], $this->config->get('checkout_successful_order'), $Message, true);
                }

                if(isset($this->session->data['fail_transaction']) && $this->session->data['fail_transaction']) { 
                    $this->model_checkout_order->update($this->session->data['order_id'], $this->config->get('checkout_successful_order'), $Message, true);
                    $this->session->data['fail_transaction'] = false;
                }

                if($this->config->get('save_card') == 'yes'){ 
                    $this->_saveCard($respondCharge, $this->customer->getId(), $this->request->post['save-card-checkbox']);
                }
                


                $json['success'] = $this->url->link('checkout/success', '', 'SSL');

            } else {
                $Payment_Error = 'Transaction failed : '.$respondCharge->getErrorMessage(). ' with response code : '.$respondCharge->getResponseCode();

                if(!isset($this->session->data['fail_transaction']) || $this->session->data['fail_transaction'] == false) {
                    $this->model_checkout_order->confirm($this->session->data['order_id'], $this->config->get('checkout_failed_order'), $Payment_Error, true);
                }
                if(isset($this->session->data['fail_transaction']) && $this->session->data['fail_transaction']) {
                    $this->model_checkout_order->update($this->session->data['order_id'], $this->config->get('checkout_failed_order'), $Payment_Error, true);
                }
                $json['error'] = 'We are sorry, but you transaction could not be processed. Please verify your card information and try again.'  ;
                $this->session->data['fail_transaction'] = true;

                $redirectUrl = $this->url->link('checkout/checkout', '', 'SSL');
                $json['redirect'] = $this->url->link('checkout/checkout', '', 'SSL');
            }

        } else  {

            $json['error'] = $respondCharge->getExceptionState()->getErrorMessage()  ;
        }
        $this->response->setOutput(json_encode($json));
    }

    protected function _createCharge($order_info)
    {
        $config = array();
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $productsLoad= $this->cart->getProducts();
        $scretKey = $this->config->get('secret_key');
        $orderId = $this->session->data['order_id'];
        $amountCents = (int) $order_info['total'] * 100;
        $config['authorization'] = $scretKey  ;
        $config['mode'] = $this->config->get('test_mode');
        $config['timeout'] =  $this->config->get('gateway_timeout');

        if($this->config->get('payment_action') =='authorize_capture') {
            $config = array_merge($config, $this->_captureConfig());

        }else {

            $config = array_merge($config,$this->_authorizeConfig());
        }

        $products = array();
        foreach ($productsLoad as $item ) {

            $products[] = array (
                'name'       =>     $item['name'],
                'sku'        =>     $item['key'],
                'price'      =>     $this->currency->format($item['price'], $this->currency->getCode(), false, false),
                'quantity'   =>     $item['quantity']
            );
        }

        $billingAddressConfig = array(
            'addressLine1'       =>  $order_info['payment_address_1'],
            'addressLine2'       =>  $order_info['payment_address_2'],
            'postcode'           =>  $order_info['payment_postcode'],
            'country'            =>  $order_info['payment_iso_code_2'],
            'city'               =>  $order_info['payment_city'],
            'phone'              =>  array('number' => $order_info['telephone']),

        );

        $shippingAddressConfig = array(
            'addressLine1'       =>  $order_info['shipping_address_1'],
            'addressLine2'       =>  $order_info['shipping_address_2'],
            'postcode'           =>  $order_info['shipping_postcode'],
            'country'            =>  $order_info['shipping_iso_code_2'],
            'city'               =>  $order_info['shipping_city'],
            'phone'              =>  array('number' => $order_info['telephone']),
            'recipientName'	 =>   $order_info['firstname']. ' '. $order_info['lastname']

        );

        $config['postedParam'] = array_merge($config['postedParam'],array (
            'email'              =>  $order_info['email'],
            'value'              =>  $amountCents,
            'trackId'            =>  $orderId,
            'currency'           =>  $this->currency->getCode(),
            'description'        =>  "Order number::$orderId",
            'shippingDetails'    =>  $shippingAddressConfig,
            'products'           =>  $products,
            'card'               =>  array (
                                     'billingDetails'   =>    $billingAddressConfig
                                )
        ));

        return $config;
    }

    private function _saveCard($respondCharge,$customerId,$saveCardCheck)
    {

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

    protected function _captureConfig()
    {
        $to_return['postedParam'] = array (
            'autoCapture' => CheckoutApi_Client_Constant::AUTOCAPUTURE_CAPTURE,
            'autoCapTime' => $this->config->get('autocapture_delay')
        );

        return $to_return;
    }

    protected function _authorizeConfig()
    {
        $to_return['postedParam'] = array (
            'autoCapture' => CheckoutApi_Client_Constant::AUTOCAPUTURE_AUTH,
            'autoCapTime' => 0
        );

        return $to_return;
    }

    protected function _getCharge($config)
    {
        $Api = CheckoutApi_Api::getApi(array('mode'=> $this->config->get('test_mode')));

        return $Api->createCharge($config);
    }
}