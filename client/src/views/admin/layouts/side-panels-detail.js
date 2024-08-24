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

import LayoutRowsView from 'views/admin/layouts/rows';

class LayoutSidePanelsDetailView extends LayoutRowsView {

    dataAttributeList = [
        'name',
        'dynamicLogicVisible',
        'style',
        'dynamicLogicStyled',
        'sticked',
    ]

    dataAttributesDefs = {
        dynamicLogicVisible: {
            type: 'base',
            view: 'views/admin/field-manager/fields/dynamic-logic-conditions',
            tooltip: 'dynamicLogicVisible',
        },
        style: {
            type: 'enum',
            options: [
                'default',
                'success',
                'danger',
                'warning',
                'info',
            ],
            style: {
                'info': 'info',
                'success': 'success',
                'danger': 'danger',
                'warning': 'warning',
            },
            default: 'default',
            translation: 'LayoutManager.options.style',
            tooltip: 'panelStyle',
        },
        dynamicLogicStyled: {
            type: 'base',
            view: 'views/admin/field-manager/fields/dynamic-logic-conditions',
            tooltip: 'dynamicLogicStyled',
        },
        sticked: {
            type: 'bool',
            tooltip: 'sticked',
        },
        name: {
            readOnly: true,
        },
    }

    dataAttributesDynamicLogicDefs = {
        fields: {
            dynamicLogicStyled: {
                visible: {
                    conditionGroup: [
                        {
                            type: 'and',
                            value: [
                                {
                                    attribute: 'style',
                                    type: 'notEquals',
                                    value: 'default',
                                },
                                {
                                    attribute: 'style',
                                    type: 'isNotEmpty',
                                },
                            ]
                        }

                    ]
                }
            },
        }
    }

    editable = true
    ignoreList = []
    //ignoreTypeList = []
    viewType = 'detail'

    setup() {
        super.setup();

        this.dataAttributesDefs = Espo.Utils.cloneDeep(this.dataAttributesDefs);

        this.dataAttributesDefs.dynamicLogicVisible.scope = this.scope;
        this.dataAttributesDefs.dynamicLogicStyled.scope = this.scope;

        this.wait(true);

        this.loadLayout(() => {
            this.wait(false);
        });
    }

    loadLayout(callback) {
        this.getHelper().layoutManager.getOriginal(this.scope, this.type, this.setId, (layout) => {
            this.readDataFromLayout(layout);

            if (callback) {
                callback();
            }
        });
    }

    readDataFromLayout(layout) {
        const panelListAll = [];
        const labels = {};
        const params = {};

        layout = Espo.Utils.cloneDeep(layout);

        if (
            this.getMetadata().get(['clientDefs', this.scope, 'defaultSidePanel', this.viewType]) !== false &&
            !this.getMetadata().get(['clientDefs', this.scope, 'defaultSidePanelDisabled'])
        ) {
            panelListAll.push('default');

            labels['default'] = 'Default';
        }

        (this.getMetadata().get(['clientDefs', this.scope, 'sidePanels', this.viewType]) || [])
            .forEach(item => {
                if (!item.name) {
                    return;
                }

                panelListAll.push(item.name);

                if (item.label) {
                    labels[item.name] = item.label;
                }

                params[item.name] = item;
            });

        this.disabledFields = [];

        layout = layout || {};

        this.rowLayout = [];

        panelListAll.push('_delimiter_');

        if (!layout['_delimiter_']) {
            layout['_delimiter_'] = {
                disabled: true,
            };
        }

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
                    o.notEditable = true;

                    if (o.name === '_delimiter_') {
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
                o.notEditable = true;
                if (o.name === '_delimiter_') {
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
            return v1.index - v2.index;
        });
    }

    fetch() {
        const layout = {};

        $('#layout ul.disabled > li').each((i, el) => {
            const name = $(el).attr('data-name');

            layout[name] = {
                disabled: true,
            };
        });

        $('#layout ul.enabled > li').each((i, el) => {
            const $el = $(el);
            const o = {};

            const name = $el.attr('data-name');

            const attributes = this.itemsData[name] || {};

            attributes.name = name;

            this.dataAttributeList.forEach(attribute => {
                if (attribute === 'name') {
                    return;
                }

                if (attribute in attributes) {
                    o[attribute] = attributes[attribute];
                }
            });

            o.index = i;

            layout[name] = o;
        })

        return layout;
    }
}

export default LayoutSidePanelsDetailView;
