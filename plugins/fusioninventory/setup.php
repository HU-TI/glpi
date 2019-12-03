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
 * This file is used to manage the setup / initialize plugin
 * FusionInventory.
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

define ("PLUGIN_FUSIONINVENTORY_VERSION", "9.1+1.1");

// Used for use config values in 'cache'
$PF_CONFIG = array();
// used to know if computer inventory is in reallity a ESX task
$PF_ESXINVENTORY = FALSE;

define ("PLUGIN_FUSIONINVENTORY_XML", '');

define ("PLUGIN_FUSIONINVENTORY_OFFICIAL_RELEASE", "1");
define ("PLUGIN_FUSIONINVENTORY_REALVERSION", "9.1+1.1 SNAPSHOT");
include_once(GLPI_ROOT."/inc/includes.php");

include_once( GLPI_ROOT . "/plugins/fusioninventory/lib/autoload.php");

define("PLUGIN_FUSIONINVENTORY_ROOT",
   implode(DIRECTORY_SEPARATOR , array(GLPI_ROOT,'plugins', 'fusioninventory', 'inc'))
);

$options = array(
   PLUGIN_FUSIONINVENTORY_ROOT
);

$fi_loader = new FusioninventoryIncludePathAutoloader($options);
$fi_loader->register();

/**
 * Check if the script name finish by
 *
 * @param string $scriptname
 * @return boolean
 */
function script_endswith($scriptname) {
   $script_name = filter_input(INPUT_SERVER, "SCRIPT_NAME");
   return substr($script_name, -strlen($scriptname))===$scriptname;
}



/**
 * Init the hooks of FusionInventory
 *
 * @global array $PLUGIN_HOOKS
 * @global array $CFG_GLPI
 */
