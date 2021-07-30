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

define('views/admin/field-manager/list', 'view', function (Dep) {

    return Dep.extend({

        template: 'admin/field-manager/list',

        data: function () {
            return {
                scope: this.scope,
                fieldDefsArray: this.fieldDefsArray,
                typeList: this.typeList,
            };
        },

        events: {
            'click [data-action="removeField"]': function (e) {
                var field = $(e.currentTarget).data('name');

                this.removeField(field);
            },
            'keyup input[data-name="quick-search"]': function (e) {
                this.processQuickSearch(e.currentTarget.value);
            },
        },

        setup: function () {
            this.scope = this.options.scope;

            this.wait(
                this.buildFieldDefs()
            );
        },

        afterRender: function () {
            this.$noData = this.$el.find('.no-data');
        },

        buildFieldDefs: function () {
            return this.getModelFactory().create(this.scope).then(model => {
                this.fields = model.defs.fields;

                this.fieldList = Object.keys(this.fields).sort();
                this.fieldDefsArray = [];

                this.fieldList.forEach(field => {
                    var defs = this.fields[field];

                    if (defs.customizationDisabled) {
                        return;
                    }

                    this.fieldDefsArray.push({
                        name: field,
                        isCustom: defs.isCustom || false,
                        type: defs.type,
                        label: this.translate(field, 'fields', this.scope),
                    });
                });
            });
        },

        removeField: function (field) {
            this.confirm(this.translate('confirmation', 'messages'), () => {
                Espo.Ui.notify(this.translate('Removing...'));

                Espo.Ajax.request('Admin/fieldManager/' + this.scope + '/' + field, 'delete').then(() => {
                    Espo.Ui.success(this.translate('Removed'));

                    this.$el.find('tr[data-name="'+field+'"]').remove();
                    var data = this.getMetadata().data;

                    delete data['entityDefs'][this.scope]['fields'][field];

                    this.getMetadata().loadSkipCache().then(() =>
                        this.buildFieldDefs()
                            .then(() => {
                                this.broadcastUpdate();

                                return this.reRender();
                            })
                            .then(() =>
                                Espo.Ui.success(this.translate('Removed'))
                            )
                    );
                });
            });
        },

        broadcastUpdate: function () {
            this.getHelper().broadcastChannel.postMessage('update:metadata');
            this.getHelper().broadcastChannel.postMessage('update:language');
        },

        processQuickSearch: function (text) {
            text = text.trim();

            let $noData = this.$noData;

            $noData.addClass('hidden');

            if (!text) {
                this.$el.find('table tr.field-row').removeClass('hidden');

                return;
            }

            let matchedList = [];

            let lowerCaseText = text.toLowerCase();

            this.fieldDefsArray.forEach(item => {
                let matched = false;

                if (
                    item.label.toLowerCase().indexOf(lowerCaseText) === 0 ||
                    item.name.toLowerCase().indexOf(lowerCaseText) === 0
                ) {
                    matched = true;
                }

                if (!matched) {
                    let wordList = item.label.split(' ')
                        .concat(
                            item.label.split(' ')
                        );

                    wordList.forEach((word) => {
                        if (word.toLowerCase().indexOf(lowerCaseText) === 0) {
                            matched = true;
                        }
                    });
                }

                if (matched) {
                    matchedList.push(item.name);
                }
            });

            if (matchedList.length === 0) {
                this.$el.find('table tr.field-row').addClass('hidden');

                $noData.removeClass('hidden');

                return;
            }

            this.fieldDefsArray
                .map(item => item.name)
                .forEach(scope => {
                    if (!~matchedList.indexOf(scope)) {
                        this.$el.find('table tr.field-row[data-name="'+scope+'"]').addClass('hidden');

                        return;
                    }

                    this.$el.find('table tr.field-row[data-name="'+scope+'"]').removeClass('hidden');
                });
        },
    });
});
