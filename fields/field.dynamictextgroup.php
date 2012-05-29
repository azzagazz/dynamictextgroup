<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

if(!defined('__IN_SYMPHONY__')) die('You cannot directly access this file');

require_once(TOOLKIT . '/fields/field.date.php');
require_once(EXTENSIONS . '/dynamictextgroup/lib/class.textgroup.php');

/**
 * fielddynamictextgroup
 * This field provides a method to dynamically add a text field or text field groups to a section entry
 *
 * @uses Field
 * @package Fields
 * @version 3
 * @author Brock Petrie <brockpetrie@gmail.com>
 * @author Thomas Appel <mail@thomas-appel.com>
 */
class fielddynamictextgroup extends Field
{

    /**
     * the default settings for a new dynamic field
     */
    private static $_dynamic_field_defaults = array(
        'handle'	=> 'field',
        'label'		=> 'field',
        'options'	=> array(
            'required'			=> false,
            'type'				=> 'text',
            'sortorder'			=> 1,
            'validationRule'	=> NULL,
            'static_values'		=> ''
        )
    );

    /**
     * canFilter
     *
     * @see http://symphony-cms.com/learn/api/2.2/toolkit/field/#canFilter
     */
    public function canFilter()
    {
        return true;
    }


    /**
     * isSortable
     *
     * @see http://symphony-cms.com/learn/api/2.2/toolkit/field/#isSortable
     */
    public function isSortable()
    {
        return true;
    }

    /**
     * canPrePopulate
     *
     * @see http://symphony-cms.com/learn/api/2.2/toolkit/field/#canPrePopulate
     */
    public function canPrePopulate()
    {
        return false;
    }


    /**
     * allowDatasourceOutputGrouping
     *
     * @see http://symphony-cms.com/learn/api/2.2/toolkit/field/#allowDatasourceOutputGrouping
     */
    public function allowDatasourceOutputGrouping() {
        return false;
    }


    /**
     * allowDatasourceParamOutput
     *
     * @see http://symphony-cms.com/learn/api/2.2/toolkit/field/#allowDatasourceParamOutput
     */
    public function allowDatasourceParamOutput() {
        return false;
    }

    /**
     * __construct
     *
     * @see http://symphony-cms.com/learn/api/2.2/toolkit/field/#__construct
     */
    public function __construct() {
        parent::__construct();
        $this->_name = __('Dynamic Text Group');
        $this->_required = true;
        $this->set('required', 'no');
    }


    /**
     * _getSectionLinkVals
     *
     * @param mixed $selected
     * @param mixed $field_id
     * @static
     * @access private
     * @return void
     */
    private static function _getSectionLinkVals($selected = NULL, $field_id = NULL) {

        $sections = SectionManager::fetch(NULL, 'ASC', 'name');
        $field_groups = array();
        if(is_array($sections) && !empty($sections))
            foreach($sections as $section) $field_groups[$section->get('id')] = array('fields' => $section->fetchFields(), 'section' => $section);

        $options = array(
            array('', false, __('None')),
        );
        foreach($field_groups as $group){
            if(!is_array($group['fields'])) continue;

            $fields = array();
            foreach($group['fields'] as $f){
                if($f->get('id') != $field_id && $f->canPrePopulate()) $fields[] = array($f->get('id'), ($selected == $f->get('id')), $f->get('label'));
            }

            if(is_array($fields) && !empty($fields)) $options[] = array('label' => $group['section']->get('name'), 'options' => $fields);
        }
        $selectbox = Widget::Select('dynamicValues', $options);
        return $selectbox->generate();
    }

    /**
     * _getToggleStates
     *
     * @param mixed $options
     * @param mixed $static
     * @static
     * @access private
     * @return void
     */
    private static function _getToggleStates($options, $static) {
        $values = preg_split('/,\s*/i', $static, -1, PREG_SPLIT_NO_EMPTY);

        if ($options != '') self::findAndAddDynamicOptions($values, $options);

        $values = array_map('trim', $values);
        $states = array();

        foreach ($values as $value) {
            $value = $value;
            $states[$value] = $value;
        }
        /*
        if($this->get('sort_options') == 'yes') {
            natsort($states);
        }
        */
        natsort($states);

        return $states;
    }

