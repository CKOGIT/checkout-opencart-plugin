<?php
include ('includes/autoload.php');
class ControllerPaymentcheckoutapipayment extends Controller_Model
{
    public function index()
    {
        return parent::index();
    }

    public function successPage()
    {


        $this->load->model('checkout/order');
        $trackId = $this->model_checkout_order->getOrder($_POST['cko-track-id']);



        $scretKey = $this->config->get('checkoutapipayment_secret_key');
        $config['authorization'] = $scretKey  ;
        $config['paymentToken']  = $_POST['cko-payment-token'];
        $Api = CheckoutApi_Api::getApi(array('mode'=> $this->config->get('checkoutapipayment_test_mode')));
        $respondBody = $Api->verifyChargePaymentToken($config);
        $json = $respondBody->getRawOutput();
        $respondCharge = $Api->chargeToObj($json);


        if( $respondCharge->isValid()) {
            if (preg_match('/^1[0-9]+$/', $respondCharge->getResponseCode())) {

                $Message = 'Your transaction has been  ' .strtolower($respondCharge->getStatus()) .' with transaction id : '.$respondCharge->getId();
                if(!isset($this->session->data['fail_transaction']) || $this->session->data['fail_transaction'] == false) {
                    $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('checkoutapipayment_checkout_successful_order'), $Message, false);
                }

                if(isset($this->session->data['fail_transaction']) && $this->session->data['fail_transaction']) {
                    $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('checkoutapipayment_checkout_successful_order'), $Message, false);
                    $this->session->data['fail_transaction'] = false;
                }

                $json= $this->url->link('checkout/success', '', 'SSL');

                $success = $this->url->link('checkout/success', '', 'SSL');

                header("Location: ".$success);
            } else {

                $Payment_Error = 'Transaction failed : '.$respondCharge->getErrorMessage(). ' with response code : '.$respondCharge->getResponseCode();
                if(!isset($this->session->data['fail_transaction']) || $this->session->data['fail_transaction'] == false) {
                    $this->model_checkout_order->confirm($trackId, $this->config->get('checkout_failed_order'), $Payment_Error, true);
                }

                if(isset($this->session->data['fail_transaction']) && $this->session->data['fail_transaction']) {
                    $this->model_checkout_order->update($trackId, $this->config->get('checkout_failed_order'), $Payment_Error, true);
                }

                $json['error'] = 'We are sorry, but you transaction could not be processed. Please verify your card information and try again.'  ;

                $this->session->data['fail_transaction'] = true;
            }
        } else  {
            $json['error'] = $respondCharge->getExceptionState()->getErrorMessage()  ;
        }
    }

    public function webhook()
    {
        if(isset($_GET['chargeId'])) {
            $stringCharge = $this->_process();
        }else {
            $stringCharge = file_get_contents ( "php://input" );
        }

        $Api = CheckoutApi_Api::getApi(array('mode'=> $this->config->get('checkoutapipayment_test_mode')));

        $objectCharge = $Api->chargeToObj($stringCharge);

        if($objectCharge->isValid()) {

            $order_id = $objectCharge->getTrackId();
            $modelOrder = $this->load->model('checkout/order');
            $order_statuses = $this->getOrderStatuses();

            $status_mapped = array();

            foreach($order_statuses as $status){
                $status_mapped[$status['name']] = $status['order_status_id'];
            }

            if ( $objectCharge->getCaptured ()) {
                $this->model_checkout_order->addOrderHistory(
                    $order_id,
                    $status_mapped['Complete'],
                    "Order status changed by webhook.",
                    true
                );
                echo "Order has been captured";

            } elseif ( $objectCharge->getRefunded () ) {
                $this->model_checkout_order->addOrderHistory(
                    $order_id,
                    $status_mapped['Refunded'],
                    "",
                    true
                );
                echo "Order has been refunded";

            } elseif(!$objectCharge->getAuthorised()) {
                $this->model_checkout_order->addOrderHistory(
                    $order_id,
                    $this->config->get('checkoutapipayment_checkout_failed_order'),
                    "",
                    true
                );
                echo "Order has been Cancel";
            }
        }
    }

    private function _process()
    {
        $config['chargeId']    =    $_GET['chargeId'];
        $config['authorization']    =    $this->config->get('checkoutapipayment_secret_key');
        $Api = CheckoutApi_Api::getApi(array('mode'=> $this->config->get('checkoutapipayment_test_mode')));
        $respondBody    =    $Api->getCharge($config);

        $json = $respondBody->getRawOutput();
        return $json;
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

}