function plugin_init_fusioninventory() {
   global $PLUGIN_HOOKS, $CFG_GLPI;

   $PLUGIN_HOOKS['csrf_compliant']['fusioninventory'] = TRUE;

   $Plugin = new Plugin();
   $moduleId = 0;

   $debug_mode = false;
   if (isset($_SESSION['glpi_use_mode'])) {
      $debug_mode = ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE);
   }

   if ($Plugin->isActivated('fusioninventory')) { // check if plugin is active

      // Register classes into GLPI plugin factory

      $Plugin->registerClass('PluginFusioninventoryAgent',
         array(
            'addtabon' => array(
               'Computer',
               'Printer',
               'NetworkEquipment',
               'PluginFusioninventoryCredentialIp'
            )
         )
      );
      $Plugin->registerClass('PluginFusioninventoryAgentmodule');
      $Plugin->registerClass('PluginFusioninventoryConfig');
      $Plugin->registerClass('PluginFusioninventoryTask',
         array(
            'addtabon' => array(
               'Computer',
               'Printer',
               'NetworkEquipment',
               'PluginFusioninventoryCredentialIp',
            )
         )
      );
      $Plugin->registerClass('PluginFusioninventoryTaskjob',
         array(
            'addtabon' => array(
               //'Computer',
               //'Printer',
               //'NetworkEquipment',
               //'PluginFusioninventoryUnmanaged',
               'PluginFusioninventoryTask',
            )
         )
      );

      $Plugin->registerClass('PluginFusioninventoryTaskjobstate',
         array(
            'addtabon' => array(
               'PluginFusioninventoryTask'
            )
         )
      );

      $Plugin->registerClass('PluginFusioninventoryUnmanaged');
      $Plugin->registerClass('PluginFusioninventoryModule');
      $Plugin->registerClass('PluginFusioninventoryProfile',
              array('addtabon' => array('Profile')));
      $Plugin->registerClass('PluginFusioninventoryEntity',
              array('addtabon' => array('Entity')));
      $Plugin->registerClass('PluginFusioninventorySetup');
      $Plugin->registerClass('PluginFusioninventoryIPRange');
      $Plugin->registerClass('PluginFusioninventoryIPRange_ConfigSecurity',
              array('addtabon' => 'PluginFusioninventoryIPRange'));
      $Plugin->registerClass('PluginFusioninventoryCredential');
      $Plugin->registerClass('PluginFusioninventoryTimeslot');
      $Plugin->registerClass('PluginFusioninventoryLock',
              array('addtabon' => array('Computer', 'Printer', 'NetworkEquipment')));

      $Plugin->registerClass('PluginFusioninventoryInventoryComputerComputer',
              array('addtabon' => array('Computer')));
      $Plugin->registerClass('PluginFusioninventoryInventoryComputerInventory');
      $Plugin->registerClass('PluginFusioninventoryInventoryComputerStorage',
              array('addtabon' => array('Computer')));
      $Plugin->registerClass('PluginFusioninventoryCollect');
      $Plugin->registerClass('PluginFusioninventoryCollect_Registry',
              array('addtabon' => array('PluginFusioninventoryCollect')));
      $Plugin->registerClass('PluginFusioninventoryCollect_Registry_Content',
              array('addtabon' => array('PluginFusioninventoryCollect',
                                        'Computer')));
      $Plugin->registerClass('PluginFusioninventoryCollect_Wmi',
              array('addtabon' => array('PluginFusioninventoryCollect')));
      $Plugin->registerClass('PluginFusioninventoryCollect_Wmi_Content',
              array('addtabon' => array('PluginFusioninventoryCollect',
                                        'Computer')));
      $Plugin->registerClass('PluginFusioninventoryCollect_File',
              array('addtabon' => array('PluginFusioninventoryCollect')));
      $Plugin->registerClass('PluginFusioninventoryCollect_File_Content',
              array('addtabon' => array('PluginFusioninventoryCollect',
                                        'Computer')));
      $Plugin->registerClass('PluginFusioninventoryComputerLicenseInfo',
              array('addtabon' => array('Computer')));
      $Plugin->registerClass('PluginFusioninventoryComputerRemoteManagement',
              array('addtabon' => array('Computer')));

         //Classes for rulesengine
      $Plugin->registerClass('PluginFusioninventoryInventoryRuleLocation');
      $Plugin->registerClass('PluginFusioninventoryInventoryRuleLocationCollection',
              array('rulecollections_types'=>TRUE));
      $Plugin->registerClass('PluginFusioninventoryInventoryRuleEntity');
      $Plugin->registerClass('PluginFusioninventoryInventoryRuleEntityCollection',
              array('rulecollections_types'=>TRUE));
      $Plugin->registerClass('PluginFusioninventoryRulematchedlog',
              array('addtabon' => array('Computer',
                                        'PluginFusioninventoryAgent',
                                        'PluginFusioninventoryUnmanaged',
                                        'Printer',
                                        'NetworkEquipment')));

      //Classes for rulesengine
      $Plugin->registerClass('PluginFusioninventoryInventoryRuleImport');
      $Plugin->registerClass('PluginFusioninventoryInventoryRuleImportCollection',
              array('rulecollections_types'=>TRUE));
      $Plugin->registerClass('PluginFusioninventoryConstructDevice');

      //Computer arch dictionnary
      $Plugin->registerClass('PluginFusioninventoryRuleDictionnaryComputerArch');
      $Plugin->registerClass('PluginFusioninventoryRuleDictionnaryComputerArchCollection',
              array('rulecollections_types'=>TRUE));
      array_push($CFG_GLPI["dictionnary_types"], 'PluginFusioninventoryRuleDictionnaryComputerArchCollection');

      // Networkinventory and networkdiscovery
      $Plugin->registerClass('PluginFusioninventorySnmpmodel');
      $Plugin->registerClass('PluginFusioninventoryNetworkEquipment',
              array('addtabon' => array('NetworkEquipment')));
      $Plugin->registerClass('PluginFusioninventoryPrinter',
              array('addtabon' => array('Printer')));
      $Plugin->registerClass('PluginFusioninventoryPrinterCartridge');
      $Plugin->registerClass('PluginFusioninventoryConfigSecurity');
      $Plugin->registerClass('PluginFusioninventoryNetworkPortLog',
              array('addtabon' => array('NetworkPort')));
      $Plugin->registerClass('PluginFusinvsnmpAgentconfig');
      $Plugin->registerClass('PluginFusioninventoryNetworkPort',
              array('classname'=>'glpi_networkports'));
      $Plugin->registerClass('PluginFusioninventoryStateDiscovery');
      $Plugin->registerClass('PluginFusioninventoryPrinterLogReport');
      $Plugin->registerClass('PluginFusioninventorySnmpmodelConstructdevice_User',
              array('addtabon' => array('User')));
      $Plugin->registerClass('PluginFusioninventoryDeployGroup');
      $Plugin->registerClass('PluginFusioninventoryDeployGroup_Staticdata',
              array('addtabon' => array('PluginFusioninventoryDeployGroup')));
      $Plugin->registerClass('PluginFusioninventoryDeployGroup_Dynamicdata',
              array('addtabon' => array('PluginFusioninventoryDeployGroup')));

      $CFG_GLPI['glpitablesitemtype']["PluginFusioninventoryPrinterLogReport"] =
                                                      "glpi_plugin_fusioninventory_printers";
      $CFG_GLPI['glpitablesitemtype']["PluginFusioninventoryComputer"] =
                                                      "glpi_computers";

      // ##### 3. get informations of the plugin #####

      $Plugin->getFromDBbyDir('fusioninventory');
      $moduleId = $Plugin->fields['id'];

      // Load config
      PluginFusioninventoryConfig::loadCache();

      // ##### 5. Set in session XMLtags of methods #####

      $_SESSION['glpi_plugin_fusioninventory']['xmltags']['WAKEONLAN'] = '';
      $_SESSION['glpi_plugin_fusioninventory']['xmltags']['INVENTORY']
                                             = 'PluginFusioninventoryInventoryComputerInventory';
      $_SESSION['glpi_plugin_fusioninventory']['xmltags']['NETWORKDISCOVERY']
                                             = 'PluginFusioninventoryCommunicationNetworkDiscovery';
      $_SESSION['glpi_plugin_fusioninventory']['xmltags']['NETWORKINVENTORY']
                                             = 'PluginFusioninventoryCommunicationNetworkInventory';

      $PLUGIN_HOOKS['import_item']['fusioninventory'] = array(
          'Computer' => array('Plugin'));

      $CFG_GLPI["specif_entities_tables"][] = 'glpi_plugin_fusioninventory_ipranges';

      $CFG_GLPI["networkport_types"][] = 'PluginFusioninventoryUnmanaged';


      /**
       * Load the relevant javascript/css files only on pages that need them.
       */
      $PLUGIN_HOOKS['add_javascript']['fusioninventory'] = array();
      $PLUGIN_HOOKS['add_css']['fusioninventory'] = array();
      if (strpos(filter_input(INPUT_SERVER, "SCRIPT_NAME"), "plugins/fusioninventory") != false) {
         $PLUGIN_HOOKS['add_css']['fusioninventory'][]="css/views.css";
         $PLUGIN_HOOKS['add_css']['fusioninventory'][]="css/deploy.css";

         array_push(
            $PLUGIN_HOOKS['add_javascript']['fusioninventory'],
            "lib/d3/d3".($debug_mode?"":".min").".js",
            "lib/expanding/expanding".($debug_mode?"":".min").".js"
         );
      }
      $PLUGIN_HOOKS['add_javascript']['fusioninventory'][] = 'js/footer.js';
      if (script_endswith("timeslot.form.php")) {
         $PLUGIN_HOOKS['add_javascript']['fusioninventory'][] = "lib/timeslot".($debug_mode?"":".min").".js";
      }
      if (script_endswith("deploypackage.form.php")) {
         $PLUGIN_HOOKS['add_css']['fusioninventory'][]="lib/extjs/resources/css/ext-all.css";

         array_push(
            $PLUGIN_HOOKS['add_javascript']['fusioninventory'],
            "lib/extjs/adapter/ext/ext-base".($debug_mode?"-debug":"").".js",
            "lib/extjs/ext-all".($debug_mode?"-debug":"").".js",
            "lib/REDIPS_drag/redips-drag".($debug_mode?"-source":"-min").".js",
            "lib/REDIPS_drag/drag_table_rows.js",
            "lib/plusbutton".($debug_mode?"":".min").".js",
            "lib/deploy_editsubtype".($debug_mode?"":".min").".js"
         );
      }
      if (script_endswith("task.form.php")
         || script_endswith("taskjob.php")
         || script_endswith("iprange.form.php")) {
         array_push(
            $PLUGIN_HOOKS['add_javascript']['fusioninventory'],
            "lib/lazy.js-0.4.0/lazy".($debug_mode?"":".min").".js",
            "lib/mustache.js-2.0.0/mustache".($debug_mode?"":".min").".js",
            "js/taskjobs".($debug_mode?"":".min").".js"
         );
      }
      if (script_endswith("menu.php")) {
         $PLUGIN_HOOKS['add_javascript']['fusioninventory'][] = "js/stats.js";
      }

      if (Session::haveRight('plugin_fusioninventory_configuration', READ)
              || Session::haveRight('profile', UPDATE)) {// Config page
         $PLUGIN_HOOKS['config_page']['fusioninventory'] = 'front/config.form.php'.
                 '?itemtype=pluginfusioninventoryconfig&glpi_tab=1';
      }

      $PLUGIN_HOOKS['autoinventory_information']['fusioninventory'] = array(
            'Computer' =>  array('PluginFusioninventoryInventoryComputerComputer',
                                 'showComputerInfo'),
            'NetworkEquipment' => array('PluginFusioninventoryNetworkEquipment',
                                        'showInfo'),
            'Printer' => array('PluginFusioninventoryPrinter',
                                        'showInfo'));

      $PLUGIN_HOOKS['post_show_tab']['fusioninventory'] = 'postShowTab';

      $PLUGIN_HOOKS['use_massive_action']['fusioninventory'] = 1;

      $PLUGIN_HOOKS['item_add']['fusioninventory'] = array(
            'NetworkPort_NetworkPort' => 'plugin_item_add_fusioninventory',
            'NetworkPort'             => 'plugin_item_add_fusioninventory'
          );


      $PLUGIN_HOOKS['pre_item_update']['fusioninventory'] = array(
            'Plugin' => 'plugin_pre_item_update_fusioninventory'
          );
      $PLUGIN_HOOKS['item_update']['fusioninventory'] =
                              array('Computer'         => 'plugin_item_update_fusioninventory',
                                    'NetworkEquipment' => 'plugin_item_update_fusioninventory',
                                    'Printer'          => 'plugin_item_update_fusioninventory',
                                    'Monitor'          => 'plugin_item_update_fusioninventory',
                                    'Peripheral'       => 'plugin_item_update_fusioninventory',
                                    'Phone'            => 'plugin_item_update_fusioninventory',
                                    'NetworkPort'      => 'plugin_item_update_fusioninventory',
                                    'PluginFusioninventoryLock' => array('PluginFusioninventoryLock', 'deleteLock'));


      $PLUGIN_HOOKS['pre_item_purge']['fusioninventory'] = array(
            'Computer'                 =>'plugin_pre_item_purge_fusioninventory',
            'NetworkPort_NetworkPort'  =>'plugin_pre_item_purge_fusioninventory',
            'PluginFusioninventoryLock'=> array('PluginFusioninventoryLock', 'deleteLock')
          );
      $p = array('NetworkPort_NetworkPort'            => 'plugin_item_purge_fusioninventory',
                 'PluginFusioninventoryTask'          => array('PluginFusioninventoryTask',
                                                               'purgeTask'),
                 'PluginFusioninventoryTaskjob'       => array('PluginFusioninventoryTaskjob',
                                                               'purgeTaskjob'),
                 'PluginFusioninventoryUnmanaged' => array('PluginFusioninventoryUnmanaged',
                                                               'purgeUnmanaged'),
                 'NetworkEquipment'                   => 'plugin_item_purge_fusinvsnmp',
                 'Printer'                            => 'plugin_item_purge_fusinvsnmp');
      $PLUGIN_HOOKS['item_purge']['fusioninventory'] = $p;


      $PLUGIN_HOOKS['item_transfer']['fusioninventory'] = 'plugin_item_transfer_fusioninventory';

      if (Session::haveRight('plugin_fusioninventory_unmanaged', READ)) {
         $PLUGIN_HOOKS["menu_toadd"]['fusioninventory']['assets'] = 'PluginFusioninventoryUnmanaged';
      }
      if (Session::haveRight('plugin_fusioninventory_menu', READ)) {
         $PLUGIN_HOOKS["menu_toadd"]['fusioninventory']['plugins'] = 'PluginFusioninventoryMenu';
      }

      // For end users
      if (isset($_SESSION['glpiactiveprofile']['interface'])
              && $_SESSION['glpiactiveprofile']['interface'] == 'helpdesk') {
         $pfDeployPackage = new PluginFusioninventoryDeployPackage();
         if ($pfDeployPackage->canUserDeploySelf()) {
            $PLUGIN_HOOKS['helpdesk_menu_entry']['fusioninventory'] = '/front/deploypackage.public.php';
         }
      }

      if (isset($_SESSION["glpiname"])) {
         $report_list = array();
         if (Session::haveRight('plugin_fusioninventory_reportprinter', READ)) {
            $report_list["front/printerlogreport.php"] = __('Printed page counter', 'fusioninventory');

         }
         if (Session::haveRight('plugin_fusioninventory_reportnetworkequipment', READ)) {
            $report_list["report/switch_ports.history.php"] = __('Switch ports history', 'fusioninventory');

            $report_list["report/ports_date_connections.php"] = __('Unused switch ports', 'fusioninventory');

            $report_list["report/not_queried_recently.php"] = __('Number of days since last inventory', 'fusioninventory');

         }
         if (Session::haveRight('computer', READ)) {
            $report_list["report/computer_last_inventory.php"] = __('Computers not inventoried since xx days', 'fusioninventory');
         }
         $PLUGIN_HOOKS['reports']['fusioninventory'] = $report_list;

         /*
          * Deploy submenu entries
          */

         if (Session::haveRight('plugin_fusioninventory_configuration', READ)) {// Config page
            $PLUGIN_HOOKS['submenu_entry']['fusioninventory']['config'] = 'front/config.form.php';
         }

         $PLUGIN_HOOKS['webservices']['fusioninventory'] = 'plugin_fusioninventory_registerMethods';

         // Hack for NetworkEquipment display ports
         if (strstr(filter_input(INPUT_SERVER, "PHP_SELF"), '/ajax/common.tabs.php')) {
            if (strstr(filter_input(INPUT_GET, "_target"), '/front/networkequipment.form.php')
                    && filter_input(INPUT_GET, "_itemtype") == 'NetworkEquipment') {

               if (filter_input(INPUT_GET, "_glpi_tab") == 'NetworkPort$1') {
                  $_GET['_glpi_tab'] = 'PluginFusioninventoryNetworkEquipment$1';
               } else if (filter_input(INPUT_GET, "_glpi_tab") == 'PluginFusioninventoryNetworkEquipment$1') {
                  $_GET['displaysnmpinfo'] = 1;
               }
            }
         }
         // Load nvd3 for printerpage counter graph
         if (strstr(filter_input(INPUT_SERVER, "PHP_SELF"), '/front/printer.form.php')
                 || strstr(filter_input(INPUT_SERVER, "PHP_SELF"), 'fusioninventory/front/menu.php')) {

            // Add graph javascript
            $PLUGIN_HOOKS['add_javascript']['fusioninventory'] = array_merge(
                  $PLUGIN_HOOKS['add_javascript']['fusioninventory'], array(
                     "lib/nvd3/nv.d3.min.js"
                  )
            );
            // Add graph css
            $PLUGIN_HOOKS['add_css']['fusioninventory'] = array_merge(
                  $PLUGIN_HOOKS['add_css']['fusioninventory'], array(
                     "lib/nvd3/nv.d3.css"
                  )
            );
         }
      }

   } else { // plugin not active, need $moduleId for uninstall check
      include_once(GLPI_ROOT.'/plugins/fusioninventory/inc/module.class.php');
      $moduleId = PluginFusioninventoryModule::getModuleId('fusioninventory');
   }

   // Check for uninstall
   $id = filter_input(INPUT_GET, "id");
   $action = filter_input(INPUT_GET, "action");
   if ($id == $moduleId
           && $action == 'uninstall'
           && (strstr(filter_input(INPUT_SERVER, "HTTP_REFERER"), "front/plugin.php"))) {

      if (PluginFusioninventoryModule::getAll(TRUE)) {
          Session::addMessageAfterRedirect(__('Other FusionInventory plugins (fusinv...) must be uninstalled before removing the FusionInventory plugin'));

         Html::redirect($CFG_GLPI["root_doc"]."/front/plugin.php");
         exit;
      }
   }

   // Add unmanaged devices in list of devices with networport
   $CFG_GLPI["netport_types"][] = "PluginFusioninventoryUnmanaged";

   // exclude some pages from splitted layout
   if (isset($CFG_GLPI['layout_excluded_pages'])) { // to be compatible with glpi 0.85
      array_push($CFG_GLPI['layout_excluded_pages'], "timeslot.form.php");
   }
}



