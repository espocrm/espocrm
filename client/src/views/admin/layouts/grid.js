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

import LayoutBaseView from 'views/admin/layouts/base';

/**
 * @abstract
 */
class LayoutGridView extends LayoutBaseView {

    template = 'admin/layouts/grid'

    dataAttributeList = null
    panels = null
    columnCount = 2
    panelDataAttributeList = ['panelName', 'style']
    panelDataAttributesDefs = {}
    panelDynamicLogicDefs = null

    data() {
        return {
            scope: this.scope,
            type: this.type,
            buttonList: this.buttonList,
            enabledFields: this.enabledFields,
            disabledFields: this.disabledFields,
            panels: this.panels,
            columnCount: this.columnCount,
            panelDataList: this.getPanelDataList(),
        };
    }

    additionalEvents = {
        /** @this LayoutGridView */
        'click #layout a[data-action="addPanel"]': function () {
            this.addPanel();
            this.setIsChanged();
            this.makeDraggable();
        },
        /** @this LayoutGridView */
        'click #layout a[data-action="removePanel"]': function (e) {
            $(e.target).closest('ul.panels > li').find('ul.cells > li').each((i, li) => {
                if ($(li).attr('data-name')) {
                    $(li).appendTo($('#layout ul.disabled'));
                }
            });

            $(e.target).closest('ul.panels > li').remove();

            const number = $(e.currentTarget).data('number');
            this.clearView('panels-' + number);

            let index = -1;

            this.panels.forEach((item, i) => {
                if (item.number === number) {
                    index = i;
                }
            });

            if (~index) {
                this.panels.splice(index, 1);
            }

            this.normalizeDisabledItemList();

            this.setIsChanged();
        },
        /** @this LayoutGridView */
        'click #layout a[data-action="addRow"]': function (e) {
            const tpl = this.unescape($("#layout-row-tpl").html());
            const html = _.template(tpl);

            $(e.target).closest('ul.panels > li').find('ul.rows').append(html);

            this.setIsChanged();
            this.makeDraggable();
        },
        /** @this LayoutGridView */
        'click #layout a[data-action="removeRow"]': function (e) {
            $(e.target).closest('ul.rows > li').find('ul.cells > li').each((i, li) => {
                if ($(li).attr('data-name')) {
                    $(li).appendTo($('#layout ul.disabled'));
                }
            });

            $(e.target).closest('ul.rows > li').remove();

            this.normalizeDisabledItemList();

            this.setIsChanged();
        },
        /** @this LayoutGridView */
        'click #layout a[data-action="removeField"]': function (e) {
            const $li = $(e.target).closest('li');
            const index = $li.index();
            const $ul = $li.parent();

            $li.appendTo($('ul.disabled'));

            const $empty = $($('#empty-cell-tpl').html());

            if (parseInt($ul.attr('data-cell-count')) === 1) {
                for (let i = 0; i < this.columnCount; i++) {
                    $ul.append($empty.clone());
                }
            } else {
                if (index === 0) {
                    $ul.prepend($empty);
                } else {
                    $empty.insertAfter($ul.children(':nth-child(' + index + ')'));
                }
            }

            const cellCount = $ul.children().length;
            $ul.attr('data-cell-count', cellCount.toString());
            $ul.closest('li').attr('data-cell-count', cellCount.toString());

            this.setIsChanged();

            this.makeDraggable();
        },
        /** @this LayoutGridView */
        'click #layout a[data-action="minusCell"]': function (e) {
            if (this.columnCount < 2) {
                return;
            }

            const $li = $(e.currentTarget).closest('li');
            const $ul = $li.parent();

            $li.remove();

            const cellCount = $ul.children().length || 2;

            this.setIsChanged();

            this.makeDraggable();

            $ul.attr('data-cell-count', cellCount.toString());
            $ul.closest('li').attr('data-cell-count', cellCount.toString());
        },
        /** @this LayoutGridView */
        'click #layout a[data-action="plusCell"]': function (e) {
            const $li = $(e.currentTarget).closest('li');
            const $ul = $li.find('ul');

            const $empty = $($('#empty-cell-tpl').html());

            $ul.append($empty);

            const cellCount = $ul.children().length;

            $ul.attr('data-cell-count', cellCount.toString());
            $ul.closest('li').attr('data-cell-count', cellCount.toString());

            this.setIsChanged();

            this.makeDraggable();
        },
        /** @this LayoutGridView */
        'click #layout a[data-action="edit-panel-label"]': function (e) {
            const $header = $(e.target).closest('header');
            const $label = $header.children('label');
            const panelName = $label.text();

            const $panel = $header.closest('li');

            const id = $panel.data('number').toString();

            const attributes = {
                panelName: panelName,
            };

            this.panelDataAttributeList.forEach((item) => {
                if (item === 'panelName') {
                    return;
                }

                attributes[item] = this.panelsData[id][item];
            });

            const attributeList = this.panelDataAttributeList;
            const attributeDefs = this.panelDataAttributesDefs;

            this.createView('dialog', 'views/admin/layouts/modals/panel-attributes', {
                attributeList: attributeList,
                attributeDefs: attributeDefs,
                attributes: attributes,
                dynamicLogicDefs: this.panelDynamicLogicDefs,
            }, view => {
                view.render();

                this.listenTo(view, 'after:save', attributes => {
                    $label.text(attributes.panelName);
                    $label.attr('data-is-custom', 'true');

                    this.panelDataAttributeList.forEach(item => {
                        if (item === 'panelName') {
                            return;
                        }

                        this.panelsData[id][item] = attributes[item];
                    });

                    $panel.attr('data-tab-break', attributes.tabBreak ? 'true' : false);

                    view.close();

                    this.$el.find('.well').focus();

                    this.setIsChanged();
                });
            });
        }
    }

