<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Core\Upgrades\Actions\Upgrade;

class Install extends \Espo\Core\Upgrades\Actions\Base\Install
{
    /**
     * @param array<string, mixed> $data
     * @return mixed
     * @throws \Espo\Core\Exceptions\Error
     * @throws \Espo\Core\Exceptions\Error
     */
    public function stepBeforeUpgradeScript(array $data)
    {
        /** @phpstan-ignore-next-line */
        return $this->stepBeforeInstallScript($data);
    }

    /**
     * @param array<string, mixed> $data
     * @return mixed
     * @throws \Espo\Core\Exceptions\Error
     * @throws \Espo\Core\Exceptions\Error
     */
    public function stepAfterUpgradeScript(array $data)
    {
        /** @phpstan-ignore-next-line */
        return $this->stepAfterInstallScript($data);
    }

    /**
     * @return void
     * @throws \Espo\Core\Exceptions\Error
     * @throws \Espo\Core\Exceptions\Error
     */
    protected function finalize()
    {
        $manifest = $this->getManifest();

        $configWriter = $this->createConfigWriter();

        $configWriter->set('version', $manifest['version']);

        $configWriter->save();
    }

    /**
     * Delete temporary package files.
     *
     * @return bool
     * @throws \Espo\Core\Exceptions\Error
     * @throws \Espo\Core\Exceptions\Error
     */
    protected function deletePackageFiles()
    {
        $res = parent::deletePackageFiles();

        $res &= $this->deletePackageArchive();

        /** @var bool @res */

        return $res;
    }
}
