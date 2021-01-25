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

define('views/dashlet', 'view', function (Dep) {

    return Dep.extend({

        name: null,

        id: null,

        elId: null,

        template: 'dashlet',

        data: function () {
            return {
                name: this.name,
                id: this.id,
                title: this.getTitle(),
                actionList: (this.getView('body') || {}).actionList || [],
                buttonList: (this.getView('body') || {}).buttonList || [],
                noPadding: (this.getView('body') || {}).noPadding
            };
        },

        events: {
            'click .action': function (e) {
                var $target = $(e.currentTarget);
                var action = $target.data('action');
                var data = $target.data();
                if (action) {
                    var method = 'action' + Espo.Utils.upperCaseFirst(action);
                    delete data['action'];
                    if (typeof this[method] == 'function') {
                        e.preventDefault();
                        this[method].call(this, data);
                    } else {
                        var bodyView = this.getView('body');
                        if (typeof bodyView[method] == 'function') {
                            e.preventDefault();
                            bodyView[method].call(bodyView, data);
                        }
                    }
                }
            },
        },

        setup: function () {
            this.name = this.options.name;
            this.id = this.options.id;

            this.on('resize', function () {
                var bodyView = this.getView('body');
                if (!bodyView) return;
                bodyView.trigger('resize');
            }, this);

            var viewName = this.getMetadata().get(['dashlets', this.name, 'view']) ||
                'views/dashlets/' + Espo.Utils.camelCaseToHyphen(this.name);

            this.createView('body', viewName, {
                el: this.options.el + ' .dashlet-body',
                id: this.id,
                name: this.name,
                readOnly: this.options.readOnly
            });
        },

        refresh: function () {
            this.getView('body').actionRefresh();
        },

        actionRefresh: function () {
            this.refresh();
        },

        actionOptions: function () {
            var optionsView =
                this.getMetadata().get(['dashlets', this.name, 'options', 'view']) ||
                this.optionsView ||
                'views/dashlets/options/base';

            this.createView('options', optionsView, {
                name: this.name,
                optionsData: this.getOptionsData(),
                fields: this.getView('body').optionsFields,
            }, function (view) {
                view.render();

                this.listenToOnce(view, 'save', function (attributes) {
                    var id = this.id;
                    this.notify('Saving...');
                    this.getPreferences().once('sync', function () {
                        this.getPreferences().trigger('update');
                        this.notify(false);
                        view.close();

                        this.trigger('change');
                    }, this);

                    var o = this.getPreferences().get('dashletsOptions') || {};
                    o[id] = attributes;

                    this.getPreferences().save({
                        dashletsOptions: o
                    }, {patch: true});
                }, this);
            }, this);
        },

        getOptionsData: function () {
            return this.getView('body').optionsData;
        },

        getOption: function (key) {
            return this.getView('body').getOption(key);
        },

        getTitle: function () {
            return this.getView('body').getTitle();
        },

        actionRemove: function () {
            this.confirm(this.translate('confirmation', 'messages'), function () {
                this.trigger('remove-dashlet');
                this.$el.remove();
                this.remove();
            }, this);
        }

    });
});
