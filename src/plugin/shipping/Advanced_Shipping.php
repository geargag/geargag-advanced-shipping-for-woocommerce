<?php

namespace vnh_namespace\shipping;

use vnh_namespace\tools\Shipping_Base;
use WC_Tax;

class Advanced_Shipping extends Shipping_Base {
	const METHOD_ID = 'geargag_advanced_shipping';

	public $available_rates = [];

	private $order_handling_fee;
	private $max_shipping_cost;
	private $calculation_type;
	private $min_cost;
	private $max_cost;
	private $rates_table;

	public function setup() {
		$this->id = self::METHOD_ID;
		$this->method_title = __('GearGag Advanced Shipping', 'vnh_textdomain');
		$this->method_description = __('GearGag Advanced Shipping are dynamic rates based on a number of cart conditions.', 'vnh_textdomain');
		$this->supports = ['shipping-zones', 'instance-settings'];
	}

	public function init() {
		// Define user set variables.
		$this->title = $this->get_option('title');
		$this->tax_status = $this->get_option('tax_status');
		$this->fee = $this->get_option('handling_fee');
		$this->order_handling_fee = $this->get_option('order_handling_fee');
		$this->max_shipping_cost = $this->get_option('max_shipping_cost');
		$this->calculation_type = $this->get_option('calculation_type');
		$this->min_cost = $this->get_option('min_cost');
		$this->max_cost = $this->get_option('max_cost');
		$this->rates_table = $this->get_option('rates_table');
	}

