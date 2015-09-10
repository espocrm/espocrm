<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Espo\Core\Mail\Mail\Header;

use Zend\Mime\Mime;

class XQueueItemId extends \Zend\Mail\Header\GenericHeader
{
    protected $fieldName = 'X-QueueItemId';

    protected $id = null;

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

    public function getFieldValue($format = \Zend\Mail\Header\HeaderInterface::FORMAT_RAW)
    {
        return $this->id;
    }
}
