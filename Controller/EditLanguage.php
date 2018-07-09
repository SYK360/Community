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

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Plugins\Community\Model\Language;
use FacturaScripts\Plugins\Community\Model\Translation;
use FacturaScripts\Plugins\webportal\Lib\WebPortal\SectionController;

/**
 * Description of EditLanguage
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class EditLanguage extends SectionController
{

    /**
     *
     * @var Language
     */
    private $languageModel;

    /**
     *
     * @var array
     */
    private $mainTranslations = [];

    public function contactCanEdit(): bool
    {
        if ($this->user) {
            return true;
        }

        return false;
    }

    public function getLanguageModel(): Language
    {
        if (isset($this->languageModel)) {
            return $this->languageModel;
        }

        $language = new Language();
        $code = $this->request->get('code', '');
        if (!empty($code)) {
            $language->loadFromCode($code);
            return $language;
        }

        $uri = explode('/', $this->uri);
        $language->loadFromCode(end($uri));
        return $language;
    }

    public function getParentLanguages(): array
    {
        $current = $this->getLanguageModel();
        $languages = [];
        foreach ($current->all([], ['langcode' => 'ASC'], 0, 0) as $language) {
            if ($language->langcode === $current->langcode) {
                continue;
            }

            if ($language->parentcode) {
                continue;
            }

            $languages[] = $language;
        }

        return $languages;
    }

    /**
     * 
     * @param Language $language
     * @param string   $translationName
     */
    private function checkTranslation(&$language, $translationName): bool
    {
        $mainlangcode = AppSettings::get('community', 'mainlanguage');
        if ($language->langcode === $mainlangcode) {
            return true;
        }

        if (empty($this->mainTranslations)) {
            $this->mainTranslations = [];
            $translation = new Translation();
            $where = [new DataBaseWhere('langcode', $mainlangcode)];
            foreach ($translation->all($where, [], 0, 0) as $trans) {
                $this->mainTranslations[] = $trans->name;
            }
        }

        return in_array($translationName, $this->mainTranslations);
    }

    protected function createSections()
    {
        $this->addSection('language', ['fixed' => true, 'template' => 'Section/Language']);

        $this->addListSection('translations', 'Translation', 'Section/Translations', 'translations', 'fa-copy');
        $this->addSearchOptions('translations', ['name', 'description', 'translation']);
        $this->addOrderOption('translations', 'name', 'code', 1);
        $this->addOrderOption('translations', 'lastmod', 'last-update');

        if ($this->user) {
            $language = $this->getLanguageModel();
            $this->addButton('translations', $language->url() . '&action=import-trans', 'import', '');
        }
    }

    protected function deleteAction()
    {
        if (!$this->contactCanEdit()) {
            $this->miniLog->alert($this->i18n->trans('not-allowed-delete'));
        }

        $language = $this->getLanguageModel();
        if ($language->delete()) {
            $this->miniLog->info($this->i18n->trans('record-deleted-correctly'));
        }
    }

    protected function editAction()
    {
        if (!$this->contactCanEdit()) {
            $this->miniLog->alert($this->i18n->trans('not-allowed-modify'));
        }

        $language = $this->getLanguageModel();
        $language->description = $this->request->request->get('description', '');
        $language->parentcode = ('' === $this->request->request->get('parentcode', '')) ? null : $this->request->request->get('parentcode', '');
        if ($language->save()) {
            $this->miniLog->info($this->i18n->trans('record-updated-correctly'));
        } else {
            $this->miniLog->alert($this->i18n->trans('record-save-error'));
        }
    }

    protected function execPreviousAction(string $action)
    {
        switch ($action) {
            case 'delete':
                $this->deleteAction();
                return true;

            case 'edit':
                $this->editAction();
                return true;

            case 'import-trans':
                $this->importTranslationsAction();
                return true;

            default:
                return parent::execPreviousAction($action);
        }
    }

    protected function importTranslationsAction()
    {
        if (!$this->user) {
            $this->miniLog->alert($this->i18n->trans('not-allowed-modify'));
        }

        $language = $this->getLanguageModel();
        if ($language->parentcode) {
            $this->miniLog->alert("You can't import a language with parent.");
        }

        // import translations from file
        $newTranslations = [];
        $idproject = AppSettings::get('community', 'idproject');
        $json = json_decode(file_get_contents(FS_FOLDER . '/Core/Translation/' . $language->langcode . '.json'), true);
        foreach ($json as $key => $value) {
            $translation = new Translation();
            $translation->idproject = $idproject;
            $translation->langcode = $language->langcode;
            $translation->name = $key;
            $translation->description = $translation->translation = $value;

            /// is this string in the main language?
            if (!$this->checkTranslation($language, $key)) {
                continue;
            }

            if ($translation->save()) {
                $newTranslations[] = $key;
            }
        }

        // generate missing translations
        $mainlangcode = AppSettings::get('community', 'mainlanguage');
        foreach ($this->mainTranslations as $mainKey) {
            if (in_array($mainKey, $newTranslations)) {
                continue;
            }

            // we need main translation
            $mainTranslation = new Translation();
            $where = [
                new DataBaseWhere('langcode', $mainlangcode),
                new DataBaseWhere('name', $mainKey)
            ];
            $mainTranslation->loadFromCode('', $where);

            $newTranslation = new Translation();
            $newTranslation->description = $mainTranslation->description;
            $newTranslation->idproject = $idproject;
            $newTranslation->langcode = $language->langcode;
            $newTranslation->lastmod = $mainTranslation->lastmod;
            $newTranslation->name = $mainTranslation->name;
            $newTranslation->translation = $mainTranslation->translation;
            $newTranslation->save();
        }

        $language->updateStats();
        $language->save();
    }

    protected function loadData(string $sectionName)
    {
        switch ($sectionName) {
            case 'translations':
                $language = $this->getLanguageModel();
                $where = [new DataBaseWhere('langcode', $language->langcode)];
                $this->loadListSection($sectionName, $where);
                break;
        }
    }
}