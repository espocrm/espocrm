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

namespace Espo\Tools\Pdf\Dompdf;

use Dompdf\Dompdf;
use Dompdf\Options;
use Espo\Core\Utils\Config;
use Espo\Tools\Pdf\Template;

class DompdfInitializer
{
    private string $defaultFontFace = 'DejaVu Sans';

    private const PT = 2.83465;

    public function __construct(
        private Config $config
    ) {}

    public function initialize(Template $template): Dompdf
    {
        $options = new Options();

        $options->setDefaultFont($this->getFontFace($template));

        $pdf = new Dompdf($options);

        $size = $template->getPageFormat() === Template::PAGE_FORMAT_CUSTOM ?
            [0.0, 0.0, $template->getPageWidth() * self::PT, $template->getPageHeight() * self::PT] :
            $template->getPageFormat();

        $orientation = $template->getPageOrientation() === Template::PAGE_ORIENTATION_PORTRAIT ?
            'portrait' :
            'landscape';

        $pdf->setPaper($size, $orientation);

        return $pdf;
    }

    private function getFontFace(Template $template): string
    {
        return
            $template->getFontFace() ??
            $this->config->get('pdfFontFace') ??
            $this->defaultFontFace;
    }
}
