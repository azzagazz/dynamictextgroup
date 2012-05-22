<?php

	/* * * 	@package dynamictextgroup 	* * */
	/* * * 	Dynamic Text Group 			* * */
	
	Class extension_dynamictextgroup extends Extension {
		
		
		/* * * @see http://symphony-cms.com/learn/api/2.2/toolkit/extension/#install * * */
		public function install() {
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


		/* * * @see http://symphony-cms.com/learn/api/2.2/toolkit/extension/#uninstall * * */
		public function uninstall() {
		
			// Drop date and time table
			Symphony::Database()->query("DROP TABLE `tbl_fields_dynamictextgroup`");
		}

		/**
		 * @see http://symphony-cms.com/learn/api/2.2/toolkit/extension/#update
		 */
		public function update($previousVersion) {
			$previousVersion = preg_replace('/\w+\s?$/i', '', $previousVersion);

			if (version_compare($previousVersion, '2.1.1', '<')) {
				Symphony::Database()->query("ALTER TABLE `tbl_fields_dynamictextgroup` ADD COLUMN `allow_new_items` tinyint(1)");
				Symphony::Database()->query("ALTER TABLE `tbl_fields_dynamictextgroup` ADD COLUMN `allow_sorting_items` tinyint(1)");
			}
		}

	}
