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
use FacturaScripts\Plugins\Community\Model\WebTeamMember;
use FacturaScripts\Plugins\webportal\Lib\WebPortal\SectionController;

/**
 * Description of PortalHome
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class CommunityHome extends SectionController
{

    /**
     * Execute common code between private and public core.
     */
    protected function commonCore()
    {
        parent::commonCore();

        /// hide sectionController template if all sections are empty
        if ($this->getTemplate() == 'Master/SectionController.html.twig') {
            $this->hideSections();
        }
    }

    protected function createMyIssuesSection($name = 'ListIssue')
    {
        $this->addListSection($name, 'Issue', 'issues', 'fas fa-question-circle', 'your');
        $this->sections[$name]->template = 'Section/Issues.html.twig';
        $this->addSearchOptions($name, ['body', 'creationroute']);
        $this->addOrderOption($name, ['lastmod'], 'last-update', 2);
        $this->addOrderOption($name, ['creationdate'], 'date');

        /// buttons
        $contactButton = [
            'action' => 'ContactForm',
            'color' => 'success',
            'icon' => 'fas fa-plus',
            'label' => 'new',
            'type' => 'link',
        ];
        $this->addButton($name, $contactButton);
    }

    protected function createPublicationsSection($name, $team = '')
    {
        $this->addListSection($name, 'Publication', 'publications', 'fas fa-newspaper', $team);
        $this->addSearchOptions($name, ['title', 'body']);
        $this->addOrderOption($name, ['creationdate'], 'date', 2);
        $this->addOrderOption($name, ['visitcount'], 'visit-counter');
    }

    protected function createTeamIssuesSection($name = 'ListIssue-teams')
    {
        $this->addListSection($name, 'Issue', 'issues', 'fas fa-question-circle', 'teams');
        $this->sections[$name]->template = 'Section/Issues.html.twig';
        $this->addSearchOptions($name, ['body', 'creationroute']);
        $this->addOrderOption($name, ['lastmod'], 'last-update', 2);
        $this->addOrderOption($name, ['creationdate'], 'date');

        /// buttons
        $contactButton = [
            'action' => 'ContactForm',
            'color' => 'success',
            'icon' => 'fas fa-plus',
            'label' => 'new',
            'type' => 'link',
        ];
        $this->addButton($name, $contactButton);

        /// filters
        $this->addFilterDatePicker($name, 'fromdate', 'from-date', 'creationdate', '>=');
        $this->addFilterDatePicker($name, 'untildate', 'until-date', 'creationdate', '<=');

        $teams = [
            $teams[] = ['code' => '', 'description' => '------']
        ];
        foreach ($this->getTeamsMemberData() as $member) {
            $team = $member->getTeam();
            $teams[] = ['code' => $team->idteam, 'description' => $team->name];
        }
        $this->addFilterSelect($name, 'idteam', 'team', 'idteam', $teams);

        $where = [new DataBaseWhere('closed', false)];
        $this->addFilterCheckbox($name, 'closed', 'closed', 'closed', '=', true, $where);
    }

    protected function createTeamLogSection($name = 'ListWebTeamLog')
    {
        $this->addListSection($name, 'WebTeamLog', 'logs', 'fas fa-file-medical-alt', 'teams');
        $this->sections[$name]->template = 'Section/TeamLogs.html.twig';
        $this->addSearchOptions($name, ['description']);
        $this->addOrderOption($name, ['time'], 'date', 2);

        /// filters
        $this->addFilterDatePicker($name, 'fromdate', 'from-date', 'creationdate', '>=');
        $this->addFilterDatePicker($name, 'untildate', 'until-date', 'creationdate', '<=');

        $teams = [];
        foreach ($this->getTeamsMemberData() as $member) {
            $team = $member->getTeam();
            $teams[] = ['code' => $team->idteam, 'description' => $team->name,];
        }
        $this->addFilterSelect($name, 'idteam', 'team', 'idteam', $teams);
    }

    /**
     * Load sections to the view.
     */
    protected function createSections()
    {
        if (empty($this->contact)) {
            $this->setTemplate('Master/PortalTemplate');
            return;
        }

        $this->addHtmlSection('home', 'home');
        $this->createPublicationsSection('ListPublication-proj');
        $this->createMyIssuesSection();

        if (count($this->getTeamsMemberData()) > 0) {
            $this->createPublicationsSection('ListPublication', 'teams');
            $this->createTeamIssuesSection();
            $this->createTeamLogSection();
        }
    }

    /**
     * Return the list of team member relations of this contact.
     *
     * @return WebTeamMember[]
     */
    protected function getTeamsMemberData(): array
    {
        $teamMember = new WebTeamMember();
        $where = [new DataBaseWhere('idcontacto', $this->contact->idcontacto)];
        return $teamMember->all($where, [], 0, 0);
    }

    /**
     * Return when was do it the last modification.
     *
     * @param object $section
     *
     * @return int
     */
    protected function getSectionLastmod(&$section): int
    {
        $lastMod = 0;

        if (isset($section->cursor[0]->creationdate) && strtotime($section->cursor[0]->creationdate) > $lastMod) {
            $lastMod = strtotime($section->cursor[0]->creationdate);
        }

        if (isset($section->cursor[0]->lastmod) && strtotime($section->cursor[0]->lastmod) > $lastMod) {
            $lastMod = strtotime($section->cursor[0]->lastmod);
        }
        
        if (isset($section->cursor[0]->actualizado) && $section->cursor[0]->actualizado > $lastMod) {
            $lastMod = $section->cursor[0]->actualizado;
        }

        return $lastMod;
    }

    /**
     * Hide unneeded sections.
     */
    protected function hideSections()
    {
        if (!empty($this->request->request->all())) {
            return;
        }

        $empty = true;
        $lastMod = 0;
        foreach ($this->sections as $name => $section) {
            if ($section->count > 0) {
                $empty = false;
            } elseif ($name !== 'home') {
                $this->sections[$name]->settings['active'] = false;
            }

            if ($this->getSectionLastmod($section) > $lastMod) {
                $lastMod = $this->getSectionLastmod($section);
            }
        }

        if ($empty) {
            $this->setTemplate('Master/PortalTemplate');
            return;
        }

        foreach ($this->sections as $name => $section) {
            $this->active = $name;
            $this->current = $name;
            if ($this->getSectionLastmod($section) >= $lastMod) {
                break;
            }
        }
    }

    /**
     * Load section data procedure
     *
     * @param string $sectionName
     */
    protected function loadData(string $sectionName)
    {
        $where = [];
        switch ($sectionName) {
            case 'ListIssue-teams':
                $where[] = new DataBaseWhere('idcontacto', $this->contact->idcontacto, '!=');
            /// no break
            case 'ListPublication':
            case 'ListWebTeamLog':
                $idTeams = [];
                foreach ($this->getTeamsMemberData() as $member) {
                    if ($member->accepted) {
                        $idTeams[] = $member->idteam;
                    }
                }
                $where[] = new DataBaseWhere('idteam', implode(',', $idTeams), 'IN');
                $this->sections[$sectionName]->loadData('', $where);
                break;

            case 'ListIssue':
                $where[] = new DataBaseWhere('idcontacto', $this->contact->idcontacto);
                $this->sections[$sectionName]->loadData('', $where);
                break;

            case 'ListPublication-proj':
                $where[] = new DataBaseWhere('idteam', null, 'IS');
                $this->sections[$sectionName]->loadData('', $where);
                break;
        }
    }
}
