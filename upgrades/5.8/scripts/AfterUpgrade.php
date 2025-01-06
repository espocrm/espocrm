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

class AfterUpgrade
{
    public function run($container)
    {
        $this->container = $container;

        $this->populateOpportunityContactId();

        $this->manageIndexes();

        $config = $container->get('config');
        $config->set('personNameFormat', 'firstLast');
        $config->set('streamEmailWithContentEntityTypeList', ['Case']);
        $config->save();

        $from = 'custom/Espo/Custom/Resources/templates/noteEmailRecieved';
        $to = 'custom/Espo/Custom/Resources/templates/noteEmailReceived';
        if (is_dir($from)) {
            rename($from, $to);
        }
    }

    protected function populateOpportunityContactId()
    {
        $pdo = $this->container->get('entityManager')->getPdo();

        $sql = "
            SELECT opportunity.id AS 'opportunityId', contact.id AS `contactId` FROM `opportunity`
            JOIN contact_opportunity ON contact_opportunity.opportunity_id = opportunity.id AND contact_opportunity.deleted = 0
            JOIN contact ON contact.id = contact_opportunity.contact_id AND contact.deleted = 0
            WHERE
            contact.id IN (
              SELECT MIN(contact.id)
              FROM `opportunity`
              JOIN contact_opportunity ON contact_opportunity.opportunity_id = opportunity.id AND contact_opportunity.deleted = 0
              JOIN contact ON contact.id = contact_opportunity.contact_id AND contact.deleted = 0
              GROUP BY opportunity.id
            ) AND
            opportunity.contact_id IS NULL AND
            opportunity.deleted = 0
        ";

        $sth = $pdo->prepare($sql);
        $sth->execute();

        while ($row = $sth->fetch()) {
            $cId = $row['contactId'] ?? null;
            $oId = $row['opportunityId'] ?? null;
            if (!$cId || !$oId) continue;

            $q = "
                UPDATE `opportunity` SET contact_id = ".$pdo->quote($cId)." WHERE id = ".$pdo->quote($oId)."
            ";
            $pdo->query($q);
        }
    }

    protected function manageIndexes()
    {
        $pdo = $this->container->get('entityManager')->getPdo();

        $sth = $pdo->prepare("SHOW INDEX FROM `note`");
        $sth->execute();
        $rows = [];
        while ($row = $sth->fetch()) {
            $rows[] = $row;
        }

        $indexes = [];

        foreach ($rows as $item) {
            $k = 0;
            foreach ($rows as $item2) {
                if ($item['Key_name'] === $item2['Key_name']) {
                    $k++;
                }
            }
            if ($k === 1 && $item['Column_name'] === 'number') {
                $indexes[] = $item['Key_name'];
            }
        }

        $isFound = false;

        $oldIndexes = [];

        foreach ($indexes as $key) {
            if ($key === 'UNIQ_NUMBER') {
                $isFound = true;
            }
        }

        if (!$isFound) {
            try {
                $sql = "CREATE UNIQUE INDEX UNIQ_NUMBER ON `note` (`number`)";
                $pdo->query($sql);
            } catch (\Exception $e) {}
        }

        foreach ($indexes as $item) {
            if ($item === 'UNIQ_NUMBER') continue;
            try {
                $sql = "DROP INDEX {$item} ON `note`";
                $pdo->query($sql);
            } catch (\Exception $e) {}
        }
    }
}