	public function init_form_fields() {
		$this->instance_form_fields = [
			'title' => [
				'title' => __('Method Title', 'vnh_textdomain'),
				'type' => 'text',
				'description' => __('This controls the title which the user sees during checkout.', 'vnh_textdomain'),
				'default' => __('Advanced Shipping', 'vnh_textdomain'),
				'desc_tip' => true,
			],
			'order_handling_fee' => [
				'title' => __('Handling Fee', 'vnh_textdomain'),
				'type' => 'number',
				'desc_tip' => __(
					'Enter an amount, e.g. 2.50. Leave blank to disable. This cost is applied once for the order as a whole.',
					'vnh_textdomain'
				),
				'default' => '',
				'placeholder' => __('n/a', 'vnh_textdomain'),
				'custom_attributes' => [
					'step' => '0.01',
				],
			],
			'max_shipping_cost' => [
				'title' => __('Maximum Shipping Cost', 'vnh_textdomain'),
				'type' => 'number',
				'desc_tip' => __(
					'Maximum cost that the customer will pay after all the shipping rules have been applied. If the shipping cost calculated is bigger than this value, this cost will be the one shown.',
					'vnh_textdomain'
				),
				'default' => '',
				'placeholder' => __('n/a', 'vnh_textdomain'),
				'custom_attributes' => [
					'step' => '0.01',
				],
			],
			'rates' => [
				'title' => __('Rates', 'vnh_textdomain'),
				'type' => 'title',
				'description' => __('This is where you define your table rates which are applied to an order.', 'vnh_textdomain'),
				'default' => '',
			],
			'calculation_type' => [
				'title' => __('Calculation Type', 'vnh_textdomain'),
				'type' => 'select',
				'description' => __(
					'Per order rates will offer the customer all matching rates. Calculated rates will sum all matching rates and provide a single total.',
					'vnh_textdomain'
				),
				'default' => '',
				'desc_tip' => true,
				'options' => [
					'' => __('Per order', 'vnh_textdomain'),
					'item' => __('Calculated rates per item', 'vnh_textdomain'),
				],
			],
			'handling_fee' => [
				'title' => __('Handling Fee Per [item]', 'vnh_textdomain'),
				'type' => 'number',
				'desc_tip' => __(
					'Handling fee. Enter an amount, e.g. 2.50, or a percentage, e.g. 5%. Leave blank to disable. Applied based on the "Calculation Type" chosen below.',
					'vnh_textdomain'
				),
				'default' => '',
				'placeholder' => __('n/a', 'vnh_textdomain'),
				'custom_attributes' => [
					'min' => '0',
					'step' => '0.01',
				],
			],
			'min_cost' => [
				'title' => __('Minimum Cost Per [item]', 'vnh_textdomain'),
				'type' => 'number',
				'desc_tip' => true,
				'description' => __(
					'Minimum cost for this shipping method (optional). If the cost is lower, this minimum cost will be enforced.',
					'vnh_textdomain'
				),
				'default' => '',
				'placeholder' => __('n/a', 'vnh_textdomain'),
				'custom_attributes' => [
					'min' => '0',
					'step' => '0.01',
				],
			],
			'max_cost' => [
				'title' => __('Maximum Cost Per [item]', 'vnh_textdomain'),
				'type' => 'number',
				'desc_tip' => true,
				'description' => __(
					'Maximum cost for this shipping method (optional). If the cost is higher, this maximum cost will be enforced.',
					'vnh_textdomain'
				),
				'default' => '',
				'placeholder' => __('n/a', 'vnh_textdomain'),
				'custom_attributes' => [
					'min' => '0',
					'step' => '0.01',
				],
			],
			'rates_table' => [
				'title' => __('Rates Table', 'vnh_textdomain'),
				'type' => 'repeater',
				'description' => __(
					'Cost excluding tax (per product) for Guys Tee products. Enter an amount, e.g. 2.50. Entering an amount here will apply a global shipping cost for all products, effectively disabling all other shipping methods',
					'vnh_textdomain'
				),
				'desc_tip' => true,
				'options' => [
					'add_button' => __('Add Row', 'vnh_textdomain'),
					'remove_button' => __('Remove', 'vnh_textdomain'),
				],
				'children' => [
					'condition' => [
						'title' => __('Condition', 'vnh_textdomain'),
						'type' => 'select',
						'width' => 40,
						'description' => __('Condition vs. destination', 'vnh_textdomain'),
						'options' => [
							'' => __('None', 'vnh_textdomain'),
							'items' => __('Item count', 'vnh_textdomain'),
						],
					],
					'min_max' => [
						'title' => __('Min Max', 'vnh_textdomain'),
						'type' => 'min_max',
						'width' => 7,
						'description' => __('Bottom and top range for the selected condition.', 'vnh_textdomain'),
					],
					'break' => [
						'title' => __('Break', 'vnh_textdomain'),
						'type' => 'checkbox',
						'width' => 9,
						'description' => __(
							'Break at this point. For per-order rates, no rates other than this will be offered. For calculated rates, this will stop any further rates being matched.',
							'vnh_textdomain'
						),
					],
					'per_item' => [
						'title' => __('Item Cost', 'vnh_textdomain'),
						'type' => 'number',
						'width' => 12,
						'description' => __('Cost per item.', 'vnh_textdomain'),
						'custom_attributes' => [
							'min' => '0',
							'step' => '0.01',
						],
					],
					'shipping_label' => [
						'title' => __('Label', 'vnh_textdomain'),
						'type' => 'text',
						'width' => 30,
						'description' => __('Label for the shipping method which the user will be presented.', 'vnh_textdomain'),
					],
				],
			],
		];
	}

	public function is_available($package) {
		$available = true;

		if (!$this->get_rates($package)) {
			$available = false;
		}

		return apply_filters('woocommerce_shipping_' . $this->id . '_is_available', $available, $package, $this);
	}

	public function calculate_shipping($package = []) {
		if ($this->available_rates) {
			foreach ($this->available_rates as $rate) {
				$this->add_rate($rate);
			}
		}
	}

