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

Espo.define('views/fields/link', 'views/fields/base', function (Dep) {

    return Dep.extend({

        type: 'link',

        listTemplate: 'fields/link/list',

        detailTemplate: 'fields/link/detail',

        editTemplate: 'fields/link/edit',

        searchTemplate: 'fields/link/search',

        nameName: null,

        idName: null,

        foreignScope: null,

        AUTOCOMPLETE_RESULT_MAX_COUNT: 7,

        selectRecordsView: 'views/modals/select-records',

        autocompleteDisabled: false,

        createDisabled: false,

        data: function () {
            return _.extend({
                idName: this.idName,
                nameName: this.nameName,
                idValue: this.model.get(this.idName),
                nameValue: this.model.get(this.nameName),
                foreignScope: this.foreignScope
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
            this.nameName = this.name + 'Name';
            this.idName = this.name + 'Id';

            this.foreignScope = this.options.foreignScope || this.foreignScope;
            this.foreignScope = this.foreignScope || this.model.getFieldParam(this.name, 'entity') || this.model.getLinkParam(this.name, 'entity');

            if ('createDisabled' in this.options) {
                this.createDisabled = this.options.createDisabled;
            }
            var self = this;

            if (this.mode != 'list') {
                this.addActionHandler('selectLink', function () {
                    this.notify('Loading...');

                    var viewName = this.getMetadata().get('clientDefs.' + this.foreignScope + '.modalViews.select') || this.selectRecordsView;

                    this.createView('dialog', viewName, {
                        scope: this.foreignScope,
                        createButton: !this.createDisabled && this.mode != 'search',
                        filters: this.getSelectFilters(),
                        boolFilterList: this.getSelectBoolFilterList(),
                        primaryFilterName: this.getSelectPrimaryFilterName(),
                        createAttributes: (this.mode === 'edit') ? this.getCreateAttributes() : null
                    }, function (view) {
                        view.render();
                        this.notify(false);
                        this.listenToOnce(view, 'select', function (model) {
                            this.select(model);
                        }, this);
                    }.bind(this));
                });
                this.addActionHandler('clearLink', function () {
                    this.clearLink();
                });
            }

            if (this.mode == 'search') {
                this.addActionHandler('selectLinkOneOf', function () {
                    this.notify('Loading...');

                    var viewName = this.getMetadata().get('clientDefs.' + this.foreignScope + '.modalViews.select') || this.selectRecordsView;

                    this.createView('dialog', viewName, {
                        scope: this.foreignScope,
                        createButton: !this.createDisabled && this.mode != 'search',
                        filters: this.getSelectFilters(),
                        boolFilterList: this.getSelectBoolFilterList(),
                        primaryFilterName: this.getSelectPrimaryFilterName(),
                        multiple: true
                    }, function (view) {
                        view.render();
                        this.notify(false);
                        this.listenToOnce(view, 'select', function (models) {
                            if (Object.prototype.toString.call(models) !== '[object Array]') {
                                models = [models];
                            }
                            models.forEach(function (model) {
                                this.addLinkOneOf(model.id, model.get('name'));
                            }, this);
                        });
                    }, this);
                });

                this.events['click a[data-action="clearLinkOneOf"]'] = function (e) {
                    var id = $(e.currentTarget).data('id').toString();
                    this.deleteLinkOneOf(id);
                };
            }
        },

        select: function (model) {
            this.$elementName.val(model.get('name'));
            this.$elementId.val(model.get('id'));
            this.trigger('change');
        },

        clearLink: function () {
            this.$elementName.val('');
            this.$elementId.val('');
            this.trigger('change');
        },

        setupSearch: function () {
            this.searchData.typeOptions = ['is', 'isEmpty', 'isNotEmpty', 'isOneOf'];
            this.searchData.oneOfIdList = this.searchParams.oneOfIdList || [];
            this.searchData.oneOfNameHash = this.searchParams.oneOfNameHash || {};

            this.events = _.extend({
                'change select.search-type': function (e) {
                    var type = $(e.currentTarget).val();
                    this.handleSearchType(type);
                },
            }, this.events || {});
        },

        handleSearchType: function (type) {
            if (~['is'].indexOf(type)) {
                this.$el.find('div.primary').removeClass('hidden');
            } else {
                this.$el.find('div.primary').addClass('hidden');
            }

            if (type === 'isOneOf') {
                this.$el.find('div.one-of-container').removeClass('hidden');
            } else {
                this.$el.find('div.one-of-container').addClass('hidden');
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
                this.$elementId = this.$el.find('input[name="' + this.idName + '"]');
                this.$elementName = this.$el.find('input[name="' + this.nameName + '"]');

                this.$elementName.on('change', function () {
                    if (this.$elementName.val() == '') {
                        this.$elementName.val('');
                        this.$elementId.val('');
                        this.trigger('change');
                    }
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

                var $elementName = this.$elementName;

                if (!this.autocompleteDisabled) {
                    this.$elementName.autocomplete({
                        serviceUrl: function (q) {
                            return this.getAutocompleteUrl(q);
                        }.bind(this),
                        paramName: 'q',
                        minChars: 1,
                        autoSelectFirst: true,
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
                                    value: item.name,
                                    attributes: item
                                });
                            }, this);
                            return {
                                suggestions: list
                            };
                        }.bind(this),
                        onSelect: function (s) {
                            this.getModelFactory().create(this.foreignScope, function (model) {
                                model.set(s.attributes);
                                this.select(model);
                            }, this);
                        }.bind(this)
                    });

                    this.once('render', function () {
                        $elementName.autocomplete('dispose');
                    }, this);

                    this.once('remove', function () {
                        $elementName.autocomplete('dispose');
                    }, this);


                    if (this.mode == 'search') {
                        var $elementOneOf = this.$el.find('input.element-one-of');
                        $elementOneOf.autocomplete({
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
                                this.addLinkOneOf(s.id, s.name);
                                $elementOneOf.val('');
                            }.bind(this)
                        });


                        this.once('render', function () {
                            $elementOneOf.autocomplete('dispose');
                        }, this);

                        this.once('remove', function () {
                            $elementOneOf.autocomplete('dispose');
                        }, this);
                    }
                }

                $elementName.on('change', function () {
                    if (!this.model.get(this.idName)) {
                        $elementName.val(this.model.get(this.nameName));
                    }
                }.bind(this));
            }

            if (this.mode == 'search') {
                var type = this.$el.find('select.search-type').val();
                this.handleSearchType(type);

                if (type == 'isOneOf') {
                    this.searchData.oneOfIdList.forEach(function (id) {
                        this.addLinkOneOfHtml(id, this.searchData.oneOfNameHash[id]);
                    }, this);
                }
            }
        },

        getValueForDisplay: function () {
            return this.model.get(this.nameName);
        },

        validateRequired: function () {
            if (this.isRequired()) {
                if (this.model.get(this.idName) == null) {
                    var msg = this.translate('fieldIsRequired', 'messages').replace('{field}', this.translate(this.name, 'fields', this.model.name));
                    this.showValidationMessage(msg);
                    return true;
                }
            }
        },

        deleteLinkOneOf: function (id) {
            this.deleteLinkOneOfHtml(id);

            var index = this.searchData.oneOfIdList.indexOf(id);
            if (index > -1) {
                this.searchParams.oneOfIdList.splice(index, 1);
            }
            delete this.searchParams.oneOfNameHash[id];
        },

        addLinkOneOf: function (id, name) {
            if (!~this.searchData.oneOfIdList.indexOf(id)) {
                this.searchData.oneOfIdList.push(id);
                this.searchData.oneOfNameHash[id] = name;
                this.addLinkOneOfHtml(id, name);
            }
        },

        deleteLinkOneOfHtml: function (id) {
            this.$el.find('.link-one-of-container .link-' + id).remove();
        },

        addLinkOneOfHtml: function (id, name) {
            var $container = this.$el.find('.link-one-of-container');
            var $el = $('<div />').addClass('link-' + id).addClass('list-group-item');
            $el.html(name + '&nbsp');
            $el.prepend('<a href="javascript:" class="pull-right" data-id="' + id + '" data-action="clearLinkOneOf"><span class="glyphicon glyphicon-remove"></a>');
            $container.append($el);

            return $el;
        },

        fetch: function () {
            var data = {};
            data[this.nameName] = this.$el.find('[name="'+this.nameName+'"]').val() || null;
            data[this.idName] = this.$el.find('[name="'+this.idName+'"]').val() || null;

            return data;
        },

        fetchSearch: function () {
            var type = this.$el.find('select.search-type').val();
            var value = this.$el.find('[name="' + this.idName + '"]').val();

            if (type == 'isEmpty') {
                var data = {
                    type: 'isNull',
                    typeFront: type,
                    field: this.idName
                };
                return data;
            } else if (type == 'isNotEmpty') {
                var data = {
                    type: 'isNotNull',
                    typeFront: type,
                    field: this.idName
                };
                return data;
            } else if (type == 'isOneOf') {
                var data = {
                    type: 'in',
                    typeFront: type,
                    field: this.idName,
                    value: this.searchData.oneOfIdList,
                    oneOfIdList: this.searchData.oneOfIdList,
                    oneOfNameHash: this.searchData.oneOfNameHash
                };
                return data;

            } else {
                if (!value) {
                    return false;
                }
                var data = {
                    type: 'equals',
                    typeFront: type,
                    field: this.idName,
                    value: value,
                    valueName: this.$el.find('[name="' + this.nameName + '"]').val(),
                };
                return data;
            }
        }
    });
});

