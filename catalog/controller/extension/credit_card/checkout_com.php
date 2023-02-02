<?php
class ControllerExtensionCreditCardCheckoutCom extends Controller {
    public function index() {
        if (!$this->customer->isLogged()) {
            $this->session->data['redirect'] = $this->url->link('extension/credit_card/checkout_com', '', true);

            $this->response->redirect($this->url->link('account/login', '', true));
        }

        $data = $this->load->language('extension/credit_card/checkout_com');

        $this->load->model('extension/payment/checkout_com');

        $this->document->setTitle($this->language->get('heading_title'));

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home')
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_account'),
            'href' => $this->url->link('account/account', '', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/credit_card/checkout_com', '', true)
        );

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        if (isset($this->session->data['error_warning'])) {
            $data['error_warning'] = $this->session->data['error_warning'];
            unset($this->session->data['error_warning']);
        } else {
            $data['error_warning'] = '';
        }

        if ($this->config->get('payment_checkout_com_card')) {
            $data['cards'] = $this->model_extension_payment_checkout_com->getCards($this->customer->getId());
            $data['delete'] = $this->url->link('extension/credit_card/checkout_com/delete', 'card_id=', true);

            if (isset($this->request->get['page'])) {
                $page = $this->request->get['page'];
            } else {
                $page = 1;
            }

            $cards_total = count($data['cards']);

            $pagination = new Pagination();
            $pagination->total = $cards_total;
            $pagination->page = $page;
            $pagination->limit = 10;
            $pagination->url = $this->url->link('extension/credit_card/checkout_com', 'page={page}', true);

            $data['pagination'] = $pagination->render();

            $data['results'] = sprintf($this->language->get('text_pagination'), ($cards_total) ? (($page - 1) * 10) + 1 : 0, ((($page - 1) * 10) > ($cards_total - 10)) ? $cards_total : ((($page - 1) * 10) + 10), $cards_total, ceil($cards_total / 10));
        } else {
            $data['cards'] = false;
            $data['pagination'] = false;
            $data['results'] = false;
        }

        $data['back'] = $this->url->link('account/account', '', true);

        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        $this->response->setOutput($this->load->view('extension/credit_card/checkout_com_list', $data));
    }

    public function delete() {
        $this->load->language('extension/credit_card/checkout_com');
        $this->load->model('extension/payment/checkout_com');

        $this->model_extension_payment_checkout_com->deleteCard($this->request->get['card_id']);

        $this->response->redirect($this->url->link('extension/credit_card/checkout_com', '', true));
    }
}
