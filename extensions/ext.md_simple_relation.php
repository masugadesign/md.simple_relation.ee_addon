<?php
/* ===========================================================================
ext.md_simple_relation.php ---------------------------
Creates a "simple" relationship that stores only the related entry_id for weblogs or galleries.
            
INFO ---------------------------
Developed by: Ryan Masuga, masugadesign.com
Created:   Jul 07 2008
Last Mod:  Jan 23 2009

Related Thread: http://expressionengine.com/forums/viewthread/84495/

http://expressionengine.com/docs/development/extensions.html
=============================================================================== */
if ( ! defined('EXT')) { exit('Invalid file request'); }


if ( ! defined('MD_SR_version')){
	define("MD_SR_version",			"1.1.3");
	define("MD_SR_docs_url",		"http://masugadesign.com/the-lab/scripts/simple-relation/");
	define("MD_SR_addon_id",		"MD Simple Relation");
	define("MD_SR_extension_class",	"Md_simple_relation");
	define("MD_SR_cache_name",		"mdesign_cache");
}


class Md_simple_relation
{
	var $settings		= array();
	var $name           = 'MD Simple Relation';
	var $type           = 'simplerel';
	var $version        = MD_SR_version;
	var $description    = 'Creates a simple relationship that stores only the entry_id for weblogs or galleries.';
	var $settings_exist = 'y';
	var $docs_url       = MD_SR_docs_url;


// --------------------------------
//  PHP 4 Constructor
// --------------------------------
	function Md_simple_relation($settings='')
	{
		$this->__construct($settings);
	}

// --------------------------------
//  PHP 5 Constructor
// --------------------------------
	function __construct($settings='')
	{
		global $IN, $SESS;
		if(isset($SESS->cache['mdesign']) === FALSE){ $SESS->cache['mdesign'] = array();}
		$this->settings = $this->_get_settings();
		$this->debug = $IN->GBL('debug');
	}


/* SETTINGS */
	function _get_settings($force_refresh = FALSE, $return_all = FALSE)
	{
		global $SESS, $DB, $REGX, $LANG, $PREFS;

		// assume there are no settings
		$settings = FALSE;

		// Get the settings for the extension
		if(isset($SESS->cache['mdesign'][MD_SR_addon_id]['settings']) === FALSE || $force_refresh === TRUE)
		{
			// check the db for extension settings
			$query = $DB->query("SELECT settings FROM exp_extensions WHERE enabled = 'y' AND class = '" . MD_SR_extension_class . "' LIMIT 1");

			// if there is a row and the row has settings
			if ($query->num_rows > 0 && $query->row['settings'] != '')
			{
				// save them to the cache
				$SESS->cache['mdesign'][MD_SR_addon_id]['settings'] = $REGX->array_stripslashes(unserialize($query->row['settings']));
			}
		}

		// check to see if the session has been set
		// if it has return the session
		// if not return false
		if(empty($SESS->cache['mdesign'][MD_SR_addon_id]['settings']) !== TRUE)
		{
			$settings = ($return_all === TRUE) ?  $SESS->cache['mdesign'][MD_SR_addon_id]['settings'] : $SESS->cache['mdesign'][MD_SR_addon_id]['settings'][$PREFS->ini('site_id')];
		}

		return $settings;
	}


