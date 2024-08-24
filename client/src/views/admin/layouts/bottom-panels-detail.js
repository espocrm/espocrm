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

import LayoutSidePanelsDetailView from 'views/admin/layouts/side-panels-detail';

class LayoutBottomPanelsDetail extends LayoutSidePanelsDetailView {

    hasStream = true
    hasRelationships = true

    TAB_BREAK_KEY = '_tabBreak_{n}'

    setup() {
        super.setup();

        this.on('update-item', (name, attributes) => {
            if (this.isTabName(name)) {
                const $li = $("#layout ul > li[data-name='" + name + "']");

                $li.find('.left > span')
                    .text(this.composeTabBreakLabel(attributes));
            }
        });
    }

    composeTabBreakLabel(item) {
        let label = '. . . ' + this.translate('tabBreak', 'fields', 'LayoutManager');

        if (item.tabLabel) {
            label += ' : ' + item.tabLabel;
        }

        return label;
    }

    readDataFromLayout(layout) {
        let panelListAll = [];
        const labels = {};
        const params = {};

        layout = Espo.Utils.cloneDeep(layout);

        if (
            this.hasStream &&
            (this.getMetadata().get(['scopes', this.scope, 'stream']) || this.scope === 'User')
        ) {
            panelListAll.push('stream');

            labels['stream'] = this.translate('Stream');

            params['stream'] = {
                name: 'stream',
                sticked: true,
                index: 2,
            };
        }

        (this.getMetadata()
            .get(['clientDefs', this.scope, 'bottomPanels', this.viewType]) || []
        ).forEach(item => {
            if (!item.name) {
                return;
            }

            panelListAll.push(item.name);

            if (item.labelText) {
                // @todo Revise.
                labels[item.name] = item.labelText;
            }

            if (item.label) {
                labels[item.name] = item.label;
            }

            params[item.name] = Espo.Utils.clone(item);

            if ('order' in item) {
                params[item.name].index = item.order;
            }
        });

        for (const name in layout) {
            const item = layout[name];

            if (item.tabBreak) {
                panelListAll.push(name);

                labels[name] = this.composeTabBreakLabel(item);

                params[name] = {
                    name: item.name,
                    index: item.index,
                    tabBreak: true,
                    tabLabel: item.tabLabel || null,
                };
            }
        }

        this.links = {};

        if (this.hasRelationships) {
            /** @type {Record<string, Record>} */
            const linkDefs = this.getMetadata().get(['entityDefs', this.scope, 'links']) || {};

            Object.keys(linkDefs).forEach(link => {
                if (
                    linkDefs[link].disabled ||
                    linkDefs[link].utility ||
                    linkDefs[link].layoutRelationshipsDisabled
                ) {
                    return;
                }

                if (!~['hasMany', 'hasChildren'].indexOf(linkDefs[link].type)) {
                    return;
                }

                panelListAll.push(link);

                labels[link] = this.translate(link, 'links', this.scope);

                const item = {
                    name: link,
                    index: 5,
                };

                this.dataAttributeList.forEach(attribute => {
                    if (attribute in item) {
                        return;
                    }

                    const value = this.getMetadata()
                        .get(['clientDefs', this.scope, 'relationshipPanels', item.name, attribute]);

                    if (value === null) {
                        return;
                    }

                    item[attribute] = value;
                });

                this.links[link] = true;

                params[item.name] = item;
            });
        }

        this.disabledFields = [];

        layout = layout || {};

        this.rowLayout = [];

        panelListAll = panelListAll.sort((v1, v2) => {
            return params[v1].index - params[v2].index
        });

        panelListAll.push('_delimiter_');

        if (!layout['_delimiter_']) {
            layout['_delimiter_'] = {
                disabled: true,
            };
        }

        labels[this.TAB_BREAK_KEY] = '. . . ' + this.translate('tabBreak', 'fields', 'LayoutManager');

        panelListAll.push(this.TAB_BREAK_KEY);

        panelListAll.forEach((item, index) => {
            let disabled = false;
            const itemData = layout[item] || {};

            if (itemData.disabled) {
                disabled = true;
            }

            if (!layout[item]) {
                if ((params[item] || {}).disabled) {
                    disabled = true;
                }
            }

            if (this.links[item]) {
                if (!layout[item]) {
                    disabled = true;
                }
            }

            if (item === this.TAB_BREAK_KEY) {
                disabled = true;
            }

            let labelText;

            if (labels[item]) {
                labelText = this.getLanguage().translate(labels[item], 'labels', this.scope);
            } else {
                labelText = this.getLanguage().translate(item, 'panels', this.scope);
            }

            if (disabled) {
                const o = {
                    name: item,
                    labelText: labelText,
                };

                if (o.name[0] === '_') {
                    if (o.name === '_delimiter_') {
                        o.notEditable = true;
                        o.labelText = '. . .';
                    }
                }

                this.disabledFields.push(o);

                return;
            }

            const o = {
                name: item,
                labelText: labelText,
            };

            if (o.name[0] === '_') {
                if (o.name === '_delimiter_') {
                    o.notEditable = true;
                    o.labelText = '. . .';
                }
            }

            if (o.name in params) {
                this.dataAttributeList.forEach(attribute => {
                    if (attribute === 'name') {
                        return;
                    }

                    const itemParams = params[o.name] || {};

                    if (attribute in itemParams) {
                        o[attribute] = itemParams[attribute];
                    }
                });
            }

            for (const i in itemData) {
                o[i] = itemData[i];
            }

            o.index = ('index' in itemData) ? itemData.index : index;

            this.rowLayout.push(o);

            this.itemsData[o.name] = Espo.Utils.cloneDeep(o);
        });

        this.rowLayout.sort((v1, v2) => {
            return (v1.index || 0) - (v2.index || 0);
        });
    }

