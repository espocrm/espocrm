/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

import View from 'view';

export default class UpgradeIndexView extends View {

    template = 'admin/upgrade/index'

    packageContents = null

    data() {
        return {
            versionMsg: this.translate('Current version') + ': ' + this.getConfig().get('version'),
            infoMsg: this.translate('upgradeInfo', 'messages', 'Admin')
                .replace('{url}', 'https://www.espocrm.com/documentation/administration/upgrading/'),
            backupsMsg: this.translate('upgradeBackup', 'messages', 'Admin'),
            upgradeRecommendation: this.translate('upgradeRecommendation', 'messages', 'Admin'),
            downloadMsg: this.translate('downloadUpgradePackage', 'messages', 'Admin')
                .replace('{url}', 'https://www.espocrm.com/download/upgrades'),
        };
    }

    afterRender() {
        this.$el.find('.panel-body a').attr('target', '_BLANK');
    }

    events = {
        /** @this UpgradeIndexView */
        'change input[name="package"]': function (e) {
            this.$el.find('button[data-action="upload"]')
                .addClass('disabled')
                .attr('disabled', 'disabled');

            this.$el.find('.message-container').html('');

            const files = e.currentTarget.files;

            if (files.length) {
                this.selectFile(files[0]);
            }
        },
        /** @this UpgradeIndexView */
        'click button[data-action="upload"]': function () {
            this.upload();
        },
    }

    selectFile(file) {
        const fileReader = new FileReader();

        fileReader.onload = (e) => {
            this.packageContents = e.target.result;

            this.$el.find('button[data-action="upload"]')
                .removeClass('disabled')
                .removeAttr('disabled');
        };

        fileReader.readAsDataURL(file);
    }

    showError(msg) {
        msg = this.translate(msg, 'errors', 'Admin');

        this.$el.find('.message-container').html(msg);
    }

    upload() {
        this.$el.find('button[data-action="upload"]')
            .addClass('disabled')
            .attr('disabled', 'disabled');

        Espo.Ui.notify(this.translate('Uploading...'));

        Espo.Ajax
            .postRequest('Admin/action/uploadUpgradePackage', this.packageContents, {
                contentType: 'application/zip',
                timeout: 0,
            })
            .then(data => {
                if (!data.id) {
                    this.showError(this.translate('Error occurred'));

                    return;
                }

                Espo.Ui.notify(false);

                this.createView('popup', 'views/admin/upgrade/ready', {
                    upgradeData: data,
                }, view => {
                    view.render();

                    this.$el.find('button[data-action="upload"]')
                        .removeClass('disabled')
                        .removeAttr('disabled');

                    view.once('run', () => {
                        view.close();

                        this.$el.find('.panel.upload').addClass('hidden');

                        this.run(data.id, data.version);
                    });
                });
            })
            .catch(xhr => {
                this.showError(xhr.getResponseHeader('X-Status-Reason'));

                Espo.Ui.notify(false);
            });
    }

    textNotification(text) {
        this.$el.find('.notify-text').html(text);
    }

    run(id, version) {
        const msg = this.translate('Upgrading...', 'labels', 'Admin');

        Espo.Ui.notify(this.translate('pleaseWait', 'messages'));

        this.textNotification(msg);

        Espo.Ajax
            .postRequest('Admin/action/runUpgrade', {id: id}, {timeout: 0, bypassAppReload: true})
            .then(() => {
                const cache = this.getCache();

                if (cache) {
                    cache.clear();
                }

                this.createView('popup', 'views/admin/upgrade/done', {
                    version: version,
                }, view => {
                    Espo.Ui.notify(false);

                    view.render();
                });
            })
            .catch(xhr => {
                this.$el.find('.panel.upload').removeClass('hidden');

                const msg = xhr.getResponseHeader('X-Status-Reason');

                this.textNotification(this.translate('Error') + ': ' + msg);
            });
    }
}
