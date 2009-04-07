<?php
/**
* LG Image Manager extension file
* 
* This file must be placed in the
* /system/extensions/ folder in your ExpressionEngine installation.
*
* @package LgImageManager
* @version 1.2.0
* @author Leevi Graham <http://leevigraham.com>
* @copyright 2007
* @see http://leevigraham.com/cms-customisation/expressionengine/addon/lg-image-manager/
* @copyright Copyright (c) 2007-2008 Leevi Graham
* @license {@link http://creativecommons.org/licenses/by-sa/3.0/ Creative Commons Attribution-Share Alike 3.0 Unported} All source code commenting and attribution must not be removed. This is a condition of the attribution clause of the license.
*/



if ( ! defined('EXT')) exit('Invalid file request');

/**
* This extension adds a new custom field type to {@link http://expressionengine.com ExpressionEngine} that integrates {@link http://tinymce.moxiecode.com/paypal/item_filemanager.php Moxiecode File Manager}. 
*
* @package LgImageManager
* @version 1.2.0
* @author Leevi Graham <http://leevigraham.com>
* @copyright 2007
* @see http://leevigraham.com/cms-customisation/expressionengine/addon/lg-image-manager/
* @copyright Copyright (c) 2007-2008 Leevi Graham
* @license {@link http://creativecommons.org/licenses/by-sa/3.0/ Creative Commons Attribution-Share Alike 3.0 Unported} All source code commenting and attribution must not be removed. This is a condition of the attribution clause of the license.
* @todo Implement better security based on member session
*
*/
class Lg_image_manager {

	/**
	* Extension settings
	* @var array
	*/
	var $settings			= array();

	/**
	* Extension name
	* @var string
	*/
	var $name				= 'LG Image Manager';

	/**
	* Extension version
	* @var string
	*/
	var $version			= '1.2.0';

	/**
	* Extension description
	* @var string
	*/
	var $description		= 'Adds a new custom field type that integrates Moxiecode Image Manager';

	/**
	* If $settings_exist = 'y' then a settings page will be shown in the ExpressionEngine admin
	* @var string
	*/
	var $settings_exist		= 'y';

	/**
	* Link to extension documentation
	* @var string
	*/
	var $docs_url			= 'http://leevigraham.com/cms-customisation/expressionengine/addon/lg-image-manager/';	

	/**
	* Custom field type id
	* @var string
	*/
	var $type				= "img_man";



	/**
	* PHP4 Constructor
	*
	* @see __construct()
	*/
	function Lg_image_manager($settings='')
	{
		$this->__construct($settings);
	}



	/**
	* PHP 5 Constructor
	*
	* @param	array|string $settings Extension settings associative array or an empty string
	* @since	Version 1.2.0
	*/
	function __construct($settings='')
	{
		$this->settings = $settings;
	}



	/**
	* Configuration for the extension settings page
	*
	* @return	array The settings array
	*/
	function settings()
	{
		$settings = array();
		$settings['script_folder_path'] = "";
		return $settings;
	}



	/**
	* Activates the extension
	*
	* @return	bool Always TRUE
	*/
	function activate_extension()
	{
		global $DB;

		$default_settings = serialize( array('script_folder_path' => '/scripts/tinymce/jscripts/tiny_mce/plugins/imagemanager/js/') );

		$hooks = array(
			'publish_admin_edit_field_extra_row'	=> 'publish_admin_edit_field_extra_row',
			'publish_form_field_unique'				=> 'publish_form_field_unique',
			'show_full_control_panel_end' 			=> 'show_full_control_panel_end'
		);

		foreach ($hooks as $hook => $method)
		{
			$sql[] = $DB->insert_string( 'exp_extensions', 
											array('extension_id' 	=> '',
												'class'			=> get_class($this),
												'method'		=> $method,
												'hook'			=> $hook,
												'settings'		=> $default_settings,
												'priority'		=> 10,
												'version'		=> $this->version,
												'enabled'		=> "y"
											)
										);
		}

		// run all sql queries
		foreach ($sql as $query)
		{
			$DB->query($query);
		}
		return TRUE;
	}



