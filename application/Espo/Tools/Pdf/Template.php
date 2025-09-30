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

namespace Espo\Tools\Pdf;

interface Template
{
    public const PAGE_FORMAT_CUSTOM = 'Custom';

    public const PAGE_ORIENTATION_PORTRAIT = 'Portrait';
    public const PAGE_ORIENTATION_LANDSCAPE = 'Landscape';

    public function getFontFace(): ?string;

    public function getBottomMargin(): float;

    public function getTopMargin(): float;

    public function getLeftMargin(): float;

    public function getRightMargin(): float;

    public function hasFooter(): bool;

    public function getFooter(): string;

    public function getFooterPosition(): float;

    public function hasHeader(): bool;

    public function getHeader(): string;

    public function getHeaderPosition(): float;

    public function getBody(): string;

    public function getPageOrientation(): string;

    public function getPageFormat(): string;

    public function getPageWidth(): float;

    public function getPageHeight(): float;

    public function hasTitle(): bool;

    public function getTitle(): string;

    public function getStyle(): ?string;
}
