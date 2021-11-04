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
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Core\Mail\Mail\Header;

use Laminas\Mail\Header;

class XQueueItemId implements Header\HeaderInterface
{
    protected $fieldName = 'X-Queue-Item-Id';

    protected $id = null;

    /**
     * @return self
     */
    public static function fromString($headerLine)
    {
        list($name, $value) = Header\GenericHeader::splitHeaderLine($headerLine);

        $valueDecoded = Header\HeaderWrap::mimeDecodeValue($value);

        if (strtolower($name) !== 'x-queue-item-id') {
            throw new Header\Exception\InvalidArgumentException('Invalid header line for x-queue-item-id string');
        }

        $header = new self();

        $header->setId($valueDecoded);

        return $header;
    }

    public function getFieldName()
    {
        return $this->fieldName;
    }

    public function setFieldName($value)
    {
    }

    public function setEncoding($encoding)
    {
        return $this;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getEncoding()
    {
        return 'ASCII';
    }

    public function toString()
    {
        return $this->fieldName . ': ' . $this->getFieldValue();
    }

    public function getFieldValue($format = Header\HeaderInterface::FORMAT_RAW)
    {
        return $this->id;
    }
}
