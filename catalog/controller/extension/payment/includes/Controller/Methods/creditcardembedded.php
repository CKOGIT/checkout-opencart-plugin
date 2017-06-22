<?php
class Controller_Methods_creditcardembedded extends Controller_Methods_Abstract implements Controller_Interface
{
    public function getData()
    {
        $this->language->load('extension/payment/checkoutapipayment');
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $config['debug'] = false;
        $config['email'] = $order_info['email'];
        $config['name'] = $order_info['firstname'] . ' ' . $order_info['lastname'];
        $config['amount'] = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false) * 100;
        $config['currency'] = $order_info['currency_code'];
        $config['widgetSelector'] = '.widget-container';
        $mode = $this->config->get('checkoutapipayment_test_mode');
        $paymentMode = $this->config->get('checkoutapipayment_payment_mode');
        $cancelUrl = $this->config->get('config_url').'index.php?route=extension/payment/checkoutapipayment/failPage';
        $redirectUrl = $this->config->get('config_url').'index.php?route=extension/payment/checkoutapipayment/send';
        $theme = $this->config->get('checkoutapipayment_embedded_theme');

        if ($mode == 'live') {
            $url = 'https://cdn.checkout.com/v2/js/checkout.js';
        } else {
            $url = 'https://cdn.checkout.com/v2/sandbox/js/checkout.js';
        }

        $customCss = 'https://cdn.checkout.com/v2/sandbox/js/css/checkout-2.0.7.css';
        if(!empty($this->config->get('checkoutapipayment_custom_css'))){
            $customCss = $this->config->get('checkoutapipayment_custom_css');
        }

        $data = array(
            'publicKey'      => $this->config->get('checkoutapipayment_public_key'),
            'customCss'      => $customCss,
            'theme'          => $theme,
            'url'            => $url,
            'redirectUrl'    => $redirectUrl,
            'button_confirm' => $this->language->get('button_confirm'),
        );


        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . 'extension/payment/checkoutapi/creditcardembedded.tpl')) { 
            $tpl = $this->config->get('config_template') . 'extension/payment/checkoutapi/creditcardembedded.tpl';

        } else {  
            $tpl = 'extension/payment/checkoutapi/creditcardembedded.tpl';
        }

        

        $data['scripts'] = 'https://dl.dropbox.com/s/linsznbejvem1sd/style2.css?dl=0';
        $data['tpl'] = $this->load->view($tpl, $data);


        return $data;
    }

    protected function _createCharge($order_info)
    {  
        $config = parent::_createCharge($order_info);

        $config['postedParam'] = array_merge($config['postedParam'], array(
                'cardToken' => $this->request->post['cko-card-token']
            )
        );

        return $this->_getCharge($config);
    }
}