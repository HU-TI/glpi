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
 * This file is used to manage the deploy packages.
 *
 * ------------------------------------------------------------------------
 *
 * @package   FusionInventory
 * @author    David Durieux
 * @author Alexandre Delaunay
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
 * Manage the deploy packages.
 */
class PluginFusioninventoryDeployPackage extends CommonDBTM {

   // Tasks running with this package (updated with getRunningTasks method)
   /**
    * Initialize the tasks running with this package (updated with
    * getRunningTasks method)
    *
    * @var array
    */
   public $running_tasks = array();

   /**
    * The right name for this class
    *
    * @var string
    */
   static $rightname = 'plugin_fusioninventory_package';

   /**
    * Initialize the users visibility of package for self-service deploy
    *
    * @var array
    */
   protected $users = array();

   /**
    * Initialize the groups visibility of package for self-service deploy
    *
    * @var array
    */
   protected $groups = array();

   /**
    * Initialize the profiles visibility of package for self-service deploy
    *
    * @var array
    */
   protected $profiles = array();

   /**
    * Initialize the entities visibility of package for self-service deploy
    *
    * @var array
    */
   protected $entities = array();


   /**
    * Get name of this type by language of the user connected
    *
    * @param integer $nb number of elements
    * @return string name of this type
    */
   static function getTypeName($nb=0) {
      return __('Package', 'fusioninventory');
   }



   /**
    * Get the massive actions for this object
    *
    * @param object|null $checkitem
    * @return array list of actions
    */
   function getSpecificMassiveActions($checkitem=NULL) {

      $actions = array();
      if (strstr($_SERVER["HTTP_REFERER"], 'deploypackage.import.php')) {
         $actions['PluginFusioninventoryDeployPackage'.MassiveAction::CLASS_ACTION_SEPARATOR.'import'] = __('Import', 'fusioninventory');
         return $actions;
      }
      $actions['PluginFusioninventoryDeployPackage'.MassiveAction::CLASS_ACTION_SEPARATOR.'transfert'] = __('Transfer');
      $actions['PluginFusioninventoryDeployPackage'.MassiveAction::CLASS_ACTION_SEPARATOR.'export'] = __('Export', 'fusioninventory');

      return $actions;
   }



   /**
    * Define standard massiveaction actions to deny
    *
    * @return array list of actions to deny
    */
   function getForbiddenStandardMassiveAction() {
      $forbidden   = parent::getForbiddenStandardMassiveAction();
      if (strstr($_SERVER["HTTP_REFERER"], 'deploypackage.import.php')) {
         $forbidden[] = 'update';
         $forbidden[] = 'add';
         $forbidden[] = 'delete';
         $forbidden[] = 'purge';
      }
      return $forbidden;
   }



   /**
    * Display form related to the massive action selected
    *
    * @param object $ma MassiveAction instance
    * @return boolean
    */
   static function showMassiveActionsSubForm(MassiveAction $ma) {

      if ($ma->getAction() == 'transfert') {
         Dropdown::show('Entity');
         echo "<br><br>".Html::submit(__('Post'),
                                      array('name' => 'massiveaction'));
         return TRUE;
      }
      return parent::showMassiveActionsSubForm($ma);
   }



