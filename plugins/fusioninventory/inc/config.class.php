<?php

/**
 * FusionInventory
 *
 * Copyright (C) 2010-2016 by the FusionInventory Development Team.
 *
 * http://www.fusioninventory.org/
 * https://github.com/fusioninventory/fusioninventory-for-glpi
 * http://forge.fusioninventory.org/
 *
 * ------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of FusionInventory project.
 *
 * FusionInventory is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * FusionInventory is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with FusionInventory. If not, see <http://www.gnu.org/licenses/>.
 *
 * ------------------------------------------------------------------------
 *
 * This file is used to manage the configuration of the plugin.
 *
 * ------------------------------------------------------------------------
 *
 * @package   FusionInventory
 * @author    David Durieux
 * @copyright Copyright (c) 2010-2016 FusionInventory team
 * @license   AGPL License 3.0 or (at your option) any later version
 *            http://www.gnu.org/licenses/agpl-3.0-standalone.html
 * @link      http://www.fusioninventory.org/
 * @link      https://github.com/fusioninventory/fusioninventory-for-glpi
 *
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Manage the configuration of the plugin.
 */
class PluginFusioninventoryConfig extends CommonDBTM {

   /**
    * Initialize the displaylist public variable
    *
    * @var boolean
    */
   public $displaylist = FALSE;

   /**
    * The right name for this class
    *
    * @var string
    */
   static $rightname = 'plugin_fusioninventory_configuration';

   /**
    * Define number to the action 'clean' of agents
    *
    * @var integer
    */
   CONST ACTION_CLEAN = 0;

   /**
    * Define number to the action 'change status' of agents
    *
    * @var integer
    */
   CONST ACTION_STATUS = 1;


   /**
    * Initialize config values of fusioninventory plugin
    *
    * @param boolean $getOnly
    * @return array
    */
   function initConfigModule($getOnly=FALSE) {

      $input = array();
      $input['version']                = PLUGIN_FUSIONINVENTORY_VERSION;
      $input['ssl_only']               = '0';
      $input['delete_task']            = '20';
      $input['inventory_frequence']    = '24';
      $input['agent_port']             = '62354';
      $input['extradebug']             = '0';
      $pfSetup = new PluginFusioninventorySetup();
      $users_id = $pfSetup->createFusionInventoryUser();
      $input['users_id']               = $users_id;
      $input['agents_old_days']        = '0';
      $input['agents_action']          = 0;
      $input['agents_status']          = 0;
      $input['wakeup_agent_max']       = '10';

      $input['import_software']        = 1;
      $input['import_volume']          = 1;
      $input['import_antivirus']       = 1;
      $input['import_registry']        = 1;
      $input['import_process']         = 1;
      $input['import_vm']              = 1;
      $input['component_processor']    = 1;
      $input['component_memory']       = 1;
      $input['component_harddrive']    = 1;
      $input['component_networkcard']  = 1;
      $input['component_graphiccard']  = 1;
      $input['component_soundcard']    = 1;
      $input['component_drive']        = 1;
      $input['component_networkdrive'] = 1;
      $input['component_control']      = 1;
      $input['states_id_default']      = 0;
      $input['location']               = 0;
      $input['group']                  = 0;
      $input['create_vm']              = 0;
      $input['component_networkcardvirtual'] = 1;
      $input['otherserial']            = 0;

      $input['threads_networkdiscovery'] = 20;
      $input['threads_networkinventory'] = 10;
      $input['timeout_networkdiscovery'] = 1;
      $input['timeout_networkinventory'] = 15;

      //deploy config variables
      $input['server_upload_path'] =
              Toolbox::addslashes_deep(
                  implode(
                     DIRECTORY_SEPARATOR,
                     array(
                        GLPI_PLUGIN_DOC_DIR,
                        'fusioninventory',
                        'upload'
                     )
                  )
               );
      $input['alert_winpath'] = 1;
      $input['server_as_mirror'] = 1;
      $input['manage_osname'] = 1;

      if (!$getOnly) {
         $this->addValues($input);
      }
      return $input;
   }



