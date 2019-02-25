/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

Espo.define('views/template/record/edit', 'views/record/edit', function (Dep) {

    return Dep.extend({

        setup: function () {
            Dep.prototype.setup.call(this);

            if (!this.model.isNew()) {
                this.setFieldReadOnly('entityType');
            }

            if (this.model.get('entityType')) {
                this.showField('variables');
            } else {
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
                    if (this.getMetadata().get(['entityDefs', 'Template', 'defaultTemplates', entityType])) {
                        header = this.getMetadata().get(['entityDefs', 'Template', 'defaultTemplates', entityType, 'header']);
                        body = this.getMetadata().get(['entityDefs', 'Template', 'defaultTemplates', entityType, 'body']);
                        footer = this.getMetadata().get(['entityDefs', 'Template', 'defaultTemplates', entityType, 'footer']);
                    } else {
                        var scopeType = this.getMetadata().get(['scopes', entityType, 'type']);
                        if (scopeType) {
                            if (this.getMetadata().get(['entityDefs', 'Template', 'defaultTemplates', scopeType])) {
                                header = this.getMetadata().get(['entityDefs', 'Template', 'defaultTemplates', scopeType, 'header']);
                                body = this.getMetadata().get(['entityDefs', 'Template', 'defaultTemplates', scopeType, 'body']);
                                footer = this.getMetadata().get(['entityDefs', 'Template', 'defaultTemplates', scopeType, 'footer']);
                            }
                        }
                    }

                    if (header) {
                        this.model.set('header', header);
                    } else {
                        this.model.set('header', '');
                    }
                    if (body) {
                        this.model.set('body', body);
                    } else {
                        this.model.set('body', '');
                    }
                    if (footer) {
                        this.model.set('footer', footer);
                    } else {
                        this.model.set('footer', '');
                    }
                }, this);

                this.listenTo(this.model, 'change', function (e, o) {
                    if (!o.ui) return;

                    if (!this.model.hasChanged('header') && !this.model.hasChanged('body') && !this.model.hasChanged('footer')) {
                        return;
                    }

                    var entityType = this.model.get('entityType');
                    if (!entityType) return;

                    storedData[entityType] = {
                        header: this.model.get('header'),
                        body: this.model.get('body'),
                        footer: this.model.get('footer')
                    };
                }, this);
            }
        }

    });

});
