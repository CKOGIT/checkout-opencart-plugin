<?php
class ModelExtensionPaymentCheckoutCom extends Model {
    public function getMethod($address, $total) {
        $this->load->language('extension/payment/checkout_com');

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('payment_checkout_com_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");

        if ($this->config->get('payment_checkout_com_total') > $total) {
            $status = false;
        } elseif (!$this->config->get('payment_checkout_com_geo_zone_id')) {
            $status = true;
        } elseif ($query->num_rows) {
            $status = true;
        } else {
            $status = false;
        }

        if (!$this->config->get('payment_checkout_com_secret_key') || !$this->config->get('payment_checkout_com_public_key')) {
            $status = false;
        }

        $method_data = array();

        $payment_title = $this->config->get('payment_checkout_com_payment_title') ? $this->config->get('payment_checkout_com_payment_title') : 'Pay by Card with Checkout.com';

        $card_icons_img = '';

        if ($this->config->get('payment_checkout_com_display_card_icons_status') && $this->config->get('payment_checkout_com_card_icons')) {
            foreach ($this->config->get('payment_checkout_com_card_icons') as $card_icon) {
                $card_icons_img .= ' <img src="catalog/view/theme/default/image/checkout_com/' . $card_icon . '.svg" />';
            }
        }

        if ($card_icons_img) {
            $payment_title .= '<br />'.  $card_icons_img;
        }

        if ($status) {
            $method_data = array(
                'code'       => 'checkout_com',
                'title'      => $payment_title,
                'terms'      => '',
                'sort_order' => $this->config->get('payment_checkout_com_sort_order')
            );
        }

        return $method_data;
    }
    
    public function addOrderHistory($order_id, $order_status_id, $comment) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "order_history SET order_id = '" . (int)$order_id . "', comment = '" . $this->db->escape($comment) . "', order_status_id = '" . (int)$order_status_id . "', notify = '0', date_added = NOW()");
    }
    
    public function getOrderHistory($order_id, $order_status_id, $comment) {
        $query = $this->db->query("SELECT order_id FROM " . DB_PREFIX . "order_history WHERE order_id = '" . (int)$order_id . "' AND order_status_id = '" . (int)$order_status_id . "' AND comment = '" . $this->db->escape($comment) . "'");
    
        return $query->row;
    }
    
    public function getCheckoutComOrder($order_id, $transaction_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "checkout_com_order WHERE order_id = '" . (int)$order_id . "' AND checkout_com_transaction_id = '" . $this->db->escape($transaction_id) . "'");

        return $query->row;
    }

    public function addOrder($order_info) {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "checkout_com_order` SET `order_id` = '" . (int)$order_info['order_id'] . "', `checkout_com_transaction_id` = '" . $this->db->escape($order_info['checkout_com_transaction_id']) . "', `currency_code` = '" . $this->db->escape($order_info['currency_code']) . "', `total` = '" . $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false) . "', `date_added` = NOW(), `date_modified` = NOW()");
    
        return $this->db->getLastId();
    }

    public function addTransaction($checkout_com_order_id, $type, $amount) {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "checkout_com_order_transaction` SET `checkout_com_order_id` = '" . (int)$checkout_com_order_id . "', `date_added` = NOW(), `type` = '" . $this->db->escape($type) . "', `amount` = '" . (float)$amount . "'");
    }

    public function getCard($card_id) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "checkout_com_card` WHERE `checkout_com_card_id` = '" . (int)$card_id . "' AND customer_id = '" . (int)$this->customer->getId() . "'");

        return $query->row;
    }

    public function addCard($card_data) {
        $this->db->query("INSERT into `" . DB_PREFIX . "checkout_com_card` SET customer_id = '" . $this->db->escape($card_data['customer_id']) . "', digits = '" . $this->db->escape($card_data['digits']) . "', expiry = '" . $this->db->escape($card_data['expiry']) . "', source_id = '" . $this->db->escape($card_data['source_id']) . "', scheme = '" . $this->db->escape($card_data['scheme']) . "'");
    }

    public function deleteCard($card_id) {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "checkout_com_card` WHERE `checkout_com_card_id` = '" . (int)$card_id . "' AND customer_id = '" . (int)$this->customer->getId() . "'");
    }

    public function getCards($customer_id) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "checkout_com_card` WHERE `customer_id` = '" . (int)$customer_id . "' ORDER BY checkout_com_card_id");

        $card_data = array();
    
        foreach ($query->rows as $row) {
            $card_data[] = array(
                'card_id'      => $row['checkout_com_card_id'],
                'customer_id'  => $row['customer_id'],
                'source_id'    => $row['source_id'],
                'digits'       => '**** ' . $row['digits'],
                'expiry'       => $row['expiry'],
                'scheme'       => $row['scheme']
            );
        }

        return $card_data;
    }

    public function editOrderPayment($order_id, $payment_method) {
        $this->load->language('extension/payment/checkout_com');
        
        $payment_method = $payment_method . ' (' . $this->language->get('text_checkout_com') . ')';
        
        $this->db->query("UPDATE " . DB_PREFIX . "order SET payment_method = '" . $this->db->escape($payment_method) . "' WHERE order_id = '" . (int)$order_id . "'");
    }

    public function updatePaymentMethodName($order_id) {
        $this->load->language('extension/payment/checkout_com');

        $payment_title = $this->config->get('payment_checkout_com_payment_title') ? $this->config->get('payment_checkout_com_payment_title') : 'Pay by Card with Checkout.com';

        $this->db->query("UPDATE `" . DB_PREFIX . "order` SET payment_method = '" . $this->db->escape($payment_title) . "' WHERE order_id = '" . (int)$order_id . "'");
    }
}
