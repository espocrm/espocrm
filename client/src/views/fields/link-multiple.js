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
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

Espo.define('views/fields/link-multiple', 'views/fields/base', function (Dep) {

    return Dep.extend({

        type: 'linkMultiple',

        listTemplate: 'fields/link-multiple/detail',

        detailTemplate: 'fields/link-multiple/detail',

        editTemplate: 'fields/link-multiple/edit',

        searchTemplate: 'fields/link-multiple/search',

        nameHashName: null,

        idsName: null,

        nameHash: null,

        foreignScope: null,

        AUTOCOMPLETE_RESULT_MAX_COUNT: 7,

        autocompleteDisabled: false,

        selectRecordsView: 'views/modals/select-records',

        createDisabled: false,

        sortable: false,

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

        getSelectBoolFilterList: function () {
            return this.selectBoolFilterList;
        },

        getSelectPrimaryFilterName: function () {
            return this.selectPrimaryFilterName;
        },

        getCreateAttributes: function () {},

        setup: function () {
            this.nameHashName = this.name + 'Names';
            this.idsName = this.name + 'Ids';

            this.foreignScope = this.options.foreignScope || this.foreignScope || this.model.getFieldParam(this.name, 'entity') || this.model.getLinkParam(this.name, 'entity');

            if ('createDisabled' in this.options) {
                this.createDisabled = this.options.createDisabled;
            }

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

            this.sortable = this.sortable || this.params.sortable;

            if (this.mode != 'list') {
                this.addActionHandler('selectLink', function () {
                    self.notify('Loading...');

                    var viewName = this.getMetadata().get('clientDefs.' + this.foreignScope + '.modalViews.select')  || this.selectRecordsView;

                    this.createView('dialog', viewName, {
                        scope: this.foreignScope,
                        createButton: !this.createDisabled && this.mode != 'search',
                        filters: this.getSelectFilters(),
                        boolFilterList: this.getSelectBoolFilterList(),
                        primaryFilterName: this.getSelectPrimaryFilterName(),
                        multiple: true,
                        createAttributes: (this.mode === 'edit') ? this.getCreateAttributes() : null
                    }, function (dialog) {
                        dialog.render();
                        self.notify(false);
                        this.listenToOnce(dialog, 'select', function (models) {
                            if (Object.prototype.toString.call(models) !== '[object Array]') {
                                models = [models];
                            }
                            models.forEach(function (model) {
                                self.addLink(model.id, model.get('name'));
                            });
                        });
                    }, this);
                });

                this.events['click a[data-action="clearLink"]'] = function (e) {
                    var id = $(e.currentTarget).attr('data-id');
                    this.deleteLink(id);
                };
            }
        },

        getAutocompleteUrl: function () {
            var url = this.foreignScope + '?sortBy=name&maxCount=' + this.AUTOCOMPLETE_RESULT_MAX_COUNT;
            var boolList = this.getSelectBoolFilterList();
            var where = [];
            if (boolList) {
                url += '&' + $.param({'boolFilterList': boolList});
            }
            var primary = this.getSelectPrimaryFilterName();
            if (primary) {
                url += '&' + $.param({'primaryFilter': primary});
            }
            return url;
        },

        afterRender: function () {
            if (this.mode == 'edit' || this.mode == 'search') {
                this.$element = this.$el.find('input.main-element');

                var $element = this.$element;

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


                    this.once('render', function () {
                        $element.autocomplete('dispose');
                    }, this);

                    this.once('remove', function () {
                        $element.autocomplete('dispose');
                    }, this);
                }

                $element.on('change', function () {
                    $element.val('');
                });

                this.renderLinks();

                if (this.mode == 'edit') {
                    if (this.sortable) {
                        this.$el.find('.link-container').sortable({
                            stop: function () {
                                this.fetchFromDom();
                                this.trigger('change');
                            }.bind(this)
                        });
                    }
                }
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
            var $container = this.$el.find('.link-container');
            var $el = $('<div />').addClass('link-' + id).addClass('list-group-item').attr('data-id', id);
            $el.html(name + '&nbsp');
            $el.prepend('<a href="javascript:" class="pull-right" data-id="' + id + '" data-action="clearLink"><span class="glyphicon glyphicon-remove"></a>');
            $container.append($el);

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
            if (this.isRequired()) {
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

        fetchFromDom: function () {
            this.ids = [];
            this.$el.find('.link-container').children().each(function(i, li) {
                var id = $(li).attr('data-id');
                if (!id) return;
                this.ids.push(id);
            }.bind(this));
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