    normalizeDisabledItemList() {
        //$('#layout ul.cells.disabled > li').each((i, el) => {});
    }

    setup() {
        super.setup();

        this.events = {
            ...this.additionalEvents,
            ...this.events,
        };

        this.panelsData = {};

        Espo.loader.require('res!client/css/misc/layout-manager-grid.css', styleCss => {
            this.$style = $('<style>').html(styleCss).appendTo($('body'));
        });
    }

    onRemove() {
        if (this.$style) this.$style.remove();
    }

    addPanel() {
        this.lastPanelNumber ++;

        const number = this.lastPanelNumber;

        const data = {
            customLabel: null,
            rows: [[]],
            number: number,
        };

        this.panels.push(data);

        const attributes = {};

        for (const attribute in this.panelDataAttributesDefs) {
            const item = this.panelDataAttributesDefs[attribute];

            if ('default' in item) {
                attributes[attribute] = item.default;
            }
        }

        this.panelsData[number.toString()] = attributes;

        const $li = $('<li class="panel-layout"></li>');

        $li.attr('data-number', number);

        this.$el.find('ul.panels').append($li);

        this.createPanelView(data, true, (view) => {
            view.render();
        });
    }

    getPanelDataList() {
        const panelDataList = [];

        this.panels.forEach(item => {
            const o = {};

            o.viewKey = 'panel-' + item.number;
            o.number = item.number;

            o.tabBreak = !!item.tabBreak;

            panelDataList.push(o);
        });

        return panelDataList;
    }

    prepareLayout() {
        return new Promise(resolve => {
            let countLoaded = 0;

            this.setupPanels(() => {
                countLoaded ++;

                if (countLoaded === this.panels.length) {
                    resolve();
                }
            });
        });
    }

    setupPanels(callback) {
        this.lastPanelNumber = -1;

        this.panels = Espo.Utils.cloneDeep(this.panels);

        this.panels.forEach((panel, i) => {
            panel.number = i;
            this.lastPanelNumber ++;
            this.createPanelView(panel, false, callback);
            this.panelsData[i.toString()] = panel;
        });
    }

    createPanelView(data, empty, callback) {
        data.label = data.label || '';

        data.isCustomLabel = false;

        if (data.customLabel) {
            data.labelTranslated = data.customLabel;
            data.isCustomLabel = true;
        } else {
            data.labelTranslated = this.translate(data.label, 'labels', this.scope);
        }

        data.style = data.style || null;

        data.rows.forEach(row => {
            const rest = this.columnCount - row.length;

            if (empty) {
                for (let i = 0; i < rest; i++) {
                    row.push(false);
                }
            }

            for (const i in row) {
                if (row[i] !== false) {
                    row[i].label = this.getLanguage().translate(row[i].name, 'fields', this.scope);

                    if ('customLabel' in row[i]) {
                        row[i].hasCustomLabel = true;
                    }
                }
            }
        });

        this.createView('panel-' + data.number, 'view', {
            selector: 'li.panel-layout[data-number="'+data.number+'"]',
            template: 'admin/layouts/grid-panel',
            data: () => {
                const o = Espo.Utils.clone(data);

                o.dataAttributeList = [];

                this.panelDataAttributeList.forEach((item) => {
                    if (item === 'panelName') {
                        return;
                    }

                    o.dataAttributeList.push(item);
                });

                return o;
            }
        }, callback);
    }

