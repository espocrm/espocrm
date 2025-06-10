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
                'primary',
            ],
            style: {
                'info': 'info',
                'success': 'success',
                'danger': 'danger',
                'warning': 'warning',
                'primary': 'primary',
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


    /**
     * @protected
     * @param {Record.<string, Record>} layout
     * @param {string} type
     * @param {function(): {panelListAll?: string[], params?: Record, labels?: Record}} [hook]
     * @return {{
     *     panelListAll: *[],
     *     disabledFields: *[],
     *     rowLayout: *[],
     *     params: {},
     *     itemsData: {},
     *     labels: {},
     * }}
     */
    getDataFromLayout(layout, type, hook) {
        const panelListAll = [];
        const labels = {};
        const params = {};
        const disabledFields = [];
        const rowLayout = []
        const itemsData = {};

        layout = Espo.Utils.cloneDeep(layout);

        if (!layout) {
            layout = {};
        }

        if (hook) {
            const additional = hook();

            if (additional.panelListAll) {
                additional.panelListAll.forEach(it => panelListAll.push(it));
            }

            if (additional.params) {
                for (const [key, it] of Object.entries(additional.params)) {
                    params[key] = it;
                }
            }

            if (additional.labels) {
                for (const [key, it] of Object.entries(additional.labels)) {
                    labels[key] = it;
                }
            }
        }

        (this.getMetadata().get(['clientDefs', this.scope, type, this.viewType]) || [])
            .forEach(/** Record */item => {
                if (item.reference) {
                    item = {
                        ...this.getMetadata().get(`app.clientRecord.panels.${item.reference}`),
                        ...item,
                    };
                }

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

                params[item.name] = item;
            });

        panelListAll.push('_delimiter_');

        if (!layout['_delimiter_']) {
            layout['_delimiter_'] = {disabled: true, index: 10000};
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

                    if (o.name === '_delimiter_') {
                        o.notEditable = true;
                        o.labelText = '. . .';
                    }
                }

                disabledFields.push(o);

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

            rowLayout.push(o);

            itemsData[o.name] = Espo.Utils.cloneDeep(o);
        });

        rowLayout.sort((v1, v2) => v1.index - v2.index);

        disabledFields.sort((v1, v2) => {
            if (v1.name === '_delimiter_') {
                return 1;
            }

            /** @type {string} */
            const label1 = labels[v1.name] || v1.name;
            /** @type {string} */
            const label2 = labels[v2.name] || v2.name;

            return label1.localeCompare(label2);
        });

        return {
            panelListAll,
            labels,
            params,
            disabledFields,
            rowLayout,
            itemsData,
        };
    }

    /**
     * @protected
     * @param {Record.<string, Record>} layout
     */
    readDataFromLayout(layout) {
        const data = this.getDataFromLayout(layout, 'sidePanels', () => {
            const panelListAll = [];
            const labels = {};

            if (
                this.getMetadata().get(`clientDefs.${this.scope}.defaultSidePanel.${this.viewType}`) !== false &&
                !this.getMetadata().get(`clientDefs.${this.scope}.defaultSidePanelDisabled`)
            ) {
                panelListAll.push('default');

                labels['default'] = 'Default';
            }

            return {panelListAll, labels};
        });

        this.disabledFields = data.disabledFields;
        this.rowLayout = data.rowLayout;
        this.itemsData = data.itemsData;
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
