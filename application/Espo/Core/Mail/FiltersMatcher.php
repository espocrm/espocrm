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

namespace Espo\Core\Mail;

use Espo\Entities\Email;
use Espo\Entities\EmailFilter;

class FiltersMatcher
{
    /**
     * @param iterable<EmailFilter>|EmailFilter $subject
     */
    public function match(Email $email, $subject, bool $skipBody = false): bool
    {
        if (is_array($subject) || is_iterable($subject)) {
            $filterList = $subject;
        }
        else {
            $filterList = [$subject];
        }

        foreach ($filterList as $filter) {
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
                    continue;
                }
            }

            if ($filter->getTo()) {
                $filterCount++;

                if (!$this->matchTo($email, $filter)) {
                    continue;
                }
            }

            if ($subject) {
                $filterCount++;

                if (
                    !$this->matchString($subject, $email->getSubject() ?? '')
                ) {
                    continue;
                }
            }

            if (count($filter->getBodyContains())) {
                $filterCount++;

                if ($skipBody) {
                    continue;
                }

                if (!$this->matchBody($email, $filter)) {
                    continue;
                }
            }

            if ($filterCount) {
                return true;
            }
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

    private function matchString(string $pattern, string $value): bool
    {
        if ($pattern == $value) {
            return true;
        }

        $pattern = preg_quote($pattern, '#');
        $pattern = str_replace('\*', '.*', $pattern).'\z';

        if (preg_match('#^' . $pattern . '#', $value)) {
            return true;
        }

        return false;
    }
}
