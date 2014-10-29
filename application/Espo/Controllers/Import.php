<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014  Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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
 ************************************************************************/
namespace Espo\Controllers;

use Espo\Core\Controllers\Base;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\ORM\EntityManager;
use Espo\Core\Utils as Utils;
use Espo\Entities\Attachment;

class Import extends
    Base
{

    public function actionUploadFile($params, $data)
    {
        /**
         * @var Attachment $attachment
         */
        $contents = $data;
        $attachment = $this->getEntityManager()->getEntity('Attachment');
        $attachment->set('type', 'text/csv');
        $attachment->set('role', 'Import File');
        $this->getEntityManager()->saveEntity($attachment);
        $this->getFileManager()->putContents('data/upload/' . $attachment->id, $contents);
        return array(
            'attachmentId' => $attachment->id
        );
    }

    /**
     * @return EntityManager

     */
    protected function getEntityManager()
    {
        return $this->getContainer()->get('entityManager');
    }

    /**
     * @return  Utils\File\Manager

     */
    protected function getFileManager()
    {
        return $this->getContainer()->get('fileManager');
    }

    public function actionRevert($params, $data)
    {
        /**
         * @var \Espo\Services\Import $importService
         */
        $importService = $this->getService('Import');
        return $importService->revert($data['entityType'], $data['idsToRemove']);
    }

    public function actionCreate($params, $data)
    {
        /**
         * @var \Espo\Services\Import $importService
         */
        $importParams = array(
            'headerRow' => $data['headerRow'],
            'fieldDelimiter' => $data['fieldDelimiter'],
            'textQualifier' => $data['textQualifier'],
            'dateFormat' => $data['dateFormat'],
            'timeFormat' => $data['timeFormat'],
            'personNameFormat' => $data['personNameFormat'],
            'decimalMark' => $data['decimalMark'],
            'currency' => $data['currency'],
            'defaultValues' => $data['defaultValues'],
            'action' => $data['action'],
        );
        $attachmentId = $data['attachmentId'];
        if (!$this->getAcl()->check($data['entityType'], 'edit')) {
            throw new Forbidden();
        }
        $importService = $this->getService('Import');
        return $importService->import($data['entityType'], $data['fields'], $attachmentId, $importParams);
    }
}

