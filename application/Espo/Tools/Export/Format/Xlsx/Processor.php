<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Tools\Export\Format\Xlsx;

use Espo\Core\Exceptions\Error;
use Espo\Tools\Export\Collection;
use Espo\Tools\Export\Processor as ProcessorInterface;
use Espo\Tools\Export\Processor\Params;

use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;
use PhpOffice\PhpSpreadsheet\Writer\Exception as WriterException;
use Psr\Http\Message\StreamInterface;

class Processor implements ProcessorInterface
{
    private const PARAM_LITE = 'lite';

    public function __construct(
        private PhpSpreadsheetProcessor $phpSpreadsheetProcessor,
        private OpenSpoutProcessor $openSpoutProcessor,
    ) {}

    /**
     * @throws Error
     */
    public function process(Params $params, Collection $collection): StreamInterface
    {
        return $params->getParam(self::PARAM_LITE) ?
            $this->processOpenSpout($params, $collection) :
            $this->processPhpSpreadsheet($params, $collection);
    }

    /**
     * @throws Error
     */
    private function processPhpSpreadsheet(Params $params, Collection $collection): StreamInterface
    {
        try {
            return $this->phpSpreadsheetProcessor->process($params, $collection);
        } catch (SpreadsheetException|WriterException $e) {
            throw new Error($e->getMessage());
        }
    }

    /**
     * @throws Error
     */
    private function processOpenSpout(Params $params, Collection $collection): StreamInterface
    {
        try {
            return $this->openSpoutProcessor->process($params, $collection);
        } catch (\Throwable $e) {
            throw new Error($e->getMessage());
        }
    }
}
