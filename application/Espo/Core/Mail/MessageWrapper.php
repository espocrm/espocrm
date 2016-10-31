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
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Core\Mail;

use \Espo\Entities\Email;

class MessageWrapper
{
    private $storage;

    private $id;

    private $rawHeader = null;

    private $rawContent = null;

    private $zendMessage = null;

    protected $zendMessageClass = '\Zend\Mail\Storage\Message';

    protected $fullRawContent = null;

    public function __construct($storage = null, $id = null, $parser = null)
    {
        if ($storage) {
            $data = $storage->getHeaderAndFlags($id);
            $this->rawHeader = $data['header'];
            $this->flagList = $data['flags'];
        }

        $this->id = $id;
        $this->storage = $storage;
        $this->parser = $parser;
    }

    public function setFullRawContent($content)
    {
        $this->fullRawContent = $content;
    }

    public function getRawHeader()
    {
        return $this->rawHeader;
    }

    public function getParser()
    {
        return $this->parser;
    }

    public function checkAttribute($attribute)
    {
        return $this->getParser()->checkMessageAttribute($this, $attribute);
    }

    public function getAttribute($attribute)
    {
        return $this->getParser()->getMessageAttribute($this, $attribute);
    }

    public function getRawContent()
    {
        if (is_null($this->rawContent)) {
            $this->rawContent = $this->storage->getRawContent($this->id);
        }

        return $this->rawContent;
    }

    public function getFullRawContent()
    {
        if ($this->fullRawContent) {
            return $this->fullRawContent;
        }

        return $this->getRawHeader() . "\n" . $this->getRawContent();
    }

    public function getZendMessage()
    {
        if (!$this->zendMessage) {
            if ($this->setFullRawContent) {
                $this->message = new $this->zendMessageClass([
                    'handler' => $this->storage,
                    'raw' => $this->fullRawContent,
                    'flags' => $this->flagList
                ]);
            } else {
                $this->message = new $this->zendMessageClass([
                    'handler' => $this->storage,
                    'id' => $this->id,
                    'headers' => $this->rawHeader,
                    'flags' => $this->flagList
                ]);
            }
        }

        return $this->message;
    }

    public function getFlags()
    {
        return $this->flagList;
    }

    public function isFetched()
    {
        return !!$this->rawHeader;
    }
}
