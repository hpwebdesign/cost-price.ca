<?php
class ModelAccountMerchant extends Model {

	public function getApplication($customer_id) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "cp_merchant_application` WHERE customer_id = '" . (int)$customer_id . "'");

		return $query->row;
	}

	/**
	 * Customer-facing save. Always sets status to pending_review on submit —
	 * customers cannot set approved/rejected themselves.
	 */
	public function saveApplication($customer_id, $data) {
		$existing = $this->getApplication($customer_id);

		$same_as_billing = !empty($data['shipping_same_as_billing']) ? 1 : 0;

		if ($same_as_billing) {
			$ship_addr1    = $data['billing_address_1'] ?? '';
			$ship_addr2    = $data['billing_address_2'] ?? '';
			$ship_city     = $data['billing_city'] ?? '';
			$ship_province = $data['billing_province'] ?? '';
			$ship_postcode = $data['billing_postcode'] ?? '';
		} else {
			$ship_addr1    = $data['shipping_address_1'] ?? '';
			$ship_addr2    = $data['shipping_address_2'] ?? '';
			$ship_city     = $data['shipping_city'] ?? '';
			$ship_province = $data['shipping_province'] ?? '';
			$ship_postcode = $data['shipping_postcode'] ?? '';
		}

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
			'shipping_same_as_billing'=> $same_as_billing,
			'shipping_address_1'      => (string)$ship_addr1,
			'shipping_address_2'      => (string)$ship_addr2,
			'shipping_city'           => (string)$ship_city,
			'shipping_province'       => (string)$ship_province,
			'shipping_postcode'       => (string)$ship_postcode,
			'business_type'           => (string)($data['business_type'] ?? ''),
			'legal_business_name'     => (string)($data['legal_business_name'] ?? ''),
			'legal_business_owner'    => (string)($data['legal_business_owner'] ?? ''),
			'gst_hst_number'          => (string)($data['gst_hst_number'] ?? ''),
			'business_started'        => (string)($data['business_started'] ?? ''),
			'categories'              => json_encode(!empty($data['categories']) ? array_values($data['categories']) : array()),
			'category_other'          => (string)($data['category_other'] ?? ''),
			'questions_comments'      => (string)($data['questions_comments'] ?? ''),
			'applicant_name'          => (string)($data['applicant_name'] ?? ''),
			'status'                  => 'pending_review',
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

	/**
	 * Table doesn't exist yet on a fresh install-order-mismatch (module not
	 * installed before this controller is hit). Kept defensive so the
	 * storefront never fatals for the customer.
	 */
	public function tableExists() {
		$query = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "cp_merchant_application'");

		return (bool)$query->num_rows;
	}
}
