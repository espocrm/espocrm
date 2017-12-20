/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

Espo.define('views/fields/phone', 'views/fields/varchar', function (Dep) {

    return Dep.extend({

        type: 'phone',

        editTemplate: 'fields/phone/edit',

        detailTemplate: 'fields/phone/detail',

        listTemplate: 'fields/phone/list',

        validations: ['required'],

        validateRequired: function () {
            if (this.isRequired()) {
                if (!this.model.get(this.name) || !this.model.get(this.name) === '') {
                    var msg = this.translate('fieldIsRequired', 'messages').replace('{field}', this.translate(this.name, 'fields', this.model.name));
                    this.showValidationMessage(msg, 'div.phone-number-block:nth-child(1) input');
                    return true;
                }
            }
        },

        data: function () {
            var phoneNumberData;
            if (this.mode == 'edit') {
                phoneNumberData = Espo.Utils.cloneDeep(this.model.get(this.dataFieldName));

                if (this.model.isNew() || !this.model.get(this.name)) {
                    if (!phoneNumberData || !phoneNumberData.length) {
                         phoneNumberData = [{
                            phoneNumber: this.model.get(this.name) || '',
                            primary: true,
                            type: this.defaultType
                        }];
                    }
                }
            } else {
                phoneNumberData = this.model.get(this.dataFieldName) || false;
            }

            if ((!phoneNumberData || phoneNumberData.length === 0) && this.model.get(this.name)) {
                 phoneNumberData = [{
                    phoneNumber: this.model.get(this.name),
                    primary: true,
                    primary: true,
                    type: this.defaultType
                }];
            }

            return _.extend({
                phoneNumberData: phoneNumberData,
                doNotCall: this.model.get('doNotCall')
            }, Dep.prototype.data.call(this));
        },

        events: {
            'click [data-action="switchPhoneProperty"]': function (e) {
                var $target = $(e.currentTarget);
                var $block = $(e.currentTarget).closest('div.phone-number-block');
                var property = $target.data('property-type');


                if (property == 'primary') {
                    if (!$target.hasClass('active')) {
                        if ($block.find('input.phone-number').val() != '') {
                            this.$el.find('button.phone-property[data-property-type="primary"]').removeClass('active').children().addClass('text-muted');
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

            'click [data-action="removePhoneNumber"]': function (e) {
                var $block = $(e.currentTarget).closest('div.phone-number-block');
                if ($block.parent().children().size() == 1) {
                    $block.find('input.phone-number').val('');
                } else {
                    this.removePhoneNumberBlock($block);
                }
                this.trigger('change');
            },

            'change input.phone-number': function (e) {
                var $input = $(e.currentTarget);
                var $block = $input.closest('div.phone-number-block');

                if ($input.val() == '') {
                    if ($block.parent().children().size() == 1) {
                        $block.find('input.phone-number').val('');
                    } else {
                        this.removePhoneNumberBlock($block);
                    }
                }

                this.trigger('change');

                this.manageAddButton();
            },

            'keypress input.phone-number': function (e) {
                this.manageAddButton();
            },

            'paste input.phone-number': function (e) {
                setTimeout(function () {
                    this.manageAddButton();
                }.bind(this), 10);
            },

            'click [data-action="addPhoneNumber"]': function () {
                var data = Espo.Utils.cloneDeep(this.fetchPhoneNumberData());

                o = {
                    phoneNumber: '',
                    primary: data.length ? false : true,
                    type: false
                };

                data.push(o);

                this.model.set(this.dataFieldName, data, {silent: true});
                this.render();
            },
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);
            this.manageButtonsVisibility();
            this.manageAddButton();
        },

        removePhoneNumberBlock: function ($block) {
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

        manageAddButton: function () {
            var $input = this.$el.find('input.phone-number');
            c = 0;
            $input.each(function (i, input) {
                if (input.value != '') {
                    c++;
                }
            });

            if (c == $input.size()) {
                this.$el.find('[data-action="addPhoneNumber"]').removeClass('disabled');
            } else {
                this.$el.find('[data-action="addPhoneNumber"]').addClass('disabled');
            }
        },

        manageButtonsVisibility: function () {
            var $primary = this.$el.find('button[data-property-type="primary"]');
            var $remove = this.$el.find('button[data-action="removePhoneNumber"]');
            if ($primary.size() > 1) {
                $primary.removeClass('hidden');
                $remove.removeClass('hidden');
            } else {
                $primary.addClass('hidden');
                $remove.addClass('hidden');
            }
        },

        setup: function () {
            this.dataFieldName = this.name + 'Data';
            this.defaultType = this.defaultType || this.getMetadata().get('entityDefs.' + this.model.name + '.fields.' + this.name + '.defaultType');

            if (this.model.has('doNotCall')) {
                this.listenTo(this.model, 'change:doNotCall', function (model, value, o) {
                    if (this.mode !== 'detail' && this.mode !== 'list') return;
                    if (!o.ui) return;
                    this.reRender();
                }, this);
            }
        },

        fetchPhoneNumberData: function () {
            var data = [];

            var $list = this.$el.find('div.phone-number-block');

            if ($list.size()) {
                $list.each(function (i, d) {
                    var row = {};
                    var $d = $(d);
                    row.phoneNumber = $d.find('input.phone-number').val().trim();
                    if (row.phoneNumber == '') {
                        return;
                    }
                    row.primary = $d.find('button[data-property-type="primary"]').hasClass('active');
                    row.type = $d.find('select[data-property-type="type"]').val();
                    data.push(row);
                }.bind(this));
            }

            return data;
        },

        fetch: function () {
            var data = {};

            var adderssData = this.fetchPhoneNumberData() || [];
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
                data[this.name] = adderssData[0].phoneNumber;
            }

            return data;
        }

    });

});


