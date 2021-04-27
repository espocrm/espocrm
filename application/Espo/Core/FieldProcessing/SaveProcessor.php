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

namespace Espo\Core\FieldProcessing;

use Espo\Core\ORM\Entity;

use Espo\Core\{
    FieldProcessing\EmailAddress\Saver as EmailAddressSaver,
    FieldProcessing\PhoneNumber\Saver as PhoneNumberSaver,
    FieldProcessing\Relation\Saver as RelationSaver,
    FieldProcessing\File\Saver as FileSaver,
    FieldProcessing\MultiEnum\Saver as MultiEnumSaver,
    FieldProcessing\Wysiwyg\Saver as WysiwygSaver,
};

/**
 * Processes saving special fields.
 */
class SaveProcessor
{
    private $emailAddressSaver;

    private $phoneNumberSaver;

    private $relationSaver;

    private $fileSaver;

    private $multiEnumSaver;

    private $wysiwygSaver;

    public function __construct(
        EmailAddressSaver $emailAddressSaver,
        PhoneNumberSaver $phoneNumberSaver,
        RelationSaver $relationSaver,
        FileSaver $fileSaver,
        MultiEnumSaver $multiEnumSaver,
        WysiwygSaver $wysiwygSaver
    ) {
        $this->emailAddressSaver = $emailAddressSaver;
        $this->phoneNumberSaver = $phoneNumberSaver;
        $this->relationSaver = $relationSaver;
        $this->fileSaver = $fileSaver;
        $this->multiEnumSaver = $multiEnumSaver;
        $this->wysiwygSaver = $wysiwygSaver;
    }

    public function process(Entity $entity, array $options): void
    {
        $this->emailAddressSaver->process($entity, $options);
        $this->phoneNumberSaver->process($entity, $options);
        $this->relationSaver->process($entity, $options);
        $this->fileSaver->process($entity, $options);
        $this->multiEnumSaver->process($entity, $options);
        $this->wysiwygSaver->process($entity, $options);
    }
}
