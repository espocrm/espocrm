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

import ArrayFieldView from 'views/fields/array';

class TabListFieldView extends ArrayFieldView {

    addItemModalView = 'views/settings/modals/tab-list-field-add'

    noGroups = false
    noDelimiters = false

    setup() {
        super.setup();

        this.selected.forEach(item => {
            if (item && typeof item === 'object') {
                if (!item.id) {
                    item.id = this.generateItemId();
                }
            }
        });

        this.addActionHandler('editGroup', (e, target) => {
            this.editGroup(target.dataset.value);
        })
    }

    generateItemId() {
        return Math.floor(Math.random() * 1000000 + 1).toString();
    }

    setupOptions() {
        this.params.options = Object.keys(this.getMetadata().get('scopes'))
            .filter(scope => {
                if (this.getMetadata().get(`scopes.${scope}.disabled`)) {
                    return false;
                }

                if (!this.getAcl().checkScope(scope)) {
                    return false;
                }

                return this.getMetadata().get(`scopes.${scope}.tab`);
            })
            .sort((v1, v2) => {
                return this.translate(v1, 'scopeNamesPlural')
                    .localeCompare(this.translate(v2, 'scopeNamesPlural'));
            });

        if (!this.noDelimiters) {
            this.params.options.push('_delimiter_');
            this.params.options.push('_delimiter-ext_');
        }

        this.translatedOptions = {};

        this.params.options.forEach(item => {
            this.translatedOptions[item] = this.translate(item, 'scopeNamesPlural');
        });

        this.translatedOptions['_delimiter_'] = '. . .';
        this.translatedOptions['_delimiter-ext_'] = '. . .';
    }

    addValue(value) {
        if (value && typeof value === 'object') {
            if (!value.id) {
                value.id = this.generateItemId();
            }

            const html = this.getItemHtml(value);

            this.$list.append(html);
            this.selected.push(value);

            this.trigger('change');

            return;
        }

        super.addValue(value);
    }

    removeValue(value) {
        const index = this.getGroupIndexById(value);

        if (~index) {
            this.$list.children(`[data-value="${value}"]`).remove();

            this.selected.splice(index, 1);
            this.trigger('change');

            return;
        }

        super.removeValue(value);
    }

    getItemHtml(value) {
        if (value && typeof value === 'object') {
            return this.getGroupItemHtml(value);
        }

        return super.getItemHtml(value);
    }

    /**
     *
     * @param {{id: string, text?: string|null, type: string}} item
     * @return {string | jQuery}
     */
    getGroupItemHtml(item) {
        const labelElement = document.createElement('span');
        labelElement.textContent = item.text ?? '';

        let icon;

        if (item.type === 'group') {
            icon = document.createElement('span');
            icon.className = 'far fa-list-alt text-muted';
        }

        if (item.type === 'url') {
            icon = document.createElement('span');
            icon.className = 'fas fa-link fa-sm text-muted';
        }

        if (item.type === 'divider') {
            labelElement.classList.add('text-soft', 'text-italic');
        }

        const itemElement = document.createElement('span');
        itemElement.append(labelElement);
        itemElement.className = 'text';

        if (icon) {
            icon.style.marginRight = 'var(--4px)';

            itemElement.prepend(icon);
        }

        const div = document.createElement('div');
        div.className = 'list-group-item';
        div.dataset.value = item.id;
        div.style.cursor = 'default';

        div.append(
            (() => {
                const span = document.createElement('span');
                span.className = 'drag-handle';
                span.append(
                    (() => {
                        const span = document.createElement('span');
                        span.className = 'fas fa-grip fa-sm';

                        return span;
                    })(),
                );

                return span;
            })(),
            (() => {
                const span = document.createElement('span');
                span.className = 'item-button'
                span.append(
                    (() => {
                        const a = document.createElement('a');
                        a.role = 'button';
                        a.tabIndex = 0;
                        a.dataset.value = item.id;
                        a.dataset.action = 'editGroup';
                        a.append(
                            (() => {
                                const span = document.createElement('span');
                                span.className = 'fas fa-pencil-alt fa-sm';

                                return span;
                            })(),
                        );

                        return a;
                    })(),
                )

                return span;
            })(),
            itemElement,
            (() => {
                const a = document.createElement('a');
                a.role = 'button';
                a.tabIndex = 0;
                a.classList.add('pull-right');
                a.dataset.value = item.id;
                a.dataset.action = 'removeValue';
                a.append(
                    (() => {
                        const span = document.createElement('span');
                        span.className = 'fas fa-times'

                        return span;
                    })(),
                );

                return a;
            })(),
        );

        return div.outerHTML;


        return $('<div>')
            .addClass('list-group-item')
            .attr('data-value', item.id)
            .css('cursor', 'default')
            .append(
                $('<a>')
                    .attr('role', 'button')
                    .attr('tabindex', '0')
                    .attr('data-value', item.id)
                    .attr('data-action', 'editGroup')
                    .css('margin-right', '7px')
                    .append(
                        $('<span>').addClass('fas fa-pencil-alt fa-sm')
                    ),
                $item,
                '&nbsp;',
                $('<a>')
                    .addClass('pull-right')
                    .attr('role', 'button')
                    .attr('tabindex', '0')
                    .attr('data-value', item.id)
                    .attr('data-action', 'removeValue')
                    .append(
                        $('<span>').addClass('fas fa-times')
                    )
            )
            .get(0).outerHTML;
    }

    fetchFromDom() {
        const selected = [];

        this.$el.find('.list-group .list-group-item').each((i, el) => {
            const value = $(el).data('value').toString();
            const groupItem = this.getGroupValueById(value);

            if (groupItem) {
                selected.push(groupItem);

                return;
            }

            selected.push(value);
        });

        this.selected = selected;
    }

    getGroupIndexById(id) {
        for (let i = 0; i < this.selected.length; i++) {
            const item = this.selected[i];

            if (item && typeof item === 'object') {
                if (item.id === id) {
                    return i;
                }
            }
        }

        return -1;
    }

    getGroupValueById(id) {
        for (const item of this.selected) {
            if (item && typeof item === 'object') {
                if (item.id === id) {
                    return item;
                }
            }
        }

        return null;
    }

    editGroup(id) {
        const item = Espo.Utils.cloneDeep(this.getGroupValueById(id) || {});

        const index = this.getGroupIndexById(id);
        const tabList = Espo.Utils.cloneDeep(this.selected);

        const view = {
            divider: 'views/settings/modals/edit-tab-divider',
            url: 'views/settings/modals/edit-tab-url'
        }[item.type] ||  'views/settings/modals/edit-tab-group';

        this.createView('dialog', view, {
            itemData: item,
            parentType: this.model.entityType,
        }, view => {
            view.render();

            this.listenToOnce(view, 'apply', itemData => {
                for (const a in itemData) {
                    tabList[index][a] = itemData[a];
                }

                this.model.set(this.name, tabList);

                view.close();
            });
        });
    }

    getAddItemModalOptions() {
        return {
            ...super.getAddItemModalOptions(),
            noGroups: this.noGroups,
        };
    }

    getValueForDisplay() {
        const labels = this.translatedOptions || {};

        /** @var {string[]} */
        const list = this.selected.map(item => {
            if (typeof item !== 'string') {
                return ' - ' + (item.text || '?');
            }

            return labels[item] || item;
        });

        return list.map(text => {
            return $('<div>')
                .addClass('multi-enum-item-container')
                .text(text)
                .get(0)
                .outerHTML
        }).join('');
    }
}

export default TabListFieldView;
