<?php

namespace vnh_namespace\tools;

use vnh_namespace\tools\contracts\Bootable;
use vnh_namespace\tools\contracts\Initable;
use WC_Shipping_Method;

abstract class Shipping_Base extends WC_Shipping_Method implements Initable, Bootable {
	public function __construct($instance_id = 0) {
		parent::__construct($instance_id);
		$this->setup();
		$this->init_form_fields(); // Load the form fields.
		$this->init();
		$this->boot();
	}

	abstract public function setup();

	abstract public function init();

	public function boot() {
		add_action('woocommerce_update_options_shipping_' . $this->id, [$this, 'process_admin_options']);
	}

	public function validate_repeater_field($key, $value) {
		return $value;
	}

	public function generate_repeater_html($key, $data) {
		$field_key = $this->get_field_key($key);
		$data = wp_parse_args($data, [
			'title' => '',
			'disabled' => false,
			'class' => '',
			'css' => '',
			'type' => 'repeater',
			'desc_tip' => false,
			'description' => '',
			'custom_attributes' => [],
		]);

		$html = '<tr>';
		$html .= '<th scope="row" class="titledesc">';
		$html .= sprintf('<label for="%s"> %s %s</label>', esc_attr($field_key), wp_kses_post($data['title']), $this->get_tooltip_html($data));
		$html .= '</th>';
		$html .= '<td class="forminp">';
		$html .= '<table class="repeat-table wp-list-table widefat striped">';
		$html .= '<colgroup>';
		foreach ($data['children'] as $child) {
			$html .= sprintf('<col span="1" style="width: %s%s">', $child['width'], '%');
		}
		$html .= '</colgroup>';
		$html .= '<thead><tr>';

		foreach ($data['children'] as $child) {
			$html .= sprintf(
				'<th>%s %s</th>',
				$child['title'],
				!empty($child['description'])
					? sprintf('<a class="tips" data-tip="%s"><span class="woocommerce-help-tip"></span></a>', $child['description'])
					: null
			);
		}

		$html .= sprintf('<th>%s</th>', __('Action', 'vnh_textdomain'));
		$html .= '</tr></thead>';
		$html .= sprintf('<tbody data-repeater-list="%s">', $field_key);
		if (!empty($this->get_option($key))) {
			foreach ($this->get_option($key) as $index => $value) {
				$html .= $this->build_repeat_field($data, $this->get_option($key), $index);
			}
		} else {
			$html .= $this->build_repeat_field($data, $this->get_option($key), 0);
		}
		$html .= '</tbody>';
		$html .= '<tfoot><tr><th class="add-row">';
		$html .= sprintf('<input data-repeater-create type="button" class="button button-primary" value="%s"/>', $data['options']['add_button']);
		$html .= '</th></tr></tfoot>';
		$html .= '</table>';
		$html .= '</td>';
		$html .= '</tr>';

		ob_start();
		echo $html;

		return ob_get_clean();
	}

	protected function build_repeat_field($data, $option, $index) {
		$html = '<tr class="repeating" data-repeater-item>';
		foreach ($data['children'] as $key => $child) {
			$html .= sprintf('<td class="%s">', $child['type']);
			switch ($child['type']) {
				case 'text':
					$html .= sprintf(
						'<input type="text" name="%s" value="%s"/>',
						$key,
						!empty($option[$index][$key]) ? $option[$index][$key] : null
					);

					break;
				case 'checkbox':
					$html .= sprintf(
						'<input type="checkbox" name="%s" value="true" %s/>',
						$key,
						!empty($option[$index][$key]) ? 'checked' : null
					);

					break;
				case 'select':
					$options = '';
					foreach ($child['options'] as $value => $label) {
						$options .= sprintf(
							'<option %s value="%s">%s</option>',
							isset($option[$index][$key]) && $option[$index][$key] === $value ? 'selected' : '',
							$value,
							$label
						);
					}
					$html .= sprintf(
						'<select class="select" name="%s" %s>%s</select>',
						$key,
						!empty($child['placeholder']) ? sprintf('placeholder="%s"', esc_attr($child['placeholder'])) : null,
						$options
					);

					break;
				case 'number':
					$html .= sprintf(
						'<input type="number" min="0" max="100" name="%s" value="%s" %s/>',
						$key,
						!empty($option[$index][$key]) ? $option[$index][$key] : null,
						$this->get_custom_attribute_html($child)
					);

					break;
				case 'min_max':
					$html .= sprintf(
						'<input type="number" min="0" name="%s" value="%s" %s/>',
						'min',
						!empty($option[$index]['min']) ? $option[$index]['min'] : null,
						$this->get_custom_attribute_html($child)
					);
					$html .= sprintf(
						'<input type="number" min="0" name="%s" value="%s" %s/>',
						'max',
						!empty($option[$index]['max']) ? $option[$index]['max'] : null,
						$this->get_custom_attribute_html($child)
					);

					break;
			}
			$html .= '</td>';
		}
		$html .= '<td><input data-repeater-delete type="button" class="button button-secondary" value="Delete"/></td>';
		$html .= '</tr>';

		return $html;
	}
}
