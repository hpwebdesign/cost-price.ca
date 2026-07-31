<?php
class ControllerExtensionModuleCpMerchant extends Controller {

	private $error = array();

	/**
	 * Standard module settings screen — reached via the pencil/edit icon
	 * on Extensions > Modules. This module isn't added to a layout like a
	 * banner, so the form is just a Status toggle plus context.
	 */
	public function index() {
		$this->load->language('extension/module/cp_merchant');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('module_cp_merchant', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
		}

		$data['heading_title'] = $this->language->get('heading_title');
		$data['text_enabled']  = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['error_warning'] = $this->error['warning'] ?? '';

		$data['breadcrumbs'] = array(
			array(
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
			),
			array(
				'text' => $this->language->get('text_extension'),
				'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
			),
			array(
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('extension/module/cp_merchant', 'user_token=' . $this->session->data['user_token'], true)
			)
		);

		$data['action'] = $this->url->link('extension/module/cp_merchant', 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

		$data['module_cp_merchant_status'] = $this->request->post['module_cp_merchant_status'] ?? $this->config->get('module_cp_merchant_status');

		$this->load->model('customer/customer_group');

		$data['customer_groups'] = $this->model_customer_customer_group->getCustomerGroups();

		$data['module_cp_merchant_group_ids'] = $this->request->post['module_cp_merchant_group_ids'] ?? $this->config->get('module_cp_merchant_group_ids');

		if (!$data['module_cp_merchant_group_ids']) {
			$data['module_cp_merchant_group_ids'] = array();
		}

		$data['header']      = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer']      = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/cp_merchant_settings', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/cp_merchant')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}

	/**
	 * GET index.php?route=extension/module/cp_merchant/checkgroup&group_id=X
	 * Lets the customer_form tab-toggle JS ask "is this group a merchant
	 * group?" against the configured setting, instead of a hardcoded id
	 * baked into the OCMOD-injected script.
	 */
	public function checkgroup() {
		$group_id = (int)($this->request->get['group_id'] ?? 0);

		$merchant_group_ids = (array)$this->config->get('module_cp_merchant_group_ids');

		$json = array(
			'is_merchant' => in_array($group_id, array_map('intval', $merchant_group_ids), true)
		);

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * GET index.php?route=extension/module/cp_merchant/form&customer_id=X
	 * Renders the tab fragment loaded into #tab-merchant via $.load()
	 */
	public function form() {
		$this->load->language('extension/module/cp_merchant');

		$customer_id = (int)($this->request->get['customer_id'] ?? 0);

		$data = $this->getApplicationViewData($customer_id);

		$this->response->setOutput($this->load->view('extension/module/cp_merchant_form', $data));
	}

	/**
	 * GET index.php?route=extension/module/cp_merchant/quickedit&customer_id=X
	 * Renders the same ledger-tab stepper design used on the storefront
	 * (account/merchant), as an editable fragment loaded into the Quick
	 * Edit popup from the customer list.
	 */
	public function quickedit() {
		$this->load->language('extension/module/cp_merchant');

		$customer_id = (int)($this->request->get['customer_id'] ?? 0);

		$data = $this->getApplicationViewData($customer_id);

		$data['catalog_url'] = HTTP_CATALOG;

		$this->load->model('customer/customer');
		$customer_info = $this->model_customer_customer->getCustomer($customer_id);
		$data['customer_name'] = $customer_info ? $customer_info['firstname'] . ' ' . $customer_info['lastname'] : '';

		$this->response->setOutput($this->load->view('extension/module/cp_merchant_quickedit', $data));
	}

	protected function getApplicationViewData($customer_id) {
		$this->load->model('extension/module/cp_merchant');

		$application = $customer_id ? $this->model_extension_module_cp_merchant->getApplicationByCustomerId($customer_id) : false;

		$data['customer_id'] = $customer_id;
		$data['save']        = $this->url->link('extension/module/cp_merchant/save', 'user_token=' . $this->session->data['user_token'], true);

		$data['categories_list'] = array(
			'sewing'      => 'Sewing',
			'toys'        => 'Toys',
			'party'       => 'Party Items & Candles',
			'kitchen'     => 'Kitchen',
			'cleaning'    => 'Cleaning Supplies',
			'electronics' => 'Electronic Products',
			'office'      => 'Office Supplies',
			'personal'    => 'Personal Care',
			'soap'        => 'Soap',
			'oral'        => 'Oral Care',
			'shaving'     => 'Shaving',
			'hardware'    => 'Home Hardware',
			'smoking'     => 'Smoking Accessories',
			'incense'     => 'Incense',
		);

		$data['business_types'] = array(
			'corporation'     => 'Corporation',
			'partnership'     => 'Partnership',
			'sole_proprietor' => 'Sole Proprietorship',
			'llc'             => 'LLC',
		);

		$data['statuses'] = array(
			'incomplete'     => 'Incomplete',
			'pending_review' => 'Pending Review',
			'approved'       => 'Approved',
			'rejected'       => 'Rejected',
		);

		$selected_categories = array();
		if (!empty($application['categories'])) {
			$decoded = json_decode($application['categories'], true);
			if (is_array($decoded)) {
				$selected_categories = $decoded;
			}
		}
		$data['categories_selected'] = $selected_categories;

		$fields = array(
			'business_operating_name', 'manager_first_name', 'manager_last_name', 'title_position',
			'phone', 'cell', 'promo_mailing_address',
			'billing_address_1', 'billing_address_2', 'billing_city', 'billing_province', 'billing_postcode',
			'shipping_address_1', 'shipping_address_2', 'shipping_city', 'shipping_province', 'shipping_postcode',
			'business_type', 'legal_business_name', 'legal_business_owner', 'gst_hst_number', 'business_started',
			'category_other', 'questions_comments', 'applicant_name', 'status'
		);

		foreach ($fields as $field) {
			$data[$field] = $application[$field] ?? '';
		}

		if (empty($data['status'])) {
			$data['status'] = 'incomplete';
		}

		$data['shipping_same_as_billing'] = !empty($application['shipping_same_as_billing']);

		$data['has_application'] = !empty($application);

		return $data;
	}

	/**
	 * POST index.php?route=extension/module/cp_merchant/save&user_token=...
	 * Saves from the tab's own Save button (independent from the main
	 * customer form submit — same pattern as the existing Reward/Transaction
	 * tabs in this admin).
	 */
	public function save() {
		$this->load->language('extension/module/cp_merchant');

		$json = array();

		$customer_id = (int)($this->request->post['customer_id'] ?? 0);

		if (!$customer_id) {
			$json['error'] = $this->language->get('error_no_customer');
		}

		if (!$json) {
			$this->load->model('extension/module/cp_merchant');

			$post = $this->request->post;
			$post['status'] = $post['cp_status'] ?? 'incomplete';

			$this->model_extension_module_cp_merchant->editApplication($customer_id, $post);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * POST index.php?route=extension/module/cp_merchant/liststatus&user_token=...
	 * Batched lookup used by the customer list to fill in the Merchant
	 * Status column and decide whether to show the Quick Edit button —
	 * without an OCMOD edit to the core list controller.
	 */
	public function liststatus() {
		$json = array();

		$customer_ids = $this->request->post['customer_ids'] ?? array();

		if (is_array($customer_ids) && $customer_ids) {
			$this->load->model('extension/module/cp_merchant');
			$this->load->model('customer/customer');

			$merchant_group_ids = array_map('intval', (array)$this->config->get('module_cp_merchant_group_ids'));

			foreach ($customer_ids as $customer_id) {
				$customer_id = (int)$customer_id;

				$application = $this->model_extension_module_cp_merchant->getApplicationByCustomerId($customer_id);

				$customer_info = $this->model_customer_customer->getCustomer($customer_id);
				$is_merchant = $customer_info && in_array((int)$customer_info['customer_group_id'], $merchant_group_ids, true);

				$json[$customer_id] = array(
					'status'      => $application ? $application['status'] : '',
					'is_merchant' => $is_merchant
				);
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function install() {
		$this->load->model('extension/module/cp_merchant');
		$this->model_extension_module_cp_merchant->install();
	}

	public function uninstall() {
		$this->load->model('extension/module/cp_merchant');
		$this->model_extension_module_cp_merchant->uninstall();
	}
}