    onDrop() {
        let tabBreakIndex = -1;

        let $tabBreak = null;

        this.$el.find('ul.enabled').children().each((i, li) => {
            const $li = $(li);
            const name = $li.attr('data-name');

            if (this.isTabName(name)) {
                if (name !== this.TAB_BREAK_KEY) {
                    const itemIndex = parseInt(name.split('_')[2]);

                    if (itemIndex > tabBreakIndex) {
                        tabBreakIndex = itemIndex;
                    }
                }
            }
        });

        tabBreakIndex++;

        this.$el.find('ul.enabled').children().each((i, li) => {
            const $li = $(li);
            const name = $li.attr('data-name');

            if (this.isTabName(name) && name === this.TAB_BREAK_KEY) {
                $tabBreak = $li.clone();

                const realName = this.TAB_BREAK_KEY.slice(0, -3) + tabBreakIndex;

                $li.attr('data-name', realName);

                delete this.itemsData[realName];
            }
        });

        if (!$tabBreak) {
            this.$el.find('ul.disabled').children().each((i, li) => {
                const $li = $(li);

                const name = $li.attr('data-name');

                if (this.isTabName(name) && name !== this.TAB_BREAK_KEY) {
                    $li.remove();
                }
            });
        }

        if ($tabBreak) {
            $tabBreak.appendTo(this.$el.find('ul.disabled'));
        }
    }

    isTabName(name) {
        return name.substring(0, this.TAB_BREAK_KEY.length - 3) === this.TAB_BREAK_KEY.slice(0, -3);
    }

    getEditAttributesModalViewOptions(attributes) {
        const options = super.getEditAttributesModalViewOptions(attributes);

        if (this.isTabName(attributes.name)) {
            options.attributeList = [
                'tabLabel',
            ];

            options.attributeDefs = {
                tabLabel: {
                    type: 'varchar',
                },
            };
        }

        return options;
    }

    fetch() {
        const layout = super.fetch();

        const newLayout = {};

        for (const name in layout) {
            if (layout[name].disabled && this.links[name]) {
                continue;
            }

            newLayout[name] = layout[name];

            if (this.isTabName(name) && name !== this.TAB_BREAK_KEY /*&& this.itemsData[name]*/) {
                const data = this.itemsData[name] || {};

                newLayout[name].tabBreak = true;
                newLayout[name].tabLabel = data.tabLabel;
            }
            else {
               delete newLayout[name].tabBreak;
               delete newLayout[name].tabLabel;
            }
        }

        delete newLayout[this.TAB_BREAK_KEY];

        return newLayout;
    }
}

export default LayoutBottomPanelsDetail;
