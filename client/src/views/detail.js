/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

/** @module module:views/detail */

import MainView from 'views/main';

/**
 * A detail view.
 */
class DetailView extends MainView {

    /** @inheritDoc */
    template = 'detail'
    /** @inheritDoc */
    name = 'Detail'

    /** @inheritDoc */
    optionsToPass = [
        'attributes',
        'returnUrl',
        'returnDispatchParams',
        'rootUrl',
    ]

    /**
     * A header view name.
     *
     * @type {string}
     */
    headerView = 'views/header'

    /**
     * A record view name.
     *
     * @type {string}
     */
    recordView = 'views/record/detail'

    /**
     * A root breadcrumb item not to be a link.
     *
     * @type {boolean}
     */
    rootLinkDisabled = false

    /**
     * A root URL.
     *
     * @type {string}
     */
    rootUrl = ''

    /**
     * Is return.
     *
     * @protected
     */
    isReturn = false

    /** @inheritDoc */
    shortcutKeys = {}

    /**
     * An entity type.
     *
     * @type {string}
     */
    entityType

    /** @inheritDoc */
    setup() {
        super.setup();

        this.entityType = this.model.entityType || this.model.name;

        this.headerView = this.options.headerView || this.headerView;
        this.recordView = this.options.recordView || this.recordView;

        this.rootUrl = this.options.rootUrl || this.options.params.rootUrl || '#' + this.scope;
        this.isReturn = this.options.isReturn || this.options.params.isReturn || false;

        this.setupHeader();
        this.setupRecord();
        this.setupPageTitle();
        this.initFollowButtons();
        this.initRedirect();
    }

    /** @inheritDoc */
    setupFinal() {
        super.setupFinal();

        this.wait(
            this.getHelper().processSetupHandlers(this, 'detail')
        );
    }

    /** @private */
    initRedirect() {
        if (!this.options.params.isAfterCreate) {
            return;
        }

        let redirect = () => {
            Espo.Ui.success(this.translate('Created'));

            setTimeout(() => {
                this.getRouter().navigate(this.rootUrl, {trigger: true});
            }, 1000)
        };

        if (
            this.model.lastSyncPromise &&
            this.model.lastSyncPromise.getStatus() === 403
        ) {
            redirect();

            return;
        }

        this.listenToOnce(this.model, 'fetch-forbidden', () => redirect())
    }

    /**
     * Set up a page title.
     */
    setupPageTitle() {
        this.listenTo(this.model, 'after:save', () => {
            this.updatePageTitle();
        });

        this.listenTo(this.model, 'sync', (model) => {
            if (model && model.hasChanged('name')) {
                this.updatePageTitle();
            }
        });
    }

    /**
     * Set up a header.
     */
    setupHeader() {
        this.createView('header', this.headerView, {
            model: this.model,
            fullSelector: '#main > .header',
            scope: this.scope,
            fontSizeFlexible: true,
        });

        this.listenTo(this.model, 'sync', (model) => {
            if (model && model.hasChanged('name')) {
                if (this.getView('header')) {
                    this.getView('header').reRender();
                }
            }
        });
    }

    /**
     * Set up a record.
     */
    setupRecord() {
        let o = {
            model: this.model,
            fullSelector: '#main > .record',
            scope: this.scope,
            shortcutKeysEnabled: true,
            isReturn: this.isReturn,
        };

        this.optionsToPass.forEach((option) => {
            o[option] = this.options[option];
        });

        if (this.options.params && this.options.params.rootUrl) {
            o.rootUrl = this.options.params.rootUrl;
        }

        if (this.model.get('deleted')) {
            o.readOnly = true;
        }

        return this.createView('record', this.getRecordViewName(), o);
    }

    /**
     * Get a record view name.
     *
     * @returns {string}
     */
    getRecordViewName() {
        return this.getMetadata()
            .get('clientDefs.' + this.scope + '.recordViews.detail') || this.recordView;
    }

    /** @private */
    initFollowButtons() {
        if (!this.getMetadata().get(['scopes', this.scope, 'stream'])) {
            return;
        }

        this.addFollowButtons();

        this.listenTo(this.model, 'change:isFollowed', () => {
            this.controlFollowButtons();
        });
    }