    makeDraggable() {
        const self = this;

        const $panels = $('#layout ul.panels');
        const $rows = $('#layout ul.rows');

        $panels.sortable({
            distance: 4,
            update: () => {
                this.setIsChanged();
            },
            cursor: 'grabbing',
        });

        // noinspection JSUnresolvedReference
        $panels.disableSelection();

        $rows.sortable({
            distance: 4,
            connectWith: '.rows',
            update: () => {
                this.setIsChanged();
            },
            cursor: 'grabbing',
        });

        // noinspection JSUnresolvedReference
        $rows.disableSelection();

        const $li = $('#layout ul.cells > li');

        // noinspection JSValidateTypes
        $li.draggable({
            revert: 'invalid',
            revertDuration: 200,
            zIndex: 10,
            cursor: 'grabbing',
        })
            .css('cursor', '');

        $li.droppable().droppable('destroy');

        $('#layout ul.cells:not(.disabled) > li').droppable({
            accept: '.cell',
            zIndex: 10,
            hoverClass: 'ui-state-hover',
            drop: function (e, ui) {
                const index = ui.draggable.index();
                const parent = ui.draggable.parent();

                if (parent.get(0) === $(this).parent().get(0)) {
                    if ($(this).index() < ui.draggable.index()) {
                        $(this).before(ui.draggable);
                    } else {
                        $(this).after(ui.draggable);
                    }
                } else {
                    ui.draggable.insertAfter($(this));

                    if (index === 0) {
                        $(this).prependTo(parent);
                    } else {
                        $(this).insertAfter(parent.children(':nth-child(' + (index) + ')'));
                    }
                }

                ui.draggable.css({
                    top: 0,
                    left: 0,
                });

                if ($(this).parent().hasClass('disabled') && !$(this).data('name')) {
                    $(this).remove();
                }

                self.makeDraggable();

                self.setIsChanged();
            }
        });
    }

    afterRender() {
        this.makeDraggable();

        const wellElement = /** @type {HTMLElement} */this.$el.find('.enabled-well').get(0);

        wellElement.focus({preventScroll: true});
    }

    fetch() {
        const layout = [];

        $("#layout ul.panels > li").each((i, el) => {
            const $label = $(el).find('header label');

            const id = $(el).data('number').toString();

            const o = {
                rows: []
            };

            this.panelDataAttributeList.forEach((item) => {
                if (item === 'panelName') {
                    return;
                }

                o[item] = this.panelsData[id][item];
            });

            o.style = o.style || 'default';

            const name = $(el).find('header').data('name');

            if (name) {
                o.name = name;
            }

            if ($label.attr('data-is-custom')) {
                o.customLabel = $label.text();
            } else {
                o.label = $label.data('label');
            }

            $(el).find('ul.rows > li').each((i, li) => {
                const row = [];

                $(li).find('ul.cells > li').each((i, li) => {
                    let cell = false;

                    if (!$(li).hasClass('empty')) {
                        cell = {};

                        this.dataAttributeList.forEach(attr => {
                            const defs = this.dataAttributesDefs[attr] || {};

                            if (defs.notStorable) {
                                return;
                            }

                            if (attr === 'customLabel') {
                                if ($(li).get(0).hasAttribute('data-custom-label')) {
                                    cell[attr] = $(li).attr('data-custom-label');
                                }

                                return;
                            }

                            const value = $(li).data(Espo.Utils.toDom(attr)) || null;

                            if (value) {
                                cell[attr] = value;
                            }
                        });
                    }

                    row.push(cell);
                });

                o.rows.push(row);
            });

            layout.push(o);
        });

        return layout;
    }

    validate(layout) {
        let fieldCount = 0;

        layout.forEach(panel => {
            panel.rows.forEach(row => {
                row.forEach(cell => {
                    if (cell !== false && cell !== null) {
                        fieldCount++;
                    }
                });
            });
        });

        if (fieldCount === 0) {
            Espo.Ui.error(
                this.translate('cantBeEmpty', 'messages', 'LayoutManager')
            );

            return false;
        }

        return true;
    }
}

export default LayoutGridView;
