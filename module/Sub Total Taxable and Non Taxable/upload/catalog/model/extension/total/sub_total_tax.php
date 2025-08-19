<?php
class ModelExtensionTotalSubTotalTax extends Model {
	public function getTotal($total) {
		$this->load->language('extension/total/sub_total_tax');

		$sub_total_tax = $this->cart->getSubTotalTax();

		if (!empty($this->session->data['vouchers'])) {
			foreach ($this->session->data['vouchers'] as $voucher) {
				$sub_total_tax += $voucher['amount'];
			}
		}

		$total['totals'][] = array(
			'code'       => 'sub_total_tax',
			'title'      => $this->language->get('text_sub_total_tax'),
			'value'      => $sub_total_tax,
			'sort_order' => $this->config->get('total_sub_total_tax_sort_order')
		);

	}
}
