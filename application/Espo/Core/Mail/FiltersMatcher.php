<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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


use \Espo\Entities\Email;

class FiltersMatcher
{
    public function __construct()
    {

    }

    protected function matchTo(Email $email, $filter)
    {
        if ($email->get('to')) {
            $toArr = explode(';', $email->get('to'));
            foreach ($toArr as $to) {
                if ($this->matchString(strtolower($filter->get('to')), strtolower($to))) {
                    return true;
                }
            }
        }
    }

    public function match(Email $email, $subject, $skipBody = false)
    {
        if (is_array($subject) || $subject instanceof \Traversable) {
            $filterList = $subject;
        } else {
            $filterList = [$subject];
        }

        foreach ($filterList as $filter) {
            $filterCount = 0;

            if ($filter->get('from')) {
                $filterCount++;
                if (!$this->matchString(strtolower($filter->get('from')), strtolower($email->get('from')))) {
                    continue;
                }
            }

            if ($filter->get('to')) {
                $filterCount++;
                if (!$this->matchTo($email, $filter)) {
                    continue;
                }
            }

            if ($filter->get('subject')) {
                $filterCount++;
                if (!$this->matchString($filter->get('subject'), $email->get('name'))) {
                    continue;
                }
            }

            $wordList = $filter->get('bodyContains');
            if (!empty($wordList)) {
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

    protected function matchBody(Email $email, $filter)
    {
        $phraseList = $filter->get('bodyContains');
        $body = $email->get('body');
        $bodyPlain = $email->get('bodyPlain');
        foreach ($phraseList as $phrase) {
            if (stripos($bodyPlain, $phrase) !== false) {
                return true;
            }
            if (stripos($body, $phrase) !== false) {
                return true;
            }
        }
    }

    protected function matchString($pattern, $value)
    {
        if ($pattern == $value) {
            return true;
        }
        $pattern = preg_quote($pattern, '#');
        $pattern = str_replace('\*', '.*', $pattern).'\z';
        if (preg_match('#^'.$pattern.'#', $value)) {
            return true;
        }
        return false;
    }
}
