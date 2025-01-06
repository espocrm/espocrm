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

namespace Espo\Core\Mail;

use Espo\Entities\Email;
use Espo\Entities\EmailFilter;

class FiltersMatcher
{
    /**
     * @param iterable<EmailFilter> $filterList
     * @param bool $skipBody Not to match if the body-contains is not empty.
     */
    public function findMatch(Email $email, $filterList, bool $skipBody = false): ?EmailFilter
    {
        foreach ($filterList as $filter) {
            if ($this->match($email, $filter, $skipBody)) {
                return $filter;
            }
        }

        return null;
    }

    /**
     * @param bool $skipBody Not to match if the body-contains is not empty.
     */
    public function match(Email $email, EmailFilter $filter, bool $skipBody = false): bool
    {
        $filterCount = 0;

        $from = $filter->getFrom();
        $subject = $filter->getSubject();

        if ($from) {
            $filterCount++;

            if (
                !$this->matchString(
                    strtolower($from),
                    strtolower($email->getFromAddress() ?? '')
                )
            ) {
                return false;
            }
        }

        if ($filter->getTo()) {
            $filterCount++;

            if (!$this->matchTo($email, $filter)) {
                return false;
            }
        }

        if ($subject) {
            $filterCount++;

            if (
                !$this->matchString($subject, $email->getSubject() ?? '')
            ) {
                return false;
            }
        }

        if (count($filter->getBodyContains())) {
            $filterCount++;

            if ($skipBody) {
                return false;
            }

            if (!$this->matchBody($email, $filter)) {
                return false;
            }
        }

        if (count($filter->getBodyContainsAll())) {
            $filterCount++;

            if ($skipBody) {
                return false;
            }

            if (!$this->matchBodyAll($email, $filter)) {
                return false;
            }
        }

        if ($filterCount) {
            return true;
        }

        return false;
    }

    private function matchTo(Email $email, EmailFilter $filter): bool
    {
        $filterTo = $filter->getTo();

        if ($filterTo === null) {
            return false;
        }

        if (count($email->getToAddressList())) {
            foreach ($email->getToAddressList() as $to) {
                if (
                    $this->matchString(
                        strtolower($filterTo),
                        strtolower($to)
                    )
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    private function matchBody(Email $email, EmailFilter $filter): bool
    {
        $phraseList = $filter->getBodyContains();
        $body = $email->getBody();
        $bodyPlain = $email->getBodyPlain();

        foreach ($phraseList as $phrase) {
            if ($phrase === '') {
                continue;
            }

            if ($bodyPlain && stripos($bodyPlain, $phrase) !== false) {
                return true;
            }

            if ($body && stripos($body, $phrase) !== false) {
                return true;
            }
        }

        return false;
    }

    private function matchBodyAll(Email $email, EmailFilter $filter): bool
    {
        $phraseList = $filter->getBodyContainsAll();
        $body = $email->getBody() ?? $email->getBodyPlain() ?? '';

        if ($phraseList === []) {
            return true;
        }

        foreach ($phraseList as $phrase) {
            if ($phrase === '') {
                continue;
            }

            if (stripos($body, $phrase) === false) {
                return false;
            }
        }

        return true;
    }

    private function matchString(string $pattern, string $value): bool
    {
        if ($pattern == $value) {
            return true;
        }

        $pattern = preg_quote($pattern, '#');
        $pattern = str_replace('\*', '.*', $pattern) . '\z';

        if (preg_match('#^' . $pattern . '#', $value)) {
            return true;
        }

        return false;
    }
}