	public function get_rates($package) {
		if (!$this->instance_id) {
			return false;
		}

		$rates = [];

		if ($this->calculation_type === 'item') {
			// For each ITEM get matching rates
			$costs = [];
			$matched = false;

			$_tax = new WC_Tax();
			$taxes = [];

			foreach ($package['contents'] as $item_id => $values) {
				$_product = $values['data'];

				if ($values['quantity'] > 0 && $_product->needs_shipping()) {
					$item_cost = 0;
					$item_fee = (float) $this->get_fee($this->fee, $this->get_product_price($_product));

					$matching_rates = $this->query_rates([
						'count' => 1,
					]);

					foreach ($matching_rates as $rate) {
						$item_cost += (float) $rate['per_item'];
						$matched = true;

						if (!empty($rate['break'])) {
							break;
						}
					}

					$cost = ($item_cost + $item_fee) * $values['quantity'];

					if ($this->min_cost && $cost < $this->min_cost) {
						$cost = $this->min_cost;
					}
					if ($this->max_cost && $cost > $this->max_cost) {
						$cost = $this->max_cost;
					}

					$costs[$item_id] = $cost;
				}

				if ($this->is_taxable()) {
					$rates = $_tax::get_shipping_tax_rates($_product->get_tax_class());
					$item_taxes = $_tax::calc_shipping_tax($costs[$item_id], $rates);

					// Sum the item taxes.
					foreach (array_keys($taxes + $item_taxes) as $key) {
						$taxes[$key] = (isset($item_taxes[$key]) ? $item_taxes[$key] : 0) + (isset($taxes[$key]) ? $taxes[$key] : 0);
					}
				}
			}

			if ($matched) {
				if ($this->order_handling_fee) {
					$costs['order'] = $this->order_handling_fee;
				} else {
					$costs['order'] = 0;
				}

				if ($this->max_shipping_cost && $costs['order'] + array_sum($costs) > $this->max_shipping_cost) {
					$rates[] = [
						'id' => $this->get_rate_id(),
						'label' => $this->title,
						'cost' => $this->max_shipping_cost,
						'taxes' => $taxes,
					];
				} else {
					$rates[] = [
						'id' => $this->get_rate_id(),
						'label' => $this->title,
						'cost' => $costs,
						'taxes' => $taxes,
						'package' => $package,
					];
				}
			}
		} else {
			// For the ORDER get matching rates
			$count = 0;

			foreach ($package['contents'] as $item_id => $values) {
				$_product = $values['data'];

				if ($values['quantity'] > 0 && $_product->needs_shipping()) {
					$count += $values['quantity'];
				}
			}

			$matching_rates = $this->query_rates([
				'count' => $count,
			]);

			foreach ($matching_rates as $rate) {
				$label = !empty($rate['shipping_label']) ? $rate['shipping_label'] : $this->title;

				$cost = (float) $rate['per_item'] * $count;

				if ($this->order_handling_fee) {
					$cost += $this->order_handling_fee;
				}

				if ($this->min_cost && $cost < $this->min_cost) {
					$cost = $this->min_cost;
				}

				if ($this->max_cost && $cost > $this->max_cost) {
					$cost = $this->max_cost;
				}

				if ($this->max_shipping_cost && $cost > $this->max_shipping_cost) {
					$cost = $this->max_shipping_cost;
				}

				$rates[] = [
					'id' => $this->get_rate_id(),
					'label' => $label,
					'cost' => $cost,
					'package' => $package,
				];

				if (!empty($rate['break'])) {
					break;
				}
			}
		}

		// None found?
		if (count($rates) === 0) {
			return false;
		}

		// Set available
		$this->available_rates = $rates;

		return true;
	}

	public function query_rates($args) {
		$args = wp_parse_args($args, [
			'count' => 1,
		]);

		$rates = [];
		foreach ($this->rates_table as $rate) {
			if ($rate['condition'] === 'items') {
				if (
					(empty($rate['min']) && empty($rate['max'])) ||
					($args['count'] >= $rate['min'] && $args['count'] <= $rate['max']) ||
					($rate['min'] > 0 && empty($rate['max']) && $args['count'] >= $rate['min']) ||
					($rate['max'] > 0 && empty($rate['min']) && $args['count'] <= $rate['max'])
				) {
					$rates[] = $rate;
				}
			} else {
				$rates[] = $rate;
			}
		}

		return $rates;
	}
}