    /**
     * getFieldSchema
     *
     * @param mixed $key
     * @access public
     * @return void
     */
    public function getFieldSchema($raw = false)
    {
        $data = $this->get('schema');
        return (!is_null($data)) ? ($raw ? gzuncompress(base64_decode($data)) : json_decode(gzuncompress(base64_decode($data)))) : array();
    }

    /**
     * writeFieldSchema
     *
     * @param array $data
     * @param mixed $settings
     * @param mixed $convert
     * @access public
     * @return void
     */
    public function writeFieldSchema(array $data, $settings = NULL, $convert = false)
    {
        return base64_encode(gzcompress(json_encode($data)));
    }

    /**
     * findAndAddDynamicOptions
     *
     * @param mixed $values
     * @param mixed $options
     * @static
     * @access public
     * @return void
     */
    public static function findAndAddDynamicOptions(&$values, $options){
        if(!is_array($values)) $values = array();

        $results = false;

        // Ensure that the table has a 'value' column
        if((boolean)Symphony::Database()->fetchVar('Field', 0, sprintf("
                SHOW COLUMNS FROM `tbl_entries_data_%d` LIKE '%s'
            ", $options, 'value'
        ))) {
            $results = Symphony::Database()->fetchCol('value', sprintf("
                    SELECT DISTINCT `value`
                    FROM `tbl_entries_data_%d`
                    ORDER BY `value` ASC
                ", $options
            ));
        }

        // In the case of a Upload field, use 'file' instead of 'value'
        if(($results == false) && (boolean)Symphony::Database()->fetchVar('Field', 0, sprintf("
                SHOW COLUMNS FROM `tbl_entries_data_%d` LIKE '%s'
            ", $options, 'file'
        ))) {
            $results = Symphony::Database()->fetchCol('file', sprintf("
                    SELECT DISTINCT `file`
                    FROM `tbl_entries_data_%d`
                    ORDER BY `file` ASC
                ", $options
            ));
        }

        if($results) {
            //if($this->get('sort_options') == 'no') {
                natsort($results);
            //}

            $values = array_merge($values, $results);
        }
    }

    /**
     * create the fieldeditor fieldset on settings panel
     *
     * @author Thomas Appel <mail@thomas-appel.com>
     * @return Mixed Boolean false or XMLElement
     */
    private function _createFieldEditor()
    {

        if (!$this->get('id')) {
            return false;
        }

        $opt_templates = '';
        $sortorder = $this->get('sortorder');

        // create field-setting templates
        foreach(explode(' ', Textgroup::getFieldTypes()) as $type) {
            $opts = $type === 'select' ? (object)array(dynamic_options => self::_getSectionLinkVals(NULL, $this->get('id'))) : NULL;
            $opt_templates .= Textgroup::make_template(true, $type, $opts);
        }

        // Editor wrapper
        $fieldset = new XMLElement('fieldset');
        $label = Widget::Label(__('Field Editor'));
        $field_wrapper = new XMLElement('div', $opt_templates, array(
            'class' => 'frame dynamictextgroup'
        ));

        // constructable elements list
        $field_list = new XMLElement('ol');

        $field_wrapper->appendChild($field_list);
        $fieldset->appendChild($label);
        $fieldset->appendChild($field_wrapper);

        // hidden settings fields
        $hidden_fields = Array();
        $hidden_fields[] = Widget::Input('fields['. $sortorder . '][schema]', preg_replace('/\"/i', '&#34;', $this->getFieldSchema(true)), 'hidden', array('class' => 'schema'));
        $hidden_fields[] = Widget::Input('fields['. $sortorder . '][addfields]', NULL, 'hidden', array('class' => 'addfields'));
        $hidden_fields[] = Widget::Input('fields['. $sortorder . '][delfields]', NULL, 'hidden', array('class' => 'delfields'));
        $hidden_fields[] = Widget::Input('fields['. $sortorder . '][renfields]', NULL, 'hidden', array('class' => 'renfields'));


        $field_wrapper->appendChildArray($hidden_fields);

        $fields = array();

        $schema = $this->getFieldSchema();


        // create settings fields from json schema
        if (!empty($schema)) {
            foreach ($schema as &$field) {
                $type = $field->options->type;

                if ($type == 'select') {
                    $field->options->dynamic_options = self::_getSectionLinkVals(isset($field->options->dynamic_values) ? $field->options->dynamic_values : NULL , $this->get('id'));
                }

                if (isset($field->options->group_name)) {
                    $field->options->add_group_field = (intVal($this->get('allow_new_items')) == 1) ? false : true;
                }

                $opt_template = Textgroup::make_template(false, $type, $field->options);

                $field->options->dynamic_options = NULL;
                unset($field->options->dynamic_options);

                $fields[] = self::_createSettingsFields($field->label, $field, $this->get('element_name'), 'instance', $opt_template);
            }
        }
        $fields[] = self::_createSettingsFields($field->label, NULL, $this->get('element_name'), 'template', Textgroup::make_template(false, 'text'));
        $field_list->appendChildArray($fields);

        return $fieldset;
    }


