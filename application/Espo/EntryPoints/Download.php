<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace Espo\EntryPoints;

use \Espo\Core\Exceptions\NotFound;
use \Espo\Core\Exceptions\Forbidden;
use \Espo\Core\Exceptions\BadRequest;

class Download extends \Espo\Core\EntryPoints\Base
{
    public static $authRequired = true;

    protected $fileTypesToShowInline = array(
        'application/pdf',
        'application/vnd.ms-word',
        'application/vnd.ms-excel',
        'application/vnd.oasis.opendocument.text',
        'application/vnd.oasis.opendocument.spreadsheet',
        'text/plain',
        'application/msword',
        'application/msexcel'
    );

    public function run()
    {
        if (empty($_GET['id'])) {
            throw new BadRequest();
        }
        $id = $_GET['id'];

        $attachment = $this->getEntityManager()->getEntity('Attachment', $id);

        if (!$attachment) {
            throw new NotFound();
        }

        if ($attachment->get('parentId') && $attachment->get('parentType')) {
            $parent = $this->getEntityManager()->getEntity($attachment->get('parentType'), $attachment->get('parentId'));
            if (!$this->getAcl()->check($parent)) {
                throw new Forbidden();
            }
        }

        $fileName = "data/upload/{$attachment->id}";

        if (!file_exists($fileName)) {
            throw new NotFound();
        }

        $type = $attachment->get('type');

        $disposition = 'attachment';
        if (in_array($type, $this->fileTypesToShowInline)) {
            $disposition = 'inline';
        }

        header('Content-Description: File Transfer');
        if ($type) {
            header('Content-Type: ' . $type);
        }
        header('Content-Disposition: ' . $disposition . '; filename=' . $attachment->get('name'));
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($fileName));
        ob_clean();
        flush();
        readfile($fileName);
        exit;
    }
}

