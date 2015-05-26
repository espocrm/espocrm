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

Espo.define('Views.Fields.LinkMultiple', 'Views.Fields.Base', function (Dep) {

    return Dep.extend({

        type: 'linkMultiple',

        listTemplate: 'fields.link-multiple.detail',

        detailTemplate: 'fields.link-multiple.detail',

        editTemplate: 'fields.link-multiple.edit',

        searchTemplate: 'fields.link-multiple.search',

        nameHashName: null,

        idsName: null,

        nameHash: null,

        foreignScope: null,

        AUTOCOMPLETE_RESULT_MAX_COUNT: 7,

        autocompleteDisabled: false,

        selectRecordsViewName: 'Modals.SelectRecords',

        data: function () {
            var ids = this.model.get(this.idsName);

            return _.extend({
                idValues: this.model.get(this.idsName),
                idValuesString: ids ? ids.join(',') : '',
                nameHash: this.model.get(this.nameHashName),
                foreignScope: this.foreignScope,
            }, Dep.prototype.data.call(this));
        },

        getSelectFilters: function () {},

        getSelectBoolFilterList: function () {},

        getSelectPrimaryFilterName: function () {},

        setup: function () {
            this.nameHashName = this.name + 'Names';
            this.idsName = this.name + 'Ids';
            this.foreignScope = this.foreignScope || this.model.defs.links[this.name].entity;

            var self = this;

            this.ids = Espo.Utils.clone(this.model.get(this.idsName) || []);
            this.nameHash = Espo.Utils.clone(this.model.get(this.nameHashName) || {});

            if (this.mode == 'search') {
                this.nameHash = Espo.Utils.clone(this.searchParams.nameHash) || {};
                this.ids = Espo.Utils.clone(this.searchParams.value) || [];
            }

            this.listenTo(this.model, 'change:' + this.idsName, function () {
                this.ids = Espo.Utils.clone(this.model.get(this.idsName) || []);
                this.nameHash = Espo.Utils.clone(this.model.get(this.nameHashName) || {});
            }, this);


            if (this.mode != 'list') {
                this.addActionHandler('selectLink', function () {
                    self.notify('Loading...');

                    this.createView('dialog', this.selectRecordsViewName, {
                        scope: this.foreignScope,
                        createButton: this.mode != 'search',
                        filters: this.getSelectFilters(),
                        boolFilterList: this.getSelectBoolFilterList(),
                        primaryFilterName: this.getSelectPrimaryFilterName(),
                        multiple: true
                    }, function (dialog) {
                        dialog.render();
                        self.notify(false);
                        dialog.once('select', function (models) {
                            if (Object.prototype.toString.call(models) !== '[object Array]') {
                                models = [models];
                            }
                            models.forEach(function (model) {
                                self.addLink(model.id, model.get('name'));
                            });
                        });
                    });
                });

                this.events['click a[data-action="clearLink"]'] = function (e) {
                    var id = $(e.currentTarget).data('id').toString();
                    this.deleteLink(id);
                };
            }
        },

        getAutocompleteUrl: function () {
            var url = this.foreignScope + '?sortBy=name&maxCount=' + this.AUTOCOMPLETE_RESULT_MAX_COUNT;
            var boolList = this.getSelectBoolFilterList();
            if (boolList) {
                boolList.forEach(function(item) {
                    url += '&where%5B0%5D%5Btype%5D=bool&where%5B0%5D%5Bvalue%5D%5B%5D=' + item;
                }, this);
            }
            var primary = this.getSelectPrimaryFilterName();
            if (primary) {
                url += '&where%5B0%5D%5Btype%5D=primary&where%5B0%5D%5Bvalue%5D=' + primary;
            }
            return url;
        },

        afterRender: function () {
            if (this.mode == 'edit' || this.mode == 'search') {
                this.$element = this.$el.find('input.main-element');

                if (!this.autocompleteDisabled) {
                    this.$element.autocomplete({
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
                            this.addLink(s.id, s.name);
                            this.$element.val('');
                        }.bind(this)
                    });
                }


                var $element = this.$element;

                $element.on('change', function () {
                    $element.val('');
                });

                this.once('render', function () {
                    $element.autocomplete('dispose');
                }, this);

                this.once('remove', function () {
                    $element.autocomplete('dispose');
                }, this);

                this.renderLinks();

            }
        },

        renderLinks: function () {
            this.ids.forEach(function (id) {
                this.addLinkHtml(id, this.nameHash[id]);
            }, this);
        },

        deleteLink: function (id) {
            this.deleteLinkHtml(id);

            var index = this.ids.indexOf(id);
            if (index > -1) {
                this.ids.splice(index, 1);
            }
            delete this.nameHash[id];
            this.trigger('change');
        },

        addLink: function (id, name) {
            if (!~this.ids.indexOf(id)) {
                this.ids.push(id);
                this.nameHash[id] = name;
                this.addLinkHtml(id, name);
            }
            this.trigger('change');
        },

        deleteLinkHtml: function (id) {
            this.$el.find('.link-' + id).remove();
        },

        addLinkHtml: function (id, name) {
            var conteiner = this.$el.find('.link-container');
            var $el = $('<div />').addClass('link-' + id).addClass('list-group-item');
            $el.html(name + '&nbsp');
            $el.append('<a href="javascript:" class="pull-right" data-id="' + id + '" data-action="clearLink"><span class="glyphicon glyphicon-remove"></a>');
            conteiner.append($el);

            return $el;
        },

        getDetailLinkHtml: function (id) {
            return '<a href="#' + this.foreignScope + '/view/' + id + '">' + this.nameHash[id] + '</a>';
        },

        getValueForDisplay: function () {
            if (this.mode == 'detail' || this.mode == 'list') {
                var names = [];
                this.ids.forEach(function (id) {
                    names.push(this.getDetailLinkHtml(id));
                }, this);
                if (names.length) {
                    return '<div>' + names.join('</div><div>') + '</div>';
                }
                return;
            }
        },

        validateRequired: function () {
            if (this.model.isRequired(this.name)) {
                if (this.model.get(this.idsName).length == 0) {
                    var msg = this.translate('fieldIsRequired', 'messages').replace('{field}', this.translate(this.name, 'fields', this.model.name));
                    this.showValidationMessage(msg);
                    return true;
                }
            }
        },

        fetch: function () {
            var data = {};
            data[this.idsName] = this.ids;
            data[this.nameHashName] = this.nameHash;

            return data;
        },

        fetchSearch: function () {
            var values = this.ids || [];

            var data = {
                type: 'linkedWith',
                value: values,
                nameHash: this.nameHash
            };
            return data;
        },

    });
});


