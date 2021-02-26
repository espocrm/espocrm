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

define('views/template/record/edit', 'views/record/edit', function (Dep) {

    return Dep.extend({

        saveAndContinueEditingAction: true,

        setup: function () {
            Dep.prototype.setup.call(this);

            if (!this.model.isNew()) {
                this.setFieldReadOnly('entityType');
            }

            if (this.model.get('entityType')) {
                this.showField('variables');
            }
            else {
                this.hideField('variables');
            }

            if (this.model.isNew()) {
                var storedData = {};

                this.listenTo(this.model, 'change:entityType', function (model) {
                    var entityType = this.model.get('entityType');

                    if (!entityType) {
                        this.model.set('header', '');
                        this.model.set('body', '');
                        this.model.set('footer', '');

                        this.hideField('variables');

                        return;
                    }
                    this.showField('variables');

                    if (entityType in storedData) {
                        this.model.set('header', storedData[entityType].header);
                        this.model.set('body', storedData[entityType].body);
                        this.model.set('footer', storedData[entityType].footer);

                        return;
                    }

                    var header, body, footer;

                    var sourceType = null;

                    if (
                        this.getMetadata().get(['entityDefs', 'Template', 'defaultTemplates', entityType])
                    ) {
                        var sourceType = entityType;
                    }
                    else {
                        var scopeType = this.getMetadata().get(['scopes', entityType, 'type']);

                        if (
                            scopeType &&
                            this.getMetadata().get(['entityDefs', 'Template', 'defaultTemplates', scopeType])
                        ) {

                            var sourceType = scopeType;
                        }
                    }

                    if (sourceType) {
                        header = this.getMetadata().get(
                            ['entityDefs', 'Template', 'defaultTemplates', sourceType, 'header']
                        );

                        body = this.getMetadata().get(
                            ['entityDefs', 'Template', 'defaultTemplates', sourceType, 'body']
                        );

                        footer = this.getMetadata().get(
                            ['entityDefs', 'Template', 'defaultTemplates', sourceType, 'footer']
                        );
                    }

                    body = body || '';
                    header = header || null;
                    footer = footer || null;

                    this.model.set('body', body);
                    this.model.set('header', header);
                    this.model.set('footer', footer);
                }, this);

                this.listenTo(this.model, 'change', function (e, o) {
                    if (!o.ui) {
                        return;
                    }

                    if (
                        !this.model.hasChanged('header') &&
                        !this.model.hasChanged('body') &&
                        !this.model.hasChanged('footer')
                    ) {
                        return;
                    }

                    var entityType = this.model.get('entityType');

                    if (!entityType) {
                        return;
                    }

                    storedData[entityType] = {
                        header: this.model.get('header'),
                        body: this.model.get('body'),
                        footer: this.model.get('footer'),
                    };
                }, this);
            }
        },

    });
});
