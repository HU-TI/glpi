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
 * This file is used to manage the search in groups (static and dynamic).
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
 * Manage the search in groups (static and dynamic).
 */
class PluginFusioninventoryComputer extends Computer {

   /**
    * The right name for this class
    *
    * @var string
    */
   static $rightname = "plugin_fusioninventory_group";


   /**
    * Get search function for the class
    *
    * @return array
    */
   function getSearchOptions() {
      $computer = new Computer();
      $options  = $computer->getSearchOptions();

      $options += NetworkPort::getSearchOptionsToAdd('Computer');

      $options['6000']['name']          = __('Static group', 'fusioninventory');
      $options['6000']['table']         = getTableForItemType('PluginFusioninventoryDeployGroup');
      $options['6000']['massiveaction'] = FALSE;
      $options['6000']['field']         ='name';
      $options['6000']['forcegroupby']  = true;
      $options['6000']['usehaving']     = true;
      $options['6000']['datatype']      = 'dropdown';
      $options['6000']['joinparams']    = array('beforejoin'
                                         => array('table'      => 'glpi_plugin_fusioninventory_deploygroups_staticdatas',
                                                  'joinparams' => array('jointype'          => 'itemtype_item',
                                                                        'specific_itemtype' => 'Computer')));
      return $options;
   }



   /**
    * Get the massive actions for this object
    *
    * @param object|null $checkitem
    * @return array list of actions
    */
   function getSpecificMassiveActions($checkitem=NULL) {

      $actions = array();
      if (isset($_GET['id'])) {
         $id = $_GET['id'];
      } else {
         $id = $_POST['id'];
      }
      $group = new PluginFusioninventoryDeployGroup();
      $group->getFromDB($id);

      //There's no massive action associated with a dynamic group !
      if ($group->isDynamicGroup() || !$group->canEdit($group->getID())) {
         return array();
      }

      if (!isset($_POST['custom_action'])) {
            $actions['PluginFusioninventoryComputer'.MassiveAction::CLASS_ACTION_SEPARATOR.'add']
               = _x('button', 'Add');
            $actions['PluginFusioninventoryComputer'.MassiveAction::CLASS_ACTION_SEPARATOR.'deleteitem']
               = _x('button','Delete permanently');
      } else {
         if ($_POST['custom_action'] == 'add_to_group') {
            $actions['PluginFusioninventoryComputer'.MassiveAction::CLASS_ACTION_SEPARATOR.'add']
               = _x('button', 'Add');
         } elseif ($_POST['custom_action'] == 'delete_from_group') {
            $actions['PluginFusioninventoryComputer'.MassiveAction::CLASS_ACTION_SEPARATOR.'deleteitem']
               = _x('button','Delete permanently');
         }
      }
      return $actions;
   }



   /**
    * Define the standard massive actions to hide for this class
    *
    * @return array list of massive actions to hide
    */
   function getForbiddenStandardMassiveAction() {

      $forbidden   = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'update';
      $forbidden[] = 'add';
      $forbidden[] = 'delete';
      return $forbidden;
   }



   /**
    * Execution code for massive action
    *
    * @param object $ma MassiveAction instance
    * @param object $item item on which execute the code
    * @param array $ids list of ID on which execute the code
    */
   static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item,
                                                       array $ids) {

      $group_item = new PluginFusioninventoryDeployGroup_Staticdata();
      switch ($ma->getAction()) {

         case 'add' :
            foreach ($ids as $key) {
               if ($item->can($key, UPDATE)) {
                  if (!countElementsInTable($group_item->getTable(),
                                            "`plugin_fusioninventory_deploygroups_id`='"
                                                .$_POST['id']."'
                                              AND `itemtype`='Computer'
                                              AND `items_id`='$key'")) {
                     $group_item->add(array(
                        'plugin_fusioninventory_deploygroups_id'
                           => $_POST['id'],
                        'itemtype' => 'Computer',
                        'items_id' => $key));
                     $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_OK);
                  } else {
                     $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
                  }
               } else {
                  $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_NORIGHT);
                  $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
               }
            }
            return;

         case 'deleteitem':
            foreach ($ids as $key) {
               if ($group_item->deleteByCriteria(array('items_id' => $key,
                                                       'itemtype' => 'Computer',
                                                       'plugin_fusioninventory_deploygroups_id'
                                                          => $_POST['id']))) {
                  $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_OK);
               } else {
                  $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
               }
            }

      }
   }



   /**
    * Display form related to the massive action selected
    *
    * @param object $ma MassiveAction instance
    * @return boolean
    */
   static function showMassiveActionsSubForm(MassiveAction $ma) {
      if ($ma->getAction() == 'add') {
         echo "<br><br>".Html::submit(_x('button', 'Add'),
                                      array('name' => 'massiveaction'));
         return TRUE;
      }
      return parent::showMassiveActionsSubForm($ma);
   }

}

?>