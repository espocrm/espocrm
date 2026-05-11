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
import type {Button, DropdownItem} from 'views/record/detail';
import {ButtonComponent, DropdownItemComponent} from 'components/controls';
import {h, fragment, VNode} from 'bullbone';

export default class DetailRecordButtonsView extends View<{
    options: {
        dataProvider: () => {
            buttonList: Button[],
            dropdownItemList: (DropdownItem | false)[],
            allDisabled: boolean,
        },
        actionClassName: string,
        entityType: string | null,
    },
}> {

    readonly useVirtualDom = true

    content(): VNode {
        const data = this.data();

        const buttons: any[] = [];

        data.buttonList.forEach(it => {
            buttons.push(
                new ButtonComponent({
                    name: it.name,
                    scope: this.options.entityType,
                    label: it.label,
                    labelTranslation: it.labelTranslation,
                    style: it.style,
                    title: it.title,
                    text: it.text,
                    className: data.buttonClassName,
                    hidden: it.hidden,
                    disabled: it.disabled || data.allDisabled,
                }).node()
            );
        });

        const elements: any[] = [...buttons];

        if (data.dropdownItemList.length) {
            const icon = h('span', {class: {'fas': true, 'fa-ellipsis-h': true}});

            elements.push(
                h('button', {
                    key: '_dropdown-button',
                    attrs: {type: 'button'},
                    class: {
                        'btn': true,
                        'btn-default': true,
                        'dropdown-toggle': true,
                        'dropdown-item-list-button': true,
                    },
                    dataset: {toggle: 'dropdown'}
                }, icon)
            );

            const dropdownItems: VNode[] = [];

            data.dropdownItemList.forEach((it, i) => {
                if (it === false) {
                    if (i === 0 || i === data.dropdownItemList.length - 1) {
                        return;
                    }

                    dropdownItems.push(
                        h('li', {class: {'divider': true}})
                    );

                    return;
                }

                dropdownItems.push(
                    new DropdownItemComponent({
                        name: it.name,
                        scope: this.options.entityType,
                        label: it.label,
                        labelTranslation: it.labelTranslation,
                        title: it.title,
                        text: it.text,
                        className: data.actionClassName,
                        hidden: it.hidden,
                        disabled: it.disabled || data.allDisabled,
                    }).node()
                );
            });

            elements.push(
                h('ul', {
                    class: {'dropdown-menu': true, 'pull-left': true},
                }, dropdownItems)
            );
        }

        return fragment(elements);
    }

    /*
    // language=Handlebars
    templateContent_ = `
        {{#each buttonList}}
            {{#unless hidden}}
                {{button name
                     scope=../entityType
                     label=label
                     labelTranslation=labelTranslation
                     style=style
                     hidden=hidden
                     html=html
                     title=title
                     text=text
                     className=../buttonClassName
                     disabled=disabled
                }}
            {{/unless}}
        {{/each}}

        {{#if dropdownItemList}}
            <button
                type="button"
                class="btn btn-default dropdown-toggle dropdown-item-list-button {{#if dropdownEmpty}} hidden {{/if}}"
                data-toggle="dropdown"
            ><span class="fas fa-ellipsis-h"></span></button>
            <ul class="dropdown-menu pull-left">
                {{#each dropdownItemList}}
                    {{#if this}}
                        {{#unless hidden}}
                            {{dropdownItem
                                name
                                scope=../entityType
                                label=label
                                labelTranslation=labelTranslation
                                html=html
                                title=title
                                text=text
                                hidden=hidden
                                disabled=disabled
                                data=data
                                className=../actionClassName
                            }}
                        {{/unless}}
                    {{else}}
                        {{#unless @first}}
                            {{#unless @last}}
                                <li class="divider"></li>
                            {{/unless}}
                        {{/unless}}
                    {{/if}}
                {{/each}}
            </ul>
        {{/if}}
    `
    */

    protected data() {
        const data = this.options.dataProvider();

        const dropdownEmpty = data.dropdownItemList.filter(it => it && !it.hidden).length === 0;

        const dropdownItemList = data.dropdownItemList
            .filter(it => it === false || !it.hidden)
            .filter((it, i, list) => {
                if (i === 0) {
                    return true;
                }

                if (it === false && list[i - 1] === false) {
                    return false;
                }

                return true;
            });

        return {
            buttonList: data.buttonList.filter(it => !it.hidden),
            dropdownItemList: dropdownItemList,
            entityType: this.options.entityType,
            buttonClassName: 'btn-xs-wide ' + this.options.actionClassName,
            actionClassName: this.options.actionClassName,
            dropdownEmpty: dropdownEmpty,
            allDisabled: data.allDisabled,
        };
    }

    protected afterRender() {
        this.adjust();
    }

    private adjust() {
        const buttons = this.element.querySelectorAll<HTMLButtonElement>('button.btn');

        buttons.forEach(element => element.classList.remove('radius-left', 'radius-right'));

        const visibleButtons = Array.from(buttons).filter(element => !element.classList.contains('hidden'));

        visibleButtons[0]?.classList.add('radius-left');
        visibleButtons[visibleButtons.length - 1]?.classList.add('radius-right');
    }

    enableItemElement(name: string) {
        const button = this.element.querySelector<HTMLButtonElement>(`button.action[data-action="${name}"]`);

        if (button) {
            button.disabled = false;
            button.classList.remove('disabled');
        }

        const a = this.element.querySelector<HTMLLIElement>(`li > .action[data-action="${name}"]`);

        if (a) {
            a.classList.remove('disabled');
        }
    }

    disableItemElement(name: string) {
        const button = this.element.querySelector<HTMLButtonElement>(`button.action[data-action="${name}"]`);

        if (button) {
            button.disabled = true;
            button.classList.add('disabled');
        }

        const a = this.element.querySelector<HTMLLIElement>(`li > .action[data-action="${name}"]`);

        if (a) {
            a.classList.add('disabled');
        }
    }
}
