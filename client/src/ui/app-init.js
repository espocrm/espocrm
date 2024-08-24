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

import $ from 'jquery';

function uiAppInit() {
    const $document = $(document);

    $document.on('keydown.espo.button', e => {
        if (
            e.code !== 'Enter' ||
            e.target.tagName !== 'A' ||
            e.target.getAttribute('role') !== 'button' ||
            e.target.getAttribute('href') ||
            e.ctrlKey ||
            e.altKey ||
            e.metaKey
        ) {
            return;
        }

        $(e.target).click();

        e.preventDefault();
    });

    $document.on('show.bs.dropdown', e => {
        if (!e.target.parentElement.classList.contains('fix-overflow')) {
            return;
        }

        const isRight = e.target.classList.contains('pull-right');

        const $ul = $(e.target.parentElement).find('.dropdown-menu');

        const rect = e.target.getBoundingClientRect();

        const parent = $ul.offsetParent().get(0);

        if (!parent) {
            return;
        }

        const scrollTop = parent === window.document.documentElement ?
            (document.documentElement.scrollTop || document.body.scrollTop) :
            parent.scrollTop;

        const top = rect.top + scrollTop + e.target.getBoundingClientRect().height;

        const left = isRight ?
            rect.left - $ul.outerWidth() + rect.width:
            rect.left

        $ul.css({
            top: top,
            left: left,
            right: 'auto',
        });
    });

    $document.on('show.bs.dropdown', e => {
        const $body = $(e.target).closest('.dashlet-body');

        if (!$body.length) {
            return;
        }

        const $group = $(e.target);

        const rect = e.target.getBoundingClientRect();
        const $ul = $group.find('.dropdown-menu');
        const isRight = e.target.classList.contains('pull-right');

        const $toggle = $group.find('.dropdown-toggle');

        $body.on('scroll.dd', () => {
            if ($group.hasClass('open')) {
                // noinspection JSUnresolvedReference
                $toggle.dropdown('toggle');
                $body.off('scroll.dd');
            }
        })

        $group.one('hide.bs.dropdown', () => {
            $body.off('scroll.dd');
        });

        const left = isRight ?
            rect.left - $ul.outerWidth() + rect.width:
            rect.left

        const top = rect.top + e.target.getBoundingClientRect().height;

        $ul.css({
            position: 'fixed',
            top: top,
            left: left,
            right: 'auto',
        });
    });
}

export default uiAppInit;
