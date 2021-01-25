/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

define('views/admin/layouts/bottom-panels-detail', 'views/admin/layouts/side-panels-detail', function (Dep) {

    return Dep.extend({

        hasStream: true,

        hasRelationships: true,

        readDataFromLayout: function (layout) {
            var panelListAll = [];
            var labels = {};
            var params = {};

            layout = Espo.Utils.cloneDeep(layout);

            if (
                this.hasStream &&
                this.getMetadata().get(['scopes', this.scope, 'stream'])
            ) {
                panelListAll.push('stream');

                labels['stream'] = this.translate('Stream');

                params['stream'] = {
                    name: 'stream',
                    sticked: true,
                    index: 2,
                };
            }

            (this.getMetadata().get(['clientDefs', this.scope, 'bottomPanels', this.viewType]) || []).forEach(function (item) {
                if (!item.name) {
                    return;
                }

                panelListAll.push(item.name);

                if (item.label) {
                    labels[item.name] = item.label;
                }

                params[item.name] = Espo.Utils.clone(item);

                if ('order' in item) {
                    params[item.name].index = item.order;
                }
            }, this);

            this.links = {};

            if (this.hasRelationships) {
                var linkDefs = this.getMetadata().get(['entityDefs', this.scope, 'links']) || {};
                Object.keys(linkDefs).forEach(function (link) {
                    if (linkDefs[link].disabled || linkDefs[link].layoutRelationshipsDisabled) {
                        return;
                    }

                    if (!~['hasMany', 'hasChildren'].indexOf(linkDefs[link].type)) {
                        return;
                    }

                    panelListAll.push(link);

                    labels[link] = this.translate(link, 'links', this.scope);

                    var item = {
                        name: link,
                        index: 5,
                    };
                    this.dataAttributeList.forEach(function (attribute) {
                        if (attribute in item) {
                            return;
                        }

                        var value = this.getMetadata().get(
                            ['clientDefs', this.scope, 'relationshipPanels', item.name, attribute]
                        );

                        if (value === null) {
                            return;
                        }

                        item[attribute] = value;
                    }, this);

                    this.links[link] = true;

                    params[item.name] = item;

                }, this);
            }

            this.disabledFields = [];

            layout = layout || {};

            this.rowLayout = [];

            panelListAll = panelListAll.sort(function (v1, v2) {
                return params[v1].index - params[v2].index
            }.bind(this));

            panelListAll.push('_delimiter_');

            if (!layout['_delimiter_']) {
                layout['_delimiter_'] = {
                    disabled: true,
                };
            }

            panelListAll.forEach(function (item, index) {
                var disabled = false;
                var itemData = layout[item] || {};
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

                var labelText;
                if (labels[item]) {
                    labelText = this.getLanguage().translate(labels[item], 'labels', this.scope);
                } else {
                    labelText = this.getLanguage().translate(item, 'panels', this.scope);
                }

                if (disabled) {
                    var o = {
                        name: item,
                        label: labelText,
                    };

                    if (o.name[0] === '_') {
                        o.notEditable = true;

                        if (o.name == '_delimiter_') {
                            o.label = '. . .';
                        }
                    }
                    this.disabledFields.push(o);
                } else {
                    var o = {
                        name: item,
                        label: labelText,
                    };

                    if (o.name[0] === '_') {
                        o.notEditable = true;
                        if (o.name == '_delimiter_') {
                            o.label = '. . .';
                        }
                    }

                    if (o.name in params) {
                        this.dataAttributeList.forEach(function (attribute) {
                            if (attribute === 'name') {
                                return;
                            }

                            var itemParams = params[o.name] || {};

                            if (attribute in itemParams) {
                                o[attribute] = itemParams[attribute];
                            }
                        }, this);
                    }

                    for (var i in itemData) {
                        o[i] = itemData[i];
                    }

                    o.index = ('index' in itemData) ? itemData.index : index;

                    this.rowLayout.push(o);

                    this.itemsData[o.name] = Espo.Utils.cloneDeep(o);
                }
            }, this);

            this.rowLayout.sort(function (v1, v2) {
                return (v1.index || 0) - (v2.index || 0);
            });
        },

        fetch: function () {
            var layout = Dep.prototype.fetch.call(this);

            var newLayout = {};

            for (var i in layout) {
                if (layout[i].disabled && this.links[i]) {
                    continue;
                }

                newLayout[i] = layout[i];
            }

            return newLayout;
        },
    });
});
