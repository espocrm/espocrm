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

Espo.define('Views.Fields.LinkParent', 'Views.Fields.Base', function (Dep) {

    return Dep.extend({

        type: 'linkParent',

        listTemplate: 'fields.link.list',

        detailTemplate: 'fields.link.detail',

        editTemplate: 'fields.link-parent.edit',

        searchTemplate: 'fields.link-parent.search',

        nameName: null,

        idName: null,

        foreignScopeList: null,

        AUTOCOMPLETE_RESULT_MAX_COUNT: 7,

        data: function () {
            return _.extend({
                idName: this.idName,
                nameName: this.nameName,
                typeName: this.typeName,
                idValue: this.model.get(this.idName),
                nameValue: this.model.get(this.nameName),
                typeValue: this.model.get(this.typeName),
                foreignScope: this.foreignScope,
                foreignScopeList: this.foreignScopeList,
            }, Dep.prototype.data.call(this));
        },

        getSelectFilters: function () {},

        getSelectBoolFilterList: function () {},

        getPrimaryFilterName: function () {},

        setup: function () {
            this.nameName = this.name + 'Name';
            this.typeName = this.name + 'Type';
            this.idName = this.name + 'Id';

            this.foreignScopeList = this.model.defs.links[this.name].entityList || [];

            if (this.mode == 'edit' && this.foreignScopeList.length == 0) {
                throw new Error('Bad parent link defenition. Model list is empty.');
            }

            this.foreignScope = this.model.get(this.typeName) || this.foreignScopeList[0];

            this.listenTo(this.model, 'change:' + this.typeName, function () {
                this.foreignScope = this.model.get(this.typeName) || this.foreignScopeList[0];
            }.bind(this));


            var self = this;

            if (this.mode != 'list') {
                this.addActionHandler('selectLink', function () {
                    this.notify('Loading...');
                    this.createView('dialog', 'Modals.SelectRecords', {
                        scope: this.foreignScope,
                        createButton: this.mode != 'search',
                        filters: this.getSelectFilters(),
                        boolFilterList: this.getSelectBoolFilterList(),
                        primaryFilterName: this.getPrimaryFilterName(),
                    }, function (dialog) {
                        dialog.render();
                        Espo.Ui.notify(false);
                        dialog.once('select', function (model) {
                            self.$elementName.val(model.get('name'));
                            self.$elementId.val(model.get('id'));
                            self.trigger('change');
                        });
                    });
                });
                this.addActionHandler('clearLink', function () {
                    this.$elementName.val('');
                    this.$elementId.val('');
                    this.trigger('change');
                });

                this.events['change select[name="' + this.typeName + '"]'] = function (e) {
                    this.foreignScope = e.currentTarget.value;
                    this.$elementName.val('');
                    this.$elementId.val('');
                }
            }
        },

        getAutocompleteUrl: function () {
            return this.foreignScope + '?sortBy=name&maxCount=' + this.AUTOCOMPLETE_RESULT_MAX_COUNT;
        },

        afterRender: function () {
            if (this.mode == 'edit' || this.mode == 'search') {
                this.$elementId = this.$el.find('input[name="' + this.idName + '"]');
                this.$elementName = this.$el.find('input[name="' + this.nameName + '"]');
                this.$elementType = this.$el.find('select[name="' + this.typeName + '"]');

                this.$elementName.on('change', function () {
                    if (this.$elementName.val() == '') {
                        this.$elementName.val('');
                        this.$elementId.val('');
                        this.trigger('change');
                    }
                }.bind(this));

                this.$elementType.on('change', function () {
                    this.$elementName.val('');
                    this.$elementId.val('');
                    this.trigger('change');
                }.bind(this));

                if (this.mode == 'edit') {
                    this.$elementName.on('blur', function (e) {
                        if (this.model.has(this.nameName)) {
                            e.currentTarget.value = this.model.get(this.nameName);
                        }
                    }.bind(this));
                } else if (this.mode == 'search') {
                    this.$elementName.on('blur', function (e) {
                        e.currentTarget.value = '';
                    }.bind(this));
                }

                this.$elementName.autocomplete({
                    serviceUrl: function (q) {
                        return this.getAutocompleteUrl(q);
                    }.bind(this),
                    minChars: 1,
                    paramName: 'q',
                       formatResult: function (suggestion) {
                        return suggestion.name;
                    },
                    transformResult: function (response) {
                        var response = JSON.parse(response);
                        var list = [];
                        response.list.forEach(function(item) {
                            list.push({
                                id: item.id,
                                name: item.name,
                                data: item.id,
                                value: item.name
                            });
                        }, this);
                        return {
                            suggestions: list
                        };
                    }.bind(this),
                    onSelect: function (s) {
                        this.$elementId.val(s.id);
                        this.$elementName.val(s.name);
                        this.trigger('change');
                    }.bind(this)
                });

                var $elementName = this.$elementName;


                $elementName.on('change', function () {
                    if (!this.model.get(this.idName)) {
                        $elementName.val(this.model.get(this.nameName));
                    }
                }.bind(this));

                this.once('render', function () {
                    $elementName.autocomplete('dispose');
                }, this);

                this.once('remove', function () {
                    $elementName.autocomplete('dispose');
                }, this);
            }
        },

        getValueForDisplay: function () {
            return this.model.get(this.model.get(this.nameName));
        },

        validateRequired: function () {
            if (this.params.required || this.model.isRequired(this.name)) {
                if (this.model.get(this.idName) == null || !this.model.get(this.typeName)) {
                    var msg = this.translate('fieldIsRequired', 'messages').replace('{field}', this.translate(this.name, 'fields', this.model.name));
                    this.showValidationMessage(msg);
                    return true;
                }
            }
        },

        fetch: function () {
            var data = {};
            data[this.typeName] = this.$elementType.val() || null;
            data[this.nameName] = this.$elementName.val() || null;
            data[this.idName] = this.$elementId.val() || null;
            return data;
        },

        fetchSearch: function () {
            var id = this.$elementId.val();
            var type = this.$elementType.val();
            var name = this.$elementName.val()

            if (!name || !type) {
                return false;
            }

            var data = {
                type: 'and',
                field: this.idName,

                value: [
                    {
                        type: 'equals',
                        field: this.idName,
                        value: id,
                    },
                    {
                        type: 'equals',
                        field: this.typeName,
                        value: type,
                    }
                ],
                valueId: id,
                valueName: name,
                valueType: type,
            };
            return data;
        },
    });
});


