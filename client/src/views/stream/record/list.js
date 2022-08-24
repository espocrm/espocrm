/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

define('views/stream/record/list', ['views/record/list-expanded'], function (Dep) {

    return Dep.extend({

        type: 'listStream',

        massActionsDisabled: true,

        setup: function () {
            this.itemViews = this.getMetadata().get('clientDefs.Note.itemViews') || {};

            Dep.prototype.setup.call(this);

            this.isRenderingNew = false;

            this.listenTo(this.collection, 'sync', (c, r, options) => {
                if (!options.fetchNew) {
                    return;
                }

                if (this.isRenderingNew) {
                    // Prevent race condition.
                    return;
                }

                let lengthBeforeFetch = options.lengthBeforeFetch || 0;

                if (lengthBeforeFetch === 0) {
                    this.buildRows(() => this.reRender());

                    return;
                }

                let $list = this.$el.find(this.listContainerEl);

                let rowCount = this.collection.length - lengthBeforeFetch;

                if (rowCount === 0) {
                    return;
                }

                this.isRenderingNew = true;

                for (let i = rowCount - 1; i >= 0; i--) {
                    let model = this.collection.at(i);

                    this.buildRow(i, model, view => {
                        view.getHtml(html => {
                            if (i === 0) {
                                this.isRenderingNew = false;
                            }

                            let $row = $(this.getRowContainerHtml(model.id));

                            // Prevent a race condition issue.
                            let $existingRow = this.$el.find(`[data-id="${model.id}"]`);

                            if ($existingRow.length) {
                                $row = $existingRow;
                            }

                            $row.append(html);

                            if (!$existingRow.length) {
                                $list.prepend($row);
                            }

                            view._afterRender();

                            if (view.options.el) {
                                view.setElement(view.options.el);
                            }
                        });
                    });
                }
            });

            this.events['auxclick a[href][data-scope][data-id]'] = e => {
                let isCombination = e.button === 1 && (e.ctrlKey || e.metaKey);

                if (!isCombination) {
                    return;
                }

                let $target = $(e.currentTarget);

                let id = $target.attr('data-id');
                let scope = $target.attr('data-scope');

                e.preventDefault();
                e.stopPropagation();

                this.actionQuickView({
                    id: id,
                    scope: scope,
                });
            };
        },

        buildRow: function (i, model, callback) {
            var key = model.id;
            this.rowList.push(key);

            var type = model.get('type');
            var viewName = this.itemViews[type] || 'views/stream/notes/' + Espo.Utils.camelCaseToHyphen(type);

            this.createView(key, viewName, {
                model: model,
                parentModel: this.model,
                acl: {
                    edit: this.getAcl().checkModel(model, 'edit')
                },
                isUserStream: this.options.isUserStream,
                noEdit: this.options.noEdit,
                optionsToPass: ['acl'],
                name: this.type + '-' + model.name,
                el: this.options.el + ' li[data-id="' + model.id + '"]',
                setViewBeforeCallback: this.options.skipBuildRows && !this.isRendered(),
            }, callback);
        },

        buildRows: function (callback) {
            this.checkedList = [];
            this.rowList = [];

            if (this.collection.length > 0) {
                this.wait(true);

                var count = this.collection.models.length;
                var built = 0;

                for (var i in this.collection.models) {
                    var model = this.collection.models[i];

                    this.buildRow(i, model, () => {
                        built++;

                        if (built === count) {
                            if (typeof callback === 'function') {
                                callback();
                            }

                            this.wait(false);

                            this.trigger('after:build-rows');
                        }
                    });
                }

                return;
            }

            if (typeof callback === 'function') {
                callback();

                this.trigger('after:build-rows');
            }
        },

        showNewRecords: function () {
            this.collection.fetchNew();
        },
    });
});
