/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

Espo.define('views/admin/layouts/base', 'view', function (Dep) {

    return Dep.extend({

        scope: null,

        type: null,

        events: {
            'click button[data-action="save"]': function () {
                this.disableButtons();
                this.notify('Saving...');
                this.save(this.enableButtons.bind(this));
            },
            'click button[data-action="cancel"]': function () {
                this.cancel();
            },
            'click button[data-action="resetToDefault"]': function () {
                this.confirm(this.translate('confirmation', 'messages'), function () {
                    this.resetToDefault();
                }, this)
            },
        },

        buttonList: [
            {
                name: 'save',
                label: 'Save',
                style: 'primary',
            },
            {
                name: 'cancel',
                label: 'Cancel',
            },
            {
                name: 'resetToDefault',
                label: 'Reset to Default',
            }
        ],

        dataAttributes: null,

        dataAttributesDefs: null,

        disableButtons: function () {
            this.$el.find('.button-container button').attr('disabled', true);
        },

        enableButtons: function () {
            this.$el.find('.button-container button').removeAttr('disabled');
        },

        save: function (callback) {
            var layout = this.fetch();

            if (!this.validate(layout)) {
                this.enableButtons();
                return false;
            }

            this.getHelper().layoutManager.set(this.scope, this.type, layout, function () {
                this.notify('Saved', 'success', 2000);

                if (typeof callback == 'function') {
                    callback();
                }
            }.bind(this));
        },

        resetToDefault: function () {
            this.getHelper().layoutManager.resetToDefault(this.scope, this.type, function () {
                this.cancel();
            }.bind(this));
        },

        reset: function () {
            this.render();
        },

        fetch: function () {},

        setup: function () {
            this.buttonList = _.clone(this.buttonList);
            this.events = _.clone(this.events);
            this.scope = this.options.scope;
            this.type = this.options.type;

            this.dataAttributeList =
                this.getMetadata().get(['clientDefs', this.scope, 'additionalLayouts', this.type, 'dataAttributeList'])
                ||
                this.dataAttributeList;

            this.dataAttributeList = Espo.Utils.clone(this.dataAttributeList);
        },

        unescape: function (string) {
            if (string == null) {
                return '';
            }

            var map = {
                '&amp;': '&',
                '&lt;': '<',
                '&gt;': '>',
                '&quot;': '"',
                '&#x27;': "'"
            };
            var reg = new RegExp('(' + _.keys(map).join('|') + ')', 'g');

            return ('' + string).replace(reg, function (match) {
                return map[match];
            });
        },

        openEditDialog: function (attributes) {
            var name = attributes.name;
            this.createView('editModal', 'views/admin/layouts/modals/edit-attributes', {
                name: attributes.name,
                scope: this.scope,
                attributeList: this.dataAttributeList,
                attributeDefs: this.dataAttributesDefs,
                attributes: attributes,
                languageCategory: this.languageCategory
            }, function (view) {
                view.render();
                this.listenToOnce(view, 'after:save', function (attributes) {
                    this.trigger('update-item', name, attributes);
                    var $li = $("#layout ul > li[data-name='" + name + "']");
                    for (var key in attributes) {
                        $li.attr('data-' + key, attributes[key]);
                        $li.data(key, attributes[key]);
                        $li.find('.' + key + '-value').text(attributes[key]);
                    }
                    view.close();
                }, this);
            }.bind(this));
        },

        cancel: function () {
            this.loadLayout(function () {
                this.reRender();
            }.bind(this));
        },

        validate: function (layout) {
            return true;
        },
    });
});

