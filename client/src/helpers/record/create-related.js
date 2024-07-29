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

/**
 * @internal
 */
class CreateRelatedHelper {

    /**
     * @param {import('view').default} view
     */
    constructor(view) {
        /** @private */
        this.view = view;
        /** @private */
        this.metadata = view.getMetadata();
    }

    /**
     * @param {import('model').default} model
     * @param {string} link
     * @param {Record} options
     */
    process(model, link, options = {}) {
        const scope = model.defs['links'][link].entity;
        const foreignLink = model.defs['links'][link].foreign;

        let attributes = {};

        const attributeMap = this.metadata
            .get(['clientDefs', model.entityType, 'relationshipPanels', link, 'createAttributeMap']) || {};

        Object.keys(attributeMap)
            .forEach(attr => attributes[attributeMap[attr]] = model.get(attr));

        Espo.Ui.notify(' ... ');

        const handler = this.metadata
            .get(['clientDefs', model.entityType, 'relationshipPanels', link, 'createHandler']);

        new Promise(resolve => {
            if (!handler) {
                resolve({});

                return;
            }

            Espo.loader.requirePromise(handler)
                .then(Handler => new Handler(this.view.getHelper()))
                .then(handler => {
                    handler.getAttributes(model)
                        .then(attributes => resolve(attributes));
                });
        }).then(additionalAttributes => {
            attributes = {...attributes, ...additionalAttributes};

            const viewName = this.metadata.get(['clientDefs', scope, 'modalViews', 'edit']) || 'views/modals/edit';

            this.view.createView('quickCreate', viewName, {
                scope: scope,
                relate: {
                    model: model,
                    link: foreignLink,
                },
                attributes: attributes,
            }, view => {
                view.render();
                view.notify(false);

                this.view.listenToOnce(view, 'after:save', () => {
                    if (options.fromSelectRelated) {
                        setTimeout(() => this.view.clearView('dialogSelectRelated'), 25);
                    }

                    model.trigger(`update-related:${link}`);

                    model.trigger('after:relate');
                    model.trigger(`after:relate:${link}`);
                });
            });
        });
    }
}

export default CreateRelatedHelper;