	function settings_form($current)
	{
		global $DB, $DSP, $LANG, $IN, $PREFS, $SESS;

		// create a local variable for the site settings
		$settings = $this->_get_settings();

		$DSP->crumbline = TRUE;

		$DSP->title  = $LANG->line('extension_settings');
		$DSP->crumb  = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=utilities', $LANG->line('utilities')).
		$DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=extensions_manager', $LANG->line('extensions_manager')));

		$DSP->crumb .= $DSP->crumb_item($LANG->line('md_simple_relation_title') . " {$this->version}");

		$DSP->right_crumb($LANG->line('disable_extension'), BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=toggle_extension_confirm'.AMP.'which=disable'.AMP.'name='.$IN->GBL('name'));

		$DSP->body = '';
		$DSP->body .= $DSP->heading($LANG->line('md_simple_relation_title') . " <small>{$this->version}</small>");
		$DSP->body .= $DSP->form_open(
								array(
									'action' => 'C=admin'.AMP.'M=utilities'.AMP.'P=save_extension_settings'
								),
								// WHAT A M*THERF!@KING B!TCH THIS WAS
								// REMEMBER THE NAME ATTRIBUTE MUST ALWAYS MATCH THE FILENAME AND ITS CASE SENSITIVE
								// BUG??
								array('name' => strtolower(MD_SR_extension_class))
		);
	
	// EXTENSION ACCESS
	$DSP->body .= $DSP->table_open(array('class' => 'tableBorder', 'border' => '0', 'style' => 'margin-top:18px; width:100%'));

	$DSP->body .= $DSP->tr()
		. $DSP->td('tableHeading', '', '2')
		. $LANG->line("access_rights")
		. $DSP->td_c()
		. $DSP->tr_c();

	$DSP->body .= $DSP->tr()
		. $DSP->td('tableCellOne', '30%')
		. $DSP->qdiv('defaultBold', $LANG->line('enable_extension_for_this_site'))
		. $DSP->td_c();

	$DSP->body .= $DSP->td('tableCellOne')
		. "<select name='enable'>"
					. $DSP->input_select_option('y', "Yes", (($settings['enable'] == 'y') ? 'y' : '' ))
					. $DSP->input_select_option('n', "No", (($settings['enable'] == 'n') ? 'y' : '' ))
					. $DSP->input_select_footer()
		. $DSP->td_c()
		. $DSP->tr_c()
		. $DSP->table_c();


		$DSP->body .= $DSP->qdiv('itemWrapperTop', $DSP->input_submit("Submit"))
					. $DSP->form_c();
	}



	/**
	* Save Settings
	* 
	* @since	Version 1.0.0
	**/
	function save_settings()
	{
		// make somethings global
		global $DB, $IN, $LANG, $OUT, $PREFS, $REGX, $SESS;

		$LANG->fetch_language_file("md_simple_relation");

		// create a default settings array
		$default_settings = array(
		//	"allowed_member_groups" => array(),
		//	"weblogs" => array()
		);

		// merge the defaults with our $_POST vars
		$site_settings = array_merge($default_settings, $_POST);

		// unset the name
		unset($site_settings['name']);

		// load the settings from cache or DB
		// force a refresh and return the full site settings
		$settings = $this->_get_settings(TRUE, TRUE);

		// add the posted values to the settings
		$settings[$PREFS->ini('site_id')] = $site_settings;

		// update the settings
		$query = $DB->query($sql = "UPDATE exp_extensions SET settings = '" . addslashes(serialize($settings)) . "' WHERE class = '" . MD_SR_extension_class . "'");

		$this->settings = $settings[$PREFS->ini('site_id')];

		if($this->settings['enable'] == 'y')
		{
			if (session_id() == "") session_start(); // if no active session we start a new one
		}
	}


	
	// --------------------------------
	//  Activate Extension
	// --------------------------------
	function activate_extension()
	{
		global $DB, $PREFS;
		
		$default_settings = serialize(
			array(
			  // 'check_for_updates' => 'y'
			)
		);


		// get the list of installed sites
		$query = $DB->query("SELECT * FROM exp_sites");

		// if there are sites - we know there will be at least one but do it anyway
		if ($query->num_rows > 0)
		{
			// for each of the sites
			foreach($query->result as $row)
			{
				// build a multi dimensional array for the settings
				$settings[$row['site_id']] = $default_settings;
			}
		}		
		
		$hooks = array(
		  'show_full_control_panel_end'         => 'show_full_control_panel_end',
		  'publish_admin_edit_field_extra_row'  => 'publish_admin_edit_field_extra_row',
			'publish_form_field_unique'           => 'publish_form_field_unique'
		);
		
		foreach ($hooks as $hook => $method)
		{
			$sql[] = $DB->insert_string( 'exp_extensions', 
				array('extension_id' 	=> '',
					'class'			=> get_class($this),
					'method'		=> $method,
					'hook'			=> $hook,
					'settings'		=> addslashes(serialize($settings)),
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


	// --------------------------------
	//  Edit Custom Field
	// --------------------------------
	
	function publish_admin_edit_field_extra_row($data, $r)
	{
		global $EXT, $LANG, $DB, $REGX;
		
	  // Check if we're not the only one using this hook
		if($EXT->last_call !== FALSE) $r = $EXT->last_call;

		// Set which blocks are displayed
		$items = array(
			"date_block" => "block",
			"select_block" => "none",
			"pre_populate" => "none",
			"text_block" => "none",
			"textarea_block" => "none",
			"rel_block" => "block",
			"relationship_type" => "block",
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


	function publish_form_field_unique( $row, $field_data )
	{
		global $EXT, $DSP, $DB;
		// Check if we're not the only one using this hook
		$r = ($EXT->last_call !== FALSE) ? $EXT->last_call : "";
		
		// if we have a match on field types
		if($row["field_type"] == $this->type)
		{
		  
		$field_id = 'field_id_'.$row['field_id'];
    $sort = "title";
    $limit = "";
    
    if ($row["field_related_sort"] == "date") {
      $sort = "entry_date";
    }
    
    if ($row["field_related_max"] > 0) {
      $limit = "LIMIT ".$row["field_related_max"];
    }
  
  if ($row["field_related_to"] == "gallery") {
    
    $results = $DB->query("SELECT cat_id, cat_name FROM exp_gallery_categories WHERE gallery_id = '".$row["field_related_id"]."' ORDER BY cat_order ".$limit."");
    
  } else {
    
    $results = $DB->query("SELECT entry_id, title FROM exp_weblog_titles WHERE weblog_id = '".$row["field_related_id"]."' ORDER BY ".$sort." ".$row["field_related_sort"]." ".$limit."");
    
  }
						
        $r .= $DSP->input_select_header($field_id, '', 1);
				$selected = ($field_data == '') ? 'y' : '';
				$r .= $DSP->input_select_option('', 'None', $selected);

				foreach ($results->result as $rw)
				{		  
      	  if ($row["field_related_to"] == "gallery") {			  
      			$selected = ($field_data == $rw['cat_id']) ? 'y' : '';
      			$r .= $DSP->input_select_option($rw['cat_id'], $rw['cat_name'], $selected); 
      		} else {
      		  $selected = ($field_data == $rw['entry_id']) ? 'y' : '';
      			$r .= $DSP->input_select_option($rw['entry_id'], $rw['title'], $selected);
      		} 
				}
				$r .= $DSP->input_select_footer();
		}
		return $r;
	}



	// --------------------------------
	//  Edit Field Group
	// --------------------------------
	
	
	function show_full_control_panel_end($out)
	{
		global $DB, $EXT, $LANG;
		
		// Check if we're not the only one using this hook
		
		if($EXT->last_call !== false)
		{
			$out = $EXT->last_call;
		}	

// add name to field type table
if(preg_match_all("/C=admin&amp;M=blog_admin&amp;P=edit_field&amp;field_id=(\d*).*?<\/td>.*?<td.*?>.*?<\/td>.*?<\/td>/is", $out, $matches))
		{
			foreach($matches[1] as $key=>$field_id)
			{
				$query = $DB->query("SELECT field_type
				                       FROM exp_weblog_fields
				                      WHERE field_id='".$field_id."'
				                      LIMIT 1");
				
				if($query->row['field_type'] == $this->type)
				{
					$replace = preg_replace("/(<td.*?<td.*?>).*?<\/td>/si", "$1".$this->name."</td>", $matches[0][$key]);
					$out = str_replace($matches[0][$key], $replace, $out);
				}
			}
			return $out;
		}
		else
		{
		    return $out;
		}
	}

	
	// --------------------------------
	//  Disable Extension
	// -------------------------------- 
	function disable_extension()
	{
		global $DB;
		$DB->query("DELETE FROM exp_extensions WHERE class = '" . get_class($this) . "'");
	}
	
	// --------------------------------
	//  Update Extension
	// --------------------------------  
	function update_extension($current='')
	{
		global $DB;
		
		if ($current == '' OR $current == $this->version)
		{
			return FALSE;
		}
		
		$DB->query("UPDATE exp_extensions
		            SET version = '".$DB->escape_str($this->version)."'
		            WHERE class = '".get_class($this)."'");
	}

}
?>