    /**
     * displaySettingsPanel
     *
     * @see http://symphony-cms.com/learn/api/2.2/toolkit/field/#displaySettingsPanel
     */
    public function displaySettingsPanel(&$wrapper, $errors=NULL)
    {

        // Initialize field settings based on class defaults (name, placement)
        parent::displaySettingsPanel($wrapper, $errors);

        $sortorder = $this->get('sortorder');


        // Field Editor
        $editor = $this->_createFieldEditor();

        if ($editor) {
            $wrapper->appendChild($editor);
        } else {
            $fieldset = new XMLElement('fieldset', '<label>' . __('Field Editor') . '</label>' . __('Please save the section to enable the Field Editor') . '<br /><br />');
            $wrapper->appendChild($fieldset);
        }

        // Behaviour

        $fieldset = new XMLElement('div');
        $group = $fieldset->getChildren();
        $wrapper->appendChild($fieldset);

        // Options
        $fieldset = new XMLElement('fieldset');
        $fieldset->appendChild(Widget::Label(__('Options')));
        $two_columns = new XMLElement('div', NULL, array('class' => 'two columns'));

        $this->appendCheckbox($two_columns, 'allow_new_items', __('Allow creating new items'));
        if ($this->get('allow_new_items')) {
            $this->appendCheckbox($two_columns, 'allow_sorting_items', __('Allow sorting items'));
        }

        $fieldset->appendChild($two_columns);
        $wrapper->appendChild($fieldset);

        // General
        $fieldset = new XMLElement('fieldset');
        $group = new XMLElement('div', NULL, array('class' => 'two columns'));
        $this->appendRequiredCheckbox($group);
        $this->appendShowColumnCheckbox($group);
        $fieldset->appendChild($group);
        $wrapper->appendChild($fieldset);
    }

    /**
     * appendCheckbox
     *
     * @param XMLElement $wrapper
     * @param mixed $fname
     * @param mixed $value
     * @static
     * @access public
     * @return void
     */
    public function appendCheckbox(XMLElement &$wrapper, $fname = NULL, $value= NULL)
    {

        $order = $this->get('sortorder');
        $name = "fields[{$order}][{$fname}]";

        $wrapper->appendChild(Widget::Input($name, '0', 'hidden'));

        $label = Widget::Label();
        $label->addClass('column');
        $input = Widget::Input($name, '1', 'checkbox');

        if (intval($this->get($fname)) > 0) {
            $input->setAttribute('checked', 'checked');
        }

        $label->setValue(__('%s ' . $value, array($input->generate())));
        $wrapper->appendChild($label);
    }

