<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2020 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: https://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Core\Console\Commands;

class Import extends Base
{
    public function run($options, $flagList, $argumentList)
    {
        $id = $options['id'] ?? null;
        $filePath = $options['file'] ?? null;
        $paramsId = $options['paramsId'] ?? null;

        $service = $this->getContainer()->get('serviceFactory')->create('Import');

        $forceResume = in_array('r', $flagList);
        $revert = in_array('u', $flagList);

        if (!$id && $filePath) {
            if (!$paramsId) {
                $this->out("You need to specify --params-id option.\n");
                return;
            }

            if (!file_exists($filePath)) {
                $this->out("File not found.\n");
                return;
            }

            $contents = file_get_contents($filePath);

            try {
                $results = $service->importFileWithParamsId($contents, $paramsId);

                $resultId = $results['id'];
                $countCreated = $results['countCreated'];
                $countUpdated = $results['countUpdated'];
            } catch (\Throwable $e) {
                $this->out("Error occured: ".$e->getMessage()."\n");
                return;
            }

            $this->out("Finished. Import ID: {$resultId}. Created: {$countCreated}. Updated: {$countUpdated}.\n");
            return;
        }

        if ($id && $revert) {
            $this->out("Reverting import...\n");

            try {
                $results = $service->revert($id);
            } catch (\Throwable $e) {
                $this->out("Error occured: ".$e->getMessage()."\n");
                return;
            }

            $this->out("Finished.\n");
            return;
        }

        if ($id) {
            $this->out("Running import, this may take a while...\n");

            try {
                $results = $service->importById($id, true, $forceResume);
            } catch (\Throwable $e) {
                $this->out("Error occured: ".$e->getMessage()."\n");
                return;
            }

            $countCreated = $results['countCreated'];
            $countUpdated = $results['countUpdated'];

            $this->out("Finished. Created: {$countCreated}. Updated: {$countUpdated}.\n");
            return;
        }

        $this->out("Not enough params passed.\n");
        return;
    }

    protected function out($string)
    {
        fwrite(\STDOUT, $string);
    }
}
