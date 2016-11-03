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

Espo.define('views/fields/email', 'views/fields/varchar', function (Dep) {

    return Dep.extend({

        type: 'email',

        editTemplate: 'fields/email/edit',

        detailTemplate: 'fields/email/detail',

        listTemplate: 'fields/email/list',

        validations: ['required', 'emailData'],

        validateEmailData: function () {
            var data = this.model.get(this.dataFieldName);
            if (data && data.length) {
                var re = /\S+@+\S+/;
                var notValid = false;
                data.forEach(function (row, i) {
                    var emailAddress = row.emailAddress;
                    if (!re.test(emailAddress)) {
                        var msg = this.translate('fieldShouldBeEmail', 'messages').replace('{field}', this.translate(this.name, 'fields', this.model.name));
                        this.showValidationMessage(msg, 'div.email-address-block:nth-child(' + (i + 1).toString() + ') input');
                        notValid = true;
                    }
                }, this);
                if (notValid) {
                    return true;
                }
            }
        },

        validateRequired: function () {
            if (this.isRequired()) {
                if (!this.model.get(this.name) || !this.model.get(this.name) === '') {
                    var msg = this.translate('fieldIsRequired', 'messages').replace('{field}', this.translate(this.name, 'fields', this.model.name));
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
                         emailAddressData = [{
                            emailAddress: this.model.get(this.name) || '',
                            primary: true,
                            optOut: false,
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

            return _.extend({
                emailAddressData: emailAddressData
            }, Dep.prototype.data.call(this));
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
                if ($block.parent().children().size() == 1) {
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
                    if ($block.parent().children().size() == 1) {
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
                    optOut: false,
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


            if (this.mode == 'search') {
                this.$element.autocomplete({
                    serviceUrl: function (q) {
                        return 'EmailAddress/action/searchInAddressBook?limit=5';
                    }.bind(this),
                    paramName: 'q',
                    minChars: 1,
                    autoSelectFirst: true,
                    formatResult: function (suggestion) {
                        return suggestion.name + ' &#60;' + suggestion.id + '&#62;';
                    },
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

            if (c == $input.size()) {
                this.$el.find('[data-action="addEmailAddress"]').removeClass('disabled');
            } else {
                this.$el.find('[data-action="addEmailAddress"]').addClass('disabled');
            }
        },

        manageButtonsVisibility: function () {
            var $primary = this.$el.find('button[data-property-type="primary"]');
            var $remove = this.$el.find('button[data-action="removeEmailAddress"]');
            if ($primary.size() > 1) {
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

            if (~['Contact', 'Lead', 'Account'].indexOf(this.model.name)) {
                attributes.nameHash = {};
                attributes.nameHash[emailAddress] = this.model.get('name');
            }

            this.notify('Loading...');
            this.createView('quickCreate', 'views/modals/compose-email', {
                attributes: attributes,
            }, function (view) {
                view.render();
                view.notify(false);
            });
        },

        setup: function () {
            this.dataFieldName = this.name + 'Data';
        },

        fetchEmailAddressData: function () {
            var data = [];

            var $list = this.$el.find('div.email-address-block');

            if ($list.size()) {
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

            var adderssData = this.fetchEmailAddressData() || [];
            data[this.dataFieldName] = adderssData;
            data[this.name] = null;

            var primaryIndex = 0;
            adderssData.forEach(function (item, i) {
                if (item.primary) {
                    primaryIndex = i;
                    return;
                }
            });

            if (adderssData.length && primaryIndex > 0) {
                var t = adderssData[0];
                adderssData[0] = adderssData[primaryIndex];
                adderssData[primaryIndex] = t;
            }

            if (adderssData.length) {
                data[this.name] = adderssData[0].emailAddress;
            }

            return data;
        }

    });
});