    /**
     * Creates a constructable field editor field
     * @author Thomas Appel <mail@thomas-appel.com>
     *
     * @param $handle		String		label name
     * @param $options		Array		options array to be converted to json string
     * @param $name			String		html5-data-name and html5-data-type
     * @param $class		String		provides basic css class
     * @param $opt_content	String		optional markup to be appended to the fieldsettingbox
     *
     * @return  XMLElement
     */
    private static function _createSettingsFields($handle=NULL, $options=NULL, $name=NULL, $class=NULL, $opt_content=NULL)
    {

        $header = new XMLElement('div', NULL, array('class' => 'content'));
        $draghandle = new XMLElement('span', NULL, array('class' => 'draw-handle'));
        $typeselector = new XMLElement('span', NULL, array('class' => 'options'));
        $label = Widget::Input('dynamictextgroup-item', $handle);
        $options_box = new XMLElement('div', '<div class="options-box-inner">' . (is_string($opt_content) ? $opt_content : '') . '</div>', array('class' => 'options-box collapsed'));

        $li = new XMLElement('li', NULL, array(
            'class' => $class . ' field-holder',
            'data-settings' => is_object($options) ?
                preg_replace('/\"/i', '&#34;', json_encode($options)) :
                preg_replace('/\"/i', '&#34;', json_encode(self::$_dynamic_field_defaults)),
            'data-name' => $name,
            'data-type' => $name,
        ));

        $header->appendChild($draghandle);
        $header->appendChild($label);
        $header->appendChild($typeselector);
        $header->appendChild($options_box);

        $li->appendChild($header);
        return $li;
    }


    /**
     * checkFields
     *
     * @see http://symphony-cms.com/learn/api/2.2/toolkit/field/#checkFields
     */
    public function checkFields(&$errors, $checkForDuplicates=true)
    {
        parent::checkFields($errors, $checkForDuplicates);
    }

    /**
     * checkFields
     *
     * @see http://symphony-cms.com/learn/api/2.2/toolkit/field/#commit
     */
    public function commit()
    {
        if(!parent::commit()) return false;

        $id = $this->get('id');
        $fields = array();
        $schema = json_decode($this->get('schema'));


        $fields['field_id'] = $id;
        $fields['fieldcount'] = sizeof($schema);
        $fields['allow_new_items'] = $this->get('allow_new_items') ? 1 : 0;
        $fields['allow_sorting_items'] = $this->get('allow_sorting_items') ? 1 : 0;

        // add new fields
        $fields_added = json_decode($this->get('addfields'), true);

        if (is_array($fields_added) && !empty($fields_added)) {

            foreach($fields_added as $handle => $value) {
                $value = trim($value);
                if (strlen($value) > 0) self::alterTable(1, Lang::createHandle($value), NULL, $this->get('id'));
            }
        }

        // remove deleted fields
        $fields_deleted = json_decode($this->get('delfields'), true);

        if (is_array($fields_deleted) && !empty($fields_deleted)) {
            foreach($fields_deleted as $handle) {
                self::alterTable(0, $handle, NULL, $this->get('id'));
            }
        }

        // rename existing fields that where renemaed
        $fields_renamed = json_decode($this->get('renfields'), true);

        if (is_array($fields_renamed) && !empty($fields_renamed)) {

            foreach($fields_renamed as $handle => $value) {
                $value = trim($value);
                if (strlen($value) > 0) self::alterTable(2, $handle, Lang::createHandle($value), $this->get($id));
            }
        }


        $this->removeSectionAssociation($id);
        //$keys = array();
        $allow_new_items = $fields['allow_new_items'] == 1;

        if (is_array($schema)) {
            foreach($schema as &$field) {

                print_r($field->label);
                print_r($field);

                $field->handle = Lang::createHandle($field->label);

                $field_id = $field->options->dynamic_values;

                if (!is_null($field_id) &&  is_numeric($field_id)) {
                    $this->createSectionAssociation(NULL, $id, $field_id, true);
                }

                if (isset($field->options->group_name) && $allow_new_items) {
                    unset($field->options->group_name);
                }

                //$keys[][$field->handle] = $field->handle;
            }
            $fields['schema'] = $this->writeFieldSchema($schema);
        }

        Symphony::Database()->query("DELETE FROM `tbl_fields_" . $this->handle() . "` WHERE `field_id` = '$id' LIMIT 1");
        return Symphony::Database()->insert($fields, 'tbl_fields_' . $this->handle());
    }

