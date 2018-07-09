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

use FacturaScripts\Plugins\Community\Model\WebDocPage;
use FacturaScripts\Plugins\Community\Model\WebProject;
use FacturaScripts\Plugins\Community\Model\WebTeam;
use FacturaScripts\Plugins\webportal\Controller\Sitemap as parentController;

/**
 * Description of Sitemap
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Sitemap extends parentController
{

    protected function getSitemapItems(): array
    {
        $items = parent::getSitemapItems();

        foreach ($this->getPluginItems() as $item) {
            $items[] = $item;
        }

        foreach ($this->getDocPagesItems() as $item) {
            $items[] = $item;
        }

        foreach ($this->getTeamItems() as $item) {
            $items[] = $item;
        }

        return $items;
    }

    protected function getDocPagesItems(): array
    {
        $items = [];

        $docPageModel = new WebDocPage();
        foreach ($docPageModel->all([], [], 0, 0) as $docPage) {
            $items[] = $this->createItem($docPage->url('public'), strtotime($docPage->lastmod));
        }

        return $items;
    }

    protected function getPluginItems(): array
    {
        $items = [];

        $projectModel = new WebProject();
        foreach ($projectModel->all([], [], 0, 0) as $project) {
            if (!$project->plugin) {
                continue;
            }

            $items[] = $this->createItem($project->url('public'), strtotime($project->creationdate));
        }

        return $items;
    }

    protected function getTeamItems(): array
    {
        $items = [];

        $teamModel = new WebTeam();
        foreach ($teamModel->all([], [], 0, 0) as $team) {
            $items[] = $this->createItem($team->url('public'), strtotime($team->creationdate));
        }

        return $items;
    }
}