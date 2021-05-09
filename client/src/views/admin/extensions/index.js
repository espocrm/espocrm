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

Espo.define('views/admin/extensions/index', 'view', function (Dep) {

    return Dep.extend({

        template: 'admin/extensions/index',

        packageContents: null,

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
            },
            'click [data-action="install"]': function (e) {
                var id = $(e.currentTarget).data('id');

                var name = this.collection.get(id).get('name');
                var version = this.collection.get(id).get('version');

                this.run(id, name, version);

            },
            'click [data-action="uninstall"]': function (e) {
                var id = $(e.currentTarget).data('id');

                var name = this.collection.get(id).get('name');

                var self = this;
                this.confirm(this.translate('uninstallConfirmation', 'messages', 'Admin'), function () {
                    Espo.Ui.notify(this.translate('Uninstalling...', 'labels', 'Admin'));

                    $.ajax({
                        url: 'Extension/action/uninstall',
                        type: 'POST',
                        data: JSON.stringify({
                            id: id
                        }),
                        timeout: 0,
                        error: function (xhr) {
                            var msg = xhr.getResponseHeader('X-Status-Reason');
                            this.showErrorNotification(this.translate('Error') + ': ' + msg);
                        }.bind(this)
                    }).done(function () {
                        window.location.reload();

                    }.bind(this));
                }, this);
            }
        },

        setup: function () {
            this.getCollectionFactory().create('Extension', function (collection) {
                this.collection = collection;

                collection.maxSize = this.getConfig().get('recordsPerPage');

                this.wait(true);
                this.listenToOnce(collection, 'sync', function () {
                    this.createView('list', 'views/extension/record/list', {
                        collection: collection,
                        el: this.options.el + ' > .list-container',
                    });
                    if (collection.length == 0) {
                        this.once('after:render', function () {
                            this.$el.find('.list-container').addClass('hidden');
                        }.bind(this));
                    }
                    this.wait(false);
                });

                collection.fetch();

            }, this);
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

        showErrorNotification: function (msg) {
            if (!msg) {
                this.$el.find('.notify-text').addClass('hidden');
            } else {
                msg = this.translate(msg, 'errors', 'Admin');
                this.$el.find('.notify-text').html(msg);
                this.$el.find('.notify-text').removeClass('hidden');
            }
        },

        upload: function () {
            this.$el.find('button[data-action="upload"]').addClass('disabled').attr('disabled', 'disabled');
            this.notify('Uploading...');
            $.ajax({
                url: 'Extension/action/upload',
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
                this.createView('popup', 'views/admin/extensions/ready', {
                    upgradeData: data
                }, function (view) {
                    view.render();
                    this.$el.find('button[data-action="upload"]').removeClass('disabled').removeAttr('disabled');

                    view.once('run', function () {
                        view.close();
                        this.$el.find('.panel.upload').addClass('hidden');
                        this.run(data.id, data.version, data.name);
                    }, this);
                }.bind(this));
            }.bind(this)).error;
        },

        run: function (id, version, name) {
            var msg = this.translate('Installing...', 'labels', 'Admin');
            this.notify('Please wait...');

            this.showError(false);
            this.showErrorNotification(false);

            Espo.Ajax.postRequest(
                'Extension/action/install',
                {
                    id: id
                },
                {
                    timeout: 0,
                }
            ).then(
                function () {
                    var cache = this.getCache();
                    if (cache) {
                        cache.clear();
                    }
                    this.createView('popup', 'views/admin/extensions/done', {
                        version: version,
                        name: name,
                    }, function (view) {
                        this.collection.fetch();
                        this.$el.find('.list-container').removeClass('hidden');
                        this.$el.find('.panel.upload').removeClass('hidden');
                        this.notify(false);
                        view.render();
                    });
                }.bind(this)
            ).fail(
                function (xhr) {
                    this.$el.find('.panel.upload').removeClass('hidden');
                    var msg = xhr.getResponseHeader('X-Status-Reason');
                    this.showErrorNotification(this.translate('Error') + ': ' + msg);
                }.bind(this)
            );
        },

    });
});
