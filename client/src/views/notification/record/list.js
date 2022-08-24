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

define('views/notification/record/list', ['views/record/list-expanded'], function (Dep) {

    return Dep.extend({

        setup: function () {
            Dep.prototype.setup.call(this);

            this.listenTo(this.collection, 'sync', (c, r, options) => {
                if (!options.fetchNew) {
                    return;
                }

                let lengthBeforeFetch = options.lengthBeforeFetch || 0;

                if (lengthBeforeFetch === 0) {
                    this.reRender();

                    return;
                }

                let $list = this.$el.find(this.listContainerEl);

                let rowCount = this.collection.length - lengthBeforeFetch;

                for (let i = rowCount - 1; i >= 0; i--) {
                    let model = this.collection.at(i);

                    this.buildRow(i, model, view => {
                        view.getHtml(html => {
                            let $row = $(this.getRowContainerHtml(model.id));

                            $row.append(html);
                            $list.prepend($row);

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

        showNewRecords: function () {
            this.collection.fetchNew();
        },
    });
});
