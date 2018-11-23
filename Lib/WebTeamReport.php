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
namespace FacturaScripts\Plugins\Community\Lib;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Model\Contacto;
use FacturaScripts\Dinamic\Lib\EmailTools;
use FacturaScripts\Plugins\Community\Model\WebTeam;
use FacturaScripts\Plugins\Community\Model\WebTeamLog;
use FacturaScripts\Plugins\Community\Model\WebTeamMember;

/**
 * Description of WebTeamReport
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class WebTeamReport
{

    /**
     * Quantity of contact to simulate pagination to send email.
     */
    const MAX_EMAIL_BCC = 50;

    /**
     *
     * @var EmailTools
     */
    protected $emailTools;

    public function __construct()
    {
        $this->emailTools = new EmailTools();
    }

    /**
     * 
     * @param string $period
     */
    public function sendMail($period)
    {
        $teamModel = new WebTeam();
        $memberTeam = new WebTeamMember();

        foreach ($teamModel->all([], [], 0, 0) as $team) {
            $members = $memberTeam->all([new DataBaseWhere('idteam', $team->idteam)], [], 0, 0);
            if (empty($members)) {
                continue;
            }

            $logs = $this->getTeamLogs($team, $period);
            if (empty($logs)) {
                continue;
            }

            /// we send an email to every MAX_EMAIL_BCC people
            $iterator = 0;
            $mail = $this->loadMail($team->name, $logs);
            foreach ($members as $member) {
                if (self::MAX_EMAIL_BCC == $iterator) {
                    $this->emailTools->send($mail);
                    $mail = $this->loadMail($team->name, $logs);
                    $iterator = 0;
                } else {
                    $iterator++;
                }

                $mail->addBCC($member->getContact()->email);
            }

            if ($iterator <= self::MAX_EMAIL_BCC) {
                $this->emailTools->send($mail);
            }
        }
    }

    /**
     * Build the body of the tabla for the email.
     * 
     * @param array  $logs
     * @param string $title
     *
     * @return string
     */
    protected function buildTableBody(array $logs, string $title): string
    {
        $content = '<ul>';
        foreach ($logs as $log) {
            $content .= '<li>';
            if (empty($log->link)) {
                $content .= $log->description . ' - ' . $log->time;
            } else {
                $content .= '<a href="' . $log->link . '">' . $log->description . '</a> - ' . $log->time;
            }

            $contact = new Contacto();
            if ($contact->loadFromCode($log->idcontacto)) {
                $content .= ' - ' . $contact->nombre;
            }

            $content .= '</li>';
        }
        $content .= '</ul>';

        $params = [
            'body' => $content,
            'company' => $title,
            'footer' => $title,
            'title' => $title,
        ];
        return $this->emailTools->getTemplateHtml($params);
    }

    /**
     * 
     * @param WebTeam $team
     * @param string  $period
     *
     * @return array
     */
    protected function getTeamLogs(WebTeam $team, string $period): array
    {
        $teamLogs = new WebTeamLog();
        $where = [
            new DataBaseWhere('idteam', $team->idteam),
            new DataBaseWhere('time', date('d-m-Y H:i:s', strtotime('-' . $period)), '>')
        ];

        return $teamLogs->all($where, [], 0, 0);
    }

    /**
     * Create and load new object Mail.
     *
     * @param string $teamName
     * @param array  $logs Array of WebTeamLog objects.
     */
    protected function loadMail(string $teamName, array $logs)
    {
        $mail = $this->emailTools->newMail();
        $mail->Subject = self::$i18n->trans('weekly-report', ['%teamName%' => $teamName]);
        $mail->msgHTML($this->buildTableBody($logs, $mail->Subject));

        return $mail;
    }
}