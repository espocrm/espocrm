/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

define('views/dashlets/fields/emails/folder', ['views/fields/enum'], function (Dep) {

    return Dep.extend({

        /** @type {{id: string, name: string}[]|null} */
        folderDataList: null,

        setup: function () {
            Dep.prototype.setup.call(this);

            let userId = this.model.userId ?? this.getUser().id;

            this.wait(
                Espo.Ajax.getRequest('EmailFolder/action/listAll', {userId: userId})
                    .then(data => this.folderDataList = data.list)
                    .then(() => this.setupOptions())
            );

            this.setupOptions();
        },

        setupOptions: function () {
            if (!this.folderDataList) {
                return;
            }

            this.params.options = this.folderDataList
                .map(item => item.id)
                .filter(item => item !== 'inbox' && item !== 'trash');

            this.params.options.unshift('');

            this.translatedOptions = {'': this.translate('inbox', 'presetFilters', 'Email')};

            this.folderDataList.forEach(item => {
                this.translatedOptions[item.id] = item.name;
            });
        },
    });
});
