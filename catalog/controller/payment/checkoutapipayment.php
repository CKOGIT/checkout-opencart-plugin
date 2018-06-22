<?php
include ('includes/autoload.php');
class ControllerPaymentcheckoutapipayment extends Controller_Model
{
    protected function index()
    {
        parent::index();
        $this->render();
    }

    public function successPage()
    {
        $this->load->model('checkout/order');
        $scretKey = $this->config->get('secret_key');
        $config['authorization'] = $scretKey  ;

        if(empty($_REQUEST['cko-payment-token'])){
            return false;
        }

        $config['paymentToken']  = $_REQUEST['cko-payment-token'];
        $Api = CheckoutApi_Api::getApi(array('mode'=> $this->config->get('test_mode')));
        $respondBody = $Api->verifyChargePaymentToken($config);
        $json = $respondBody->getRawOutput();
        $respondCharge = $Api->chargeToObj($json);
        $trackId = $respondCharge->getTrackId();
        $order_info = $this->model_checkout_order->getOrder($trackId);

        if( $respondCharge->isValid()) {

            if($respondCharge->getChargeMode()==3){

                $Message = 'Your transaction has been completed with transaction id : '.$respondCharge->getId();

                $this->model_checkout_order->confirm($trackId, $this->config->get('checkout_successful_order'), $Message, true);
                $success = $this->data['continue'] = $this->url->link('checkout/success');

                header("Location: ".$success);

            } elseif (preg_match('/^1[0-9]+$/', $respondCharge->getResponseCode())) {

                $Message = 'Your transaction has been  ' .strtolower($respondCharge->getStatus()) .' with transaction id : '.$respondCharge->getId();

                $this->model_checkout_order->confirm($trackId, $this->config->get('checkout_successful_order'), $Message, true);
                $success = $this->data['continue'] = $this->url->link('checkout/success');

                header("Location: ".$success);

            } else {

                $Payment_Error = 'Transaction failed : '.$respondCharge->getErrorMessage(). ' with response code : '.$respondCharge->getResponseCode();

                if(!isset($this->session->data['fail_transaction']) || $this->session->data['fail_transaction'] == false) {
                    $this->model_checkout_order->confirm($trackId, $this->config->get('checkout_failed_order'), $Payment_Error, true);
                }
                if(isset($this->session->data['fail_transaction']) && $this->session->data['fail_transaction']) {
                    $this->model_checkout_order->update($trackId, $this->config->get('checkout_failed_order'), $Payment_Error, true);
                }
                
                $this->session->data['error'] = 'An error has occured while processing your order. Please check your card details or try with a different card';
                $this->response->redirect($this->url->link('checkout/checkout', '', 'SSL'));
            }

        } else  {
            $this->session->data['error'] = 'An error has occured while processing your order. Please check your payment details and try again.';
            $this->response->redirect($this->url->link('checkout/checkout', '', 'SSL'));
        }
    }

    public function failPage(){
        $this->load->model('checkout/order');
        $this->language->load('payment/checkoutapipayment');

        if(empty($this->session->data['order_id'])){
            return false;
        }

        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        $this->data['title'] = sprintf($this->language->get('heading_title'), $this->config->get('config_name'));
    
        if (!isset($this->request->server['HTTPS']) || ($this->request->server['HTTPS'] != 'on')) {
            $this->data['base'] = HTTP_SERVER;
        } else {
            $this->data['base'] = HTTPS_SERVER;
        }

        $this->data['language'] = $this->language->get('code');
        $this->data['direction'] = $this->language->get('direction');

        $this->data['heading_title'] = sprintf($this->language->get('heading_title'), $this->config->get('config_name'));

        $this->data['text_response'] = $this->language->get('text_response');
        $this->data['text_success'] = $this->language->get('text_success');
        $this->data['text_success_wait'] = sprintf($this->language->get('text_success_wait'), $this->url->link('checkout/success'));
        $this->data['text_failure'] = $this->language->get('text_failure');
        $this->data['text_failure_wait'] = sprintf($this->language->get('text_failure_wait'), $this->url->link('checkout/cart'));

        $Message = 'Transaction has failed or has been canceled';
        
        if(!isset($this->session->data['fail_transaction']) || $this->session->data['fail_transaction'] == false) {
            $this->model_checkout_order->confirm($this->session->data['order_id'], $this->config->get('checkout_failed_order'), $Message, true);
        }
        if(isset($this->session->data['fail_transaction']) && $this->session->data['fail_transaction']) { 
            if(empty($order_info['order_status'])){
                $this->model_checkout_order->confirm($this->session->data['order_id'], $this->config->get('checkout_failed_order'), $Message, true);
            } else {
                $this->model_checkout_order->update($this->session->data['order_id'], $this->config->get('checkout_failed_order'), $Message, true);
            }
        }

        $this->data['continue'] = $this->url->link('checkout/checkout', '', 'SSL');

        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/checkoutapi/checkoutapipayment_failure.tpl')) {
            $this->template = $this->config->get('config_template') . '/template/payment/checkoutapi/checkoutapipayment_failure.tpl';
        } else {
            $this->template = 'default/template/payment/checkoutapi/checkoutapipayment_failure.tpl';
        }


