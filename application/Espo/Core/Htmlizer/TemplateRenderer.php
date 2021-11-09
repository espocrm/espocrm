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

namespace Espo\Core\Htmlizer;

use Espo\Core\ApplicationState;
use Espo\ORM\Entity;
use Espo\Entities\User;

use stdClass;
use InvalidArgumentException;
use LogicException;

class TemplateRenderer
{
    private $data;

    private $user = null;

    private $entity = null;

    private $skipRelations = false;

    private $applyAcl = false;

    private $useUserTimezone = false;

    private $htmlizerFactory;

    private $applicationState;

    /**
     * @var ?string
     */
    private $template = null;

    public function __construct(HtmlizerFactory $htmlizerFactory, ApplicationState $applicationState)
    {
        $this->htmlizerFactory = $htmlizerFactory;
        $this->applicationState = $applicationState;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function setEntity(Entity $entity): self
    {
        $this->entity = $entity;

        return $this;
    }

    /**
     * @param stdClass|array $data Additional data.
     */
    public function setData($data): self
    {
        /** @var mixed $data */

        if (!is_array($data) && !$data instanceof stdClass) {
            throw new InvalidArgumentException();
        }

        if (is_object($data)) {
            $data = get_object_vars($data);
        }

        $this->data = $data;

        return $this;
    }

    public function setSkipRelations(bool $skipRelations = true): self
    {
        $this->skipRelations = $skipRelations;

        return $this;
    }

    public function setApplyAcl(bool $applyAcl = true): self
    {
        $this->applyAcl = $applyAcl;

        return $this;
    }

    public function setUseUserTimezone(bool $useUserTimezone = true): self
    {
        $this->useUserTimezone = $useUserTimezone;

        return $this;
    }

    public function setTemplate(string $template): self
    {
        $this->template = $template;

        return $this;
    }

    public function render(): string
    {
        if (!$this->template) {
            throw new LogicException("No template.");
        }

        return $this->renderTemplate($this->template);
    }

    public function renderTemplate(string $template): string
    {
        return $this->renderTemplateInternal($template, $this->createHtmlizer());
    }

    private function renderTemplateInternal(string $template, Htmlizer $htmlizer): string
    {
        return $htmlizer->render(
            $this->entity,
            $template,
            null,
            $this->data,
            $this->skipRelations
        );
    }

    /**
     * @return string[]
     */
    public function renderMultipleTemplates(string ...$templateList): array
    {
        $htmlizer = $this->createHtmlizer();

        $resultList = [];

        foreach ($templateList as $template) {
            $resultList[] = $this->renderTemplateInternal($template, $htmlizer);
        }

        return $resultList;
    }

    private function createHtmlizer(): Htmlizer
    {
        $user = $this->user ?? $this->applicationState->getUser();

        $params = new CreateForUserParams();

        $params->applyAcl = $this->applyAcl;
        $params->useUserTimezone = $this->useUserTimezone;

        return $this->htmlizerFactory->createForUser($user, $params);
    }
}
