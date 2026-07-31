<?php
class ControllerAccountMerchant extends Controller {

	private $error = array();

	public function index() {
		$this->load->language('account/merchant');

		// Must be logged in
		if (!$this->customer->isLogged()) {
			$this->session->data['redirect'] = $this->url->link('account/merchant', '', true);

			$this->response->redirect($this->url->link('account/login', '', true));
		}

		// Must be in one of the configured merchant customer groups
		// (Admin > Extensions > Modules > Merchant Application) — everyone
		// else has no reason to see this page
		if (!$this->isMerchantGroup((int)$this->customer->getGroupId())) {
			$this->response->redirect($this->url->link('account/account', '', true));
		}

		$this->load->model('account/merchant');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_account_merchant->saveApplication($this->customer->getId(), $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('account/account', '', true));
		}

		$this->document->setTitle($this->language->get('heading_title'));

		$this->response->setOutput($this->load->view('account/merchant', $this->getViewData()));
	}

	protected function isMerchantGroup($group_id) {
		$merchant_group_ids = (array)$this->config->get('module_cp_merchant_group_ids');

		return in_array($group_id, array_map('intval', $merchant_group_ids), true);
	}

	protected function getViewData() {
		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_step_contact']  = $this->language->get('text_step_contact');
		$data['text_step_billing']  = $this->language->get('text_step_billing');
		$data['text_step_shipping'] = $this->language->get('text_step_shipping');
		$data['text_step_business'] = $this->language->get('text_step_business');
		$data['text_step_category'] = $this->language->get('text_step_category');
		$data['text_step_review']   = $this->language->get('text_step_review');

		$data['breadcrumbs'] = array(
			array(
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/home')
			),
			array(
				'text' => $this->language->get('text_account'),
				'href' => $this->url->link('account/account', '', true)
			),
			array(
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('account/merchant', '', true)
			)
		);

		$data['action'] = $this->url->link('account/merchant', '', true);
		$data['back']   = $this->url->link('account/account', '', true);

		$data['categories'] = array(
			'sewing'      => $this->language->get('text_cat_sewing'),
			'toys'        => $this->language->get('text_cat_toys'),
			'party'       => $this->language->get('text_cat_party'),
			'kitchen'     => $this->language->get('text_cat_kitchen'),
			'cleaning'    => $this->language->get('text_cat_cleaning'),
			'electronics' => $this->language->get('text_cat_electronics'),
			'office'      => $this->language->get('text_cat_office'),
			'personal'    => $this->language->get('text_cat_personal'),
			'soap'        => $this->language->get('text_cat_soap'),
			'oral'        => $this->language->get('text_cat_oral'),
			'shaving'     => $this->language->get('text_cat_shaving'),
			'hardware'    => $this->language->get('text_cat_hardware'),
			'smoking'     => $this->language->get('text_cat_smoking'),
			'incense'     => $this->language->get('text_cat_incense'),
		);

		$data['business_types'] = array(
			'corporation'       => $this->language->get('text_biz_corporation'),
			'partnership'       => $this->language->get('text_biz_partnership'),
			'sole_proprietor'   => $this->language->get('text_biz_sole_proprietor'),
			'llc'               => $this->language->get('text_biz_llc'),
		);

		// Repopulate from POST first (validation bounce), else from any
		// existing saved/partial application, else empty defaults
		$existing = $this->model_account_merchant->getApplication($this->customer->getId());

		$saved_categories = array();
		if (!empty($existing['categories'])) {
			$decoded = json_decode($existing['categories'], true);
			if (is_array($decoded)) {
				$saved_categories = $decoded;
			}
		}

		$fields = array(
			'business_operating_name', 'manager_first_name', 'manager_last_name',
			'title_position', 'phone', 'cell', 'promo_mailing_address',
			'billing_address_1', 'billing_address_2', 'billing_city', 'billing_province', 'billing_postcode',
			'shipping_address_1', 'shipping_address_2', 'shipping_city', 'shipping_province', 'shipping_postcode',
			'business_type', 'legal_business_name', 'legal_business_owner', 'gst_hst_number', 'business_started',
			'category_other', 'questions_comments', 'applicant_name'
		);

		foreach ($fields as $field) {
			if (isset($this->request->post[$field])) {
				$data[$field] = $this->request->post[$field];
			} elseif (!empty($existing[$field])) {
				$data[$field] = $existing[$field];
			} else {
				$data[$field] = '';
			}
		}

		$data['shipping_same_as_billing'] = isset($this->request->post['shipping_same_as_billing'])
			? (bool)$this->request->post['shipping_same_as_billing']
			: (!empty($existing['shipping_same_as_billing']));

		$data['categories_selected'] = !empty($this->request->post['categories'])
			? $this->request->post['categories']
			: $saved_categories;

		$data['error'] = $this->error;

		$data['customer_email'] = $this->customer->getEmail();

		$data['column_left']  = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top']  = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer']       = $this->load->controller('common/footer');
		$data['header']       = $this->load->controller('common/header');

		return $data;
	}

	protected function validateForm() {
		if ((utf8_strlen(trim((string)($this->request->post['business_operating_name'] ?? ''))) < 1)) {
			$this->error['business_operating_name'] = $this->language->get('error_business_operating_name');
		}

		if (utf8_strlen(trim((string)($this->request->post['manager_first_name'] ?? ''))) < 1) {
			$this->error['manager_first_name'] = $this->language->get('error_manager_first_name');
		}

		if (utf8_strlen(trim((string)($this->request->post['manager_last_name'] ?? ''))) < 1) {
			$this->error['manager_last_name'] = $this->language->get('error_manager_last_name');
		}

		if (utf8_strlen(trim((string)($this->request->post['title_position'] ?? ''))) < 1) {
			$this->error['title_position'] = $this->language->get('error_title_position');
		}

		if (utf8_strlen(trim((string)($this->request->post['phone'] ?? ''))) < 3) {
			$this->error['phone'] = $this->language->get('error_phone');
		}

		if (utf8_strlen(trim((string)($this->request->post['promo_mailing_address'] ?? ''))) < 1) {
			$this->error['promo_mailing_address'] = $this->language->get('error_promo_mailing_address');
		}

		if (utf8_strlen(trim((string)($this->request->post['billing_address_1'] ?? ''))) < 1) {
			$this->error['billing_address_1'] = $this->language->get('error_billing_address_1');
		}

		if (utf8_strlen(trim((string)($this->request->post['billing_city'] ?? ''))) < 1) {
			$this->error['billing_city'] = $this->language->get('error_billing_city');
		}

		if (utf8_strlen(trim((string)($this->request->post['billing_province'] ?? ''))) < 1) {
			$this->error['billing_province'] = $this->language->get('error_billing_province');
		}

		if (utf8_strlen(trim((string)($this->request->post['billing_postcode'] ?? ''))) < 1) {
			$this->error['billing_postcode'] = $this->language->get('error_billing_postcode');
		}

		$same_as_billing = !empty($this->request->post['shipping_same_as_billing']);

		if (!$same_as_billing) {
			if (utf8_strlen(trim((string)($this->request->post['shipping_address_1'] ?? ''))) < 1) {
				$this->error['shipping_address_1'] = $this->language->get('error_shipping_address_1');
			}

			if (utf8_strlen(trim((string)($this->request->post['shipping_city'] ?? ''))) < 1) {
				$this->error['shipping_city'] = $this->language->get('error_shipping_city');
			}

			if (utf8_strlen(trim((string)($this->request->post['shipping_province'] ?? ''))) < 1) {
				$this->error['shipping_province'] = $this->language->get('error_shipping_province');
			}

			if (utf8_strlen(trim((string)($this->request->post['shipping_postcode'] ?? ''))) < 1) {
				$this->error['shipping_postcode'] = $this->language->get('error_shipping_postcode');
			}
		}

		if (empty($this->request->post['business_type'])) {
			$this->error['business_type'] = $this->language->get('error_business_type');
		}

		if (utf8_strlen(trim((string)($this->request->post['legal_business_name'] ?? ''))) < 1) {
			$this->error['legal_business_name'] = $this->language->get('error_legal_business_name');
		}

		if (utf8_strlen(trim((string)($this->request->post['legal_business_owner'] ?? ''))) < 1) {
			$this->error['legal_business_owner'] = $this->language->get('error_legal_business_owner');
		}

		if (utf8_strlen(trim((string)($this->request->post['gst_hst_number'] ?? ''))) < 1) {
			$this->error['gst_hst_number'] = $this->language->get('error_gst_hst_number');
		}

		if (utf8_strlen(trim((string)($this->request->post['business_started'] ?? ''))) < 1) {
			$this->error['business_started'] = $this->language->get('error_business_started');
		}

		if (utf8_strlen(trim((string)($this->request->post['applicant_name'] ?? ''))) < 1) {
			$this->error['applicant_name'] = $this->language->get('error_applicant_name');
		}

		return !$this->error;
	}
}