        $this->response->setOutput($this->render());
    }


    public function webhook()
    {
        $stringCharge = file_get_contents ( "php://input" );
        $data = json_decode($stringCharge);

        if(!$data){
            return false;
        }

        $eventType          = $data->eventType;

        $message = $data->message;
        $order_id = $message->trackId;
        $chargeId = $message->id;

        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($order_id);

        if($data) {
            $Message = 'Webhook received from Checkout.com. ' ;
            if($eventType == 'charge.succeeded'){
                $Message .= ' Order has been authorised. chargeId : '.$chargeId;

                if(empty($order_info['order_status'])){
                    $this->model_checkout_order->confirm($order_id, 1, $Message, true);
                } else {
                    $this->model_checkout_order->update($order_id, 1, $Message, true);
                }

            } elseif ($eventType == 'charge.captured') { 
                $Message .= ' Order has been captured. chargeId : '.$chargeId;

                if(empty($order_info['order_status'])){
                    $this->model_checkout_order->confirm($order_id, $this->config->get('checkout_successful_order'), $Message, true);
                } else {
                    $this->model_checkout_order->update($order_id, $this->config->get('checkout_successful_order'), $Message, true);
                }

            } elseif ($eventType == 'charge.refunded') {
                $Message .= ' Order has been refunded. chargeId : '.$chargeId;
                
                if(empty($order_info['order_status'])){
                    $this->model_checkout_order->confirm($order_id, 11, $Message, true);
                } else {
                    $this->model_checkout_order->update($order_id, 11, $Message, true);
                }

            } elseif($eventType == 'charge.voided' || $eventType == 'invoice.cancelled') {
                $Message .= ' Order has been voided/cancelled. ChargeId : '.$chargeId;

                if(empty($order_info['order_status'])){
                    $this->model_checkout_order->confirm($order_id, 16, $Message, true);
                } else {
                    $this->model_checkout_order->update($order_id, 16, $Message, true);
                }
            }
        }
    }

    private function getOrderStatuses($data = array()) {
        if ($data) {
            $sql = "SELECT * FROM " . DB_PREFIX . "order_status WHERE language_id = '" . (int)$this->config->get('config_language_id') . "'";

            $sql .= " ORDER BY name";

            if (isset($data['order']) && ($data['order'] == 'DESC')) {
                $sql .= " DESC";
            } else {
                $sql .= " ASC";
            }

            if (isset($data['start']) || isset($data['limit'])) {
                if ($data['start'] < 0) {
                    $data['start'] = 0;
                }

                if ($data['limit'] < 1) {
                    $data['limit'] = 20;
                }

                $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
            }

            $query = $this->db->query($sql);

            return $query->rows;
        } else {
            $order_status_data = $this->cache->get('order_status.' . (int)$this->config->get('config_language_id'));

            if (!$order_status_data) {
                $query = $this->db->query("SELECT order_status_id, name FROM " . DB_PREFIX . "order_status WHERE language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY name");

                $order_status_data = $query->rows;

                $this->cache->set('order_status.' . (int)$this->config->get('config_language_id'), $order_status_data);
            }

            return $order_status_data;
        }
    }

    public function customerCard(){
        if (!$this->customer->isLogged()) {
            $this->session->data['redirect'] = $this->url->link('account/account', '', 'SSL');
      
            $this->redirect($this->url->link('account/login', '', 'SSL'));
        } 
    
        $this->language->load('account/account');

        $this->document->setTitle('My Saved Cards');

        $this->data['breadcrumbs'] = array();

        $this->data['breadcrumbs'][] = array(
            'text'      => $this->language->get('text_home'),
            'href'      => $this->url->link('common/home'),
            'separator' => false
        ); 

        $this->data['breadcrumbs'][] = array(           
            'text'      => $this->language->get('text_account'),
            'href'      => $this->url->link('account/account', '', 'SSL'),
            'separator' => $this->language->get('text_separator')
        );
        
        if (isset($this->session->data['success'])) {
            $this->data['success'] = $this->session->data['success'];
            
            unset($this->session->data['success']);
        } else {
            $this->data['success'] = '';
        }
        
        $this->data['heading_title'] = 'My Saved Cards';

        if($this->customer->getId()){
            $this->session->data['customer_login'] = 'yes';

            $cardList = $this->_getCustomerCardList($this->customer->getId());
        
            $this->session->data['cardLists']= '';
            
            if(!empty($cardList)){
                foreach ($cardList as $key) {
                    $test[] = $key;
                }

                $this->session->data['cardLists'] = $test;
            }
        } else {
            $this->session->data['customer_login'] = 'no';
        }
            
        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/checkoutapi/customerCard.tpl')) {
            $this->template = $this->config->get('config_template') . '/template/payment/checkoutapi/customerCard.tpl';
        } else {
            $this->template = 'default/template/payment/checkoutapi/customerCard.tpl';
        }
        
        $this->children = array(
            'common/column_left',
            'common/column_right',
            'common/content_top',
            'common/content_bottom',
            'common/footer',
            'common/header'     
        );
                
        $this->response->setOutput($this->render());
    }

    private function _getCustomerCardList($customerId) {
        $sql = "SELECT * FROM ".DB_PREFIX."checkout_customer_cards WHERE customer_id = '".$customerId."' AND card_enabled = '1'";

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function deleteCard(){

        $hasError = true;

        if(!empty($this->request->post['entity_id'])){
            $sql = "DELETE FROM ".DB_PREFIX."checkout_customer_cards WHERE entity_id = '".$this->request->post['entity_id']."'";
            $query = $this->db->query($sql);
            $hasError = false;
        }

        if($hasError == false){
            $json['success'] = true;
        } else {
            $json['success'] = false;
        }

        $this->response->setOutput(json_encode($json));
    }

}
