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
namespace FacturaScripts\Plugins\Community;

use FacturaScripts\Core\Base\CronClass;
use FacturaScripts\Plugins\Community\Lib\WebTeamReport;
use FacturaScripts\Plugins\Community\Model\Language;

/**
 * Define the taks of Community's crons.
 * 
 * @author Cristo M. Estévez Hernández  <cristom.estevez@gmail.com>
 * @author Carlos Garcia Gomez          <carlos@facturascripts.com>
 */
class Cron extends CronClass
{

    public function run()
    {
        if ($this->isTimeForJob('send-mail-to-team-members', '1 week')) {
            $teamReport = new WebTeamReport();
            $teamReport->sendMail('1 week');
            $this->jobDone('send-mail-to-team-members');
        }

        if ($this->isTimeForJob('fix-translations', '1 week')) {
            $this->fixTranslations();
            $this->jobDone('fix-translations');
        }
    }

    protected function fixTranslations()
    {
        $languageModel = new Language();
        foreach ($languageModel->all() as $lang) {
            $lang->updateStats();
            $lang->save();
        }
    }
}
