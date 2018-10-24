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

    protected function createMyIssuesSection()
    {
        $this->addListSection('ListIssue', 'Issue', 'issues', 'fas fa-question-circle', 'your');
        $this->sections['ListIssue']->template = 'Section/Issues.html.twig';
        $this->addSearchOptions('ListIssue', ['body', 'creationroute']);
        $this->addOrderOption('ListIssue', ['lastmod'], 'last-update', 2);
        $this->addOrderOption('ListIssue', ['creationdate'], 'date');

        $contactButton = [
            'action' => 'ContactForm',
            'color' => 'success',
            'icon' => 'fas fa-plus',
            'label' => 'new',
            'type' => 'link',
        ];
        $this->addButton('ListIssue', $contactButton);
    }

    protected function createTeamIssuesSection()
    {
        $this->addListSection('ListIssue-teams', 'Issue', 'issues', 'fas fa-question-circle', 'teams');
        $this->sections['ListIssue-teams']->template = 'Section/Issues.html.twig';
        $this->addSearchOptions('ListIssue-teams', ['body', 'creationroute']);
        $this->addOrderOption('ListIssue-teams', ['lastmod'], 'last-update', 2);
        $this->addOrderOption('ListIssue-teams', ['creationdate'], 'date');

        /// buttons
        $contactButton = [
            'action' => 'ContactForm',
            'color' => 'success',
            'icon' => 'fas fa-plus',
            'label' => 'new',
            'type' => 'link',
        ];
        $this->addButton('ListIssue-teams', $contactButton);

        /// filters
        $this->addFilterDatePicker('ListIssue-teams', 'fromdate', 'from-date', 'creationdate', '>=');
        $this->addFilterDatePicker('ListIssue-teams', 'untildate', 'until-date', 'creationdate', '<=');

        $teams = [];
        foreach ($this->getTeamsMemberData() as $member) {
            $team = $member->getTeam();
            $teams[] = ['code' => $team->idteam, 'description' => $team->name,];
        }
        $this->addFilterSelect('ListIssue-teams', 'idteam', 'team', 'idteam', $teams);

        $where = [new DataBaseWhere('closed', false)];
        $this->addFilterCheckbox('ListIssue-teams', 'closed', 'closed', 'closed', '=', true, $where);
    }

    protected function createTeamLogSection()
    {
        $this->addListSection('ListWebTeamLog', 'WebTeamLog', 'logs', 'fas fa-file-medical-alt', 'teams');
        $this->sections['ListWebTeamLog']->template = 'Section/TeamLogs.html.twig';
        $this->addSearchOptions('ListWebTeamLog', ['description']);
        $this->addOrderOption('ListWebTeamLog', ['time'], 'date', 2);

        /// filters
        $this->addFilterDatePicker('ListWebTeamLog', 'fromdate', 'from-date', 'creationdate', '>=');
        $this->addFilterDatePicker('ListWebTeamLog', 'untildate', 'until-date', 'creationdate', '<=');

        $teams = [];
        foreach ($this->getTeamsMemberData() as $member) {
            $team = $member->getTeam();
            $teams[] = ['code' => $team->idteam, 'description' => $team->name,];
        }
        $this->addFilterSelect('ListWebTeamLog', 'idteam', 'team', 'idteam', $teams);
    }

    /**
     * Load sections to the view.
     */
    protected function createSections()
    {
        if (null === $this->contact) {
            $this->setTemplate('Master/PortalTemplate');
            return;
        }

        $this->addHtmlSection('home', 'home');
        $this->createMyIssuesSection();
        $this->createTeamIssuesSection();
        $this->createTeamLogSection();
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
        }
    }
}
