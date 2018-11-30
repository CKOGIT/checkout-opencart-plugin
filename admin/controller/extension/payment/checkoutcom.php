<?php
class ControllerExtensionPaymentCheckoutcom extends Controller
{
    private $error = array();

    public function install() {
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "checkout_customer_cards` (
            `entity_id` int(10) NOT NULL AUTO_INCREMENT COMMENT 'ID',
            `customer_id` INT(11) NOT NULL COMMENT 'Customer ID from OPC',
            `card_id` VARCHAR(100) NOT NULL COMMENT 'Card ID from Checkout API',
            `card_number` VARCHAR(4) NOT NULL COMMENT 'Short Customer Credit Card Number',
            `card_type` VARCHAR(20) NOT NULL COMMENT 'Credit Card Type',
            `card_enabled` BIT NOT NULL DEFAULT 1 COMMENT 'Credit Card Enabled',
            `cko_customer_id` VARCHAR(100) NOT NULL COMMENT 'Customer ID from Checkout API',
          PRIMARY KEY (`entity_id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=0;");
    }

    public function index()
    {
        $this->install();
        $this->load->language('extension/payment/checkoutcom');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate())
        {
            $this->model_setting_setting->editSetting('payment_checkoutcom', $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
        }

        $data['heading_title']                 = $this->language->get('heading_title');
        $data['text_edit']                     = $this->language->get('text_edit');
        $data['text_checkoutcom']              = $this->language->get('text_checkoutcom');
        $data['text_payment']                  = $this->language->get('text_payment');
        $data['text_success']                  = $this->language->get('text_success');
        $data['text_page_title']               = $this->language->get('text_page_title');
        $data['text_status_on']                = $this->language->get('text_status_on');
        $data['text_status_off']               = $this->language->get('text_status_off');
        $data['text_mode_sandbox']             = $this->language->get('text_mode_sandbox');
        $data['text_mode_live']                = $this->language->get('text_mode_live');
        $data['text_auth_only']                = $this->language->get('text_auth_only');
        $data['text_auth_capture']             = $this->language->get('text_auth_capture');
        $data['text_integration_pci']          = $this->language->get('text_integration_pci');
        $data['text_integration_hosted']       = $this->language->get('text_integration_hosted');
        $data['text_integration_embedded']     = $this->language->get('text_integration_embedded');
        $data['text_paymentMode_mix']          = $this->language->get('text_paymentMode_mix');
        $data['text_paymentMode_cards']        = $this->language->get('text_paymentMode_cards');
        $data['text_paymentMode_lp']           = $this->language->get('text_paymentMode_lp');
        $data['text_gateway_timeout']          = $this->language->get('text_gateway_timeout');
        $data['text_button_settings']          = $this->language->get('text_button_settings');
        $data['text_code']                     = $this->language->get('text_code');
        $data['text_symbol']                   = $this->language->get('text_symbol');
        $data['text_yes']                   = $this->language->get('text_yes');
        $data['text_no']                    = $this->language->get('text_no');
        $data['text_embedded_settings']        = $this->language->get('text_embedded_settings');
        $data['text_theme_standard']           = $this->language->get('text_theme_standard');
        $data['text_theme_simple']             = $this->language->get('text_theme_simple');
        $data['text_save_card_no']             = $this->language->get('text_save_card_no');
        $data['text_save_card_yes']            = $this->language->get('text_save_card_yes');

        $data['entry_test_mode']               = $this->language->get('entry_test_mode');
        $data['entry_secret_key']              = $this->language->get('entry_secret_key');
        $data['entry_public_key']              = $this->language->get('entry_public_key');
        $data['entry_payment_mode']            = $this->language->get('entry_payment_mode');
        $data['entry_payment_url']             = $this->language->get('entry_payment_url');
        $data['entry_integration_type']        = $this->language->get('entry_integration_type');
        $data['entry_payment_action']          = $this->language->get('entry_payment_action');
        $data['entry_autocapture_delay']       = $this->language->get('entry_autocapture_delay');
        $data['entry_card_type']               = $this->language->get('entry_card_type');
        $data['entry_gateway_timeout']         = $this->language->get('entry_gateway_timeout');
        $data['entry_successful_order_status'] = $this->language->get('entry_successful_order_status');
        $data['entry_failed_order_status']     = $this->language->get('entry_failed_order_status');
        $data['entry_sort_order']              = $this->language->get('entry_sort_order');
        $data['entry_status']                  = $this->language->get('entry_status');
        $data['entry_sort_order']              = $this->language->get('entry_sort_order');
        $data['entry_gateway_timeout']         = $this->language->get('entry_gateway_timeout');
        $data['entry_logo_url']                = $this->language->get('entry_logo_url');
        $data['entry_theme_color']             = $this->language->get('entry_theme_color');
        $data['entry_button_label']            = $this->language->get('entry_button_label');
        $data['entry_icon_color']              = $this->language->get('entry_icon_color');
        $data['entry_currency_format']         = $this->language->get('entry_currency_format');
        $data['entry_3D_secure']               = $this->language->get('entry_3D_secure');
        $data['entry_attempt_non3D']           = $this->language->get('entry_attempt_non3D');
        $data['entry_skip_risk_check']         = $this->language->get('entry_skip_risk_check');
        $data['entry_title']                   = $this->language->get('entry_title');
        $data['entry_frames_theme']            = $this->language->get('entry_frames_theme');
        $data['entry_custom_css']              = $this->language->get('entry_custom_css');
        $data['entry_save_card']               = $this->language->get('entry_save_card');

        $data['button_save']                   = $this->language->get('button_save');
        $data['button_cancel']                 = $this->language->get('button_cancel');

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['checkoutcom_secret_key'])) {
            $data['error_secret_key'] = $this->error['checkoutcom_secret_key'];
        } else {
            $data['error_secret_key'] = '';
        }

        if (isset($this->error['checkoutcom_public_key'])) {
            $data['error_public_key'] = $this->error['checkoutcom_public_key'];
        } else {
            $data['error_public_key'] = '';
        }

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], 'SSL')
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/payment/checkoutcom', 'user_token=' . $this->session->data['user_token'], 'SSL')
        );

        $data['action'] = $this->url->link('extension/payment/checkoutcom', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

        if (isset($this->request->post['payment_checkoutcom_test_mode'])) {
            $data['payment_checkoutcom_test_mode'] = $this->request->post['payment_checkoutcom_test_mode'];
        } else {
            $data['payment_checkoutcom_test_mode'] = $this->config->get('payment_checkoutcom_test_mode');
        }

        if (isset($this->request->post['payment_checkoutcom_3D_secure'])) {
            $data['payment_checkoutcom_3D_secure'] = $this->request->post['payment_checkoutcom_3D_secure'];
        } else {
            $data['payment_checkoutcom_3D_secure'] = $this->config->get('payment_checkoutcom_3D_secure');
        }

        if (isset($this->request->post['payment_checkoutcom_attempt_non3D'])) {
            $data['payment_checkoutcom_attempt_non3D'] = $this->request->post['payment_checkoutcom_attempt_non3D'];
        } else {
            $data['payment_checkoutcom_attempt_non3D'] = $this->config->get('payment_checkoutcom_attempt_non3D');
        }

        if (isset($this->request->post['payment_checkoutcom_secret_key'])) {
            $data['payment_checkoutcom_secret_key'] = $this->request->post['payment_checkoutcom_secret_key'];
        } else {
            $data['payment_checkoutcom_secret_key'] = $this->config->get('payment_checkoutcom_secret_key');
        }

        if (isset($this->request->post['payment_checkoutcom_public_key'])) {
            $data['payment_checkoutcom_public_key'] = $this->request->post['payment_checkoutcom_public_key'];
        } else {
            $data['payment_checkoutcom_public_key'] = $this->config->get('payment_checkoutcom_public_key');
        }

        if (isset($this->request->post['payment_checkoutcom_payment_mode'])) {
            $data['payment_checkoutcom_payment_mode'] = $this->request->post['payment_checkoutcom_payment_mode'];
        } else {
            $data['payment_checkoutcom_payment_mode'] = $this->config->get('payment_checkoutcom_payment_mode');
        }

        if (isset($this->request->post['payment_checkoutcom_integration_type'])) {
            $data['payment_checkoutcom_integration_type'] = $this->request->post['payment_checkoutcom_integration_type'];
        } else {
            $data['payment_checkoutcom_integration_type'] = $this->config->get('payment_checkoutcom_integration_type');
        }

        if (isset($this->request->post['payment_checkoutcom_integration_pci'])) {
            $data['payment_checkoutcom_integration_pci'] = $this->request->post['payment_checkoutcom_integration_pci'];
        } else {
            $data['payment_checkoutcom_integration_pci'] = $this->config->get('payment_checkoutcom_integration_pci');
        }

        if (isset($this->request->post['checkoutcom_integration_hosted'])) {
            $data['checkoutcom_integration_hosted'] = $this->request->post['checkoutcom_integration_hosted'];
        } else {
            $data['checkoutcom_integration_hosted'] = $this->config->get('checkoutcom_integration_hosted');
        }

        if (isset($this->request->post['payment_checkoutcom_integration_embedded'])) {
            $data['payment_checkoutcom_integration_embedded'] = $this->request->post['payment_checkoutcom_integration_embedded'];
        } else {
            $data['payment_checkoutcom_integration_embedded'] = $this->config->get('payment_checkoutcom_integration_embedded');
        }

        if (isset($this->request->post['payment_checkoutcom_payment_action'])) {
            $data['payment_checkoutcom_payment_action'] = $this->request->post['payment_checkoutcom_payment_action'];
        } else {
            $data['payment_checkoutcom_payment_action'] = $this->config->get('payment_checkoutcom_payment_action');
        }

        if (isset($this->request->post['payment_checkoutcom_autocapture_delay'])) {
            $data['payment_checkoutcom_autocapture_delay'] = $this->request->post['payment_checkoutcom_autocapture_delay'];
        } else {
            $data['payment_checkoutcom_autocapture_delay'] = $this->config->get('payment_checkoutcom_autocapture_delay');
        }

        if (isset($this->request->post['payment_checkoutcom_gateway_timeout'])) {
            $data['payment_checkoutcom_gateway_timeout'] = $this->request->post['payment_checkoutcom_gateway_timeout'];
        } else {
            $data['payment_checkoutcom_gateway_timeout'] = $this->config->get('payment_checkoutcom_gateway_timeout');
        }

        if (isset($this->request->post['payment_checkoutcom_order_status_id'])) {
            $data['payment_checkoutcom_order_status_id'] = $this->request->post['payment_checkoutcom_order_status_id'];
        } else {
            $data['payment_checkoutcom_order_status_id'] = $this->config->get('payment_checkoutcom_order_status_id');
        }

        if (isset($this->request->post['payment_checkoutcom_checkout_failed_order'])) {
            $data['payment_checkoutcom_checkout_failed_order'] = $this->request->post['payment_checkoutcom_checkout_failed_order'];
        } else {
            $data['payment_checkoutcom_checkout_failed_order'] = $this->config->get('payment_checkoutcom_checkout_failed_order');
        }

        if (isset($this->request->post['payment_checkoutcom_save_card'])) {
            $data['payment_checkoutcom_save_card'] = $this->request->post['payment_checkoutcom_save_card'];
        } else {
            $data['payment_checkoutcom_save_card'] = $this->config->get('payment_checkoutcom_save_card');
        }

        if (isset($this->request->post['checkoutcom_logo_url'])) {
            $data['checkoutcom_logo_url'] = $this->request->post['checkoutcom_logo_url'];
        } else {
            $data['checkoutcom_logo_url'] = $this->config->get('checkoutcom_logo_url');
        }

        if (isset($this->request->post['checkoutcom_theme_color'])) {
            $data['checkoutcom_theme_color'] = $this->request->post['checkoutcom_theme_color'];
        } else {
            $data['checkoutcom_theme_color'] = $this->config->get('checkoutcom_theme_color');
        }

        if (isset($this->request->post['checkoutcom_button_label'])) {
            $data['checkoutcom_button_label'] = $this->request->post['checkoutcom_button_label'];
        } else {
            $data['checkoutcom_button_label'] = $this->config->get('checkoutcom_button_label');
        }

        if (isset($this->request->post['checkoutcom_icon_color'])) {
            $data['checkoutcom_icon_color'] = $this->request->post['checkoutcom_icon_color'];
        } else {
            $data['checkoutcom_icon_color'] = $this->config->get('checkoutcom_icon_color');
        }

        if (isset($this->request->post['checkoutcom_currency_format'])) {
            $data['checkoutcom_currency_format'] = $this->request->post['checkoutcom_currency_format'];
        } else {
            $data['checkoutcom_currency_format'] = $this->config->get('checkoutcom_currency_format');
        }

        if (isset($this->request->post['checkoutcom_title'])) {
            $data['checkoutcom_title'] = $this->request->post['checkoutcom_title'];
        } else {
            $data['checkoutcom_title'] = $this->config->get('checkoutcom_title');
        }

        if (isset($this->request->post['payment_checkoutcom_skip_risk_check'])) {
            $data['payment_checkoutcom_skip_risk_check'] = $this->request->post['payment_checkoutcom_skip_risk_check'];
        } else {
            $data['payment_checkoutcom_skip_risk_check'] = $this->config->get('payment_checkoutcom_skip_risk_check');
        }

        $this->load->model('localisation/order_status');

        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        if (isset($this->request->post['payment_checkoutcom_status'])) {
            $data['payment_checkoutcom_status'] = $this->request->post['payment_checkoutcom_status'];
        } else {
            $data['payment_checkoutcom_status'] = $this->config->get('payment_checkoutcom_status');
        }

        if (isset($this->request->post['payment_checkoutcom_sort_order'])) {
            $data['payment_checkoutcom_sort_order'] = $this->request->post['payment_checkoutcom_sort_order'];
        } else {
            $data['payment_checkoutcom_sort_order'] = $this->config->get('payment_checkoutcom_sort_order');
        }

        if (isset($this->request->post['payment_checkoutcom_frames_theme'])) {
            $data['payment_checkoutcom_frames_theme'] = $this->request->post['payment_checkoutcom_frames_theme'];
        } else {
            $data['payment_checkoutcom_frames_theme'] = $this->config->get('payment_checkoutcom_frames_theme');
        }

        if (isset($this->request->post['payment_checkoutcom_custom_css'])) {
            $data['payment_checkoutcom_custom_css'] = $this->request->post['payment_checkoutcom_custom_css'];
        } else {
            $data['payment_checkoutcom_custom_css'] = $this->config->get('payment_checkoutcom_custom_css');
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/payment/checkoutcom', $data));
        
    }


    protected function validate()
    {
        if (!$this->user->hasPermission('modify', 'extension/payment/checkoutcom')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (!$this->request->post['payment_checkoutcom_secret_key']) {
            $this->error['checkoutcom_secret_key'] = $this->language->get('error_secret_key');
        }

        if (!$this->request->post['payment_checkoutcom_public_key']) {
            $this->error['checkoutcom_public_key'] = $this->language->get('error_public_key');
        }

        return !$this->error;
    }
}
