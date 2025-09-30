<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Classes\TemplateHelpers;

use Espo\Core\Htmlizer\Helper;
use Espo\Core\Htmlizer\Helper\Data;
use Espo\Core\Htmlizer\Helper\Result;

class TableTag implements Helper
{
    public function render(Data $data): Result
    {
        $border = $data->getOption('border') ?? '0.5pt';
        $cellpadding = $data->getOption('cellpadding') ?? '2';
        $width = $data->getOption('width') ?? null;

        $attributesPart = "";

        if ($width) {
            $attributesPart .= " width=\"{$width}\"";
        }

        $function = $data->getFunction();

        $content = $function !== null ? $function() : '';

        $style = "border: {$border}; border-spacing: 0; border-collapse: collapse;";

        return Result::createSafeString(
            "<table style=\"{$style}\" border=\"{$border}\" cellpadding=\"{$cellpadding}\" {$attributesPart}>" .
            $content .
            "</table>"
        );
    }
}
