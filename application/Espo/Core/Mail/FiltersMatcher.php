<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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
 ************************************************************************/

namespace Espo\Core\Mail;


use \Espo\Entities\Email;

class FiltersMatcher
{
    public function __construct()
    {

    }

    public function match(Email $email, $filterList = [])
    {
        foreach ($filterList as $filter) {
            if ($filter->get('from')) {
                if (strtolower($filter->get('from')) === strtolower($email->get('from'))) {
                    return true;
                }
            }
            if ($filter->get('to')) {
                if ($email->get('to')) {
                    $toArr = explode(';', $email->get('to'));
                    foreach ($toArr as $to) {
                        if (strtolower($to) === strtolower($filter->get('to'))) {
                            return true;
                        }
                    }
                }
            }
            if ($filter->get('subject')) {
                if ($this->matchString($filter->get('subject'), $email->get('name'))) {
                    return true;
                }
            }
        }
        return false;
    }

    public function matchBody(Email $email, $filterList = [])
    {
        foreach ($filterList as $filter) {
            if ($filter->get('bodyContains')) {
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
        }
        return false;
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