   /**
    * Execution code for massive action
    *
    * @param object $ma MassiveAction instance
    * @param object $item item on which execute the code
    * @param array $ids list of ID on which execute the code
    */
   static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item, array $ids) {

      switch ($ma->getAction()) {

         case 'export' :
            foreach ($ids as $key) {
               if ($item->can($key, UPDATE)) {
                  $item->exportPackage($key);
                  $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_OK);
              }
            }
            break;

         case 'transfert' :
            $pfDeployPackage = new PluginFusioninventoryDeployPackage();
            foreach ($ids as $key) {
               if ($pfDeployPackage->getFromDB($key)) {
                  $input = array();
                  $input['id'] = $key;
                  $input['entities_id'] = $ma->POST['entities_id'];
                  $pfDeployPackage->update($input);
               }
            }
            break;

         case 'import' :
            foreach ($ids as $key) {
               $item->importPackage($key);
               $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_OK);
            }
            break;

      }
   }



   /**
    * Define error message if package used in task. This will prevent edit the
    * package
    *
    * @return string
    */
   function getEditErrorMessage() {

      $this->getRunningTasks();
      $error_message = "";
      $tasklist = array_filter(
         $this->running_tasks,
         create_function('$task', 'return $task["taskjob"]["method"]=="deploy";')
      );

      if (count($tasklist) > 0) {
         // Display error message
         $error_message .= "<h3 class='red'>";
         $error_message .=
            __("Modification Denied", 'fusioninventory');
         $error_message .= "</h3>\n";
         $error_message .=
            "<h4>".
               _n(
                  "The following task is running with this package",
                  "The following tasks are running with this package",
                  count($this->running_tasks), 'fusioninventory'
               ).
            "</h4>\n";

         foreach ($this->running_tasks as $task) {
            $taskurl_base =
               Toolbox::getItemTypeFormURL("PluginFusioninventoryTask", TRUE);

            $error_message .= "<a href='$taskurl_base?id=".$task['task']['id']."'>";
            $error_message .=  $task['task']['name'];
            $error_message .= "</a>, ";
         }
      }
      return $error_message;
   }



   /**
    * Prepare data before add to database
    *
    * @param array $input
    * @return array
    */
   function prepareInputForAdd($input) {
      if (!isset($input['json'])) {
         $input['json'] = json_encode(array(
             'jobs' => array(
                 'checks'          => array(),
                 'associatedFiles' => array(),
                 'actions'         => array()
             ),
             'associatedFiles' => array()));
      }
      return parent::prepareInputForAdd($input);
   }



   /**
    * Get all tasks runnning
    */
   function getRunningTasks() {
      $this->running_tasks =
            PluginFusioninventoryTask::getItemsFromDB(
               array(
                   'is_active'   => TRUE,
                   'is_running'  => TRUE,
                   'targets'     => array(__CLASS__ => $this->fields['id']),
                   'by_entities' => FALSE,
               )
            );
   }



   /**
    * Get search function for the class
    *
    * @return array
    */
   function getSearchOptions() {
      $tab = array();
      $tab['common']           = __('Characteristics');

      $tab[1]['table']         = $this->getTable();
      $tab[1]['field']         = 'name';
      $tab[1]['linkfield']     = 'name';
      $tab[1]['name']          = __('Name');
      $tab[1]['datatype']      = 'itemlink';
      $tab[1]['itemlink_link'] = $this->getType();

      $tab[2]['table']     = $this->getTable();
      $tab[2]['field']     = 'id';
      $tab[2]['linkfield'] = '';
      $tab[2]['name']      = __('ID');

      $tab[16]['table']     = $this->getTable();
      $tab[16]['field']     = 'comment';
      $tab[16]['linkfield'] = 'comment';
      $tab[16]['name']      = __('Comments');
      $tab[16]['datatype']  = 'text';

      $tab[19]['table']     = $this->getTable();
      $tab[19]['field']     = 'date_mod';
      $tab[19]['linkfield'] = '';
      $tab[19]['name']      = __('Last update');
      $tab[19]['datatype']  = 'datetime';

      $tab[80]['table']     = 'glpi_entities';
      $tab[80]['field']     = 'completename';
      $tab[80]['name']      = __('Entity');
      $tab[80]['datatype']  = 'dropdown';

      $tab[86]['table']     = $this->getTable();
      $tab[86]['field']     = 'is_recursive';
      $tab[86]['linkfield'] = 'is_recursive';
      $tab[86]['name']      = __('Child entities');
      $tab[86]['datatype']  = 'bool';

      $tab[19]['table']     = $this->getTable();
      $tab[19]['field']     = 'date_mod';
      $tab[19]['linkfield'] = '';
      $tab[19]['name']      = __('Last update');
      $tab[19]['datatype']  = 'datetime';

      return $tab;
   }



   /**
    * Get all packages in json format
    *
    * @return json
    */
   function getAllDatas() {
      global $DB;

      $sql = " SELECT id, name
               FROM `".$this->getTable()."`
               ORDER BY name";
      $res  = $DB->query($sql);
      $nb   = $DB->numrows($res);
      $json  = array();
      $i = 0;
      while ($row = $DB->fetch_assoc($res)) {
         $json['packages'][$i]['package_id'] = $row['id'];
         $json['packages'][$i]['package_name'] = $row['name'];
         $i++;
      }
      $json['results'] = $nb;
      return json_encode($json);
   }



   /**
    * Clean orders when delete the package
    *
    * @global type $DB
    */
   function cleanDBonPurge() {
      global $DB;

      $query = "DELETE FROM `glpi_plugin_fusioninventory_deployorders`
                WHERE `plugin_fusioninventory_deploypackages_id`=".$this->fields['id'];
      $DB->query($query);
   }



   /**
    * Display the menu / list of packages
    *
    * @param array $options
    */
   function showMenu($options=array()) {

      $this->displaylist = FALSE;

      $this->fields['id'] = -1;
      $this->showList();
   }



   /**
    * Display list of packages
    */
   function showList() {
      Search::show('PluginFusioninventoryDeployPackage');
   }



   /**
    * Define tabs to display on form page
    *
    * @param array $options
    * @return array containing the tabs name
    */
   function defineTabs($options=array()) {
      $ong = array();
      $this->addDefaultFormTab($ong);
      if ($this->fields['id'] > 0) {
         $this->addStandardTab('PluginFusioninventoryDeployinstall', $ong, $options);
      }
      if ($this->fields['plugin_fusioninventory_deploygroups_id'] > 0) {
         $this->addStandardTab(__CLASS__, $ong, $options);
      }
      $ong['no_all_tab'] = TRUE;
      return $ong;
   }



   /**
    * Display form
    *
    * @param integer $ID
    * @param array $options
    * @return true
    */
   function showForm($ID, $options=array()) {
      $this->initForm($ID, $options);
      $this->showFormHeader($options);
      //Add redips_clone element before displaying tabs
      //If we don't do this, dragged element won't be visible on the other tab not displayed at
      //first (for reminder, GLPI tabs are displayed dynamically on-demand)
      echo "<div id='redips_clone'></div>";
      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Name')."&nbsp;:</td>";
      echo "<td>";
      Html::autocompletionTextField($this,'name', array('size' => 40));
      echo "</td>";

      echo "<td>".__('Comments')."&nbsp;:</td>";
      echo "<td>";
      echo "<textarea cols='40' rows='2' name='comment' >".$this->fields["comment"]."</textarea>";
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Enable self-service in defining computer group', 'fusioninventory')."&nbsp;:</td>";
      echo "<td>";
      PluginFusioninventoryDeployGroup::dropdown(array('value' => $this->fields["plugin_fusioninventory_deploygroups_id"]));
      echo "</td>";

      echo "<td colspan='2'></td>";
      echo "</tr>";

      $this->showFormButtons($options);
      return TRUE;
   }



   /**
    * Display order type form
    *
    * @global array $CFG_GLPI
    */
   function displayOrderTypeForm() {
      global $CFG_GLPI;

      $subtypes = array(
         'check'  => __("Audits", 'fusioninventory'),
         'file'   => __("Files", 'fusioninventory'),
         'action' => __("Actions", 'fusioninventory')
      );
      $json_subtypes = array(
         'check'  => 'checks',
         'file'   => 'associatedFiles',
         'action' => 'actions'
      );
      $rand = mt_rand();

      $datas = json_decode($this->fields['json'], TRUE);

      // Display an error if the package modification is not possible
      $error_msg = $this->getEditErrorMessage();
      if (!empty($error_msg)) {
         Session::addMessageAfterRedirect($error_msg);
         Html::displayMessageAfterRedirect();
         echo "<div id='package_order_".$this->getID()."_span'>";
      }

      echo "<table class='tab_cadre_fixe' id='package_order_".$this->getID()."'>";

      // Display the lists of each subtypes of a package
      foreach ($subtypes as $subtype => $label) {
         echo "<tr>";
         echo "<th id='th_title_{$subtype}_$rand'>";
         echo "<img src='".$CFG_GLPI["root_doc"]."/plugins/fusioninventory/pics/$subtype.png' />";
         echo "&nbsp;".__($label, 'fusioninventory');
         $this->plusButtonSubtype($this->getID(), $subtype, $rand);
         echo "</th>";
         echo "</tr>";

         /**
          * File's form must be encoded as multipart/form-data
          **/
         $multipart = "";
         if ($subtype == "file") {
            $multipart = "enctype='multipart/form-data'";
         }
         echo "<tr>";
         echo "<td style='vertical-align:top'>";

         /**
          * Display subtype form
          **/
         echo "<form name='addition$subtype' method='post' ".$multipart.
            " action='deploypackage.form.php'>";
         echo "<input type='hidden' name='id' value='".$this->getID()."' />";
         echo "<input type='hidden' name='itemtype' value='PluginFusioninventoryDeploy".
            ucfirst($subtype)."' />";

         $classname = "PluginFusioninventoryDeploy".ucfirst($subtype);
         $classname::displayForm($this, $datas, $rand, "init");
         Html::closeForm();

         $json_subtype = $json_subtypes[$subtype];
         /**
          * Display stored actions datas
          **/
         if (isset($datas['jobs'][$json_subtype])
               && !empty($datas['jobs'][$json_subtype])) {
            echo  "<div id='drag_deploypackage_". $subtype . "s'>";
            echo  "<form name='remove" . $subtype. "s' ".
                  "method='post' action='deploypackage.form.php' ".
                  "id='" . $subtype . "sList" . $rand . "'>";
            echo "<input type='hidden' name='remove_item' />";
            echo "<input type='hidden' name='itemtype' value='". $classname . "' />";
            echo "<input type='hidden' name='packages_id' value='".$this->getID()."' />";
            $classname::displayList($this, $datas, $rand);
            Html::closeForm();
            echo "</div>";
         }

         /**
          * Initialize drag and drop on subtype lists
          **/
         echo "<script type='text/javascript'>";
         echo "redipsInit('deploypackage', '$subtype', '".$this->getID()."');";
         echo "</script>";
         echo "</td>";
         echo "</tr>";
      }

      if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
         // === debug ===
         echo "<tr><td>";
         echo "<span id='package_json_debug'>";
         $this->displayJSONDebug();
         echo "</sp3an>";
         echo "</td></tr>";
      }
      echo "</table>";
      if (!empty($error_msg)) {
         echo "</div>";
         echo "<script type='text/javascript'>
                  Ext.onReady(function() {
                     Ext.select('#package_order_".$this->getID()."_span').mask();
                  });
               </script>";
      }
   }



   /**
    * Manage + button (audits, files, actions)
    *
    * @global array $CFG_GLPI
    * @param integer $id id of the package
    * @param string $subtype name of subtype (audits, files, actions)
    * @param string $rand random string for js to prevent collisions
    */
   function plusButtonSubtype($id, $subtype, $rand) {
      global $CFG_GLPI;

      if ($this->can($id, UPDATE)) {
         echo "&nbsp;";
         echo "<img id='plus_{$subtype}s_block{$rand}'";
         echo " onclick=\"new_subtype('{$subtype}', {$id}, {$rand})\" ";
         echo  " title='".__('Add')."' alt='".__('Add')."' ";
         echo  " class='pointer' src='".
               $CFG_GLPI["root_doc"].
               "/pics/add_dropdown.png' /> ";
      }
   }



   /**
    * Plus button used to add an element
    *
    * @global array $CFG_GLPI
    * @param string $dom_id
    * @param boolean $clone
    */
   static function plusButton($dom_id, $clone = FALSE) {
      global $CFG_GLPI;

      echo  "&nbsp;";
      echo  "<img id='plus_$dom_id' ";
      if ($clone !== FALSE) {
         echo " onClick=\"plusbutton('$dom_id', '$clone')\" ";
      } else {
         echo " onClick=\"plusbutton('$dom_id')\" ";
      }
      echo " title='".__('Add')."' alt='".__('Add')."' ";
      echo " class='pointer' src='".$CFG_GLPI["root_doc"].
              "/pics/add_dropdown.png'> ";
   }



   /**
    * When user is in DEBUG mode, we display the json
    *
    * @global array $CFG_GLPI
    */
   function displayJSONDebug() {
      global $CFG_GLPI;

      if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {

         echo "<span class='red'><b>DEBUG</b></span>";
         echo "<form action='".$CFG_GLPI["root_doc"].
         "/plugins/fusioninventory/front/deploypackage.form.php' method='POST'>";
         echo "<textarea cols='132' rows='25' style='border:0' name='json'>";
         echo PluginFusioninventoryToolbox::formatJson($this->fields['json']);
         echo "</textarea>";
         if ($this->can($this->getID(), UPDATE)) {
            echo "<input type='hidden' name='packages_id' value='{$this->fields['id']}' />";
            echo "<input type='submit' name='update_json' value=\"".
               _sx('button', 'Save')."\" class='submit'>";
         }
         Html::closeForm();
      }
   }



   /**
    * Update the json structure
    *
    * @param string $action_type type of action
    * @param array $params data used to update the json
    */
   static function alterJSON($action_type, $params) {
      //route to sub class
      $item_type = $params['itemtype'];

      if (
         in_array(
            $item_type,
            array(
               'PluginFusioninventoryDeployCheck',
               'PluginFusioninventoryDeployFile',
               'PluginFusioninventoryDeployAction'
            ))) {
         switch ($action_type) {

            case "add_item" :
               $item_type::add_item($params);
               break;

            case "save_item" :
               $item_type::save_item($params);
               break;

            case "remove_item" :
               $item_type::remove_item($params);
               break;

            case "move_item" :
               $item_type::move_item($params);
               break;

         }
      } else {
         Toolbox::logDebug("package subtype not found : " . $params['itemtype']);
         Html::displayErrorAndDie ("package subtype not found");
      }
   }



   /**
    * Export the package (information, actions, files...)
    *
    * @param integer $packages_id id of the package to export
    */
   function exportPackage($packages_id) {
      $this->getFromDB($packages_id);
      if (empty($this->fields['uuid'])) {
         $input = array(
             'id'   => $this->fields['id'],
             'uuid' => Rule::getUuid()
         );
         $this->update($input);
      }

      $pfDeployFile  = new PluginFusioninventoryDeployFile();

      // Generate JSON
      $input = $this->fields;
      unset($input['id']);
      $a_xml = array(
          'package'    => $input,
          'files'      => array(),
          'manifests'  => array(),
          'repository' => array(),
          'orders'     => array(array('json' => $this->fields['json'])),
      );
      $json = json_decode($this->fields['json'], true);
      $a_files = $json['associatedFiles'];

      // Add files
      foreach ($a_files as $files_id=>$data) {
         $a_pkgfiles = current($pfDeployFile->find("`sha512`='".$files_id."'", '', 1));
         if (count($a_pkgfiles) > 0) {
            unset($a_pkgfiles['id']);
            $a_xml['files'][] = $a_pkgfiles;
         }
      }

      // Create zip with JSON and files
      $name = preg_replace("/[^a-zA-Z0-9]/", '', $this->fields['name']);
      $filename = GLPI_PLUGIN_DOC_DIR."/fusioninventory/files/export/".$this->fields['uuid'].".".$name.".zip";
      if (file_exists($filename)) {
         unlink($filename);
      }

      $zip = new ZipArchive();
      if ($zip->open($filename) == TRUE) {
         if ($zip->open($filename, ZipArchive::CREATE) == TRUE) {
            $zip->addEmptyDir('files');
            $zip->addEmptyDir('files/manifests');
            $zip->addEmptyDir('files/repository');
            foreach ($a_files as $hash=>$data) {
               $sha512 = trim(file_get_contents(GLPI_PLUGIN_DOC_DIR."/fusioninventory/files/manifests/".$hash));
               $zip->addFile(GLPI_PLUGIN_DOC_DIR."/fusioninventory/files/manifests/".$hash, "files/manifests/".$hash);
               $a_xml['manifests'][] = $hash;
               $file = PluginFusioninventoryDeployFile::getDirBySha512($sha512).
                       "/".$sha512;
               $zip->addFile(GLPI_PLUGIN_DOC_DIR."/fusioninventory/files/repository/".$file, "files/repository/".$file);
               $a_xml['repository'][] = $file;
            }
            $json_string = json_encode($a_xml);
            $zip->addFromString('information.json', $json_string);
         }
         $zip->close();
         Session::addMessageAfterRedirect(__("Package exported in", "fusioninventory")." ".GLPI_PLUGIN_DOC_DIR."/fusioninventory/files/export/".$this->fields['uuid'].".".$name.".zip");
      }
   }



   /**
    * Import the package
    *
    * @param string $zipfile the zip file with all data inside
    */
   function importPackage($zipfile) {

      $zip           = new ZipArchive();
      $pfDeployFile  = new PluginFusioninventoryDeployFile();

      $filename = GLPI_PLUGIN_DOC_DIR."/fusioninventory/files/import/".$zipfile;

      $extract_folder = GLPI_PLUGIN_DOC_DIR."/fusioninventory/files/import/".$zipfile.".extract";

      if ($zip->open($filename, ZipArchive::CREATE) == TRUE) {
         $zip->extractTo($extract_folder);
         $zip->close();
      }
      $json_string = file_get_contents($extract_folder."/information.json");

      $a_info = json_decode($json_string, true);

      // Find package with this uuid
      $a_packages = $this->find("`uuid`='".$a_info['package']['uuid']."'");
      if (count($a_packages) == 0) {
         // Create it
         $_SESSION['tmp_clone_package'] = true;
         $this->add($a_info['package']);
         foreach ($a_info['files'] as $input) {
            $pfDeployFile->add($input);
         }
      }
      // Copy files
      foreach ($a_info['manifests'] as $manifest) {
         rename($extract_folder."/files/manifests/".$manifest, GLPI_PLUGIN_DOC_DIR."/fusioninventory/files/manifests/".$manifest);
      }
      foreach ($a_info['repository'] as $repository) {
         $split = explode('/', $repository);
         array_pop($split);
         $folder = '';
         foreach ($split as $dir) {
            $folder .= '/'.$dir;
            if (!file_exists(GLPI_PLUGIN_DOC_DIR."/fusioninventory/files/repository".$folder)) {
               mkdir(GLPI_PLUGIN_DOC_DIR."/fusioninventory/files/repository".$folder);
            }
         }
         rename($extract_folder."/files/repository/".$repository, GLPI_PLUGIN_DOC_DIR."/fusioninventory/files/repository/".$repository);
      }
   }



   /**
    * Display list of packages to import
    */
   function listPackagesToImport() {

      $rand = mt_rand();

      echo "<div class='spaced'>";
      Html::openMassiveActionsForm('mass'.__CLASS__.$rand);

      $massiveactionparams = array('container' => 'mass'.__CLASS__.$rand);
      Html::showMassiveActions($massiveactionparams);
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr class='tab_bg_1'>";
      echo "<th colspan='5'>";
      echo __('Packages to import', 'fusioninventory');
      echo "</th>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<th width='10'>".Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand)."</th>";
      echo "<th>";
      echo __('Name');
      echo "</th>";
      echo "<th>";
      echo __('uuid');
      echo "</th>";
      echo "<th>";
      echo __('Package to update');
      echo "</th>";
      echo "</tr>";

      foreach (glob(GLPI_PLUGIN_DOC_DIR."/fusioninventory/files/import/*.zip") as $file) {
         echo "<tr class='tab_bg_1'>";
         $file = str_replace(GLPI_PLUGIN_DOC_DIR."/fusioninventory/files/import/", "", $file);
         $split = explode('.', $file);
         echo "<td>";
         Html::showMassiveActionCheckBox(__CLASS__, $file);
         echo "</td>";
         echo "<td>";
         echo $split[2];
         echo "</td>";
         echo "<td>";
         echo $split[0].".".$split[1];
         echo "</td>";
         echo "<td>";
         $a_packages = current($this->find("`uuid`='".$split[0].".".$split[1]."'", '', 1));
         if (count($a_packages) > 1) {
            $this->getFromDB($a_packages['id']);
            echo $this->getLink();
         }
         echo "</td>";
         echo "</tr>";
      }
      echo "</table>";
      $massiveactionparams['ontop'] =false;
      Html::showMassiveActions($massiveactionparams);
      echo "</div>";
   }



   /**
    * Get a sub element at index
    *
    * @param string $subtype
    * @param integer $index
    * @return string
    */
   function getSubElement($subtype, $index) {
      $data_o = json_decode($this->fields['json'], TRUE);
      return $data_o['jobs'][$subtype][$index];
   }



   /**
    * Get Order's associated file by hash
    *
    * @param string $hash
    * @return null|string
    */
   function getAssociatedFile($hash) {
      $data_o = json_decode($this->fields['json'], TRUE);

      if (array_key_exists( $hash, $data_o['associatedFiles'])) {
         return $data_o['associatedFiles'][$hash];
      }
      return NULL;
   }



   /**
    * Get the json
    *
    * @param integer $packages_id id of the order
    * @return boolean|string the string is in json format
    */
   static function getJson($packages_id) {
      $pfDeployPackage = new self;
      $pfDeployPackage->getFromDB($packages_id);
      if (!empty($pfDeployPackage->fields['json'])) {
         return $pfDeployPackage->fields['json'];
      } else {
         return FALSE;
      }
   }



   /**
    * Update the order json
    *
    * @param integer $packages_id
    * @param array $datas
    * @return integer error number
    */
   static function updateOrderJson($packages_id, $datas) {
      $pfDeployPackage = new self;
      $options = JSON_UNESCAPED_SLASHES;

      $json = json_encode($datas, $options);

      $json_error_consts = array(
         JSON_ERROR_NONE           => "JSON_ERROR_NONE",
         JSON_ERROR_DEPTH          => "JSON_ERROR_DEPTH",
         JSON_ERROR_STATE_MISMATCH => "JSON_ERROR_STATE_MISMATCH",
         JSON_ERROR_CTRL_CHAR      => "JSON_ERROR_CTRL_CHAR",
         JSON_ERROR_SYNTAX         => "JSON_ERROR_SYNTAX",
         JSON_ERROR_UTF8           => "JSON_ERROR_UTF8"
      );

      $error_json = json_last_error();

      if (version_compare(PHP_VERSION, '5.5.0',"ge")) {
         $error_json_message = json_last_error_msg();
      } else {
         $error_json_message = "";
      }
      $error = 0;
      if ($error_json != JSON_ERROR_NONE) {
         $error_msg = $json_error_consts[$error_json];
         Session::addMessageAfterRedirect(
            __("The modified JSON contained a syntax error :", "fusioninventory") . "<br/>" .
            $error_msg . "<br/>". $error_json_message, FALSE, ERROR, FALSE
         );
         $error = 1;
      } else {
         $error = $pfDeployPackage->update(
            array(
               'id'   => $packages_id,
               'json' => Toolbox::addslashes_deep($json)
            )
         );
      }
      return $error;
   }



   /**
    * Get the tab name used for item
    *
    * @param object $item the item object
    * @param integer $withtemplate 1 if is a template form
    * @return string name of the tab
    */
   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if (!$withtemplate) {
         switch ($item->getType()) {

            case __CLASS__ :
               if ($item->canUpdateItem()) {
                  if ($_SESSION['glpishow_count_on_tabs']) {
                     $nb = $item->countVisibilities();
                     return self::createTabEntry(_n('Target for self-service', 'Targets for self-service', $nb, 'fusioninventory'),
                                                    $nb);
                  } else {
                     return _n('Target for self-service', 'Targets for self-service', 2, 'fusioninventory');
                  }
               }

         }
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

      if ($item->getType() == __CLASS__) {
         switch($tabnum) {

            case 1:
               $item->showVisibility();
               return TRUE;
         }
      }
      return FALSE;
   }



   /**
    * Count number elements for the visibility
    *
    * @return integer
    */
   function countVisibilities() {
      return (count($this->entities)
              + count($this->users)
              + count($this->groups)
              + count($this->profiles));
   }



   /**
    * Display the visibility, so who can read. write...
    *
    * @global array $CFG_GLPI
    * @return true
    */
   function showVisibility() {
      global $CFG_GLPI;

      $ID      = $this->fields['id'];
      $canedit = $this->can($ID, UPDATE);

      echo "<div class='center'>";

      $rand = mt_rand();
      $nb   = count($this->users) + count($this->groups) + count($this->profiles)
              + count($this->entities);

      if ($canedit) {
         echo "<div class='firstbloc'>";
         echo "<form name='deploypackagevisibility_form$rand' id='deploypackagevisibility_form$rand' ";
         echo " method='post' action='".Toolbox::getItemTypeFormURL('PluginFusioninventoryDeployPackage')."'>";
         echo "<input type='hidden' name='plugin_fusioninventory_deploypackages_id' value='$ID'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_1'><th colspan='4'>".__('Add a target for self-service', 'fusioninventory')."</th></tr>";
         echo "<tr class='tab_bg_2'><td width='100px'>";

         $types = array('Entity', 'Group', 'Profile', 'User');

         $addrand = Dropdown::showItemTypes('_type', $types);
         $params  = array('type'  => '__VALUE__',
                          'right' => 'plugin_fusioninventory_selfpackage');

         Ajax::updateItemOnSelectEvent("dropdown__type".$addrand,"visibility$rand",
                                       $CFG_GLPI["root_doc"]."/ajax/visibility.php",
                                       $params);

         echo "</td>";
         echo "<td><span id='visibility$rand'></span>";
         echo "</td></tr>";
         echo "</table>";
         Html::closeForm();
         echo "</div>";
      }

      echo "<div class='spaced'>";
      if ($canedit && $nb) {
         Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
         $massiveactionparams
            = array('num_displayed'
                        => $nb,
                    'container'
                        => 'mass'.__CLASS__.$rand,
                    'specific_actions'
                         => array('delete' => _x('button', 'Delete permanently')) );
         Html::showMassiveActions($massiveactionparams);
      }
      echo "<table class='tab_cadre_fixehov'>";
      $header_begin  = "<tr>";
      $header_top    = '';
      $header_bottom = '';
      $header_end    = '';
      if ($canedit && $nb) {
         $header_begin  .= "<th width='10'>";
         $header_top    .= Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
         $header_bottom .= Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
         $header_end    .= "</th>";
      }
      $header_end .= "<th>".__('Type')."</th>";
      $header_end .= "<th>"._n('Recipient', 'Recipients', Session::getPluralNumber())."</th>";
      $header_end .= "</tr>";
      echo $header_begin.$header_top.$header_end;

      // Users
      if (count($this->users)) {
         foreach ($this->users as $key => $val) {
            foreach ($val as $data) {
               echo "<tr class='tab_bg_1'>";
               if ($canedit) {
                  echo "<td>";
                  Html::showMassiveActionCheckBox('PluginFusioninventoryDeployPackage_User',$data["id"]);
                  echo "</td>";
               }
               echo "<td>".__('User')."</td>";
               echo "<td>".getUserName($data['users_id'])."</td>";
               echo "</tr>";
            }
         }
      }

      // Groups
      if (count($this->groups)) {
         foreach ($this->groups as $key => $val) {
            foreach ($val as $data) {
               echo "<tr class='tab_bg_1'>";
               if ($canedit) {
                  echo "<td>";
                  Html::showMassiveActionCheckBox('PluginFusioninventoryDeployPackage_Group',$data["id"]);
                  echo "</td>";
               }
               echo "<td>".__('Group')."</td>";
               echo "<td>";
               $names     = Dropdown::getDropdownName('glpi_groups', $data['groups_id'],1);
               $groupname = sprintf(__('%1$s %2$s'), $names["name"],
                                    Html::showToolTip($names["comment"], array('display' => false)));
               if ($data['entities_id'] >= 0) {
                  $groupname = sprintf(__('%1$s / %2$s'), $groupname,
                                       Dropdown::getDropdownName('glpi_entities',
                                                                 $data['entities_id']));
                  if ($data['is_recursive']) {
                     $groupname = sprintf(__('%1$s %2$s'), $groupname,
                                          "<span class='b'>(".__('R').")</span>");
                  }
               }
               echo $groupname;
               echo "</td>";
               echo "</tr>";
            }
         }
      }

      // Entity
      if (count($this->entities)) {
         foreach ($this->entities as $key => $val) {
            foreach ($val as $data) {
               echo "<tr class='tab_bg_1'>";
               if ($canedit) {
                  echo "<td>";
                  Html::showMassiveActionCheckBox('PluginFusioninventoryDeployPackage_Entity',$data["id"]);
                  echo "</td>";
               }
               echo "<td>".__('Entity')."</td>";
               echo "<td>";
               $names      = Dropdown::getDropdownName('glpi_entities', $data['entities_id'],1);
               $entityname = sprintf(__('%1$s %2$s'), $names["name"],
                                    Html::showToolTip($names["comment"], array('display' => false)));
               if ($data['is_recursive']) {
                  $entityname = sprintf(__('%1$s %2$s'), $entityname,
                                        "<span class='b'>(".__('R').")</span>");
               }
               echo $entityname;
               echo "</td>";
               echo "</tr>";
            }
         }
      }

      // Profiles
      if (count($this->profiles)) {
         foreach ($this->profiles as $key => $val) {
            foreach ($val as $data) {
               echo "<tr class='tab_bg_1'>";
               if ($canedit) {
                  echo "<td>";
                  Html::showMassiveActionCheckBox('PluginFusioninventoryDeployPackage_Profile',$data["id"]);
                  echo "</td>";
               }
               echo "<td>"._n('Profile', 'Profiles', 1)."</td>";
               echo "<td>";
               $names       = Dropdown::getDropdownName('glpi_profiles', $data['profiles_id'], 1);
               $profilename = sprintf(__('%1$s %2$s'), $names["name"],
                                    Html::showToolTip($names["comment"], array('display' => false)));
               if ($data['entities_id'] >= 0) {
                  $profilename = sprintf(__('%1$s / %2$s'), $profilename,
                                       Dropdown::getDropdownName('glpi_entities',
                                                                 $data['entities_id']));
                  if ($data['is_recursive']) {
                     $profilename = sprintf(__('%1$s %2$s'), $profilename,
                                        "<span class='b'>(".__('R').")</span>");
                  }
               }
               echo $profilename;
               echo "</td>";
               echo "</tr>";
            }
         }
      }
      if ($nb) {
         echo $header_begin.$header_bottom.$header_end;
      }

      echo "</table>";
      if ($canedit && $nb) {
         $massiveactionparams['ontop'] =false;
         Html::showMassiveActions($massiveactionparams);
         Html::closeForm();
      }

      echo "</div>";

      return TRUE;
   }



   /**
    * Fill internal variable with visibility elements when load package
    * information from database
    */
   function post_getFromDB() {
      // Users
      $this->users    = PluginFusioninventoryDeployPackage_User::getUsers($this->fields['id']);

      // Entities
      $this->entities = PluginFusioninventoryDeployPackage_Entity::getEntities($this->fields['id']);

      // Group / entities
      $this->groups   = PluginFusioninventoryDeployPackage_Group::getGroups($this->fields['id']);

      // Profile / entities
      $this->profiles = PluginFusioninventoryDeployPackage_Profile::getProfiles($this->fields['id']);
   }



   /**
    * Display form with deploy state of software user has requested on his computer(s) and form
    * to install packages to his computer(s)
    *
    * @param integer $users_id id of the user
    */
   function showPackageForMe($users_id) {

      $computer = new Computer();

      $my_packages = $this->getPackageForMe($users_id);

      $states = array(
          'agents_notdone'   => __('Not done yet', 'fusioninventory'),
          'agents_error'     => __('In error', 'fusioninventory'),
          'agents_success'   => __('Successful', 'fusioninventory'),
          'agents_running'   => __('Running', 'fusioninventory'),
          'agents_prepared'  => __('Prepared' , 'fusioninventory'),
          'agents_cancelled' => __('Cancelled', 'fusioninventory')
      );

      // Display for each computer, list of packages you can deploy
      echo "<form name='form' method='post' action='deploypackage.public.php' "
         . "enctype=\"multipart/form-data\">";

      echo "<table class='tab_cadre_fixe'>";
      foreach ($my_packages as $computers_id=>$data) {
         $package_to_install = array();
         $computer->getFromDB($computers_id);
         echo "<tr>";
         echo "<th><img src='../pics/computer_icon.png'/> Computer <i>".$computer->fields['name']."</i></th>";
         echo "</tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td>";
         echo '<div class="target_block">';
         echo '<div class="target_details">';
         echo '<div class="target_stats">';
         foreach ($data as $packages_id => $package_info) {
            if (isset($package_info['taskjobs_id'])) {
               echo '<div class="counter_block '.$package_info['last_taskjobstate']['state'].'">';
               echo "<table>";
               echo "<tr>";
               echo "<td style='width: 600px'>";
               echo $package_info['name'];
               echo "</td>";
               echo "<td style='width: 200px'>";
               echo Html::convDateTime($package_info['last_taskjobstate']['date']);
               echo "</td>";
               echo "<td style='width: 200px'>";
               echo $states[$package_info['last_taskjobstate']['state']];
               echo "</td>";
               echo "</tr>";
               echo "</table>";
               echo '</div>';
            } else {
               $package_to_install[$packages_id] = $package_info['name'];
            }
         }
         echo '</div>';
         echo '</div>';
         echo '</div>';
         echo "</td>";
         echo "</tr>";

         $p['name']     = 'deploypackages_'.$computers_id;
         $p['display']  = true;
         $p['multiple'] = true;
         $p['size']     = 3;
         $p['width']    = 950;

         echo "<tr class='tab_bg_1'>";
         echo "<td>";
         echo __('Select packages you want install:', 'fusioninventory');
         echo "<br/>";
         Dropdown::showFromArray($p['name'], $package_to_install, $p);
         echo "</td>";
         echo "</tr>";
      }
      if (count($my_packages)) {
         echo "<tr>";
         echo "<th colspan='2'>";
         echo "<input name='prepareinstall' value=\"".__('Prepare for install', 'fusioninventory').
            "\" class='submit' type='submit'>";
         echo "</th>";
         echo "</tr>";
      } else {
         echo "<tr>";
         echo "<th colspan='2'>";
         echo __('No packages available to install', 'fusioninventory');
         echo "</th>";
         echo "</tr>";
      }
      echo "</table>";
      Html::closeForm();
   }



   /**
    * Get deploy packages available to install on user computer(s) and for
    * packages requested the state of deploy
    *
    * @param integer $users_id id of the user
    */
   function getPackageForMe($users_id) {

      $computer      = new Computer();
      $pfDeployGroup = new PluginFusioninventoryDeployGroup();
      // get all computers of the user
      $mycomputers = $computer->find("`users_id`='".$users_id."'"
              . "AND `entities_id` IN (".$_SESSION['glpiactiveentities_string'].")");

      $my_packages = array();
      foreach ($mycomputers as $computers_id=>$data) {
         $my_packages[$computers_id] = array();
      }
      $packages_used = $this->getMyDepoyPackages($my_packages, $users_id);

      $packages = $this->canUserDeploySelf();
      if ($packages) {
         foreach ($packages as $package) {
            $computers = $pfDeployGroup->getTargetsForGroup($package['plugin_fusioninventory_deploygroups_id']);
            foreach ($mycomputers as $computers_id=>$data) {
               if (isset($computers[$computers_id])) {
                  $my_packages[$computers_id][$package['id']] = array('name' => $package['name']);
                  if (isset($packages_used[$computers_id][$package['id']])) {
                     $taskjobs_id = $packages_used[$computers_id][$package['id']];
                     $my_packages[$computers_id][$package['id']]['taskjobs_id'] = $taskjobs_id;
                     $last_job_state = $this->getMyDepoyPackagesState($computers_id, $taskjobs_id);
                     $my_packages[$computers_id][$package['id']]['last_taskjobstate'] = $last_job_state;
                  }
               }
            }
         }
      }
      return $my_packages;
   }



   /**
    * Add the package in task or use existant task and add the computer in
    * taskjob
    *
    * @global object $DB
    * @param integer $computers_id id of the computer where depoy package
    * @param integer $packages_id id of the package to install in computer
    * @param integer $users_id id of the user have requested the installation
    */
   function deployToComputer($computers_id, $packages_id, $users_id) {
      global $DB;

      $pfTask    = new PluginFusioninventoryTask();
      $pfTaskJob = new PluginFusioninventoryTaskJob();
      $computer  = new Computer();
      // search if a taskjob exist
      $computer->getFromDB($computers_id);
      $sql = "SELECT glpi_plugin_fusioninventory_taskjobs.* FROM `glpi_plugin_fusioninventory_taskjobs`"
              . " LEFT JOIN `glpi_plugin_fusioninventory_tasks`"
              . "    ON glpi_plugin_fusioninventory_tasks.id = plugin_fusioninventory_tasks_id"
              . " WHERE `targets`='[{\"PluginFusioninventoryDeployPackage\":\"".$packages_id."\"}]'"
              . "    AND `glpi_plugin_fusioninventory_tasks`.`is_active`='1'"
              . "    AND `glpi_plugin_fusioninventory_tasks`.`name` like '[self-deploy] %'"
              . "    AND `glpi_plugin_fusioninventory_tasks`.`entities_id`='".$computer->fields['entities_id']."'"
              . "    AND `glpi_plugin_fusioninventory_tasks`.`reprepare_if_successful`='0'"
              . " LIMIT 1";
      $result = $DB->query($sql);

      // case 1: if exist, we add computer in actors of the taskjob
      if ($DB->numrows($result) == 1) {
         while ($data = $DB->fetch_array($result)) {
            $actors = importArrayFromDB($data['actors']);
            $actors[] = array('Computer' => $computers_id);
            $enduser = importArrayFromDB($data['enduser']);
            if (isset($enduser[$users_id])) {
               if (!in_array($enduser[$users_id], $computers_id)) {
                  $enduser[$users_id][] = $computers_id;
               }
            } else {
               $enduser[$users_id] = array($computers_id);
            }
            $input = array(
                'id'      => $data['id'],
                'actors'  => exportArrayToDB($actors),
                'enduser' => exportArrayToDB($enduser)
            );
            $pfTaskJob->update($input);
         }
      } else {
      // case 2: if not exist, create a new task + taskjob
         $this->getFromDB($packages_id);
         $input = array(
             'name'                    => '[self-deploy] '.$this->fields['name'],
             'entities_id'             => $computer->fields['entities_id'],
             'reprepare_if_successful' => 0
         );
         $tasks_id = $pfTask->add($input);
         $input = array(
             'plugin_fusioninventory_tasks_id' => $tasks_id,
             'entities_id' => $computer->fields['entities_id'],
             'name'        => 'deploy',
             'method'      => 'deployinstall',
             'targets'     => '[{"PluginFusioninventoryDeployPackage":"'.$packages_id.'"}]',
             'actors'      => exportArrayToDB(array(array('Computer' => $computers_id))),
             'enduser'     => exportArrayToDB(array($users_id => array($computers_id)))
         );
         $pfTaskJob->add($input);
         $input = array(
             'id'        => $tasks_id,
             'is_active' => 1,
         );
         $pfTask->update($input);
      }
   }



   /**
    * Get all packages on all my computer I have requested to install
    *
    * @global object $DB
    * @param array $computers_packages
    * @param integer $users_id
    * @return array
    */
   function getMyDepoyPackages($computers_packages, $users_id) {
      global $DB;

      // Get packages yet deployed by enduser
      $packages_used = array();
      foreach ($computers_packages as $computers_id=>$data) {
         $packages_used[$computers_id] = array();
      }
      $sql = "SELECT glpi_plugin_fusioninventory_taskjobs.* FROM `glpi_plugin_fusioninventory_taskjobs`"
              . " LEFT JOIN `glpi_plugin_fusioninventory_tasks`"
              . "    ON glpi_plugin_fusioninventory_tasks.id = plugin_fusioninventory_tasks_id"
              . " WHERE `enduser` IS NOT NULL"
              . "    AND `glpi_plugin_fusioninventory_tasks`.`is_active`='1'"
              . "    AND `glpi_plugin_fusioninventory_tasks`.`entities_id` IN (".$_SESSION['glpiactiveentities_string'].")";
      $result = $DB->query($sql);
      while ($data = $DB->fetch_array($result)) {
         $enduser = importArrayFromDB($data['enduser']);
         if (isset($enduser[$users_id])) {
            $targets = importArrayFromDB($data['targets']);
            foreach ($enduser[$users_id] as $computers_id) {
               $packages_used[$computers_id][$targets[0]['PluginFusioninventoryDeployPackage']] = $data['id'];
            }
         }
      }
      return $packages_used;
   }



   /**
    * Get the state of the package I have requeted to install
    *
    * @param integer $computers_id id of the computer
    * @param integer $taskjobs_id id of the taskjob (where order defined)
    * @param string $packages_name name of the package
    */
   function getMyDepoyPackagesState($computers_id, $taskjobs_id) {
      $pfTaskJobState = new PluginFusioninventoryTaskjobstate();
      $pfAgent = new PluginFusioninventoryAgent();

      // get taskjobstate with taskjobs_id and agent of computers_id
      $agents_id = $pfAgent->getAgentWithComputerid($computers_id);

      $last_job_state = array();
      $taskjobstates = current($pfTaskJobState->find("`plugin_fusioninventory_taskjobs_id`='".$taskjobs_id."'"
              . " AND `plugin_fusioninventory_agents_id`='".$agents_id."'", '`id` DESC', 1));
      if ($taskjobstates) {
         $state = '';

         switch ($taskjobstates['state']) {

            case PluginFusioninventoryTaskjobstate::CANCELLED :
               $state = 'agents_cancelled';
               break;

            case PluginFusioninventoryTaskjobstate::PREPARED :
               $state = 'agents_prepared';
               break;

            case PluginFusioninventoryTaskjobstate::SERVER_HAS_SENT_DATA :
            case PluginFusioninventoryTaskjobstate::AGENT_HAS_SENT_DATA :
               $state = 'agents_running';
               break;

            case PluginFusioninventoryTaskjobstate::IN_ERROR :
               $state = 'agents_error';
               break;

            case PluginFusioninventoryTaskjobstate::FINISHED :
               $state = 'agents_success';
               break;

         }
         $logs = $pfTaskJobState->getLogs($taskjobstates['id'], date("Y-m-d H:i:s"));
         $last_job_state['state'] = $state;
         $last_job_state['date'] = $logs['logs'][0]['log.date'];
      }
      return $last_job_state;
   }



   /**
    * Check I have rights to deploy packages
    *
    * @global object $DB
    * @return false|array
    */
   function canUserDeploySelf() {
      global $DB;

      $table = "glpi_plugin_fusioninventory_deploypackages";
      $where = " WHERE `".$table."`.`plugin_fusioninventory_deploygroups_id` > 0 "
              . " AND (";
      // groups
      if (!empty($_SESSION['glpigroups'])) {
         $where .= " `glpi_plugin_fusioninventory_deploypackages_groups`.`groups_id` IN ('".implode("', '", $_SESSION['glpigroups'])."') OR ";
      }
      // entity
      $where .= " (`glpi_plugin_fusioninventory_deploypackages_entities`.`entities_id`='".$_SESSION['glpiactive_entity']."') OR "
              . "(`glpi_plugin_fusioninventory_deploypackages_entities`.`entities_id` IN "
              . "('".implode("','", getAncestorsOf('glpi_entities', $_SESSION['glpiactive_entity']))."') "
              . "AND `glpi_plugin_fusioninventory_deploypackages_entities`.`is_recursive`= '1') OR ";
      // user
      $where .= " `glpi_plugin_fusioninventory_deploypackages_users`.`users_id`='".$_SESSION['glpiID']."' OR ";
      // profile
      $where .= " `glpi_plugin_fusioninventory_deploypackages_profiles`.`profiles_id`='".$_SESSION['glpiactiveprofile']['id']."' ";

      $where .= " )";

      $query = "SELECT DISTINCT `".$table."`.*
                FROM `$table`
                LEFT JOIN `glpi_plugin_fusioninventory_deploypackages_groups`
                     ON (`glpi_plugin_fusioninventory_deploypackages_groups`.`plugin_fusioninventory_deploypackages_id` = `$table`.`id`)
                LEFT JOIN `glpi_plugin_fusioninventory_deploypackages_entities`
                     ON (`glpi_plugin_fusioninventory_deploypackages_entities`.`plugin_fusioninventory_deploypackages_id` = `$table`.`id`)
                LEFT JOIN `glpi_plugin_fusioninventory_deploypackages_users`
                     ON (`glpi_plugin_fusioninventory_deploypackages_users`.`plugin_fusioninventory_deploypackages_id` = `$table`.`id`)
                LEFT JOIN `glpi_plugin_fusioninventory_deploypackages_profiles`
                     ON (`glpi_plugin_fusioninventory_deploypackages_profiles`.`plugin_fusioninventory_deploypackages_id` = `$table`.`id`)
               $where";
      $result = $DB->query($query);
      $a_packages = array();
      if ($DB->numrows($result) > 0) {
         while ($data = $DB->fetch_assoc($result)) {
            $a_packages[$data['id']] = $data;
         }
         return $a_packages;
      }
      return False;
   }
}

?>