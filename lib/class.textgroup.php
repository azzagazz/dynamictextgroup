<?php

	class Textgroup {
		
		const field_types = 'text select radio checkbox';

		private static $field_types_map = array(
			'text'		=> 'textfield',
			'select'	=> 'selectbox',
			'radio'		=> 'radio button',
			'checkbox'	=> 'checkbox',
		);

		public static function createNewTextGroup($element, $fieldCount=2, $values=NULL, $class=NULL, $schema=NULL, $sortable = false) {
			// Additional classes
			$classes = array();
			if($class) {
				$classes[] = $class;
			}
			
			// Field creator
			$fields = '';
			$draw_handle = $sortable ? '<div class="draw-handle"></div>' : '';
			for ($i=0; $i<$fieldCount; $i++) {
				$fieldVal = ($values != NULL && $values[$i] != ' ') ? $values[$i] : NULL;
				$type = $schema[$i]->options->type;

				$fn = strtoupper($type{0}) . substr($type, 1);
				$fn = sprintf('__create%sField', $fn);

				$fields .= self::$fn($element, $fieldVal, $schema[$i]);
			}
			// Create element
			return new XMLElement(
				'li', 
				'<div class="content flexgroup">' . $draw_handle . $fields . '</div>',
				array(
					'class' => implode($classes, ' '),
					'data-name' => 'dynamictextgroup item',
					'data-type' => 'dynamictextgroup-item',
				)
			);
		}
		
		// Generate text field
		private static function __createTextField($element, $textvalue, stdClass $schema) {
			$handle = $schema->handle; 
			$label = $schema->label; 
			$required = $schema->options->required; 
			//$f_label = Widget::Label($label);
			$field = Widget::Input('fields['. $element .']['. $handle .'][]', $textvalue, 'text', array('placeholder' => $label));
			$div = new XMLElement('div', NULL, array('class' => 'dtg-text dbox'));
			//$f_label->appendChild($field);
			$div->appendChild($field);
			return $div->generate();
		}
		
		private static function __createSelectField($element, $val, stdClass $schema) {
			// Generate select list
			$handle = $schema->handle; 
			$label = $schema->label; 
			$options = $schema->options->selectOptions; 

			$fieldname = 'fields['. $element .']['. $handle .']';
			$fieldname .= $schema->options->allow_multiple ? '[{{multiple}}][]' : '[]';

			$field = Widget::Select($fieldname, $options);
			if ($schema->options->allow_multiple) {
				$field->setAttribute('multiple', '');	
			}
			$div = new XMLElement('div', $field->generate(), array('class' => 'dtg-select dbox'));

			return $div->generate();
		}
		
		private static function __createCheckboxField($element, $val, stdClass $schema) {
			// Generate radio button field
			$handle = $schema->handle; 
			$label = $schema->label; 

			$f_label = '<label>';
			$field = Widget::Input('fields['. $element .']['. $handle .'][]', $val, 'checkbox');
			if ($val == 'yes') {
				$field->setAttribute('checked', 'checked');
			}
			$f_label .= $field->generate() . $label .'</label>';
			$div = new XMLElement('div', $f_label, array('class' => 'dtg-checkbox dbox'));
			return $div->generate();
		}

		private static function __createRadioField($element, $val, stdClass $schema) {
			// Generate radio button field
			$handle = $schema->handle; 
			$label = $schema->label; 

			$f_label = '<label>';
			$gname = $schema->options->group_name ? $schema->options->group_name : $schema->handle;
			$field = Widget::Input($gname, $val, 'radio');
			if ($val == 'yes') {
				$field->setAttribute('checked', 'checked');
			}
			$hidden = Widget::Input('fields['. $element .']['. $handle .'][]', $val, 'hidden');
			$hidden->addClass('dtg-radio hidden-' . $gname);
			$f_label .= $field->generate() . $hidden->generate() . $label .'</label>';
			$div = new XMLElement('div', $f_label, array('class' => 'dtg-radio dbox'));
			return $div->generate();
		}

		private static function _appendRequiredCheckbox($required = false) {
			$checked = $required ? 'yes' : 'no';
			$is_checked = $required ? ' checked' : '';
			return '<label>' . sprintf(__('%s Make this a required field'), '<input type="checkbox" value="' . $checked . '"' . $is_checked . ' name="required"/>') . '</label>';
		}
		private static function _wrapInScriptTag($type, $value) {
			return '<script type="text/template" class="' . $type . '">' . $value . '</script>';
		}

		private static function _makeTypeOptions($selected, $fieldset = true) {
			$opt = '<label>' . __('Field Type');
			$opt .= '<select name="type">';
			foreach (explode(' ', self::getFieldTypes()) as $key) {
				$is_selected = $selected == $key ? ' selected' : '';
				$opt .= '<option name="' . $key . '" value="' . $key . '" ' . $is_selected .'>' . self::$field_types_map[$key]. '</option>';
			}
			$opt .= '</select></label>';
			if ($fieldset) {
				$opt = '<fieldset>' . $opt  . '</fieldset>';
			}
			return $opt;
		}	
		public static function getFieldTypes() {
			return self::field_types;
		} 

		public static function make_template($wrap = false, $type = null, $options = NULL) {
			$fn = '_tpl_options_' . $type;
			return self::$fn($wrap, $options); 
		}

		/**
		 *  settings template for radio field 
		 */
		public static function _tpl_options_radio($wrap = false, $options = NULL) {
			$required = (is_array($options) && isset($options['required'])) ? $options['required'] : false;
			$add_group_field = isset($options['add_group_field']) && $options['add_group_field'];
			$fields = '';
			$fields .= $add_group_field ? '<div class="two columns"><div class="column">' : '';
			$fields .= self::_makeTypeOptions('radio', false);
			$fields .= $add_group_field ? '</div><div class="column"><label>' . __('group name') . '<input type="text" name="radio_group" value="' . (isset($options['group_name']) ? $options['group_name'] : '') . '"/></label>' : '';
			$fields .= $add_group_field ? '<p class="help">' . __('only avilable if creating multiple items is deactiveted') . '</p></div></div>' : '';
			$fields .= self::_appendRequiredCheckbox($required);
			if ($wrap) {
				$fields = self::_wrapInScriptTag('radio', $fields);
			}
			return $fields;
		}
		
		/** 
		 * settings template select field 
		 */
		public static function _tpl_options_select($wrap = false, $options = NULL) {
			$required = (is_array($options) && isset($options['required'])) ? $options['required'] : false;
			$checked = (is_array($options) && isset($options['allow_multiple'])) ? ' checked' : '';
			$dynamic_values = (is_array($options) && isset($options['dynamic_options'])) ? $options['dynamic_options'] : NULL;

			$fields	 = self::_makeTypeOptions('select');
			$fields .= '<fieldset><div class="two columns"><div class="column"><label>' . __('Predefined Values') . '<i>' . __('Optional') . '</i>';
			$fields .= '<input type="text" name=selectValues value="' . $options['static_values']. '"/></div><div class="column"></label>';
			$fields .= '<label>' . __('Dynamic Values') . $dynamic_values;
			$fields .= '</label></div></div></fieldset>';
			$fields .= '<fieldset><div class="two columns"><div class="column">';
			$fields .= self::_appendRequiredCheckbox($required);
			$fields .= '</div><div class="column"><label><input type="checkbox" name="allow_multiple"' . $checked . '/>' . __('allow multiple') . '</label></div></fieldset>';
			if ($wrap) {
				$fields = self::_wrapInScriptTag('select', $fields);
			}
			return $fields;
		}

		/** 
		 * settings template for text field 
		 */
		public static function _tpl_options_text($wrap = false, $options = NULL) {
			$required = (is_array($options) && isset($options['required'])) ? $options['required'] : false;
			$value = (is_array($options) && isset($options['validationRule'])) ? $options['value'] : NULL;
			$fields = self::_makeTypeOptions('text');
			$fields .= '<fieldset><label>' . __('Validation Rule') . '</label>';
			$fields .= '<input type="text" name=validationRule value="' . $value .'" placeholder="' . __('Enter a regex pattern') . '"/>';
			$fields .= '<div class="tabgroup help"><a data-type="number" href="#">' . __('Number') . '</a><a data-type="mail" href="#">' . __('E-Mail') . '</a><a data-type="uri" href="#">' . __('URI') . '</a></div>';
			$fields .= '</fieldset>';
			$fields .= '<fieldset>';
			$fields .= self::_appendRequiredCheckbox($required);
			$fields .= '</fieldset>';
			if ($wrap) {
				$fields = self::_wrapInScriptTag('text', $fields);
			}
			return $fields;
		}

		/**
		 *  settings template for checkbox field 
		 */
		public static function _tpl_options_checkbox($wrap = false) {
			$fields = self::_makeTypeOptions('checkbox');
			if ($wrap) {
				$fields = self::_wrapInScriptTag('checkbox', $fields);
			}
			return $fields;
		}

	}
