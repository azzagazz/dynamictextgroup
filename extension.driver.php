<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */


/**
 * extension_dynamictextgroup 
 * 
 * @uses Extension
 * @package DynamicTextgroup
 * @author Brock Petrie <brockpetrie@gmail.com> 
 * @author Thomas Appel <mail@thomas-appel.com> 
 */
class extension_dynamictextgroup extends Extension 
{
    
    /**
     * install 
     * 
     * @see http://symphony-cms.com/learn/api/2.2/toolkit/extension/#install
     */
    public function install()
    {
        $status = array();
        
        // Create database field table
        $status[] = Symphony::Database()->query(
            "CREATE TABLE `tbl_fields_dynamictextgroup` (
                `id` int(11) unsigned NOT NULL auto_increment,
                `field_id` int(11) unsigned NOT NULL,
                `fieldcount` tinyint(1),
                `allow_new_items` tinyint(1),
                `allow_sorting_items` tinyint(1),
                `schema` text,
                PRIMARY KEY  (`id`),
                KEY `field_id` (`field_id`)
            )"
        );

        // Report status
        if(in_array(false, $status, true)) {
            return false;
        }
        else {
            return true;
        }
    }

    /**
     * getSubscribedDelegates 
     * 
     * @see http://symphony-cms.com/learn/api/2.2/toolkit/extension/#getSubscribedDelegates
     */
    public function getSubscribedDelegates()
    {
        return array(
            array(
                'page' => '/backend/',
                'delegate' => 'AdminPagePreGenerate',
                'callback' => '_appendAssets'
            ),
        );
    }

    /**
     * hasInstance 
     *
     * determine wheater an extension field is used on a backendpage or not
     * 
     * @param mixed $ext_name name of the extension
     * @param mixed $section_handle handle of the section the field is used on
     * @param mixed $sid  section id of the section the field is used on
     *
     * @static
     * @access public
     * @return void
     */
    public static function hasInstance($ext_name=NULL, $section_handle = NULL, $sid = NULL)
    {
        $sid  = $sid ? $sid : SectionManager::fetchIDFromHandle($section_handle);
        $section = SectionManager::fetch($sid);
        
        if ($section instanceof Section) {
            $fm = $section->fetchFields($ext_name);
            return is_array($fm) && !empty($fm);
        }

        return false;
    }		

    /**
     * _appendAssets 
     * 
     * @param  mixed $context 
     * @access public
     * @return void
     */
    public function _appendAssets($context) 
    {	

        $callback = Symphony::Engine()->getPageCallback();

        // Append styles for publish area
        if ($callback['driver'] == 'publish' && $callback['context']['page'] != 'index') {

            if (self::hasInstance('dynamictextgroup', $callback['context']['section_handle'])) {
                Administration::instance()->Page->addStylesheetToHead(URL . '/extensions/dynamictextgroup/assets/dynamictextgroup.publish.css', 'screen', 103, false);
                Administration::instance()->Page->addScriptToHead(URL . '/extensions/dynamictextgroup/assets/flexie.min.js', 106, false);
                Administration::instance()->Page->addScriptToHead(URL . '/extensions/dynamictextgroup/assets/dynamictextgroup.multiselect.js', 108, false);
                Administration::instance()->Page->addScriptToHead(URL . '/extensions/dynamictextgroup/assets/dynamictextgroup.publish.js', 109, false);
            }
        }

        // Append styles for section area
        if($callback['driver'] == 'blueprintssections' && is_array($callback['context'])) {
            if (self::hasInstance('dynamictextgroup', NULL, isset($callback['context'][1])? $callback['context'][1] : NULL)) {
                Administration::instance()->Page->addStylesheetToHead(URL . '/extensions/dynamictextgroup/assets/dynamictextgroup.fieldeditor.css', 'screen', 103, false);
                Administration::instance()->Page->addScriptToHead(URL . '/extensions/dynamictextgroup/assets/json2.js', 104, false);
                Administration::instance()->Page->addScriptToHead(URL . '/extensions/dynamictextgroup/assets/dynamictextgroup.fieldeditor.js', 105, false);
            }
        }
    }

    /**
     * uninstall 
     * 
     * @see http://symphony-cms.com/learn/api/2.2/toolkit/extension/#uninstall
     */
    public function uninstall() 
    {
    
        // Drop date and time table
        Symphony::Database()->query("DROP TABLE `tbl_fields_dynamictextgroup`");
    }

    /**
     * update 
     * 
     * @see http://symphony-cms.com/learn/api/2.2/toolkit/extension/#update
     */
    public function update($previousVersion)
    {
        $previousVersion = preg_replace('/\w+\s?$/i', '', $previousVersion);

        if (version_compare($previousVersion, '2.1.1', '<')) {
            Symphony::Database()->query("ALTER TABLE `tbl_fields_dynamictextgroup` ADD COLUMN `allow_new_items` tinyint(1)");
            Symphony::Database()->query("ALTER TABLE `tbl_fields_dynamictextgroup` ADD COLUMN `allow_sorting_items` tinyint(1)");
        }
    }

}
