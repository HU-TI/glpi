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
 * This file is used to manage the operating system on the computer.
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
   die("Sorry. You can't access this file directly");
}

/**
 * Mnage the operating system on the computer.
 */
class PluginFusioninventoryComputerOperatingSystem extends CommonDropdown {

   /**
    * We activate the history.
    *
    * @var boolean
    */
   public $dohistory = true;

   /**
    * Set this dropdown class will not be translated
    *
    * @var boolean
    */
   public $can_be_translated = false;


   /**
    * Define first level menu name
    *
    * @var string
    */
   public $first_level_menu  = "plugins";

   /**
    * Define second level menu name
    *
    * @var string
    */
   public $second_level_menu = "pluginfusioninventorymenu";

   /**
    * Define third level menu name
    *
    * @var string
    */
   public $third_level_menu  = "computeroperatingsystem";


   /**
    * Get name of this type by language of the user connected
    *
    * @param integer $nb number of elements
    * @return string name of this type
    */
   static function getTypeName($nb=0) {
      return _n('Operating system (computer)', 'Operating systems (computer)', $nb, 'fusioninventory');
   }



   /**
    * Fields added to this class
    *
    * @return array
    */
   function getAdditionalFields() {

      return array(array('name'  => 'operatingsystemarchitectures_id',
                         'label' => __('Architecture', 'fusioninventory'),
                         'type'  => 'dropdownValue'),
                   array('name'  => 'plugin_fusioninventory_computeroskernelnames_id',
                         'label' => __('Kernel name', 'fusioninventory'),
                         'type'  => 'dropdownValue'),
                   array('name'  => 'plugin_fusioninventory_computeroskernelversions_id',
                         'label' => __('Kernel version', 'fusioninventory'),
                         'type'  => 'dropdownValue'),
                   array('name'  => 'operatingsystems_id',
                         'label' => _n('Operating system', 'Operating systems', 1),
                         'type'  => 'dropdownValue'),
                   array('name'  => 'operatingsystemversions_id',
                         'label' => _n('Version of the operating system', 'Versions of the operating systems', 1),
                         'type'  => 'dropdownValue'),
                   array('name'  => 'operatingsystemservicepacks_id',
                         'label' => _n('Service pack', 'Service packs', 1),
                         'type'  => 'dropdownValue'),
                   array('name'  => 'plugin_fusioninventory_computeroperatingsystemeditions_id',
                         'label' => __('Operating system edition', 'fusioninventory'),
                         'type'  => 'dropdownValue'));
   }

}
