<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Textgroup
 * Provides all field templates for fields for fields available on
 * DynamicTextgroup field
 *
 * @package Lib
 * @author Brock Petrie <brockpetrie@gmail.com>
 * @author Thomas Appel <mail@thomas-appel.com>
 */
class Textgroup
{

    const field_types = 'text select radio checkbox date';

    /**
     * field types available on this class
     */
    private static $field_types_map = array(
        'text'		=> 'textfield',
        'select'	=> 'selectbox',
        'radio'		=> 'radio button',
        'checkbox'	=> 'checkbox',
        'date'		=> 'date'
    );

    /**
     * createNewTextGroup
     *
     * @param mixed $element
     * @param int   $fieldCount
     * @param mixed $values
     * @param mixed $class
     * @param mixed $schema
     * @param mixed $sortable
     * @static
     * @access public
     * @return void
     */
    public static function createNewTextGroup($element, $fieldCount=2, $values=NULL, $class=NULL, $schema=NULL, $sortable = false)
    {
        // Additional classes
        $classes = array();

        if ($class) {
            $classes[] = $class;
        }

        // Field creator:
        $fields = '';
        $draw_handle = $sortable ? '<div class="draw-handle"></div>' : '';
        for ($i=0; $i<$fieldCount; $i++) {
            $fieldVal = ($values != NULL && $values[$i] != ' ') ? $values[$i] : NULL;
            $type = $schema[$i]->options->type;

            $fn = strtoupper($type{0}) . substr($type, 1);
            $fn = sprintf('__create%sField', $fn);

            $fields .= self::$fn($element, $fieldVal, $schema[$i]);
        }
        // Create element:
        return new XMLElement(
            'li',
            '<div class="content">' . $draw_handle . '<div class="flexgroup">' . $fields . ' </div></div>',
            array(
                'class' => implode($classes, ' '),
                'data-name' => 'dynamictextgroup item',
                'data-type' => 'dynamictextgroup-item',
            )
        );
    }

