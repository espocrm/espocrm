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
use \Espo\Core\Exceptions\Error;

class Avatar extends Image
{
    public static $authRequired = true;

    private $colorList = [
        [111,168,214],
        [237,197,85],
        [212,114,155],
        '#8093BD',
        [124,196,164],
        [138,124,194],
        [222,102,102],
        '#ABE3A1',
        '#E8AF64',
    ];

    protected function getColor($hash)
    {
        $length = strlen($hash);
        $sum = 0;
        for ($i = 0; $i < $length; $i++) {
            $sum += ord($hash[$i]);
        }
        $x = intval($sum % 128) + 1;

        $index = intval($x * count($this->colorList) / 128);
        return $this->colorList[$index];
    }

    public function run()
    {
        if (empty($_GET['id'])) {
            throw new BadRequest();
        }

        $userId = $_GET['id'];


        $user = $this->getEntityManager()->getEntity('User', $userId);
        if (!$user) {
            throw new NotFound();
        }

        if (isset($_GET['attachmentId'])) {
            $id = $_GET['attachmentId'];
            if ($id == 'false') {
                $id = false;
            }
        } else {
            $id = $user->get('avatarId');
        }

        $size = null;
        if (!empty($_GET['size'])) {
            $size = $_GET['size'];
        }

        if (!empty($id)) {
            $this->show($id, $size);
        } else {
            $identicon = new \Identicon\Identicon();
            if (empty($size)) {
                $size = 'small';
            }
            if (!empty($this->imageSizes[$size])) {
                $width = $this->imageSizes[$size][0];

                header('Cache-Control: max-age=360000, must-revalidate');
                header('Content-Type: image/png');

                ob_clean();
                flush();
                $identicon->displayImage($userId, $width, $this->getColor($userId));
                exit;
            }
        }
    }

}

