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
use FacturaScripts\Plugins\Community\Model\Issue;
use FacturaScripts\Plugins\Community\Model\IssueComment;

/**
 * Description of EditIssue
 *
 * @author carlos
 */
class EditIssue extends SectionController
{

    /**
     *
     * @var Issue
     */
    protected $issue;

    public function getGravatar(string $email, int $size = 80): string
    {
        return "https://www.gravatar.com/avatar/" . md5(strtolower(trim($email))) . "?s=" . $size;
    }

    public function getIssue(): Issue
    {
        if (isset($this->issue)) {
            return $this->issue;
        }

        $issue = new Issue();
        $code = $this->request->get('code', '');
        if (!empty($code)) {
            $issue->loadFromCode($code);
            return $issue;
        }

        $uri = explode('/', $this->uri);
        $issue->loadFromCode(end($uri));
        return $issue;
    }

    public function obfuscateEmail(string $email): string
    {
        $aux = explode('@', $email);
        return (count($aux) == 2) ? $aux[0] . '_' . substr(md5($aux[1]), 0, 6) : '-';
    }

    protected function addNewComment()
    {
        $text = $this->request->get('newComment', '');
        if (empty($text)) {
            return;
        }

        $issue = $this->getIssue();
        $comment = new IssueComment();
        $comment->body = $text;
        $comment->idcontacto = $this->contact->idcontacto;
        $comment->idissue = $issue->idissue;

        if ($comment->save()) {
            $this->miniLog->notice($this->i18n->trans('record-updated-correctly'));
            $issue->save();
        } else {
            $this->miniLog->alert($this->i18n->trans('record-save-error'));
        }

        return true;
    }

    protected function createSections()
    {
        $this->addSection('issue', ['fixed' => true, 'template' => 'Section/Issue']);
        $this->addListSection('comments', 'IssueComment', 'Section/IssueComments', 'comments', 'fa-comments');
        $this->addOrderOption('comments', 'creationdate', 'date');
    }

    protected function execPreviousAction(string $action)
    {
        if ($action === 'new-comment') {
            return $this->addNewComment();
        }

        return parent::execPreviousAction($action);
    }

    protected function loadData(string $sectionName)
    {
        switch ($sectionName) {
            case 'comments':
                $issue = $this->getIssue();
                $where = [new DataBaseWhere('idissue', $issue->idissue)];
                $this->loadListSection($sectionName, $where);
                break;

            case 'issue':
                $this->loadIssue();
                break;
        }
    }

    protected function loadIssue()
    {
        $this->issue = $this->getIssue();
        if ($this->issue->exists()) {
            $this->title = 'Issue #' . $this->issue->idissue;
            $this->description = $this->issue->description();
            $this->issue->increaseVisitCount($this->request->getClientIp());
            return;
        }

        $this->miniLog->alert($this->i18n->trans('no-data'));
        $this->response->setStatusCode(Response::HTTP_NOT_FOUND);
        $this->webPage->noindex = true;
    }
}