	/**
	* Updates the extension
	*
	* If the exisiting version is below 1.2 then the update process changes some
	* method names. This may cause an error which can be resolved by reloading
	* the page.
	*
	* @param	string $current If installed the current version of the extension otherwise an empty string
	* @return	bool FALSE if the extension is not installed or is the current version
	*/
	function update_extension($current = '')
	{
		global $DB;
		
		if ($current == '' OR $current == $this->version) return FALSE;
		
		if ($current < '1.2.0')
	    {
			$sql[] = "UPDATE `exp_extensions` SET `method` = 'publish_admin_edit_field_extra_row' WHERE `method` = 'edit_custom_field' AND `class` =  '" . get_class($this) ."' LIMIT 1";
			$sql[] = "UPDATE `exp_extensions` SET `method` = 'publish_form_field_unique' WHERE `method` = 'publish' AND `class` =  '" . get_class($this) ."' LIMIT 1";
			$sql[] = "UPDATE `exp_extensions` SET `method` = 'show_full_control_panel_end' WHERE `method` = 'edit_field_groups' AND `class` =  '" . get_class($this) ."' LIMIT 1";
			$sql[] = "DELETE FROM `exp_extensions` WHERE `method` = 'add_header' AND `class` =  '" . get_class($this) ."' LIMIT 1";
		}

		$sql[] = "UPDATE exp_extensions SET version = '" . $DB->escape_str($this->version) . "' WHERE class = '" . get_class($this) . "'";

		// run all sql queries
		foreach ($sql as $query)
		{
			$DB->query($query);
		}
	}



	/**
	* Disables the extension the extension and deletes settings from DB
	*/
	function disable_extension()
	{
		global $DB;
		$DB->query("DELETE FROM exp_extensions WHERE class = '" . get_class($this) . "'");
	}



	/**
	* Adds the custom field option to the {@link http://expressionengine.com/docs/cp/admin/weblog_administration/custom_fields_edit.html Custom Weblog Fields - Add/Edit page}.
	*
	* @param	array $data The data about this field from the database
	* @return	string $r The page content
	* @since 	Version 1.2.0
	*/
	function publish_admin_edit_field_extra_row( $data, $r )
	{
		global $EXT, $REGX;

		// Check if we're not the only one using this hook
		if($EXT->last_call !== FALSE) $r = $EXT->last_call;

		// Set which blocks are displayed
		$items = array(
			"date_block" => "block",
			"select_block" => "none",
			"pre_populate" => "none",
			"text_block" => "none",
			"textarea_block" => "none",
			"rel_block" => "none",
			"relationship_type" => "none",
			"formatting_block" => "none",
			"formatting_unavailable" => "block",
			"direction_available" => "none",
			"direction_unavailable" => "block"
		);

		// is this field type equal to this type
		$selected = ($data["field_type"] == $this->type) ? " selected='true'" : "";

		// Add the option to the select drop down
		$r = preg_replace("/(<select.*?name=.field_type.*?value=.select.*?[\r\n])/is", "$1<option value='" . $REGX->form_prep($this->type) . "'" . $selected . ">" . $REGX->form_prep($this->name) . "</option>\n", $r);

		$js = "$1\n\t\telse if (id == '".$this->type."'){";

		foreach ($items as $key => $value)
		{
			$js .= "\n\t\t\tdocument.getElementById('" . $key . "').style.display = '" . $value . "';";
		}
		
		// automatically make this field have no formatting
		$js.= "\ndocument.field_form.field_fmt.selectedIndex = 0;\n";

		$js .= "\t\t}";

		 // Add the JS
		$r = preg_replace("/(id\s*==\s*.rel.*?})/is", $js, $r);

		// If existing field, select the proper blocks
		if(isset($data["field_type"]) && $data["field_type"] == $this->type)
		{
			foreach ($items as $key => $value)
			{
				preg_match('/(id=.' . $key . '.*?display:\s*)block/', $r, $match);

				// look for a block
				if(count($match) > 0 && $value == "none")
				{
					$r = str_replace($match[0], $match[1] . $value, $r);
				}
				// no block matches
				elseif($value == "block")
				{ 
					preg_match('/(id=.' . $key . '.*?display:\s*)none/', $r, $match);

					if(count($match) > 0)
					{
						$r = str_replace($match[0], $match[1] . $value, $r);
					}
				}
			}
		}
		return $r;
	}



