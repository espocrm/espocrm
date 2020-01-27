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

namespace Espo\Core\AppParams;

class TemplateEntityTypeList extends \Espo\Core\Injectable
{
    protected function init()
    {
        $this->addDependency('acl');
        $this->addDependency('selectManagerFactory');
        $this->addDependency('entityManager');
    }

    public function get()
    {
        if (!$this->getInjection('acl')->checkScope('Template')) {
            return [];
        }

        $list = [];

        $selectManager = $this->getInjection('selectManagerFactory')->create('Template');

        $selectParams = $selectManager->getEmptySelectParams();
        $selectManager->applyAccess($selectParams);

        $templateList = $this->getInjection('entityManager')->getRepository('Template')
            ->select(['entityType'])
            ->groupBy(['entityType'])
            ->find($selectParams);

        foreach ($templateList as $template) {
            $list[] = $template->get('entityType');
        }

        return $list;
    }
}
