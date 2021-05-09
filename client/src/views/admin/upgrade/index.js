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

define('views/admin/upgrade/index', 'view', function (Dep) {

    return Dep.extend({

        template: 'admin/upgrade/index',

        packageContents: null,

        data: function () {
            return {
                versionMsg: this.translate('Current version') + ': ' + this.getConfig().get('version'),
                infoMsg: this.translate('upgradeInfo', 'messages', 'Admin').replace('{url}', 'https://www.espocrm.com/documentation/administration/upgrading/'),
                backupsMsg: this.translate('upgradeBackup', 'messages', 'Admin'),
                upgradeRecommendation: this.translate('upgradeRecommendation', 'messages', 'Admin'),
                downloadMsg: this.translate('downloadUpgradePackage', 'messages', 'Admin').replace('{url}', 'https://www.espocrm.com/download/upgrades')
            };
        },

        afterRender: function () {
            this.$el.find('.panel-body a').attr('target', '_BLANK');
        },

        events: {
            'change input[name="package"]': function (e) {
                this.$el.find('button[data-action="upload"]').addClass('disabled').attr('disabled', 'disabled');
                this.$el.find('.message-container').html('');
                var files = e.currentTarget.files;
                if (files.length) {
                    this.selectFile(files[0]);
                }
            },
            'click button[data-action="upload"]': function () {
                this.upload();
            }
        },

        setup: function () {

        },

        selectFile: function (file) {
            var fileReader = new FileReader();
            fileReader.onload = function (e) {
                this.packageContents = e.target.result;
                this.$el.find('button[data-action="upload"]').removeClass('disabled').removeAttr('disabled');
            }.bind(this);
            fileReader.readAsDataURL(file);
        },

        showError: function (msg) {
            msg = this.translate(msg, 'errors', 'Admin');
            this.$el.find('.message-container').html(msg);
        },

        upload: function () {
            this.$el.find('button[data-action="upload"]').addClass('disabled').attr('disabled', 'disabled');
            this.notify('Uploading...');
            $.ajax({
                url: 'Admin/action/uploadUpgradePackage',
                type: 'POST',
                contentType: 'application/zip',
                timeout: 0,
                data: this.packageContents,
                error: function (xhr, t, e) {
                    this.showError(xhr.getResponseHeader('X-Status-Reason'));
                    this.notify(false);
                }.bind(this)
            }).done(function (data) {
                if (!data.id) {
                    this.showError(this.translate('Error occurred'));
                    return;
                }
                this.notify(false);
                this.createView('popup', 'views/admin/upgrade/ready', {
                    upgradeData: data
                }, function (view) {
                    view.render();
                    this.$el.find('button[data-action="upload"]').removeClass('disabled').removeAttr('disabled');

                    view.once('run', function () {
                        view.close();
                        this.$el.find('.panel.upload').addClass('hidden');
                        this.run(data.id, data.version);
                    }, this);
                }.bind(this));
            }.bind(this)).error;
        },

        textNotification: function (text) {
            this.$el.find('.notify-text').html(text);
        },

        run: function (id, version) {
            var msg = this.translate('Upgrading...', 'labels', 'Admin');
            this.notify('Please wait...');
            this.textNotification(msg);

            $.ajax({
                url: 'Admin/action/runUpgrade',
                type: 'POST',
                data: JSON.stringify({
                    id: id
                }),
                timeout: 0,
                error: function (xhr) {
                    this.$el.find('.panel.upload').removeClass('hidden');
                    var msg = xhr.getResponseHeader('X-Status-Reason');
                    this.textNotification(this.translate('Error') + ': ' + msg);
                }.bind(this)
            }).done(function () {
                var cache = this.getCache();
                if (cache) {
                    cache.clear();
                }
                this.createView('popup', 'views/admin/upgrade/done', {
                    version: version
                }, function (view) {
                    this.notify(false);
                    view.render();
                }.bind(this));
            }.bind(this));
        },

    });
});