    /**
     * alterTable
     *
     */
    protected static function alterTable($mode, $col, $rename=NULL, $id)
    {
        // Function $mode options:
        // 0 = Delete column; 	e.g.  alterTable(0, 'badcolumn');
        // 1 = Add column; 		e.g.  alterTable(1, 'newcolumn');
        // 2 = Rename column;	e.g.  alterTable(2, 'newcolumnname', 'oldcolumnname');
        switch ($mode) {
            case 0:
                // Delete column
                Symphony::Database()->query("ALTER TABLE `tbl_entries_data_" . $id . "` DROP COLUMN `". $col ."`");
                break;
            case 1:
                // Add column
                Symphony::Database()->query("ALTER TABLE `tbl_entries_data_" . $id . "` ADD COLUMN `". $col ."` varchar(255) null");
                break;
            case 2:
                // Rename column
                Symphony::Database()->query("ALTER TABLE `tbl_entries_data_" . $id . "` CHANGE `". $col ."` `". $rename ."` varchar(255) null");
                break;
            default:
                return false;
        }
    }

    /**
     * create select options for select fields on the publish panel
     *
     * @param		$data		String
     * @param		$field		Object
     *
     * @return		void
     */
    private function _makePublishSelectOptions($data = NULL, stdClass $field)
    {


        if ($field->options->type !== 'select') return;

        $selected = explode(',', $data);

        $states = self::_getToggleStates($field->options->dynamic_values, $field->options->static_values);

        $select_opts = array(
            array(NULL, false, '- ' . $field->label . ' -')
        );
        $options = array();

        foreach($states as $handle => $v){
            $options[] = array(General::sanitize($v), in_array($v, $selected), General::sanitize($v));
        }

        $select_opts[] = array('label' => $field->label, 'options' => $options);

        $field->options->selectOptions = $select_opts;
        unset($select_opts);
    }

    /**
     * displayPublishPanel
     *
     * @see http://symphony-cms.com/learn/api/2.2/toolkit/field/#displayPublishPanel
     */
    public function displayPublishPanel(&$wrapper, $data=NULL, $flagWithError=NULL, $fieldnamePrefix=NULL, $fieldnamePostfix=NULL)
    {

        // Get settings
        $settings = array();

        $schema = json_decode($this->get('schema'));
        $fieldCount = $this->get('fieldcount');

        $sortable = $this->get('allow_sorting_items') ? true : false;

        // Populate existing entries:
        $content = array();
        if(is_array($data)) {
            $entryCount = 1;
            foreach ($data as &$row) {
                if (!is_array($row)) $row = array($row);
                if (count($row) > $entryCount) $entryCount = count($row);
            }

            for($i=0; $i<$entryCount; $i++) {
                foreach ($schema as &$field) {
                    $entryValues[$i][] = $data[$field->handle][$i];

                    // append select box options:
                    if ($field->options->type == 'select') {
                        $this->_makePublishSelectOptions($data[$field->handle][$i], $field);
                    }
                }
                $content[] = Textgroup::createNewTextGroup($this->get('element_name'), $fieldCount, $entryValues[$i], NULL, $schema, $sortable);
            }
        }
        // Blank entry:
        else {
            foreach ($schema as &$field) {
                // append select box options
                if ($field->options->type == 'select') {
                    $this->_makePublishSelectOptions(NULL, $field);
                }
            }
            $content[] = Textgroup::createNewTextGroup($this->get('element_name'), $fieldCount, NULL, NULL, $schema, $sortable);
        }

        // Add template:
        if ($this->get('allow_new_items')) {
            $content[] = Textgroup::createNewTextGroup($this->get('element_name'), $fieldCount, NULL, 'template', $schema, $sortable);
        }

        // Create stage:
        $stage = new XMLElement('div', NULL, array('class' => 'dtg-stage' . ($sortable ? ' orderable' : '')));
        $stageInner = new XMLElement('ol');

        $stage->appendChild($stageInner);
        $stageInner->appendChildArray($content);

        // Field label:
        $holder = new XMLElement('div');

        $label = new XMLElement('label', $this->get('label'));
        if ($this->get('required') != 'yes') {
            $label->appendChild(new XMLElement('i', __('Optional')));
        }
        $holder->appendChild($label);

        // Append Stage:
        $holder->appendChild($stage);
        if ($this->get('allow_new_items')) {
            $stage->addClass('frame');
        }
        if ($this->get('allow_sorting_items')) {
            $stage->addClass('sortable');
        }

        if($flagWithError != NULL) $wrapper->appendChild(Widget::wrapFormElementWithError($holder, $flagWithError));
        else $wrapper->appendChild($holder);
    }