   /**
    * Get name of this type by language of the user connected
    *
    * @param integer $nb number of elements
    * @return string name of this type
    */
   static function getTypeName($nb=0) {

      return __('General setup');

   }



   /**
    * Add multiple configuration values
    *
    * @param array $values configuration values, indexed by name
    * @param boolean $update say if add or update in database
    */
   function addValues($values, $update=TRUE) {

      foreach ($values as $type=>$value) {
         if ($this->getValue($type) === NULL) {
            $this->addValue($type, $value);
         } else if ($update == TRUE) {
            $this->updateValue($type, $value);
         }
      }
   }



   /**
    * Define tabs to display on form page
    *
    * @param array $options
    * @return array containing the tabs name
    */
   function defineTabs($options=array()) {

      $plugin = new Plugin;

      $ong = array();
      $moduleTabs = array();
      $this->addStandardTab("PluginFusioninventoryConfig", $ong, $options);
      $this->addStandardTab("PluginFusioninventoryAgentmodule", $ong, $options);
      $this->addStandardTab("PluginFusioninventoryLock", $ong, $options);

      if (isset($_SESSION['glpi_plugin_fusioninventory']['configuration']['moduletabforms'])) {
         $fusionTabs = $ong;
         $moduleTabForms =
                  $_SESSION['glpi_plugin_fusioninventory']['configuration']['moduletabforms'];
         if (count($moduleTabForms)) {
            foreach ($moduleTabForms as $module=>$form) {
               if ($plugin->isActivated($module)) {
                  $this->addStandardTab($form[key($form)]['class'], $ong, $options);
               }
            }
            $moduleTabs = array_diff($ong, $fusionTabs);
         }
         $_SESSION['glpi_plugin_fusioninventory']['configuration']['moduletabs'] = $moduleTabs;
      }
      return $ong;
   }



