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

define('views/email-account/fields/folder', 'views/fields/base', function (Dep) {

    return Dep.extend({

        editTemplate: 'email-account/fields/folder/edit',

        getFoldersUrl: 'EmailAccount/action/getFolders',

        events: {
            'click [data-action="selectFolder"]': function () {
                Espo.Ui.notify(this.translate('pleaseWait', 'messages'));

                var data = {
                    host: this.model.get('host'),
                    port: this.model.get('port'),
                    security: this.model.get('security'),
                    username: this.model.get('username'),
                    emailAddress: this.model.get('emailAddress'),
                    userId: this.model.get('assignedUserId'),
                };

                if (this.model.has('password')) {
                    data.password = this.model.get('password');
                }
                else {
                    if (!this.model.isNew()) {
                        data.id = this.model.id;
                    }
                }

                Espo.Ajax.postRequest(this.getFoldersUrl, data).then(function (folders) {
                    this.createView('modal', 'views/email-account/modals/select-folder', {
                        folders: folders
                    }, function (view) {
                        this.notify(false);

                        view.render();

                        this.listenToOnce(view, 'select', function (folder) {
                            view.close();
                            this.addFolder(folder);
                        }, this);
                    });
                }.bind(this)).fail(function () {
                    Espo.Ui.error(this.translate('couldNotConnectToImap', 'messages', 'EmailAccount'));

                    xhr.errorIsHandled = true;
                }.bind(this));
            }
        },

        addFolder: function (folder) {
            this.$element.val(folder);
        },
    });
});
