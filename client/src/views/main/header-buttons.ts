/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

import View from 'view';
import {MenuItem} from 'views/main';
import {h, VNode} from 'bullbone';
import {ButtonComponent, DropdownItemComponent} from 'components/controls';
import Language from 'language';
import {inject} from 'di';

export default class HeaderButtonsView extends View<{
    options: {
        dataProvider: () => {
            buttons: MenuItem[],
            actions: MenuItem[],
            dropdown: (MenuItem | false)[],
            hidden: boolean,
        },
        scope: string | null,
    },
}> {

    readonly useVirtualDom = true

    @inject(Language)
    private language: Language

    protected content(): VNode {
        const data = this.options.dataProvider();

        const buttons = data.buttons.filter(it => !it.hidden);
        const actions = data.actions.filter(it => !it.hidden);

        const dropdown = data.dropdown
            .filter(it => it === false || !it.hidden)
            .filter((it, i, list) => {
                if (it === false && (i === 0 || i === list.length - 1)) {
                    return false;
                }

                if (it === false && list[i - 1] === false) {
                    return false;
                }

                return true;
            });

        const elements: any[] = [];

        let lastButtonIsText = false;

        buttons.forEach(it => {
            const className = ('btn-xs-wide main-header-manu-action ' + (it.className ?? '')).trim();

            if (!it.hidden) {
                lastButtonIsText = it.style === 'text';
            }

            elements.push(
                new ButtonComponent({
                    scope: this.options.scope,
                    name: it.name ?? null,
                    action: it.action ?? it.name,
                    link: it.link,
                    text: it.text,
                    label: it.label,
                    labelTranslation: it.labelTranslation,
                    style: it.style,
                    hidden: it.hidden,
                    disabled: it.disabled,
                    data: it.data,
                    title: it.title,
                    iconClass: it.iconClass,
                    iconHtml: it.iconHtml,
                    html: it.html,
                    className: className,
                }).node()
            );
        });

        if (actions.length) {
            const button = h(
                'button',
                {
                    key: '_actions-dropdown-button',
                    attrs: {type: 'button'},
                    props: {
                        className: 'btn btn-default dropdown-toggle',
                    },
                    dataset: {toggle: 'dropdown'},
                },
                [
                    this.language.translate('Actions'),
                    ' ',
                    h('span', {props: {className: 'caret'}})
                ]
            );

            const dropdownItems: any[] = [];

            actions.forEach(it => {
                dropdownItems.push(
                    new DropdownItemComponent({
                        name: it.name ?? null,
                        action: it.action ?? it.name,
                        scope: this.options.scope,
                        link: it.link,
                        label: it.label,
                        labelTranslation: it.labelTranslation,
                        title: it.title,
                        text: it.text,
                        className: 'main-header-manu-action',
                        hidden: it.hidden,
                        disabled: it.disabled,
                        iconClass: it.iconClass,
                    }).node()
                );
            });

            const ul = h('ul', {props: {className: 'dropdown-menu pull-right'}}, dropdownItems);

            elements.push(
                h('div', {props: {className: 'btn-group', role: 'group'}}, [button, ul]),
            );
        }

        if (dropdown.length) {
            const button = h(
                'button',
                {
                    key: '_menu-dropdown-button',
                    attrs: {type: 'button'},
                    props: {
                        className: 'btn btn-default dropdown-toggle' +
                            (lastButtonIsText ? ' radius-left' : ''),
                    },
                    dataset: {toggle: 'dropdown'},
                },
                h('span', {props: {className: 'fas fa-ellipsis-h'}})
            );

            const dropdownItems: any[] = [];

            dropdown.forEach(it => {
                if (it === false) {
                    dropdownItems.push(
                        h('li', {class: {'divider': true}})
                    );

                    return;
                }

                dropdownItems.push(
                    new DropdownItemComponent({
                        name: it.name ?? null,
                        action: it.action ?? it.name,
                        scope: this.options.scope,
                        link: it.link,
                        label: it.label,
                        labelTranslation: it.labelTranslation,
                        title: it.title,
                        text: it.text,
                        className: 'main-header-manu-action',
                        hidden: it.hidden,
                        disabled: it.disabled,
                        iconClass: it.iconClass,
                    }).node()
                );
            });

            const ul = h('ul', {props: {className: 'dropdown-menu pull-right'}}, dropdownItems);

            elements.push(
                h('div', {props: {className: 'btn-group', role: 'group'}}, [button, ul]),
            );
        }

        return h('div', {
            props: {
                className: 'header-buttons btn-group pull-right',
            },
            class: {
                'hidden': data.hidden,
            },
        }, elements)
    }
}