    /** @private */
    addFollowButtons() {
        let isFollowed = this.model.get('isFollowed');

        this.addMenuItem('buttons', {
            name: 'unfollow',
            label: 'Followed',
            style: 'success',
            action: 'unfollow',
            hidden: !isFollowed,
        }, true);

        this.addMenuItem('buttons', {
            name: 'follow',
            label: 'Follow',
            style: 'default',
            iconHtml: '<span class="fas fa-rss fa-sm"></span>',
            text: this.translate('Follow'),
            action: 'follow',
            hidden: isFollowed ||
                !this.model.has('isFollowed') ||
                !this.getAcl().checkModel(this.model, 'stream'),
        }, true);
    }

    /** @private */
    controlFollowButtons() {
        let isFollowed = this.model.get('isFollowed');

        if (isFollowed) {
            this.hideHeaderActionItem('follow');
            this.showHeaderActionItem('unfollow');

            return;
        }

        this.hideHeaderActionItem('unfollow');

        if (this.getAcl().checkModel(this.model, 'stream')) {
            this.showHeaderActionItem('follow');
        }
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * Action 'follow'.
     */
    actionFollow() {
        this.disableMenuItem('follow');

        Espo.Ajax
            .putRequest(this.entityType + '/' + this.model.id + '/subscription')
            .then(() => {
                this.hideHeaderActionItem('follow');

                this.model.set('isFollowed', true, {sync: true});

                this.enableMenuItem('follow');
            })
            .catch(() => {
                this.enableMenuItem('follow');
            });
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * Action 'unfollow'.
     */
    actionUnfollow() {
        this.disableMenuItem('unfollow');

        Espo.Ajax
            .deleteRequest(this.entityType + '/' + this.model.id + '/subscription')
            .then(() => {
                this.hideHeaderActionItem('unfollow');

                this.model.set('isFollowed', false, {sync: true});

                this.enableMenuItem('unfollow');
            })
            .catch(() => {
                this.enableMenuItem('unfollow');
            });
    }

    /**
     * @inheritDoc
     */
    getHeader() {
        let name = this.model.get('name') || this.model.id;

        let $name =
            $('<span>')
                .addClass('font-size-flexible title')
                .text(name);

        if (this.model.get('deleted')) {
            $name.css('text-decoration', 'line-through');
        }

        let headerIconHtml = this.getHeaderIconHtml();
        let scopeLabel = this.getLanguage().translate(this.scope, 'scopeNamesPlural');

        let $root = $('<span>').text(scopeLabel);

        if (!this.rootLinkDisabled) {
            $root = $('<span>')
                .append(
                    $('<a>')
                        .attr('href', this.rootUrl)
                        .addClass('action')
                        .attr('data-action', 'navigateToRoot')
                        .text(scopeLabel)
                );
        }

        if (headerIconHtml) {
            $root.prepend(headerIconHtml);
        }

        return this.buildHeaderHtml([
            $root,
            $name,
        ]);
    }

    /**
     * @inheritDoc
     */
    updatePageTitle() {
        if (this.model.has('name')) {
            this.setPageTitle(this.model.get('name') || this.model.id);

            return;
        }

        super.updatePageTitle();
    }

    /**
     * @return {module:views/record/detail}
     */
    getRecordView() {
        return this.getView('record');
    }

    /**
     * Update a relationship panel (fetch data).
     *
     * @param {string} name A relationship name.
     */
    updateRelationshipPanel(name) {
        let bottom = this.getView('record').getView('bottom');

        if (bottom) {
            let rel = bottom.getView(name);

            if (rel) {
                rel.collection.fetch();
            }
        }
    }

    /**
     * @deprecated Use metadata clientDefs > {EntityType} > relationshipPanels > {link} > createAttributeMap.
     * @type {Object}
     */
    relatedAttributeMap = {}

    /**
     * @deprecated Use clientDefs > {EntityType} > relationshipPanels > {link} > createHandler.
     * @type {Object}
     */
    relatedAttributeFunctions = {}

    /**
     * @deprecated Use clientDefs > {EntityType} > relationshipPanels > {link} > selectHandler.
     * @type {Object}
     */
    selectRelatedFilters = {}

    /**
     * @deprecated Use clientDefs > {EntityType} > relationshipPanels > {link} > selectHandler or
     *  clientDefs > {EntityType} > relationshipPanels > {link} > selectPrimaryFilter.
     * @type {Object}
     */
    selectPrimaryFilterNames = {}

    /**
     * @deprecated Use clientDefs > {EntityType} > relationshipPanels > {link} > selectHandler or
     *  clientDefs > {EntityType} > relationshipPanels > {link} > selectBoolFilterList.
     * @type {Object}
     */
    selectBoolFilterLists = []

    /**
     * Action 'createRelated'.
     *
     * @param {Object} data
     */
    actionCreateRelated(data) {
        data = data || {};

        let link = data.link;
        let scope = this.model.defs['links'][link].entity;
        let foreignLink = this.model.defs['links'][link].foreign;

        let attributes = {};

        if (
            this.relatedAttributeFunctions[link] &&
            typeof this.relatedAttributeFunctions[link] === 'function'
        ) {
            attributes = _.extend(this.relatedAttributeFunctions[link].call(this), attributes);
        }

        let attributeMap = this.getMetadata()
            .get(['clientDefs', this.scope, 'relationshipPanels', link, 'createAttributeMap']) ||
            this.relatedAttributeMap[link] || {};

        Object.keys(attributeMap)
            .forEach(attr => {
                attributes[attributeMap[attr]] = this.model.get(attr);
            });

        Espo.Ui.notify(' ... ');

        let handler = this.getMetadata()
            .get(['clientDefs', this.scope, 'relationshipPanels', link, 'createHandler']);

        new Promise(resolve => {
            if (!handler) {
                resolve({});

                return;
            }

            Espo.loader.requirePromise(handler)
                .then(Handler => new Handler(this.getHelper()))
                .then(handler => {
                    handler.getAttributes(this.model)
                        .then(attributes => resolve(attributes));
                });
        }).then(additionalAttributes => {
            attributes = {...attributes, ...additionalAttributes};

            let viewName = this.getMetadata()
                .get(['clientDefs', scope, 'modalViews', 'edit']) || 'views/modals/edit';

            this.createView('quickCreate', viewName, {
                scope: scope,
                relate: {
                    model: this.model,
                    link: foreignLink,
                },
                attributes: attributes,
            }, view => {
                view.render();
                view.notify(false);

                this.listenToOnce(view, 'after:save', () => {
                    if (data.fromSelectRelated) {
                        setTimeout(() => this.clearView('dialogSelectRelated'), 25);
                    }

                    this.updateRelationshipPanel(link);

                    this.model.trigger('after:relate');
                    this.model.trigger('after:relate:' + link);
                });
            });
        });
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * Action 'selectRelated'.
     *
     * @param {Object.<string, *>} data
     */
    actionSelectRelated(data) {
        let link = data.link;

        if (!data.foreignEntityType && !this.model.defs['links'][link]) {
            throw new Error('Link ' + link + ' does not exist.');
        }

        let scope = data.foreignEntityType || this.model.defs['links'][link].entity;
        let massRelateEnabled = data.massSelect;

        /** @var {Object.<string, *>} */
        let panelDefs = this.getMetadata().get(['clientDefs', this.scope, 'relationshipPanels', link]) || {};

        let advanced = {};

        if (link in this.selectRelatedFilters) {
            advanced = Espo.Utils.cloneDeep(this.selectRelatedFilters[link]) || advanced;

            for (let filterName in advanced) {
                if (typeof advanced[filterName] === 'function') {
                    let filtersData = advanced[filterName].call(this);

                    if (filtersData) {
                        advanced[filterName] = filtersData;
                    } else {
                        delete advanced[filterName];
                    }
                }
            }
        }

        let foreignLink = this.model.getLinkParam(link, 'foreign');

        if (foreignLink && scope) {
            // Select only records not related with any.
            let foreignLinkType = this.getMetadata()
                .get(['entityDefs', scope, 'links', foreignLink, 'type']);
            let foreignLinkFieldType = this.getMetadata()
                .get(['entityDefs', scope, 'fields', foreignLink, 'type']);

            if (
                ~['belongsTo', 'belongsToParent'].indexOf(foreignLinkType) &&
                foreignLinkFieldType &&
                !advanced[foreignLink] &&
                ~['link', 'linkParent'].indexOf(foreignLinkFieldType)
            ) {
                advanced[foreignLink] = {
                    type: 'isNull',
                    attribute: foreignLink + 'Id',
                    data: {
                        type: 'isEmpty',
                    },
                };
            }
        }

        let primaryFilterName = data.primaryFilterName || this.selectPrimaryFilterNames[link] || null;

        if (typeof primaryFilterName === 'function') {
            primaryFilterName = primaryFilterName.call(this);
        }

        let dataBoolFilterList = data.boolFilterList;

        if (typeof data.boolFilterList === 'string') {
            dataBoolFilterList = data.boolFilterList.split(',');
        }

        let boolFilterList = dataBoolFilterList ||
            panelDefs.selectBoolFilterList ||
            this.selectBoolFilterLists[link] ||
            [];

        if (typeof boolFilterList === 'function') {
            boolFilterList = boolFilterList.call(this);
        }

        boolFilterList = Espo.Utils.clone(boolFilterList);

        primaryFilterName = primaryFilterName || panelDefs.selectPrimaryFilter || null;

        let viewKey = data.viewKey || 'select';

        let viewName = panelDefs.selectModalView ||
            this.getMetadata().get(['clientDefs', scope, 'modalViews', viewKey]) ||
            'views/modals/select-records';

        Espo.Ui.notify(' ... ');

        let handler = panelDefs.selectHandler || null;

        new Promise(resolve => {
            if (!handler) {
                resolve({});

                return;
            }

            Espo.loader.requirePromise(handler)
                .then(Handler => new Handler(this.getHelper()))
                .then(/** module:handlers/select-related */handler => {
                    handler.getFilters(this.model)
                        .then(filters => resolve(filters));
                });
        }).then(filters => {
            advanced = {...advanced, ...(filters.advanced || {})};
            boolFilterList = [...boolFilterList, ...(filters.bool || [])];

            if (filters.primary && !primaryFilterName) {
                primaryFilterName = filters.primary;
            }

            this.createView('dialogSelectRelated', viewName, {
                scope: scope,
                multiple: true,
                createButton: data.createButton || false,
                triggerCreateEvent: true,
                filters: advanced,
                massRelateEnabled: massRelateEnabled,
                primaryFilterName: primaryFilterName,
                boolFilterList: boolFilterList,
                mandatorySelectAttributeList: panelDefs.selectMandatoryAttributeList,
            }, dialog => {
                dialog.render();

                Espo.Ui.notify(false);

                this.listenTo(dialog, 'create', () => {
                    this.actionCreateRelated({
                        link: data.link,
                        fromSelectRelated: true,
                    });
                });

                this.listenToOnce(dialog, 'select', (selectObj) => {
                    let data = {};

                    if (Object.prototype.toString.call(selectObj) === '[object Array]') {
                        let ids = [];

                        selectObj.forEach(model => ids.push(model.id));

                        data.ids = ids;
                    }
                    else {
                        if (selectObj.massRelate) {
                            data.massRelate = true;
                            data.where = selectObj.where;
                            data.searchParams = selectObj.searchParams;
                        }
                        else {
                            data.id = selectObj.id;
                        }
                    }

                    let url = this.scope + '/' + this.model.id + '/' + link;

                    Espo.Ajax.postRequest(url, data)
                        .then(() => {
                            Espo.Ui.success(this.translate('Linked'))

                            this.updateRelationshipPanel(link);

                            this.model.trigger('after:relate');
                            this.model.trigger('after:relate:' + link);
                        });
                });
            });
        });
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * Action 'duplicate'.
     */
    actionDuplicate() {
        Espo.Ui.notify(' ... ');

        Espo.Ajax
            .postRequest(this.scope + '/action/getDuplicateAttributes', {id: this.model.id})
            .then(attributes => {
                Espo.Ui.notify(false);

                let url = '#' + this.scope + '/create';

                this.getRouter().dispatch(this.scope, 'create', {
                    attributes: attributes,
                    returnUrl: this.getRouter().getCurrentUrl(),
                    options: {
                        duplicateSourceId: this.model.id,
                    },
                });

                this.getRouter().navigate(url, {trigger: false});
            });
    }
}

export default DetailView;