    /**
     * __createTextField
     *
     * @param mixed    $element
     * @param mixed    $textvalue
     * @param stdClass $schema
     * @static
     * @access private
     * @return void
     */
    private static function __createTextField($element, $textvalue, stdClass $schema)
    {
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

    /**
     * __createDateField
     *
     * @param mixed    $element
     * @param mixed    $textvalue
     * @param stdClass $schema
     * @static
     * @access private
     * @return void
     */
    private static function __createDateField($element, $textvalue, stdClass $schema)
    {
        $handle = $schema->handle;
        $label = $schema->label;
        $required = $schema->options->required;
        //$f_label = Widget::Label($label);
        $field = Widget::Input('fields['. $element .']['. $handle .'][]', $textvalue, 'text', array('placeholder' =>$label));
        $div = new XMLElement('div', NULL, array('class' => 'dtg-date dbox'));
        $div->appendChild($field);

        return $div->generate();
    }

    /**
     * __createSelectField
     *
     * @param mixed    $element
     * @param mixed    $val
     * @param stdClass $schema
     * @static
     * @access private
     * @return void
     */
    private static function __createSelectField($element, $val, stdClass $schema)
    {
        // Generate select list:
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

    /**
     * __createCheckboxField
     *
     * @param mixed    $element
     * @param mixed    $val
     * @param stdClass $schema
     * @static
     * @access private
     * @return void
     */
    private static function __createCheckboxField($element, $val, stdClass $schema)
    {
        // Generate radio button field
        $handle = $schema->handle;
        $label = $schema->label;

        $val = $val === 'yes' ? 'yes' : 'no';

        $f_label = '<label>';
        $field = Widget::Input('fields['. $element .']['. $handle .'][]', $val, 'hidden');
        $chk = Widget::Input('dtg-checkbox', NULL, 'checkbox');
        if ($val == 'yes') {
            $chk->setAttribute('checked', 'checked');
        }
        $f_label .= $field->generate() . $chk->generate() . $label .'</label>';
        $div = new XMLElement('div', $f_label, array('class' => 'dtg-checkbox dbox'));

        return $div->generate();
    }

    /**
     * __createRadioField
     *
     * @param mixed    $element
     * @param mixed    $val
     * @param stdClass $schema
     * @static
     * @access private
     * @return void
     */
    private static function __createRadioField($element, $val, stdClass $schema)
    {
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

    /**
     * _appendRequiredCheckbox
     *
     * @param mixed $required
     * @static
     * @access private
     * @return void
     */
    private static function _appendRequiredCheckbox($required = false)
    {
        $checked = $required ? 'yes' : 'no';
        $is_checked = $required ? ' checked' : '';

        return '<label>' . sprintf(__('%s Make this a required field'), '<input type="checkbox" value="' . $checked . '"' . $is_checked . ' name="required"/>') . '</label>';
    }
    /**
     * _wrapInScriptTag
     *
     * @param mixed $type
     * @param mixed $value
     * @static
     * @access private
     * @return void
     */
    private static function _wrapInScriptTag($type, $value)
    {
        return '<script type="text/template" class="' . $type . '">' . $value . '</script>';
    }

    /**
     * _makeTypeOptions
     * creates fieldtype selectioen field
     *
     * @param string  $selected option name to be selected
     * @param boolean $fieldset if set, wrapps the field in a fieldset (defaults to true)
     * @static
     * @access private
     * @return void
     */
    private static function _makeTypeOptions($selected, $fieldset = true)
    {
        $opt = '<label>' . __('Field Type');
        $opt .= '<select name="type">';
        foreach (explode(' ', self::getFieldTypes()) as $key) {
            $is_selected = $selected == $key ? ' selected' : '';
            $opt .= '<option name="' . $key . '" value="' . $key . '" ' . $is_selected .'>' . __(self::$field_types_map[$key]) . '</option>';
        }
        $opt .= '</select></label>';
        if ($fieldset) {
            $opt = '<fieldset>' . $opt  . '</fieldset>';
        }

        return $opt;
    }
    /**
     * get all avaiable fieldtypes as a whitespace sepeated string
     *
     * @static
     * @access public
     * @return string
     */
    public static function getFieldTypes()
    {
        return self::field_types;
    }

    /**
     * create a fieldsettings template
     *
     * @param boolean $wrap    weather to wrap the template in script tag or not
     * @param mixed   $type    the fieldtype the template will be ceated for
     * @param array   $options options array
     * @static
     * @access public
     * @return void
     */
    public static function make_template($wrap = false, $type = null, $options = NULL)
    {
        $fn = '_tpl_options_' . $type;

        return self::$fn($wrap, $options);
    }

    /**
     * _tpl_options_radio
     *
     *  settings template for radio field
     *
     * @param boolean $wrap    weather to wrap the template in script tag or not
     * @param array   $options options array
     * @static
     * @access private
     * @return void
     */
    private static function _tpl_options_radio($wrap = false, $options = NULL)
    {
        $required = (is_object($options) && isset($options->required)) ? $options->required : false;
        $add_group_field = isset($options->add_group_field) && $options->add_group_field;

        $fields  = '';
        $fields .= $add_group_field ? '<div class="two columns"><div class="column">' : '';
        $fields .= self::_makeTypeOptions('radio', false);
        $fields .= $add_group_field ? '</div><div class="column"><label>' . __('group name') . '<input type="text" name="radio_group" value="' . (isset($options->group_name) ?
            $options->group_name : '') . '"/></label>' :
            '';
        $fields .= $add_group_field ?
            '<p class="help">' . __('only avilable if creating multiple items is deactiveted') . '</p></div></div>' :
            '';
        $fields .= self::_appendRequiredCheckbox($required);
        if ($wrap) {
            $fields = self::_wrapInScriptTag('radio', $fields);
        }

        return $fields;
    }

    /**
     * _tpl_options_select
     * settings template select field
     *
     * @param boolean $wrap    weather to wrap the template in script tag or not
     * @param array   $options options array
     * @static
     * @access private
     * @return void
     */
    private static function _tpl_options_select($wrap = false, $options = NULL)
    {
        $opts = is_object($options);
        $required = $opts && isset($options->required) ? $options->required : false;
        $checked  = $opts && isset($options->allow_multiple) ? ' checked' : '';
        $dynamic_values = $opts && isset($options->dynamic_options) ? $options->dynamic_options : NULL;

        $fields	 = self::_makeTypeOptions('select');
        $fields .= '<fieldset><div class="two columns"><div class="column"><label>' . __('Predefined Values') . '<i>' . __('Optional') . '</i>';
        $fields .= '<input type="text" name=selectValues value="' . $options->static_values . '"/></div><div class="column"></label>';
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
     * _tpl_options_text
     * settings template for text field
     *
     * @param boolean $wrap    weather to wrap the template in script tag or not
     * @param array   $options options array
     * @static
     * @access private
     * @return void
     */
    private static function _tpl_options_text($wrap = false, $options = NULL)
    {
        $opts = is_object($options);
        $required = $opts && isset($options->required) ? $options->required : false;
        $value   =  $opts && isset($options->validationRule) ? $options->validationRule : NULL;

        $fields  = self::_makeTypeOptions('text');
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
     * _tpl_options_date
     *
     * @param boolean $wrap    weather to wrap the template in script tag or not
     * @param array   $options options array
     * @static
     * @access private
     * @return void
     */
    private static function _tpl_options_date($wrap = false, $options = NULL)
    {
        $required = (is_object($options) && isset($options->required)) ? $options->required : false;
        $fields = self::_makeTypeOptions('date');
        $fields .= '<fieldset>';
        $fields .= self::_appendRequiredCheckbox($required);
        $fields .= '</fieldset>';
        if ($wrap) {
            $fields = self::_wrapInScriptTag('date', $fields);
        }

        return $fields;
    }

    /**
     * _tpl_options_checkbox
     * settings template for checkbox field
     *
     * @param boolean $wrap weather to wrap the template in script tag or not
     * @static
     * @access private
     * @return void
     */
    private static function _tpl_options_checkbox($wrap = false)
    {
        $fields = self::_makeTypeOptions('checkbox');
        if ($wrap) {
            $fields = self::_wrapInScriptTag('checkbox', $fields);
        }

        return $fields;
    }

}