   /**
    * Get the tab name used for item
    *
    * @param object $item the item object
    * @param integer $withtemplate 1 if is a template form
    * @return string|array name of the tab
    */
   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if ($item->getType()==__CLASS__) {
         return array(
             __('General setup'),
             __('Computer Inventory', 'fusioninventory'),
             __('Network Inventory', 'fusioninventory'),
             __('Package management', 'fusioninventory')
         );
      }
      return '';
   }



   /**
    * Display the content of the tab
    *
    * @param object $item
    * @param integer $tabnum number of the tab to display
    * @param integer $withtemplate 1 if is a template form
    * @return boolean
    */
   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      switch ($tabnum) {

         case 0:
            $item->showForm();
            return TRUE;

         case 1:
            $item->showFormInventory();
            return TRUE;

         case 2:
            $item->showFormNetworkInventory();
            return TRUE;

         case 3:
            $item->showFormDeploy();
            return TRUE;

      }
      return FALSE;
   }



   /**
    * Get configuration value with name
    *
    * @global array $PF_CONFIG
    * @param string $name name in configuration
    * @return null|string|integer
    */
   function getValue($name) {
      global $PF_CONFIG;

      if (isset($PF_CONFIG[$name])) {
         return $PF_CONFIG[$name];
      }

      $config = current($this->find("`type`='".$name."'"));
      if (isset($config['value'])) {
         return $config['value'];
      }
      return NULL;
   }



   /**
    * Give state of a config field for a fusioninventory plugin
    *
    * @param string $name name in configuration
    * @return boolean
    */
   function isActive($name) {
      if (!($this->getValue($name))) {
         return FALSE;
      } else {
         return TRUE;
      }
   }



   /**
    * Display form
    *
    * @param array $options
    * @return true
    */
   function showForm($options=array()) {

      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('SSL-only for agent', 'fusioninventory')."&nbsp;:</td>";
      echo "<td width='20%'>";
      Dropdown::showYesNo("ssl_only", $this->isActive('ssl_only'));
      echo "</td>";
      echo "<td>".__('Inventory frequency (in hours)', 'fusioninventory')."&nbsp;:</td>";
      echo "<td width='20%'>";
      Dropdown::showNumber("inventory_frequence", array(
             'value' => $this->getValue('inventory_frequence'),
             'min' => 1,
             'max' => 240)
         );
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Delete tasks logs after', 'fusioninventory')." :</td>";
      echo "<td>";
      Dropdown::showNumber("delete_task", array(
             'value' => $this->getValue('delete_task'),
             'min'   => 1,
             'max'   => 240,
             'unit'  => 'day')
      );
      echo "</td>";

      echo "<td>".__('Agent port', 'fusioninventory')." :</td>";
      echo "<td>";
      echo "<input type='text' name='agent_port' value='".$this->getValue('agent_port')."'/>";
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Extra-debug', 'fusioninventory')." :</td>";
      echo "<td>";
      Dropdown::showYesNo("extradebug", $this->isActive('extradebug'));
      echo "</td>";
      echo "<td colspan=2></td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td colspan =2></td>";
      echo "<td>".__('Maximum number of agents to wake up in a task', 'fusioninventory')."&nbsp;:</td>";
      echo "<td width='20%'>";
      Dropdown::showNumber("wakeup_agent_max", array(
             'value' => $this->getValue('wakeup_agent_max'),
             'min' => 1,
             'max' => 100)
         );
      echo "</td>";

      echo "</tr>";
      echo "<tr class='tab_bg_1'>";
      echo "<th colspan=4 >" . __('Update agents', 'fusioninventory') . "</th></tr>";
      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Update agents not have contacted server since (in days)', 'fusioninventory') . "&nbsp;:</td>";
      echo "<td width='20%'>";
      Dropdown::showNumber("agents_old_days", array(
         'value' => $this->getValue('agents_old_days'),
         'min'   => 1,
         'max'   => 1000,
         'toadd' => array('0' => __('Disabled')))
      );
      echo "</td>";
      echo "<td>" . __('Action') . "&nbsp;:</td>";
      echo "<td width='20%'>";
      //action
      $rand = Dropdown::showFromArray('agents_action',
                                      array(self::getActions(self::ACTION_CLEAN), self::getActions(self::ACTION_STATUS)),
                                      array('value' => $this->getValue('agents_action'), 'on_change' => 'changestatus();'));
      //if action == action_status => show blocation else hide blocaction
      echo Html::scriptBlock("
         function changestatus() {
            if ($('#dropdown_agents_action$rand').val() != 0) {
               $('#blocaction1').show();
               $('#blocaction2').show();
            } else {
               $('#blocaction1').hide();
               $('#blocaction2').hide();
            }
         }
         changestatus();

      ");
      echo "</td>";
      echo "</tr>";
      //blocaction with status
      echo "<tr class='tab_bg_1'><td colspan=2></td>";
      echo "<td>";
      echo "<span id='blocaction1' style='display:none'>";
      echo __('Change the status', 'fusioninventory') . "&nbsp;:";
      echo "</span>";
      echo "</td>";
      echo "<td width='20%'>";
      echo "<span id='blocaction2' style='display:none'>";
      State::dropdown(array('name'   => 'agents_status',
         'value'  => $this->getValue('agents_status'),
         'entity' => $_SESSION['glpiactive_entity']));
      echo "</span>";
      echo "</td>";

      echo "</tr>";

      $options['candel'] = FALSE;
      $this->showFormButtons($options);

      return TRUE;
   }



   /**
    * Get the action for agent action
    *
    * @param integer $action
    * @return string
    */
   static function getActions($action) {
      switch ($action) {

         case self::ACTION_STATUS :
              return __('Change the status', 'fusioninventory');

         case self::ACTION_CLEAN :
              return __('Clean agents', 'fusioninventory');

      }
   }



   /**
    * Display form for tab 'Inventory'
    *
    * @param array $options
    * @return true
    */
   static function showFormInventory($options=array()) {

      $pfConfig = new PluginFusioninventoryConfig();

      $pfConfig->fields['id'] = 1;
      $pfConfig->showFormHeader($options);

      echo "<tr>";
      echo "<th colspan='4'>";
      echo __('Import options', 'fusioninventory');
      echo "</th>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo _n('Volume', 'Volumes', 2)."&nbsp;:";
      echo "</td>";
      echo "<td width='360'>";
      Dropdown::showYesNo("import_volume", $pfConfig->getValue('import_volume'));
      echo "</td>";
      echo "<th colspan='2' width='30%'>";
      echo _n('Component', 'Components', 2);
      echo "</th>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo _n('Software', 'Software', 2)."&nbsp;:";
      echo "</td>";
      echo "<td>";
      Dropdown::showYesNo("import_software", $pfConfig->getValue('import_software'));
      echo "</td>";
      echo "<td>";
      echo _n('Processor', 'Processors', 2)."&nbsp;:";
      echo "</td>";
      echo "<td>";
      Dropdown::showYesNo("component_processor", $pfConfig->getValue('component_processor'));
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo _n('Virtual machine', 'Virtual machines', 2)."&nbsp;:";
      echo "</td>";
      echo "<td>";
      Dropdown::showYesNo("import_vm", $pfConfig->getValue('import_vm'));
      echo "</td>";
      echo "<td>";
      echo _n('Memory', 'Memories', 2)."&nbsp;:";
      echo "</td>";
      echo "<td>";
      Dropdown::showYesNo("component_memory", $pfConfig->getValue('component_memory'));
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo __('Antivirus', 'fusioninventory')."&nbsp;:";
      echo "</td>";
      echo "<td>";
      Dropdown::showYesNo("import_antivirus",
                          $pfConfig->getValue('import_antivirus'));
      echo "</td>";
      echo "<td>";
      echo _n('Hard drive', 'Hard drives', 2)."&nbsp;:";
      echo "</td>";
      echo "<td>";
      Dropdown::showYesNo("component_harddrive", $pfConfig->getValue('component_harddrive'));
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo _n('Location', 'Locations', 2)."&nbsp;:";
      echo "</td>";
      echo "<td>";
      Dropdown::showFromArray("location",
                              array("0"=>"------",
                                    "1"=>__('FusionInventory tag', 'fusioninventory')),
                              array('value'=>$pfConfig->getValue('location')));
      echo "</td>";
      echo "<td>";
      echo _n('Network card', 'Network cards', 2)."&nbsp;:";
      echo "</td>";
      echo "<td>";
      Dropdown::showYesNo("component_networkcard", $pfConfig->getValue('component_networkcard'));
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo _n('Group', 'Groups', 2)."&nbsp;:";
      echo "</td>";
      echo "<td>";
      Dropdown::showFromArray("group",
                              array("0"=>"------",
                                    "1"=>__('FusionInventory tag', 'fusioninventory')),
                              array('value'=>$pfConfig->getValue('group')));
      echo "</td>";
      echo "<td>";
      echo __('Virtual network card', 'fusioninventory')."&nbsp;:";
      echo "</td>";
      echo "<td>";
      Dropdown::showYesNo("component_networkcardvirtual",
                          $pfConfig->getValue('component_networkcardvirtual'));
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Default status', 'fusioninventory')."&nbsp;:</td>";
      echo "<td>";
      Dropdown::show('State',
                     array('name'   => 'states_id_default',
                           'value'  => $pfConfig->getValue('states_id_default')));
      echo "</td>";
      echo "<td>";
      echo _n('Graphics card', 'Graphics cards', 2)."&nbsp;:";
      echo "</td>";
      echo "<td>";
      Dropdown::showYesNo("component_graphiccard", $pfConfig->getValue('component_graphiccard'));
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo __('Inventory number')."&nbsp;:";
      echo "</td>";
      echo "<td>";
      Dropdown::showFromArray("otherserial",
                              array("0"=>"------",
                                    "1"=>__('FusionInventory tag', 'fusioninventory')),
                              array('value'=>$pfConfig->getValue('otherserial')));
      echo "</td>";
      echo "<td>";
      echo _n('Soundcard', 'Soundcards', 2)."&nbsp;:";
      echo "</td>";
      echo "<td>";
      Dropdown::showYesNo("component_soundcard", $pfConfig->getValue('component_soundcard'));
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo __('Create computer based on virtual machine information ( only when the virtual machine has no inventory agent ! )', 'fusioninventory')."&nbsp;:";
      echo "</td>";
      echo "<td>";
      Dropdown::showYesNo("create_vm", $pfConfig->getValue('create_vm'));
      echo "</td>";
      echo "<td>";
      echo _n('Drive', 'Drives', 2)."&nbsp;:";
      echo "</td>";
      echo "<td>";
      Dropdown::showYesNo("component_drive", $pfConfig->getValue('component_drive'));
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo __('Manage operating system name:', 'fusioninventory');
      echo "</td>";
      echo "<td>";
      Dropdown::showYesNo("manage_osname", $pfConfig->getValue('manage_osname'));
      echo "</td>";
      echo "<td>";
      echo __('Network drives', 'fusioninventory')."&nbsp;:";
      echo "</td>";
      echo "<td>";
      Dropdown::showYesNo("component_networkdrive", $pfConfig->getValue('component_networkdrive'));
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td colspan='2'>";
      echo "</td>";
      echo "<td>";
      echo _n('Controller', 'Controllers', 2)."&nbsp;:";
      echo "</td>";
      echo "<td>";
      Dropdown::showYesNo("component_control", $pfConfig->getValue('component_control'));
      echo "</td>";
      echo "</tr>";

      $options['candel'] = FALSE;
      $pfConfig->showFormButtons($options);

      return TRUE;
   }



   /**
    * Display form for tab 'Network inventory'
    *
    * @param array $options
    * @return true
    */
   static function showFormNetworkInventory($options=array()) {
      global $CFG_GLPI;

      $pfConfig     = new PluginFusioninventoryConfig();
      $pfsnmpConfig = new self();

      $pfsnmpConfig->fields['id'] = 1;
      $pfsnmpConfig->showFormHeader($options);

      echo "<tr>";
      echo "<th colspan='4'>";
      echo __('Network options', 'fusioninventory');
      echo "</th>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Threads number', 'fusioninventory')."&nbsp;".
              "(".strtolower(__('Network discovery', 'fusioninventory')).")&nbsp;:</td>";
      echo "<td align='center'>";
      Dropdown::showNumber("threads_networkdiscovery", array(
             'value' => $pfConfig->getValue('threads_networkdiscovery'),
             'min'   => 1,
             'max'   => 400)
      );
      echo "</td>";

      echo "<td>".__('Threads number', 'fusioninventory')."&nbsp;".
              "(".strtolower(__('Network inventory (SNMP)', 'fusioninventory')).")&nbsp;:</td>";
      echo "<td align='center'>";
      Dropdown::showNumber("threads_networkinventory", array(
             'value' => $pfConfig->getValue('threads_networkinventory'),
             'min'   => 1,
             'max'   => 400)
      );
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('SNMP timeout', 'fusioninventory')."&nbsp;".
              "(".strtolower(__('Network discovery', 'fusioninventory')).")&nbsp;:</td>";
      echo "<td align='center'>";
      Dropdown::showNumber("timeout_networkdiscovery", array(
             'value' => $pfConfig->getValue('timeout_networkdiscovery'),
             'min'   => 1,
             'max'   => 60)
      );
      echo "</td>";
      echo "<td>".__('SNMP timeout', 'fusioninventory')."&nbsp;".
              "(".strtolower(__('Network inventory (SNMP)', 'fusioninventory')).")&nbsp;:</td>";
      echo "<td align='center'>";
      Dropdown::showNumber("timeout_networkinventory", array(
             'value' => $pfConfig->getValue('timeout_networkinventory'),
             'min'   => 1,
             'max'   => 60)
      );
      echo "</td>";
      echo "</tr>";

      $options['candel'] = FALSE;
      $pfsnmpConfig->showFormButtons($options);

      $pfConfigLogField = new PluginFusioninventoryConfigLogField();
      $pfConfigLogField->showForm(array(
          'target'=>$CFG_GLPI['root_doc']."/plugins/fusioninventory/front/configlogfield.form.php")
          );

      $pfNetworkporttype = new PluginFusioninventoryNetworkporttype();
      $pfNetworkporttype->showNetworkporttype();

      return TRUE;
   }



   /**
    * Display form for tab 'Deploy'
    *
    * @param array $options
    * @return true
    */
   static function showFormDeploy($options=array()) {

      $pfConfig = new PluginFusioninventoryConfig();
      $pfConfig->fields['id'] = 1;
      $options['colspan'] = 1;
      $pfConfig->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Root folder for sending files from server', 'fusioninventory')."&nbsp;:</td>";
      echo "<td>";
      echo "<input type='text' name='server_upload_path' value='".
         $pfConfig->getValue('server_upload_path')."' size='60' />";
      echo "</td>";
      echo "</tr>";

      echo "<tr>";
      echo "<td>".__('Use this GLPI server as a mirror server', 'fusioninventory')."&nbsp;:</td>";
      echo "<td>";
      Dropdown::showYesNo("server_as_mirror", $pfConfig->getValue('server_as_mirror'));
      echo "</td>";
      echo "</tr>";

      $options['candel'] = FALSE;
      $pfConfig->showFormButtons($options);

      return TRUE;
   }



   /**
    * Add name + value in configuration if not exist
    *
    * @param string $name
    * @param string $value
    * @return integer|false integer is the id of this configuration name
    */
   function addValue($name, $value) {
      $existing_value = $this->getValue($name);
      if (!is_null($existing_value)) {
         return $existing_value;
      } else {
         return $this->add(array('type'  => $name,
                                 'value' => $value));
      }
   }



   /**
    * Update configuration value
    *
    * @param string $name name of configuration
    * @param string $value
    * @return boolean
    */
   function updateValue($name, $value) {
      $config = current($this->find("`type`='".$name."'"));
      if (isset($config['id'])) {
         return $this->update(array('id'=> $config['id'], 'value'=>$value));
      } else {
         return $this->add(array('type' => $name, 'value' => $value));
      }
   }



   /**
    * Check if extradebug mode is activate
    *
    * @return null|integer the integer is 1 or 0 (it's like boolean)
    */
   static function isExtradebugActive() {
      $fConfig = new self();
      return $fConfig->getValue('extradebug');
   }



   /**
    * Log when extra-debug is activated
    *
    * @param string $file name of log file to update
    * @param string $message the message to put in log file
    */
   static function logIfExtradebug($file, $message) {
      if (self::isExtradebugActive()) {
         if (is_array($message)) {
            $message = print_r($message, TRUE);
         }
         Toolbox::logInFile($file, $message);
      }
   }



   /**
    * Load all configuration in global variable $PF_CONFIG
    *
    * Test if table exists before loading cache
    * The only case where table doesn't exists is when you click on
    * uninstall the plugin and it's already uninstalled
    *
    * @global object $DB
    * @global array $PF_CONFIG
    */
   static function loadCache() {
      global $DB, $PF_CONFIG;

      if (TableExists('glpi_plugin_fusioninventory_configs')) {
         $PF_CONFIG = array();
         foreach ($DB->request('glpi_plugin_fusioninventory_configs') as $data) {
            $PF_CONFIG[$data['type']] = $data['value'];
         }
      }
   }
}

?>
