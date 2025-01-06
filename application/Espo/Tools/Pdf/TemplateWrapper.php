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

namespace Espo\Tools\Pdf;

use Espo\Entities\Template as TemplateEntity;

class TemplateWrapper implements Template
{
    protected TemplateEntity $template;

    public function __construct(TemplateEntity $template)
    {
        $this->template = $template;
    }

    public function getFontFace(): ?string
    {
        return $this->template->get('fontFace');
    }

    public function getBottomMargin(): float
    {
        return $this->template->get('bottomMargin') ?? 0.0;
    }

    public function getTopMargin(): float
    {
        return $this->template->get('topMargin') ?? 0.0;
    }

    public function getLeftMargin(): float
    {
        return $this->template->get('leftMargin') ?? 0.0;
    }

    public function getRightMargin(): float
    {
        return $this->template->get('rightMargin') ?? 0.0;
    }

    public function hasFooter(): bool
    {
        return $this->template->get('printFooter') ?? false;
    }

    public function getFooter(): string
    {
        return $this->template->get('footer') ?? '';
    }

    public function getFooterPosition(): float
    {
        return $this->template->get('footerPosition') ?? 0.0;
    }

    public function hasHeader(): bool
    {
        return $this->template->get('printHeader') ?? false;
    }

    public function getHeader(): string
    {
        return $this->template->get('header') ?? '';
    }

    public function getHeaderPosition(): float
    {
        return $this->template->get('headerPosition') ?? 0.0;
    }

    public function getBody(): string
    {
        return $this->template->get('body') ?? '';
    }

    public function getPageOrientation(): string
    {
        return $this->template->get('pageOrientation') ?? 'Portrait';
    }

    public function getPageFormat(): string
    {
        return $this->template->get('pageFormat') ?? 'A4';
    }

    public function getPageWidth(): float
    {
        return $this->template->get('pageWidth') ?? 0.0;
    }

    public function getPageHeight(): float
    {
        return $this->template->get('pageHeight') ?? 0.0;
    }

    public function hasTitle(): bool
    {
        return $this->template->get('title') !== null;
    }

    public function getTitle(): string
    {
        return $this->template->get('title') ?? '';
    }

    public function getStyle(): ?string
    {
        return $this->template->get('style') ?? null;
    }
}
