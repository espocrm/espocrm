<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Modules\Crm\Controllers;

use Espo\Core\Exceptions\BadRequest;

use Espo\Core\Api\Request;

use Espo\Modules\Crm\Services\KnowledgeBaseArticle as Service;

class KnowledgeBaseArticle extends \Espo\Core\Controllers\Record
{
    public function postActionGetCopiedAttachments(Request $request)
    {
        $data = $request->getParsedBody();

        if (empty($data->id)) {
            throw new BadRequest();
        }

        $id = $data->id;

        return $this->getArticleService()->getCopiedAttachments($id);
    }

    public function postActionMoveToTop(Request $request)
    {
        $data = $request->getParsedBody();

        if (empty($data->id)) {
            throw new BadRequest();
        }

        $where = null;

        if (!empty($data->where)) {
            $where = $data->where;
            $where = json_decode(json_encode($where), true);
        }

        $this->getArticleService()->moveToTop($data->id, $where);

        return true;
    }

    public function postActionMoveUp(Request $request)
    {
        $data = $request->getParsedBody();

        if (empty($data->id)) {
            throw new BadRequest();
        }

        $where = null;

        if (!empty($data->where)) {
            $where = $data->where;
            $where = json_decode(json_encode($where), true);
        }

        $this->getArticleService()->moveUp($data->id, $where);

        return true;
    }

    public function postActionMoveDown(Request $request)
    {
        $data = $request->getParsedBody();

        if (empty($data->id)) {
            throw new BadRequest();
        }

        $where = null;

        if (!empty($data->where)) {
            $where = $data->where;
            $where = json_decode(json_encode($where), true);
        }

        $this->getArticleService()->moveDown($data->id, $where);

        return true;
    }

    public function postActionMoveToBottom(Request $request)
    {
        $data = $request->getParsedBody();

        if (empty($data->id)) {
            throw new BadRequest();
        }

        $where = null;

        if (!empty($data->where)) {
            $where = $data->where;
            $where = json_decode(json_encode($where), true);
        }

        $this->getArticleService()->moveToBottom($data->id, $where);

        return true;
    }

    private function getArticleService(): Service
    {
        /** @var Service */
        return $this->getRecordService();
    }
}
