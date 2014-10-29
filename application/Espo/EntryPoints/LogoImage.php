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
namespace Espo\EntryPoints;

use Espo\Core\Exceptions\NotFound;

class LogoImage extends
    Image
{

    public static $authRequired = false;

    public function run()
    {
        $this->imageSizes['small-logo'] = array(173, 38);
        $id = $this->getConfig()->get('companyLogoId');
        if (empty($id)) {
            throw new NotFound();
        }
        $size = null;
        if (!empty($_GET['size'])) {
            $size = $_GET['size'];
        }
        $this->show($id, $size);
    }
}

