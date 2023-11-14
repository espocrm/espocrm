<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

use Espo\Core\Container;
use Espo\Core\InjectableFactory;
use Espo\Core\Utils\Config\ConfigWriter;

class AfterUpgrade
{
    public function run(Container $container): void
    {
        $config = $container->getByClass(Espo\Core\Utils\Config::class);

        $configWriter = $container->getByClass(InjectableFactory::class)
            ->create(ConfigWriter::class);

        $configWriter->setMultiple([
            'phoneNumberNumericSearch' => false,
            'phoneNumberInternational' => false,
        ]);

        if ($config->get('pdfEngine') === 'Tcpdf') {
            $configWriter->set('pdfEngine', 'Dompdf');

            if (php_sapi_name() === 'cli') {
                echo "Important. The 'Tcpdf' PDF engine has been removed from EspoCRM. " .
                    "Now PDF printing is handled by the 'Dompdf' engine. " .
                    "If you would like to continue using the 'Tcpdf', download and install the free extension: {link}. " .
                    "After that, set 'Tcpdf' at Administration > Settings > PDF Engine.";
            }
        }

        $configWriter->save();
    }
}
