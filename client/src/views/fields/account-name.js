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

Espo.define('views/fields/account-name', 'views/fields/varchar', function (Dep) {

    return Dep.extend({

        AUTOCOMPLETE_RESULT_MAX_COUNT: 7,

        type: 'accountName',

        generateDuplicateRow: function(item) {
            let html = '<div class="autocomplete-duplicate-container">';
            html += '<span style="float: left; margin-right: 10px;" class="glyphicon glyphicon-home"></span>';
            let address = typeof item.billingAddressCity == 'string' ? (', ' + item.billingAddressCity) : '';
            if (typeof item.billingAddressCity == 'string') {
                address = typeof item.billingAddressPostalCode == 'string' ? address + ', ' + item.billingAddressPostalCode : address;
            }
            html += '<span style="width: 65%; overflow: hidden; text-overflow: ellipsis"><span style="border-bottom: 1px dotted;">' + item.name + '</span>' + address + '</span>';
            html += '<span style="float: right; color: #999">' + item.entityType + '</span></div><!-- autocomplete-duplicate-container -->';
            return html;
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);

            if (this.mode == 'edit' && typeof this.model.id == 'undefined') {
                this.$element.autocomplete({
                    serviceUrl: [
                        'Account?sortBy=name&maxCount=' + this.AUTOCOMPLETE_RESULT_MAX_COUNT
                    ],
                    paramName: 'q',
                    minChars: 3,
                    autoSelectFirst: false,
                    transformResult: function (response) {
                        var list = [];
                        response.list.forEach(function(item) {
                            item.entityType = 'Company';
                            list.push({
                                item: item,
                                data: item.id,
                                value: this.generateDuplicateRow(item)
                            });
                        }, this);
                        return {
                            suggestions: list
                        };
                    }.bind(this),
                    onSelect: function (s) {
                        if (this.model.urlRoot == 'Lead') {

                            this.notify('Creating Contact instead...');

                            var viewName = this.getMetadata().get('clientDefs.Contact.modalViews.edit') || 'views/modals/edit';

                            //FIXME HACK
                            let model = {
                                id: s.item.id,
                                name: 'Account',
                                _name: s.item.name,
                                get: function(q) {
                                    return this["_"+q];
                                }
                            };

                            let attributes = {
                                accountId: s.item.id,
                                accountName: s.item.name,
                                addressCity: s.item.addressCity,
                                addressPostalCode: s.item.addressPostalCode,
                                addressState: s.item.addressState,
                                addressStreet: s.item.addressStreet
                            };

                            $('.modal-header > a')[0].click();

                            this.createView('quickCreate', viewName, {
                                scope: 'Contact',
                                relate: {
                                    model: model,
                                    link: 'accounts',
                                },
                                attributes: attributes,
                            }, function (view) {
                                view.render();
                                view.notify(false);
                            }.bind(this));
                        } else {
                            this.$element.val('');
                            $('.modal-header > a')[0].click();
                            window.location.href = this.getConfig().get('siteUrl') + '#Account/view/' + s.data;
                        }
                    }.bind(this)
                });

                this.once('render', function () {
                    this.$element.autocomplete('dispose');
                }, this);

                this.once('remove', function () {
                    this.$element.autocomplete('dispose');
                }, this);
            }
        }
    });
});