	/**
	* Renders the custom field in the publish / edit form and sets a $SESS->cache array element so we know the field has been rendered
	*
	* @param	array $row Parameters for the field from the database
	* @param	string $field_data If entry is not new, this will have field's current value
	* @return	string The custom field html
	* @since 	Version 1.2.0
	*/
	function publish_form_field_unique( $row, $field_data )
	{
		global $DSP, $EXT, $SESS;

		// Check if we're not the only one using this hook
		$r = ($EXT->last_call !== FALSE) ? $EXT->last_call : "";

		// if this is a LG Image Manager field
		if($row["field_type"] == $this->type)
		{
			// create the input field
			$r .= $DSP->input_hidden('field_ft_'.$row['field_id'],$row['field_fmt']);
			$r .= $DSP->input_text("field_id_" . $row['field_id'], $field_data, '200', '600', 'input', '500px', '', TRUE);
			// add the javascript to launch the file manager
			$r .= "\n<a href='javascript:mcImageManager.open(\"entryform\",\"field_id_" . $row['field_id'] . "\",\"\",\"\", { remove_script_host:true, insert_filter : filterFunc } });'>";
			// add the image button
			$r .= "\n\t<img src='" . $this->settings['script_folder_path'] . "../pages/im/img/insertimage.gif' border='0' style='margin-left:4px; position:relative; top:5px;' />";
			// close the link
			$r .= "\n</a>";
			// make sure we add the initialisation scripts in show_full_control_panel_end
			$SESS->cache['Lg_image_manager']['require_scripts'] = TRUE;
		}
		return $r;
	}
	
	
	
	/**
	* Takes the control panel html and adds the Moxiecode Image Manager initialisation script
	*
	* @param	string $out The control panel html
	* @return	string The modified control panel html
	* @since 	Version 1.2.0
	*/
	function show_full_control_panel_end( $out )
	{
		global $DB, $EXT, $IN, $REGX, $SESS;

		// -- Check if we're not the only one using this hook
		if($EXT->last_call !== FALSE)
			$out = $EXT->last_call;

		// if we are displaying the custom field list
		if($IN->GBL('M', 'GET') == 'blog_admin' && ($IN->GBL('P', 'GET') == 'field_editor' || $IN->GBL('P', 'GET') == 'update_weblog_fields')  || $IN->GBL('P', 'GET') == 'delete_field')
		{
			// get the table rows
			if( preg_match_all("/C=admin&amp;M=blog_admin&amp;P=edit_field&amp;field_id=(\d*).*?<\/td>.*?<td.*?>.*?<\/td>.*?<\/td>/is", $out, $matches) )
			{
				// for each field id
				foreach($matches[1] as $key=>$field_id)
				{
					// get the field type
					$query = $DB->query("SELECT field_type FROM exp_weblog_fields WHERE field_id='" . $DB->escape_str($field_id) . "' LIMIT 1");

					// if the field type is wysiwyg
					if($query->row["field_type"] == $this->type)
					{
						$out = preg_replace("/(C=admin&amp;M=blog_admin&amp;P=edit_field&amp;field_id=" . $field_id . ".*?<\/td>.*?<td.*?>.*?<\/td>.*?)<\/td>/is", "$1" . $REGX->form_prep($this->name) . "</td>", $out);
					}
				}
			}
		}

		// if
		if(
			// we haven't already included the script
			isset($SESS->cache['Lg_image_manager']['scripts_included']) !== TRUE &&
			// AND a LG Image Manager field has been rendered
			isset($SESS->cache['Lg_image_manager']['require_scripts']) === TRUE &&
			// AND its a publish or an edit page
			($IN->GBL('C', 'GET') == 'publish' || $IN->GBL('C', 'GET') == 'edit')
		)
		{
			// create the script string
			$r = "<script type='text/javascript' src='" . $this->settings['script_folder_path'] . "mcimagemanager.js' ></script>\n";
			$r .= "\n<script type='text/javascript' charset='utf-8'>
				function filterFunc(data) {
					console.log(data);
				    data.url = data.url.toUpperCase(); // Converts the URL to uppercase
				}
			</script>";
			// add the script string before the closing head tag
			$out = str_replace("</head>", $r . "</head>", $out);
			// make sure we don't add it again
			$SESS->cache['Lg_image_manager']['scripts_included'] = TRUE;
		}
		// return the modified control panel
		return $out;
	}

}

?>