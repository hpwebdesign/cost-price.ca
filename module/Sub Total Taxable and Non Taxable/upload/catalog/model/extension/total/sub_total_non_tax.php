<?php
class ModelExtensionTotalSubTotalNonTax extends Model {
	public function getTotal($total) {
		$this->load->language('extension/total/sub_total_non_tax');

		$sub_total_non_tax = $this->cart->getSubTotalNonTax();

		if (!empty($this->session->data['vouchers'])) {
			foreach ($this->session->data['vouchers'] as $voucher) {
				$sub_total_non_tax += $voucher['amount'];
			}
		}

		$total['totals'][] = array(
			'code'       => 'sub_total_non_tax',
			'title'      => $this->language->get('text_sub_total_non_tax'),
			'value'      => $sub_total_non_tax,
			'sort_order' => $this->config->get('total_sub_total_non_tax_sort_order')
		);

	}
}
