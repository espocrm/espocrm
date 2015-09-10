<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Espo\Core\Mail\Mail\Header;

use \Zend\Mail\Header;
use Zend\Mime\Mime;

class XQueueItemId implements Header\HeaderInterface
{
    protected $fieldName = 'X-QueueItemId';

    protected $id = null;

    public static function fromString($headerLine)
    {
        list($name, $value) = Header\GenericHeader::splitHeaderLine($headerLine);
        $value = Header\HeaderWrap::mimeDecodeValue($value);

        if (strtolower($name) !== 'x-queue-item-id') {
            throw new Header\Exception\InvalidArgumentException('Invalid header line for Message-ID string');
        }

        $header = new static();
        $header->setId($value);

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
