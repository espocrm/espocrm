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


Espo.define('views/email-folder/modals/select-folder', 'views/modal', function (Dep) {

    return Dep.extend({

        cssName: 'select-folder',

        template: 'email-folder/modals/select-folder',

        fitHeight: true,

        data: function () {
            return {
                dashletList: this.dashletList,
            };
        },

        events: {
            'click a[data-action="selectFolder"]': function (e) {
                var id = $(e.currentTarget).data('id');
                var model = this.collection.get(id);
                var name = this.translate('inbox', 'presetFilters', 'Email');
                if (model) {
                    name = model.get('name');
                }
                this.trigger('select', id, name);
                this.close();
            },
        },

        buttonList: [
            {
                name: 'cancel',
                label: 'Cancel'
            }
        ],

        setup: function () {
            this.headerHtml = '';
            this.wait(true);

            this.getCollectionFactory().create('EmailFolder', function (collection) {
                this.collection = collection;
                collection.maxSize = this.getConfig().get('emailFolderMaxCount') || 100;
                collection.data.boolFilterList = ['onlyMy'];
                collection.fetch().then(function () {
                    this.wait(false);
                }.bind(this));

            }, this);
        },
    });
});
