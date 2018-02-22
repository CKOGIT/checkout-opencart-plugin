<?php

define('PLUGIN_VERSION', '2.4.1');

abstract class Controller_Methods_Abstract extends Controller
{   
    public function index()
    {        
        $this->language->load('extension/payment/checkoutapipayment');
        $data = $this->getData();

        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . 'extension/payment/checkoutapi/checkoutapipayment.tpl')) {

            return $this->load->view ($this->config->get('config_template') . 'extension/payment/checkoutapi/checkoutapipayment.tpl',$data);

        } else {
            return $this->load->view ( 'extension/payment/checkoutapi/checkoutapipayment.tpl',$data);
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

        if(empty($this->session->data['order_id'])){
            $redirectUrl = $this->url->link('checkout/checkout', '', 'SSL');
            header("Location: ".$redirectUrl);
        }

        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        //building charge
        $respondCharge = $this->_createCharge($order_info);

        if( $respondCharge->isValid()) {

            if (preg_match('/^1[0-9]+$/', $respondCharge->getResponseCode())) {

                if($respondCharge->getChargeMode() != 2) {

                    if($respondCharge->getResponseCode()==10100){

                        $Message = 'Your transaction has been flagged with transaction id : '.$respondCharge->getId();

                        if(!isset($this->session->data['fail_transaction']) || $this->session->data['fail_transaction'] == false) {
                            $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], 1, $Message, true); // 1 for pending order
                        }

                        if(isset($this->session->data['fail_transaction']) && $this->session->data['fail_transaction']) {
                            $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], 1, $Message, true); // 1 for pending order
                            $this->session->data['fail_transaction'] = false;
                        }
                    }else {
                        $Message = 'Your transaction has been successfully authorized with transaction id : ' . $respondCharge->getId();

                        if (!isset($this->session->data['fail_transaction']) || $this->session->data['fail_transaction'] == false) {
                            $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('checkoutapipayment_checkout_successful_order'), $Message, false);
                        }

                        if (isset($this->session->data['fail_transaction']) && $this->session->data['fail_transaction']) {
                            $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('checkoutapipayment_checkout_successful_order'), $Message, false);
                            $this->session->data['fail_transaction'] = false;
                        }
                    }

                    if($this->config->get('checkoutapipayment_integration_type') == 'pci'){
                        $json['redirect'] = $this->url->link('checkout/success', '', 'SSL');
                    } else{
                        $redirectUrl = $this->url->link('checkout/success', '', 'SSL');
                        header("Location: ".$redirectUrl);
                    }

                } else {
                    if (!empty($respondCharge['redirectUrl'])) {
                        if($this->config->get('checkoutapipayment_integration_type') == 'pci'){
                            $json['redirect'] = $respondCharge['redirectUrl'];
                        } else{
                            $redirectUrl = $respondCharge['redirectUrl'];
                            header("Location: ".$redirectUrl);
                        }
                    }
                }

            } else {
                $Payment_Error = 'Transaction failed : '.$respondCharge->getErrorMessage(). ' with response code : '.$respondCharge->getResponseCode();

                if(!isset($this->session->data['fail_transaction']) || $this->session->data['fail_transaction'] == false) {
                    $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('checkoutapipayment_checkout_failed_order'), $Payment_Error, false);
                }
                if(isset($this->session->data['fail_transaction']) && $this->session->data['fail_transaction']) {
                    $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('checkoutapipayment_checkout_failed_order'), $Payment_Error, false);
                }
                
                $this->session->data['error'] = 'An error has occured while processing your order. Please check your card details or try with a different card';
                $this->session->data['fail_transaction'] = true; 

                if($this->config->get('checkoutapipayment_integration_type') == 'pci'){
                    $json['redirect'] = $this->url->link('checkout/checkout', '', 'SSL');
                } else{
                    $redirectUrl = $this->url->link('checkout/checkout', '', 'SSL');;
                    header("Location: ".$redirectUrl);
                }
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
        $scretKey = $this->config->get('checkoutapipayment_secret_key');
        $orderId = $this->session->data['order_id'];
        $amountCents = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false) * 100;
        $config['authorization'] = $scretKey  ;
        $config['mode'] = $this->config->get('checkoutapipayment_test_mode');
        $config['timeout'] =  $this->config->get('checkoutapipayment_gateway_timeout');

        if($this->config->get('checkoutapipayment_payment_action') =='capture') {
            $config = array_merge($config, $this->_captureConfig());
        }else {
            $config = array_merge($config,$this->_authorizeConfig());
        }

        $is3D = $this->config->get('checkoutapipayment_3D_secure');
        $chargeMode = 1;

        if($is3D == 'yes'){
            $chargeMode = 2;
        }

        $integrationType = $this->config->get('checkoutapipayment_integration_type');

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
            'card'               =>  array(),
            'metadata'           => array(
                                        'server'            => $this->config->get('config_url'),
                                        'quote_id'          => $orderId,
                                        'oc_version'        => VERSION,
                                        'plugin_version'    => PLUGIN_VERSION,
                                        'lib_version'       => CheckoutApi_Client_Constant::LIB_VERSION,
                                        'integration_type'  => $integrationType,
                                        'time'              => date('Y-m-d H:i:s')
                                    )
        ));

        return $config;
    }

    protected function _captureConfig()
    {
        $to_return['postedParam'] = array (
            'autoCapture' => CheckoutApi_Client_Constant::AUTOCAPUTURE_CAPTURE,
            'autoCapTime' => $this->config->get('checkoutapipayment_autocapture_delay')
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
        $Api = CheckoutApi_Api::getApi(array('mode'=> $this->config->get('checkoutapipayment_test_mode')));

        return $Api->createCharge($config);
    }
}