    /**
     * TODO fix this
     *
     * @see http://symphony-cms.com/learn/api/2.2/toolkit/field/#checkPostFieldData
     */
    public function checkPostFieldData($data, &$message, $entry_id=NULL)
    {
        $data = is_array($data) ? $data : array();
        $message = __("'%s' is a required field.", array($this->get('label')));

        $schema = $this->getFieldSchema();
        $entryCount = sizeof($data[0]);


        $empty = true;

        $badValidate = false;


        $badDate    = array();
        $badRadio   = array();
        $badCheck   = array();

        $checkItems = array();
        $radioItems = array();


        foreach($data as $i => $entry) {

            $emptyRow = true;
            $emptyReq = false;

            foreach ($schema as $f => $field) {

                // Get/set required option:
                $req = $field->options->required ? true : false;
                switch ($field->options->type) {
                    case 'text':
                        // Check if field passes any rules:
                        $rule = $field->options->validationRule != '' ? $field->options->validationRule : false;
                        if ($rule && !General::validateString($data[$field->handle][$i], $rule)){
                            $badValidate[] = array('handle' => $field->handle.'-holder', 'index' => $i);
                        }
                        // Check if required subfield is empty:
                        if ($req && $data[$field->handle][$i] == '') {
                            $emptyReq = true;
                        } else if ($data[$field->handle][$i] != '') {
                            $empty = false;
                            $emptyRow = false;
                        }
                        break;
                    case 'date':
                        // Check if field passes any rules:
                        if (fieldDate::parseFilter($data[$field->handle][$i]) == fieldDate::ERROR) {
                            $badDate[] = array('handle' => $field->handle.'-holder', 'index' => $i);
                        }
                        // Check if required subfield is empty:
                        if ($req && $data[$field->handle][$i] == '') {
                            $emptyReq = true;
                        } else if ($data[$field->handle][$i] != '') {
                            $empty = false;
                            $emptyRow = false;
                        }
                        break;

                    case 'select':
                        if ($req && $data[$field->handle][$i] == '') {
                            $emptyReq = true;
                        } else if ($data[$field->handle][$i] != '') {
                            $empty = false;
                            $emptyRow = false;
                        }
                        break;

                    case 'checkbox':
                        if ($i == 0) $checkItems[$f] = false;
                        if ($data[$field->handle][$i] == 'yes') {
                            $checkItems[$f] = true;
                            $emptyRow = false;
                            $empty = false;
                        }
                        if ($i == $entryCount-1  &&  $entryCount > 0  &&  !$checkItems[$f]  &&  $req  &&  !$empty) {
                            $badCheck[] = array('handle' => $field->handle.'-holder');
                        }
                        break;

                    case 'radio':
                        if ($i == 0) $radioItems[$f] = false;
                        if ($data[$field->handle][$i] == 'yes') {
                            $radioItems[$f] = true;
                            $emptyRow = false;
                            $empty = false;
                        }
                        if ($i == $entryCount-1  &&  $entryCount > 0  &&  !$radioItems[$f]  &&  $req  &&  !$empty) {
                            $badRadio[] = array('handle' => $field->handle.'-holder');
                        }
                        break;
                }
            }

            if (!$emptyRow && $emptyReq) {
                $message = __("'%s' contains required fields that are empty.", array($this->get('label')));
                return self::__MISSING_FIELDS__;
            }
        }

        if (!empty($badValidate)) {
            $badValidate = json_encode($badValidate);
            $message = __("'%s' contains invalid data. Please check the contents.<input type='hidden' id='badItems' value='%s' />", array($this->get('label'), $badValidate));
            return self::__INVALID_FIELDS__;
        }
        if (!empty($badDate)) {
            $badDate = json_encode($badDate);
            $message = __("'%s' contains invalid data. Please check the contents.<input type='hidden' id='badItems' value='%s' />", array($this->get('label'), $badDate));
            return self::__INVALID_FIELDS__;
        }
        if (!empty($badRadio)) {
            $badRadio = json_encode($badRadio);
            $message = __("'%s' contains required fields that are empty. <input type='hidden' id='badItems' value='%s' />", array($this->get('label'), $badRadio));
            return self::__MISSING_FIELDS__;
        }
        if (!empty($badCheck)) {
            $badCheck = json_encode($badCheck);
            $message = __("'%s' contains required fields that are empty. <input type='hidden' id='badItems' value='%s' />", array($this->get('label'), $badCheck));
            return self::__MISSING_FIELDS__;
        }

        if ($empty && $this->get('required') == 'yes') return self::__MISSING_FIELDS__;

        return self::__MISSING_FIELDS__;
    }


