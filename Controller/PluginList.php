<?php
/**
 * This file is part of Community plugin for FacturaScripts.
 * Copyright (C) 2018 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Plugins\Community\Controller;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Plugins\webportal\Lib\WebPortal\SectionController;

/**
 * Description of PluginList
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class PluginList extends SectionController
{

    /**
     * Load sections to the view.
     */
    protected function createSections()
    {
        $this->addListSection('ListWebProject', 'WebProject', 'plugins', 'fas fa-plug', '2018');
        $this->addOrderOption('ListWebProject', ['LOWER(name)'], 'name', 1);
        $this->addSearchOptions('ListWebProject', ['name', 'description']);
    }

    /**
     * Load section data procedure
     *
     * @param string $sectionName
     */
    protected function loadData(string $sectionName)
    {
        switch ($sectionName) {
            case 'ListWebProject':
                $where = [new DataBaseWhere('plugin', true)];
                $this->sections[$sectionName]->loadData('', $where);
                break;
        }
    }
}
