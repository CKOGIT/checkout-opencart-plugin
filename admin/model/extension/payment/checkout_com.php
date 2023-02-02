<?php
class ModelExtensionPaymentCheckoutCom extends Model {
    public function addOrderHistory($order_id, $order_status_id, $comment) {
        $this->db->query("UPDATE `" . DB_PREFIX . "order` SET order_status_id = '" . (int)$order_status_id . "' WHERE order_id = '" . (int)$order_id . "'");
        $this->db->query("INSERT INTO " . DB_PREFIX . "order_history SET order_id = '" . (int)$order_id . "', comment = '" . $this->db->escape($comment) . "', order_status_id = '" . (int)$order_status_id . "', notify = '0', date_added = NOW()");
    }
    
    public function getOrderHistory($order_id, $order_status_id, $comment) {
        $query = $this->db->query("SELECT order_id FROM " . DB_PREFIX . "order_history WHERE order_id = '" . (int)$order_id . "' AND order_status_id = '" . (int)$order_status_id . "' AND comment = '" . $this->db->escape($comment) . "'");
    
        return $query->row;
    }

    public function getOrder($order_id) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "checkout_com_order` WHERE `order_id` = '" . (int)$order_id . "' LIMIT 1");

        if ($query->num_rows) {
            $query->row['transactions'] = $this->getTransactions($query->row['checkout_com_order_id']);

            return $query->row;
        } else {
            return false;
        }
    }
    
    public function addTransaction($checkout_com_order_id, $type, $amount) {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "checkout_com_order_transaction` SET `checkout_com_order_id` = '" . (int)$checkout_com_order_id . "', `date_added` = NOW(), `type` = '" . $this->db->escape($type) . "', `amount` = '" . (float)$amount . "'");
    }
    
    public function getTransaction($checkout_com_order_id, $status, $amount) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "checkout_com_order_transaction WHERE checkout_com_order_id = '" . (int)$checkout_com_order_id . "' AND type = '" . $this->db->escape($status) . "' AND amount = '" . (float)$amount . "'");
        
        return $query->row;
    }

    private function getTransactions($checkout_com_order_id) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "checkout_com_order_transaction` WHERE `checkout_com_order_id` = '" . (int)$checkout_com_order_id . "' ORDER BY checkout_com_order_transaction_id ASC");
        
        return $query->rows;
    }

    public function install() {
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "checkout_com_order` (
          `checkout_com_order_id` int(11) NOT NULL AUTO_INCREMENT,
          `order_id` int(11) NOT NULL,
          `checkout_com_transaction_id` varchar(30) NOT NULL,
          `currency_code` CHAR(3) NOT NULL,
          `total` DECIMAL (10,2) NOT NULL,
          `date_added` DATETIME NOT NULL,
          `date_modified` DATETIME NOT NULL,
          PRIMARY KEY (`checkout_com_order_id`)
        ) ENGINE=MyISAM DEFAULT COLLATE=utf8_general_ci;");

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "checkout_com_order_transaction` (
          `checkout_com_order_transaction_id` INT(11) NOT NULL AUTO_INCREMENT,
          `checkout_com_order_id` INT(11) NOT NULL,
          `date_added` DATETIME NOT NULL,
          `type` ENUM('cardverifydeclined', 'cardverified', 'approved', 'riskmatched', 'pending', 'declined', 'expired', 'canceled', 'voided', 'voiddeclined', 'captured', 'capturedeclined', 'capturepending', 'refunded', 'refunddeclined', 'refundpending', 'chargeback') DEFAULT NULL,
          `amount` DECIMAL (10,2) NOT NULL,
          PRIMARY KEY (`checkout_com_order_transaction_id`)
        ) ENGINE=MyISAM DEFAULT COLLATE=utf8_general_ci;");

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "checkout_com_card` (
          `checkout_com_card_id` INT(11) NOT NULL AUTO_INCREMENT,
          `customer_id` INT(11) NOT NULL,
          `source_id` VARCHAR(50) NOT NULL,
          `digits` VARCHAR(4) NOT NULL,
          `expiry` VARCHAR(8) NOT NULL,
          `scheme` VARCHAR(50) NOT NULL,
          PRIMARY KEY (`checkout_com_card_id`)
        ) ENGINE=MyISAM DEFAULT COLLATE=utf8_general_ci;");
    }

    public function uninstall() {
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "checkout_com_order`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "checkout_com_order_transaction`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "checkout_com_card`");
    }
}