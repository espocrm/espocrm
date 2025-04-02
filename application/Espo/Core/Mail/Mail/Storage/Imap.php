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

namespace Espo\Core\Mail\Mail\Storage;

class Imap extends \Laminas\Mail\Storage\Imap
{
    /**
     * @return int[]
     */
    public function getIdsFromUniqueId(string $uid): array
    {
        $nextUid = strval(intval($uid) + 1);

        assert($this->protocol !== null);

        return $this->protocol->search(['UID ' . $nextUid . ':*']);
    }

    /**
     * @param string $date A date in the `d-M-Y` format.
     * @return int[]
     */
    public function getIdsSinceDate(string $date): array
    {
        assert($this->protocol !== null);

        return $this->protocol->search(['SINCE ' . $date]);
    }

    /**
     * @param int $id
     * @return array{header: string, flags: string[]}
     */
    public function getHeaderAndFlags(int $id): array
    {
        assert($this->protocol !== null);

        /** @var array{'RFC822.HEADER': string, 'FLAGS': string[]} $data */
        $data = $this->protocol->fetch(['FLAGS', 'RFC822.HEADER'], $id);

        $header = $data['RFC822.HEADER'];

        $flags = [];

        foreach ($data['FLAGS'] as $flag) {
            $flags[] = static::$knownFlags[$flag] ?? $flag;
        }

        return [
            'flags' => $flags,
            'header' => $header,
        ];
    }
}