/**
 * Manage the version information of the plugin
 *
 * @return array
 */
function plugin_version_fusioninventory() {
   return array('name'           => 'FusionInventory',
                'shortname'      => 'fusioninventory',
                'version'        => PLUGIN_FUSIONINVENTORY_VERSION,
                'license'        => 'AGPLv3+',
                'oldname'        => 'tracker',
                'author'         =>'<a href="mailto:david@durieux.family">David DURIEUX</a>
                                    & FusionInventory team',
                'homepage'       =>'http://forge.fusioninventory.org/projects/fusioninventory-for-glpi/',
                'minGlpiVersion' => '9.1.1'
   );
}



/**
 * Manage / check the prerequisites of the plugin
 *
 * @global object $DB
 * @return boolean
 */
function plugin_fusioninventory_check_prerequisites() {
   global $DB;

   if (!isset($_SESSION['glpi_plugins'])) {
      $_SESSION['glpi_plugins'] = array();
   }

   if (version_compare(GLPI_VERSION, '9.1.1', 'lt') || version_compare(GLPI_VERSION, '9.2', 'ge')) {
      echo __('Your GLPI version not compatible, require >= 9.1.1 and < 9.2', 'fusioninventory');
      return FALSE;
   }

   if (!function_exists('finfo_open')) {
      echo __('fileinfo extension (PHP) is required...', 'fusioninventory');
      return FALSE;
   }

   $plugin = new Plugin();
   if ($plugin->isActivated("fusioninventory")
           && !TableExists("glpi_plugin_fusioninventory_configs")) {
      return FALSE;
   }

   $a_plugins = array('fusinvinventory', 'fusinvsnmp', 'fusinvdeploy');
   foreach ($a_plugins as $pluginname) {
      if (file_exists(GLPI_ROOT.'/plugins/'.$pluginname)) {
         printf(__('Please remove folder %s in glpi/plugins/', 'fusioninventory'), $pluginname);
         return FALSE;
      }
   }

   $crontask = new CronTask();
   if ($plugin->isActivated("fusioninventory")) {
      if ((TableExists("glpi_plugin_fusioninventory_agents")
              AND !FieldExists("glpi_plugin_fusioninventory_agents", "tag"))
           OR ($crontask->getFromDBbyName('PluginFusioninventoryTaskjobstatus', 'cleantaskjob'))
           OR (TableExists("glpi_plugin_fusioninventory_agentmodules")
              AND FieldExists("glpi_plugin_fusioninventory_agentmodules", "url"))) {
         $DB->query("UPDATE `glpi_plugin_fusioninventory_configs` SET `value`='0.80+1.4'
                        WHERE `type`='version'");
         $DB->query("UPDATE `glpi_plugins` SET `version`='0.80+1.4'
                        WHERE `directory` LIKE 'fusi%'");
      }
   }
   return TRUE;
}



/**
 * Check if the config is ok
 *
 * @return boolean
 */
function plugin_fusioninventory_check_config() {
   return TRUE;
}



/**
 * Check the rights
 *
 * @param string $type
 * @param string $right
 * @return boolean
 */
function plugin_fusioninventory_haveTypeRight($type, $right) {
   return TRUE;
}



/**
 * Add the FusionInventory footer in GLPI interface
 *
 * @param string $baseroot
 */
function plugin_fusioninventory_footer($baseroot) {

      echo "<div id='footer'>";
      echo "<table width='100%'>";
      echo "<tr>";
      echo "<td class='right'>";
      echo "<a href='http://fusioninventory.org/'>";
      echo "<span class='copyright'>FusionInventory ".PLUGIN_FUSIONINVENTORY_VERSION." | copyleft ".
           "<img src='".$baseroot."/plugins/fusioninventory/pics/copyleft.png'/> "
              . " 2010-2016 by FusionInventory Team".
           "</span>";
      echo "</a>";
      echo "</td>";
      echo "</tr>";
      echo "</table>";
      echo "</div>";
}
?>
