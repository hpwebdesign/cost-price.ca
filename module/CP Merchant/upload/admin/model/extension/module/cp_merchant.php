<?php
class ModelExtensionModuleCpMerchant extends Model {

	/**
	 * Creates the merchant application table.
	 * Called from this module's install controller.
	 */
	public function install() {
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "cp_merchant_application` (
			`merchant_application_id` INT(11) NOT NULL AUTO_INCREMENT,
			`customer_id` INT(11) NOT NULL,
			`business_operating_name` VARCHAR(255) NOT NULL DEFAULT '',
			`manager_first_name` VARCHAR(64) NOT NULL DEFAULT '',
			`manager_last_name` VARCHAR(64) NOT NULL DEFAULT '',
			`title_position` VARCHAR(128) NOT NULL DEFAULT '',
			`phone` VARCHAR(32) NOT NULL DEFAULT '',
			`cell` VARCHAR(32) NOT NULL DEFAULT '',
			`promo_mailing_address` TEXT NOT NULL,
			`billing_address_1` VARCHAR(255) NOT NULL DEFAULT '',
			`billing_address_2` VARCHAR(255) NOT NULL DEFAULT '',
			`billing_city` VARCHAR(128) NOT NULL DEFAULT '',
			`billing_province` VARCHAR(128) NOT NULL DEFAULT '',
			`billing_postcode` VARCHAR(32) NOT NULL DEFAULT '',
			`shipping_same_as_billing` TINYINT(1) NOT NULL DEFAULT 0,
			`shipping_address_1` VARCHAR(255) NOT NULL DEFAULT '',
			`shipping_address_2` VARCHAR(255) NOT NULL DEFAULT '',
			`shipping_city` VARCHAR(128) NOT NULL DEFAULT '',
			`shipping_province` VARCHAR(128) NOT NULL DEFAULT '',
			`shipping_postcode` VARCHAR(32) NOT NULL DEFAULT '',
			`business_type` VARCHAR(32) NOT NULL DEFAULT '',
			`legal_business_name` VARCHAR(255) NOT NULL DEFAULT '',
			`legal_business_owner` VARCHAR(255) NOT NULL DEFAULT '',
			`gst_hst_number` VARCHAR(32) NOT NULL DEFAULT '',
			`business_started` VARCHAR(7) NOT NULL DEFAULT '',
			`categories` TEXT NOT NULL,
			`category_other` VARCHAR(255) NOT NULL DEFAULT '',
			`questions_comments` TEXT NOT NULL,
			`applicant_name` VARCHAR(255) NOT NULL DEFAULT '',
			`status` VARCHAR(20) NOT NULL DEFAULT 'incomplete',
			`date_added` DATETIME NOT NULL,
			`date_modified` DATETIME NOT NULL,
			PRIMARY KEY (`merchant_application_id`),
			UNIQUE KEY `customer_id` (`customer_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8");
	}

	public function uninstall() {
		// Intentionally left as a no-op: application data should survive
		// module disable/uninstall. Drop manually if a full wipe is needed:
		// $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "cp_merchant_application`");
	}

	/**
	 * Fetch one application by customer_id, for the Customer Form tab.
	 */
	public function getApplicationByCustomerId($customer_id) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "cp_merchant_application` WHERE customer_id = '" . (int)$customer_id . "'");

		return $query->row;
	}

	/**
	 * Admin-side save: called from the Customer Form tab. Full overwrite of
	 * editable fields, including status (approve/reject/etc).
	 */
	public function editApplication($customer_id, $data) {
		$existing = $this->getApplicationByCustomerId($customer_id);

		$fields = array(
			'business_operating_name' => (string)($data['business_operating_name'] ?? ''),
			'manager_first_name'      => (string)($data['manager_first_name'] ?? ''),
			'manager_last_name'       => (string)($data['manager_last_name'] ?? ''),
			'title_position'          => (string)($data['title_position'] ?? ''),
			'phone'                   => (string)($data['phone'] ?? ''),
			'cell'                    => (string)($data['cell'] ?? ''),
			'promo_mailing_address'   => (string)($data['promo_mailing_address'] ?? ''),
			'billing_address_1'       => (string)($data['billing_address_1'] ?? ''),
			'billing_address_2'       => (string)($data['billing_address_2'] ?? ''),
			'billing_city'            => (string)($data['billing_city'] ?? ''),
			'billing_province'        => (string)($data['billing_province'] ?? ''),
			'billing_postcode'        => (string)($data['billing_postcode'] ?? ''),
			'shipping_same_as_billing'=> !empty($data['shipping_same_as_billing']) ? 1 : 0,
			'shipping_address_1'      => (string)($data['shipping_address_1'] ?? ''),
			'shipping_address_2'      => (string)($data['shipping_address_2'] ?? ''),
			'shipping_city'           => (string)($data['shipping_city'] ?? ''),
			'shipping_province'       => (string)($data['shipping_province'] ?? ''),
			'shipping_postcode'       => (string)($data['shipping_postcode'] ?? ''),
			'business_type'           => (string)($data['business_type'] ?? ''),
			'legal_business_name'     => (string)($data['legal_business_name'] ?? ''),
			'legal_business_owner'    => (string)($data['legal_business_owner'] ?? ''),
			'gst_hst_number'          => (string)($data['gst_hst_number'] ?? ''),
			'business_started'        => (string)($data['business_started'] ?? ''),
			'categories'              => json_encode(!empty($data['categories']) ? array_values($data['categories']) : array()),
			'category_other'          => (string)($data['category_other'] ?? ''),
			'questions_comments'      => (string)($data['questions_comments'] ?? ''),
			'applicant_name'          => (string)($data['applicant_name'] ?? ''),
			'status'                  => (string)($data['status'] ?? 'incomplete'),
		);

		if ($existing) {
			$sql = "UPDATE `" . DB_PREFIX . "cp_merchant_application` SET ";
			$set = array();
			foreach ($fields as $col => $val) {
				$set[] = "`" . $col . "` = '" . $this->db->escape($val) . "'";
			}
			$set[] = "date_modified = NOW()";
			$sql .= implode(', ', $set) . " WHERE customer_id = '" . (int)$customer_id . "'";
			$this->db->query($sql);
		} else {
			$cols = array('customer_id');
			$vals = array((int)$customer_id);
			foreach ($fields as $col => $val) {
				$cols[] = $col;
				$vals[] = "'" . $this->db->escape($val) . "'";
			}
			$sql = "INSERT INTO `" . DB_PREFIX . "cp_merchant_application` (`" . implode('`, `', $cols) . "`, date_added, date_modified) VALUES (" . implode(', ', $vals) . ", NOW(), NOW())";
			$this->db->query($sql);
		}
	}
}
