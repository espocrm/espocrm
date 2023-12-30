/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

define('views/admin/template-manager/index', ['view'], function (Dep) {

    return Dep.extend({

        template: 'admin/template-manager/index',

        data: function () {
            return {
                templateDataList: this.templateDataList,
            };
        },

        events: {
            'click [data-action="selectTemplate"]': function (e) {
                var name = $(e.currentTarget).data('name');

                this.getRouter().checkConfirmLeaveOut(() => {
                    this.selectTemplate(name);
                });
            }
        },

        setup: function () {
            this.templateDataList = [];

            var templateList = Object.keys(this.getMetadata().get(['app', 'templates']) || {});

            templateList.sort((v1, v2) => {
                return this.translate(v1, 'templates', 'Admin')
                    .localeCompare(this.translate(v2, 'templates', 'Admin'));
            });

            templateList.forEach(template =>{
                var defs = this.getMetadata().get(['app', 'templates', template]);

                if (defs.scopeListConfigParam || defs.scopeList) {
                    var scopeList = Espo.Utils.clone(
                        defs.scopeList || this.getConfig().get(defs.scopeListConfigParam) || []);

                    scopeList.sort((v1, v2) => {
                        return this.translate(v1, 'scopeNames')
                            .localeCompare(this.translate(v2, 'scopeNames'));
                    });

                    scopeList.forEach(scope => {
                        let o = {
                            name: template + '_' + scope,
                            text: this.translate(template, 'templates', 'Admin') + ' :: ' +
                                this.translate(scope, 'scopeNames'),
                        };

                        this.templateDataList.push(o);
                    });

                    return;
                }

                var o = {
                    name: template,
                    text: this.translate(template, 'templates', 'Admin'),
                };

                this.templateDataList.push(o);
            });

            this.selectedTemplate = this.options.name;

            if (this.selectedTemplate) {
                this.once('after:render', () => {
                    this.selectTemplate(this.selectedTemplate, true);
                });
            }
        },

        selectTemplate: function (name) {
            this.selectedTemplate = name;

            this.getRouter().navigate('#Admin/templateManager/name=' + this.selectedTemplate, {trigger: false});

            this.createRecordView();

            this.$el.find('[data-action="selectTemplate"]')
                .removeClass('disabled')
                .removeAttr('disabled');

            this.$el.find('[data-name="'+name+'"][data-action="selectTemplate"]')
                .addClass('disabled')
                .attr('disabled', 'disabled');
        },

        createRecordView: function () {
            Espo.Ui.notify(' ... ');

            this.createView('record', 'views/admin/template-manager/edit', {
                selector: '.template-record',
                name: this.selectedTemplate,
            }, (view) => {
                view.render();

                Espo.Ui.notify(false);
                $(window).scrollTop(0);
            });
        },

        updatePageTitle: function () {
            this.setPageTitle(this.getLanguage().translate('Template Manager', 'labels', 'Admin'));
        },
    });
});
