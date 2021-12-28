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

define('views/fields/email', 'views/fields/varchar', function (Dep) {

    return Dep.extend({

        type: 'email',

        editTemplate: 'fields/email/edit',

        detailTemplate: 'fields/email/detail',

        listTemplate: 'fields/email/list',

        validations: ['required', 'emailData'],

        validateEmailData: function () {
            var data = this.model.get(this.dataFieldName);
            if (!data || !data.length) return;

            var addressList = [];

            var re = /^[-!#$%&'*+/=?^_`{|}~A-Za-z0-9]+(?:\.[-!#$%&'*+/=?^_`{|}~A-Za-z0-9]+)*@([A-Za-z0-9]([A-Za-z0-9-]*[A-Za-z0-9])?\.)+[A-Za-z0-9][A-Za-z0-9-]*[A-Za-z0-9]/;

            var notValid = false;

            data.forEach(function (row, i) {
                var address = row.emailAddress || '';
                var addressLowerCase = String(address).toLowerCase();
                if (!re.test(addressLowerCase) && address.indexOf(this.erasedPlaceholder) !== 0) {
                    var msg = this.translate('fieldShouldBeEmail', 'messages').replace('{field}', this.getLabelText());
                    this.reRender();
                    this.showValidationMessage(msg, 'div.email-address-block:nth-child(' + (i + 1).toString() + ') input');
                    notValid = true;
                    return;
                }
                if (~addressList.indexOf(addressLowerCase)) {
                    var msg = this.translate('fieldValueDuplicate', 'messages').replace('{field}', this.getLabelText());
                    this.showValidationMessage(msg, 'div.email-address-block:nth-child(' + (i + 1).toString() + ') input');
                    notValid = true;
                    return;
                }
                addressList.push(addressLowerCase);
            }, this);
            if (notValid) {
                return true;
            }
        },

        validateRequired: function () {
            if (this.isRequired()) {
                if (!this.model.get(this.name) || !this.model.get(this.name) === '') {
                    var msg = this.translate('fieldIsRequired', 'messages').replace('{field}', this.getLabelText());
                    this.showValidationMessage(msg, 'div.email-address-block:nth-child(1) input');
                    return true;
                }
            }
        },

        data: function () {
            var emailAddressData;
            if (this.mode == 'edit') {
                emailAddressData = Espo.Utils.clone(this.model.get(this.dataFieldName));

                if (this.model.isNew() || !this.model.get(this.name)) {
                    if (!emailAddressData || !emailAddressData.length) {
                        var optOut = false;
                        if (this.model.isNew()) {
                            optOut = this.emailAddressOptedOutByDefault && this.model.name !== 'User';
                        } else {
                            optOut = this.model.get(this.isOptedOutFieldName)
                        }
                        emailAddressData = [{
                            emailAddress: this.model.get(this.name) || '',
                            primary: true,
                            optOut: optOut,
                            invalid: false
                        }];
                    }
                }

            } else {
                emailAddressData = this.model.get(this.dataFieldName) || false;
            }

            if ((!emailAddressData || emailAddressData.length === 0) && this.model.get(this.name)) {
                emailAddressData = [{
                    emailAddress: this.model.get(this.name),
                    primary: true,
                    optOut: false,
                    invalid: false
                }];
            }

            if (emailAddressData) {
                emailAddressData = Espo.Utils.cloneDeep(emailAddressData);
                emailAddressData.forEach(function (item) {
                    var address = item.emailAddress || '';
                    item.erased = address.indexOf(this.erasedPlaceholder) === 0;
                    item.lineThrough = item.optOut || item.invalid;
                }, this);
            }

            var data = _.extend({
                emailAddressData: emailAddressData
            }, Dep.prototype.data.call(this));

            if (this.isReadMode()) {
                data.isOptedOut = this.model.get(this.isOptedOutFieldName);
                if (this.model.get(this.name)) {
                    data.isErased = this.model.get(this.name).indexOf(this.erasedPlaceholder) === 0
                }
                data.valueIsSet = this.model.has(this.name);
            }

            data.itemMaxLength = this.itemMaxLength;

            return data;
        },

        getAutocompleteMaxCount: function () {
            if (this.autocompleteMaxCount) {
                return this.autocompleteMaxCount;
            }
            return this.getConfig().get('recordsPerPage');
        },

        events: {
            'click [data-action="mailTo"]': function (e) {
                this.mailTo($(e.currentTarget).data('email-address'));
            },
            'click [data-action="switchEmailProperty"]': function (e) {
                var $target = $(e.currentTarget);
                var $block = $(e.currentTarget).closest('div.email-address-block');
                var property = $target.data('property-type');


                if (property == 'primary') {
                    if (!$target.hasClass('active')) {
                        if ($block.find('input.email-address').val() != '') {
                            this.$el.find('button.email-property[data-property-type="primary"]').removeClass('active').children().addClass('text-muted');
                            $target.addClass('active').children().removeClass('text-muted');
                        }
                    }
                } else {
                    if ($target.hasClass('active')) {
                        $target.removeClass('active').children().addClass('text-muted');
                    } else {
                        $target.addClass('active').children().removeClass('text-muted');
                    }
                }
                this.trigger('change');
            },

            'click [data-action="removeEmailAddress"]': function (e) {
                var $block = $(e.currentTarget).closest('div.email-address-block');
                if ($block.parent().children().length == 1) {
                    $block.find('input.email-address').val('');
                } else {
                    this.removeEmailAddressBlock($block);
                }
                this.trigger('change');
            },

            'change input.email-address': function (e) {
                var $input = $(e.currentTarget);
                var $block = $input.closest('div.email-address-block');

                if ($input.val() == '') {
                    if ($block.parent().children().length == 1) {
                        $block.find('input.email-address').val('');
                    } else {
                        this.removeEmailAddressBlock($block);
                    }
                }

                this.trigger('change');

                this.manageAddButton();
            },

            'keypress input.email-address': function (e) {
                this.manageAddButton();
            },
            'paste input.email-address': function (e) {
                setTimeout(function () {
                    this.manageAddButton();
                }.bind(this), 10);
            },
            'click [data-action="addEmailAddress"]': function () {
                var data = Espo.Utils.cloneDeep(this.fetchEmailAddressData());

                o = {
                    emailAddress: '',
                    primary: data.length ? false : true,
                    optOut: this.emailAddressOptedOutByDefault,
                    invalid: false,
                    lower: ''
                };

                data.push(o);

                this.model.set(this.dataFieldName, data, {silent: true});
                this.render();
            },
        },

        removeEmailAddressBlock: function ($block) {
            var changePrimary = false;
            if ($block.find('button[data-property-type="primary"]').hasClass('active')) {
                changePrimary = true;
            }
            $block.remove();

            if (changePrimary) {
                this.$el.find('button[data-property-type="primary"]').first().addClass('active').children().removeClass('text-muted');
            }

            this.manageButtonsVisibility();
            this.manageAddButton();
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);
            this.manageButtonsVisibility();
            this.manageAddButton();


            if (this.mode == 'search' && this.getAcl().check('Email', 'create')) {
                this.$element.autocomplete({
                    serviceUrl: function (q) {
                        return 'EmailAddress/action/searchInAddressBook?maxSize=' + this.getAutocompleteMaxCount();
                    }.bind(this),
                    paramName: 'q',
                    minChars: 1,
                    autoSelectFirst: true,
                    triggerSelectOnValidInput: false,
                    formatResult: function (suggestion) {
                        return this.getHelper().escapeString(suggestion.name) + ' &#60;' + this.getHelper().escapeString(suggestion.id) + '&#62;';
                    }.bind(this),
                    transformResult: function (response) {
                        var response = JSON.parse(response);
                        var list = [];
                        response.forEach(function(item) {
                            list.push({
                                id: item.emailAddress,
                                name: item.entityName,
                                emailAddress: item.emailAddress,
                                entityId: item.entityId,
                                entityName: item.entityName,
                                entityType: item.entityType,
                                data: item.emailAddress,
                                value: item.emailAddress
                            });
                        }, this);
                        return {
                            suggestions: list
                        };
                    }.bind(this),
                    onSelect: function (s) {
                        this.$element.val(s.emailAddress);
                    }.bind(this)
                });
            }
        },

        manageAddButton: function () {
            var $input = this.$el.find('input.email-address');
            c = 0;
            $input.each(function (i, input) {
                if (input.value != '') {
                    c++;
                }
            });

            if (c == $input.length) {
                this.$el.find('[data-action="addEmailAddress"]').removeClass('disabled').removeAttr('disabled');
            } else {
                this.$el.find('[data-action="addEmailAddress"]').addClass('disabled').attr('disabled', 'disabled');
            }
        },

        manageButtonsVisibility: function () {
            var $primary = this.$el.find('button[data-property-type="primary"]');
            var $remove = this.$el.find('button[data-action="removeEmailAddress"]');
            if ($primary.length > 1) {
                $primary.removeClass('hidden');
                $remove.removeClass('hidden');
            } else {
                $primary.addClass('hidden');
                $remove.addClass('hidden');
            }
        },

        mailTo: function (emailAddress) {
            var attributes = {
                status: 'Draft',
                to: emailAddress
            };

            var scope = this.model.name;

            switch (scope) {
                case 'Account':
                case 'Lead':
                    attributes.parentType = scope;
                    attributes.parentName = this.model.get('name');
                    attributes.parentId = this.model.id;
                    break;
                case 'Contact':
                    if (this.getConfig().get('b2cMode')) {
                        attributes.parentType = 'Contact';
                        attributes.parentName = this.model.get('name');
                        attributes.parentId = this.model.id;
                    } else {
                        if (this.model.get('accountId')) {
                            attributes.parentType = 'Account';
                            attributes.parentName = this.model.get('accountName');
                            attributes.parentId = this.model.get('accountId');
                        }
                    }
                    break;
            }

            if (this.model.collection && this.model.collection.parentModel) {
                if (this.checkParentTypeAvailability(this.model.collection.parentModel.entityType)) {
                    attributes.parentType = this.model.collection.parentModel.entityType;
                    attributes.parentId = this.model.collection.parentModel.id;
                    attributes.parentName = this.model.collection.parentModel.get('name');
                }
            }

            if (!attributes.parentId) {
                if (this.checkParentTypeAvailability(this.model.name)) {
                    attributes.parentType = this.model.name;
                    attributes.parentId = this.model.id;
                    attributes.parentName = this.model.get('name');
                }
            } else {
                if (attributes.parentType && !this.checkParentTypeAvailability(attributes.parentType)) {
                    attributes.parentType = null;
                    attributes.parentId = null;
                    attributes.parentName = null;
                }
            }


            if (~['Contact', 'Lead', 'Account'].indexOf(this.model.name)) {
                attributes.nameHash = {};
                attributes.nameHash[emailAddress] = this.model.get('name');
            }

            if (
                this.getConfig().get('emailForceUseExternalClient') ||
                this.getPreferences().get('emailUseExternalClient') ||
                !this.getAcl().checkScope('Email', 'create')
            ) {
                require('email-helper', function (EmailHelper) {
                    var emailHelper = new EmailHelper();
                    var link = emailHelper.composeMailToLink(attributes, this.getConfig().get('outboundEmailBccAddress'));
                    document.location.href = link;
                }.bind(this));

                return;
            }

            var viewName = this.getMetadata().get('clientDefs.' + this.scope + '.modalViews.compose') || 'views/modals/compose-email';

            this.notify('Loading...');
            this.createView('quickCreate', viewName, {
                attributes: attributes,
            }, function (view) {
                view.render();
                view.notify(false);
            });
        },

        checkParentTypeAvailability: function (parentType) {
            return ~(this.getMetadata().get(['entityDefs', 'Email', 'fields', 'parent', 'entityList']) || []).indexOf(parentType);
        },

        setup: function () {
            this.dataFieldName = this.name + 'Data';
            this.isOptedOutFieldName = this.name + 'IsOptedOut';

            this.erasedPlaceholder = 'ERASED:';

            this.emailAddressOptedOutByDefault = this.getConfig().get('emailAddressIsOptedOutByDefault');

            this.itemMaxLength = this.getMetadata().get(['entityDefs', 'EmailAddress', 'fields', 'name', 'maxLength']) || 255;
        },

        fetchEmailAddressData: function () {
            var data = [];

            var $list = this.$el.find('div.email-address-block');

            if ($list.length) {
                $list.each(function (i, d) {
                    var row = {};
                    var $d = $(d);
                    row.emailAddress = $d.find('input.email-address').val().trim();
                    if (row.emailAddress == '') {
                        return;
                    }
                    row.primary = $d.find('button[data-property-type="primary"]').hasClass('active');
                    row.optOut = $d.find('button[data-property-type="optOut"]').hasClass('active');
                    row.invalid = $d.find('button[data-property-type="invalid"]').hasClass('active');
                    row.lower = row.emailAddress.toLowerCase()
                    data.push(row);
                }.bind(this));
            }

            return data;
        },


        fetch: function () {
            var data = {};

            var addressData = this.fetchEmailAddressData() || [];
            data[this.dataFieldName] = addressData;
            data[this.name] = null;
            data[this.isOptedOutFieldName] = false;

            var primaryIndex = 0;
            addressData.forEach(function (item, i) {
                if (item.primary) {
                    primaryIndex = i;
                    if (item.optOut) {
                        data[this.isOptedOutFieldName] = true;
                    }
                    return;
                }
            }, this);

            if (addressData.length && primaryIndex > 0) {
                var t = addressData[0];
                addressData[0] = addressData[primaryIndex];
                addressData[primaryIndex] = t;
            }

            if (addressData.length) {
                data[this.name] = addressData[0].emailAddress;
            } else {
                data[this.isOptedOutFieldName] = null;
            }

            return data;
        }

    });
});
