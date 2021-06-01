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

define('crm:view-setup-handlers/document/record-list-drag-n-drop', [], function () {

    var Handler = function (view) {
        this.view = view;
    };

    _.extend(Handler.prototype, {

        process: function () {
            this.listenTo(this.view, 'after:render', function () {
                this.initDragDrop();
            }, this);
        },

        initDragDrop: function () {
            var $el = this.view.$el.parent();

            $el.off('drop');
            $el.off('dragover');
            $el.off('dragleave');

            $el.on('drop', function (e) {
                e.preventDefault();

                e.stopPropagation();

                var e = e.originalEvent;

                if (
                    e.dataTransfer &&
                    e.dataTransfer.files &&
                    e.dataTransfer.files.length === 1 &&
                    this.dropEntered
                ) {
                    this.removeDrop();

                    this.create(e.dataTransfer.files[0]);

                    return;
                }

                this.removeDrop($el);
            }.bind(this));

            $el.get(0).addEventListener('dragover', function (e) {
                e.preventDefault();
            });

            this.dropEntered = false;

            $el.get(0).addEventListener('dragenter', function (e) {
                e.preventDefault();

                if (!e.dataTransfer.types || !e.dataTransfer.types.length) {
                    return;
                }

                if (!~e.dataTransfer.types.indexOf('Files')) {
                    return;
                }

                if (!this.dropEntered) {
                    this.renderDrop();
                }
            }.bind(this));

            $el.get(0).addEventListener('dragleave', function (e) {
                e.preventDefault();

                if (!this.dropEntered) {
                    return;
                }

                var fromElement = e.fromElement || e.relatedTarget;

                if (fromElement && $.contains($el.get(0), fromElement)) {
                    return;
                }

                if (
                    fromElement &&
                    fromElement.parentNode &&
                    fromElement.parentNode.toString() === '[object ShadowRoot]'
                ) {
                    return;
                }

                this.removeDrop();

            }.bind(this));
        },

        renderDrop: function () {
            this.dropEntered = true;

            let $backdrop =
                $('<div class="dd-backdrop" />')
                    .css('pointer-events', 'none')
                    .html(
                        '<span class="fas fa-paperclip"></span> ' +
                        this.view.getLanguage().translate('Create Document', 'labels', 'Document')
                    );

            this.view.$el.append($backdrop);
        },

        removeDrop: function () {
            this.view.$el.find('> .dd-backdrop').remove();

            this.dropEntered = false;
        },

        create: function (file) {
            this.view
                .actionQuickCreate()
                .then(
                    function (view) {
                        var fileView = view.getRecordView().getFieldView('file');

                        if (!fileView) {
                            var msg = "No 'file' field on the layout.";

                            Espo.Ui.error(msg);
                            console.error(msg);

                            return;
                        }

                        if (fileView.isRendered()) {
                            fileView.uploadFile(file);

                            return;
                        }

                        this.listenToOnce(fileView, 'after:render', function () {
                            fileView.uploadFile(file);
                        });
                    }.bind(this)
                );

        },
    });

    _.extend(Handler.prototype, Backbone.Events);

    return Handler;
});