    /**
     * processRawFieldData
     *
     * @see http://symphony-cms.com/learn/api/2.2/toolkit/field/#processRawFieldData
     */
    public function processRawFieldData($data, &$status, $simulate=false, $entry_id=NULL)
    {
        $status = self::__OK__;
        if(!is_array($data)) return NULL;

        $result = array();
        $count = $this->get('fieldcount');

        // Check for the field with the most values
        $entryCount = 0;
        foreach ($data as $row) if (count($row) > $entryCount) $entryCount = count($row);

        // Check for empties
        $empty = true;

        for($i=0; $i < $entryCount; $i++) {
            $emptyEntry = true;
            foreach ($data as &$field) {
                if (!empty($field[$i])) {
                    $empty = false;
                    $emptyEntry = false;
                    // care for multiple select controls
                    if (is_array($field[$i])) {
                        $field[$i] = implode(',', $field[$i]);
                    }
                } else {
                    $field[$i] = ' ';
                }
            }
            if ($emptyEntry) {
                foreach ($data as &$field) {
                    unset($field[$i]);
                }
            }
        }

        if ($empty) {
            return null;
        } else {
            return $data;
        }

    }


    /**
     * createTable
     *
     * @see http://symphony-cms.com/learn/api/2.2/toolkit/field/#createTable
     */
    public function createTable()
    {
        return Symphony::Database()->query(
            "CREATE TABLE IF NOT EXISTS `tbl_entries_data_" . $this->get('id') . "` (
            `id` int(11) unsigned NOT NULL auto_increment,
            `entry_id` int(11) unsigned NOT NULL,
            PRIMARY KEY (`id`),
            KEY `entry_id` (`entry_id`)
            );"
        );
    }


    /**
     * prepareTableValue
     *
     * @param mixed $data
     * @see http://symphony-cms.com/learn/api/2.2/toolkit/field/#prepareTableValue
     */
    public function prepareTableValue($data, XMLElement $link=NULL)
    {
        if (is_array($data)) {
            $keys = array_keys($data);
            $key = $keys[0];

            if(!is_array($data[$key])) $data[$key] = array($data[$key]);
            if ($data[$key][0] != null) {
                $strung = count($data[$key]) == 1 ? count($data[$key]) . ' item' : count($data[$key]) . ' items';
            } else {
                $strung = null;
            }
        } else {
            $strung = null;
        }
        return $strung;
    }


    /**
     * buildDSRetrivalSQL
     *
     * Accepted filter:
     * handle:value	(e.g. first-name:Brock)
     * Where 'handle' is equal to the handle of a subfield, and 'value' is equal to the input of said subfield. All entries with a matching value in this subfield will be returned.
     *
     * @see http://symphony-cms.com/learn/api/2.2/toolkit/field/#buildDSRetrivalSQL
     */
    public function buildDSRetrivalSQL($data, &$joins, &$where, $andOperation = false)
    {
        $field_id = $this->get('id');

        if (preg_match('/.*:.*/', $data[0])) {
            $this->_key++;
            $joins .= "
                LEFT JOIN
                    `tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
                ON
                    (e.id = t{$field_id}_{$this->_key}.entry_id)
            ";

            $data[0] = explode(':', trim($this->cleanValue($data[0])));
            $handle = $data[0][0];
            $value = $data[0][1];

            $where .= "
                AND (
                    `t{$field_id}_{$this->_key}`.`{$handle}` IN ('{$value}')
                )
            ";
        }

        return true;
    }


    /* * * @see http://symphony-cms.com/learn/api/2.2/toolkit/field/#groupRecords * * */
    /*
    public function groupRecords($records) {
    }
    */


    /**
     * appendFormattedElement
     *
     * @see http://symphony-cms.com/learn/api/2.2/toolkit/field/#appendFormattedElement
     */
    public function appendFormattedElement(&$wrapper, $data, $encode = false, $mode = null, $entry_id)
    {

        // Get field properties and decode schema
        $fieldCount = $this->get('fieldcount');
        $schema = json_decode($this->get('schema'));
        $sampling = $schema[0]->handle;
        $entryCount = count($data[$sampling]);

        // Parse data
        $textgroup = new XMLElement($this->get('element_name'));
        if(is_array($data)) {
            foreach ($data as &$row) { if (!is_array($row)) $row = array($row); }
        }
        for($i=0; $i<$entryCount; $i++) {
            $item = new XMLElement('item');
            $empty = true;
            foreach ($schema as $field) {

                $f = new XMLElement($field->handle);
                // is select box and can have multiple items
                if ($field->options->type == 'select' && $field->options->allow_multiple) {
                    $val = array();
                    $pieces = explode(',', $data[$field->handle][$i]);
                    foreach ($pieces as $bit) {
                        $val[] = new XMLElement('item', General::sanitize($bit));
                    }
                    $f->setAttribute('items', sizeof($val));
                    $f->appendChildArray($val);
                // is datefield
                } elseif ($field->options->type == 'date') {
                    $f->setAttribute('time', NULL);
                    $f->setAttribute('weekday', NULL);
                    $f = General::createXMLDateObject($data[$field->handle][$i], $field->handle);
                } else {
                    $val = $data[$field->handle][$i] != ' ' ? General::sanitize($data[$field->handle][$i]) : '';
                    $f->setValue($val);
                }
                $item->appendChild($f);
            }
            $textgroup->appendChild($item);
        }

        // Append to data source
        $wrapper->appendChild($textgroup);
    }


    /* * * @see http://symphony-cms.com/learn/api/2.2/toolkit/field/#getParameterPoolValue * * */
    /*
    public function getParameterPoolValue($data) {
    }
    */


    /**
     * getExampleFormMarkup
     *
     * @access public
     * @see http://symphony-cms.com/learn/api/2.2/toolkit/field/#getExampleFormMarkup
     */
    public function getExampleFormMarkup()
    {
        $label = Widget::Label($this->get('label'));
        $schema = json_decode($this->get('schema'));
        $note = new XMLElement('strong', 'IMPORTANT: the event sample code is not updated when you make changes to DynamicTextGroup subfields in the section editor. Remember that your front-end fields must always match the back-end fields!');
        foreach ($schema as $field) $label->appendChild(Widget::Input('fields['.$this->get('element_name').']['.$field->handle.'][]'));
        return $label;
    }
}
