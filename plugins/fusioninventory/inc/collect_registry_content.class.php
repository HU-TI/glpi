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
 * This file is used to manage the windows registry keys found by agent and
 * linked to the computer
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
 * Manage the registry keys found by the collect module of agent.
 */
class PluginFusioninventoryCollect_Registry_Content extends CommonDBTM {

   /**
    * The right name for this class
    *
    * @var string
    */
   static $rightname = 'plugin_fusioninventory_collect';


   /**
    * Get name of this type by language of the user connected
    *
    * @param integer $nb number of elements
    * @return string name of this type
    */
   static function getTypeName($nb=0) {
      return __('Windows registry content', 'fusioninventory');
   }



   /**
    * Get the tab name used for item
    *
    * @param object $item the item object
    * @param integer $withtemplate 1 if is a template form
    * @return string name of the tab
    */
   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if ($item->getID() > 0) {
         if (get_class($item) == 'PluginFusioninventoryCollect') {
            if ($item->fields['type'] == 'registry') {
               $a_colregs = getAllDatasFromTable('glpi_plugin_fusioninventory_collects_registries',
                                                 "`plugin_fusioninventory_collects_id`='".$item->getID()."'");
               if (count($a_colregs) == 0) {
                  return '';
               }
               $in = array_keys($a_colregs);
               if (countElementsInTable('glpi_plugin_fusioninventory_collects_registries_contents',
                                "`plugin_fusioninventory_collects_registries_id` IN ('".implode("','", $in)."')") > 0) {
                  return __('Windows registry content', 'fusioninventory');
               }
            }
         } else if (get_class($item) == 'Computer') {
            if (countElementsInTable('glpi_plugin_fusioninventory_collects_registries_contents',
                             "`computers_id`='".$item->getID()."'") > 0) {
               return __('Windows registry content', 'fusioninventory');
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
      $pfCollect_Registry = new PluginFusioninventoryCollect_Registry_Content();
      if (get_class($item) == 'PluginFusioninventoryCollect') {
         $pfCollect_Registry->showForCollect($item->getID());
      } else if (get_class($item) == 'Computer') {
         $pfCollect_Registry->showForComputer($item->getID());
      }
      return TRUE;
   }

   /**
    * Delete all registriies contents linked to the computer (most cases when delete a
    * computer)
    *
    * @param integer $computers_id
    */
   static function cleanComputer($computers_id) {
      $registry_content = new self();
      $registry_content->deleteByCriteria(array('computers_id' => $computers_id));
   }

   /**
    * Update computer registry values (add and update) related to this
    * collect registry id
    *
    * @global object $DB
    * @param integer $computers_id id of the computer
    * @param array $registry_data registry info sent by agent
    * @param integer $collects_registries_id id of collect_registry
    */
   function updateComputer($computers_id, $registry_data, $collects_registries_id) {
      global $DB;

      $db_registries = array();
      $query = "SELECT `id`, `key`, `value`
            FROM `glpi_plugin_fusioninventory_collects_registries_contents`
         WHERE `computers_id` = '".$computers_id."'
              AND `plugin_fusioninventory_collects_registries_id`=
               '".$collects_registries_id."'";
      $result = $DB->query($query);
      while ($data = $DB->fetch_assoc($result)) {
         $idtmp = $data['id'];
         unset($data['id']);
         $data1 = Toolbox::addslashes_deep($data);
         $db_registries[$idtmp] = $data1;
      }

      unset($registry_data['_cpt']);
      unset($registry_data['_sid']);

      foreach ($registry_data as $key => $value) {
         foreach ($db_registries as $keydb => $arraydb) {
            if ($arraydb['key'] == $key) {
               $input = array();
               $input['key'] = $arraydb['key'];
               $input['id'] = $keydb;
               $input['value'] = $value;
               $this->update($input);
               unset($registry_data[$key]);
               unset($db_registries[$keydb]);
               break;
            }
         }
      }

      if (count($registry_data) == 0
         AND count($db_registries) == 0) {
         // Nothing to do
      } else {
         if (count($db_registries) != 0) {
            foreach ($db_registries as $idtmp => $data) {
               $this->delete(array('id'=>$idtmp), 1);
            }
         }
         if (count($registry_data) != 0) {
            foreach ($registry_data as $key=>$value) {
               $input = array(
                   'computers_id' => $computers_id,
                   'plugin_fusioninventory_collects_registries_id' => $collects_registries_id,
                   'key'          => $key,
                   'value'        => $value
               );
               $this->add($input);
            }
         }
      }
   }



   /**
    * Display registries keys related with collect id
    *
    * @param integer $collects_id id of collect
    */
   function showForCollect($collects_id) {

      $a_colregs = getAllDatasFromTable('glpi_plugin_fusioninventory_collects_registries',
                                              "`plugin_fusioninventory_collects_id`='".$collects_id."'");
      foreach ($a_colregs as $data) {
         $this->showForCollectRegistry($data['id']);
      }
   }



   /**
    * Show registries keys of the computer
    *
    * @param integer $computers_id id of the computer
    */
   function showForComputer($computers_id) {
      $pfCollect_Registry = new PluginFusioninventoryCollect_Registry();
      echo "<table class='tab_cadre_fixe'>";
      $a_data = $this->find("`computers_id`='".$computers_id."'",
                              "`plugin_fusioninventory_collects_registries_id`,
                                 `key`");
      $previous_key = 0;
      foreach ($a_data as $data) {
         $pfCollect_Registry->getFromDB($data['plugin_fusioninventory_collects_registries_id']);
         if ($previous_key != $data['plugin_fusioninventory_collects_registries_id']) {
            echo "<tr class='tab_bg_1'>";
            echo '<th colspan="3">';
            echo $pfCollect_Registry->fields['name'];
            echo '</th>';
            echo '</tr>';

            echo "<tr>";
            echo "<th>".__('Path', 'fusioninventory')."</th>";
            echo "<th>".__('Value', 'fusioninventory')."</th>";
            echo "<th>".__('Data', 'fusioninventory')."</th>";
            echo "</tr>";

            $previous_key = $data['plugin_fusioninventory_collects_registries_id'];
         }

         echo "<tr class='tab_bg_1'>";
         echo '<td>';
         echo $pfCollect_Registry->fields['hive'].
              $pfCollect_Registry->fields['path'];
         echo '</td>';
         echo '<td>';
         echo $data['key'];
         echo '</td>';
         echo '<td>';
         echo $data['value'];
         echo '</td>';
         echo "</tr>";
      }
      echo '</table>';
   }



   /**
    * Display registry keys / values of collect_registry id
    *
    * @param integer $collects_registries_id
    */
   function showForCollectRegistry($collects_registries_id) {
      $pfCollect_Registry = new PluginFusioninventoryCollect_Registry();
      $computer = new Computer();

      $pfCollect_Registry->getFromDB($collects_registries_id);

      echo "<table class='tab_cadre_fixe'>";

      echo "<tr>";
      echo "<th colspan='3'>";
      echo $pfCollect_Registry->fields['hive'].
           $pfCollect_Registry->fields['path'];
      echo "</th>";
      echo "</tr>";

      echo "<tr>";
      echo "<th>".__('Computer')."</th>";
      echo "<th>".__('Value', 'fusioninventory')."</th>";
      echo "<th>".__('Data', 'fusioninventory')."</th>";
      echo "</tr>";

      $a_data = $this->find("`plugin_fusioninventory_collects_registries_id`='".$collects_registries_id."'",
                              "`key`");
      foreach ($a_data as $data) {
         echo "<tr class='tab_bg_1'>";
         echo '<td>';
         $computer->getFromDB($data['computers_id']);
         echo $computer->getLink(1);
         echo '</td>';
         echo '<td>';
         echo $data['key'];
         echo '</td>';
         echo '<td>';
         echo $data['value'];
         echo '</td>';
         echo "</tr>";
      }
      echo '</table>';
   }
}

?>
