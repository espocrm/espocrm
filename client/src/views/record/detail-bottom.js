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

define('views/record/detail-bottom', 'views/record/panels-container', function (Dep) {

    return Dep.extend({

        template: 'record/bottom',

        mode: 'detail',

        streamPanel: true,

        relationshipPanels: true,

        readOnly: false,

        portalLayoutDisabled: false,

        setupPanels: function () {
            var scope = this.scope;

            this.panelList = Espo.Utils.clone(
                this.getMetadata().get(['clientDefs', scope, 'bottomPanels', this.type]) || this.panelList || []
            );

            this.panelList.forEach(function (item) {
                if ('index' in item) {
                    item.index = item.index;
                } else if ('order' in item) {
                    item.index = item.order;
                }
            });

            if (this.streamPanel && this.getMetadata().get(['scopes', scope, 'stream'])) {
                this.setupStreamPanel();
            }
        },

        setupStreamPanel: function () {
            var streamAllowed = this.getAcl().checkModel(this.model, 'stream', true);

            if (streamAllowed === null) {
                this.listenToOnce(this.model, 'sync', () => {
                    streamAllowed = this.getAcl().checkModel(this.model, 'stream', true);

                    if (streamAllowed) {
                        this.onPanelsReady(() => {
                            this.showPanel('stream', 'acl');
                        });
                    }
                });
            }
            if (streamAllowed !== false) {
                this.panelList.push({
                    name: 'stream',
                    label: 'Stream',
                    view: this.getMetadata().get(['clientDefs', this.scope, 'streamPanelView']) || 'views/stream/panel',
                    sticked: true,
                    hidden: !streamAllowed,
                    index: 2,
                });

                if (!streamAllowed) {
                    this.recordHelper.setPanelStateParam('stream', 'hiddenAclLocked', true);
                }
            }
        },

        init: function () {
            this.recordHelper = this.options.recordHelper;
            this.scope = this.entityType = this.model.name;

            this.readOnlyLocked = this.options.readOnlyLocked || this.readOnly;
            this.readOnly = this.options.readOnly || this.readOnly;
            this.inlineEditDisabled = this.options.inlineEditDisabled || this.inlineEditDisabled;

            this.portalLayoutDisabled = this.options.portalLayoutDisabled || this.portalLayoutDisabled;

            this.recordViewObject = this.options.recordViewObject;
        },

        setup: function () {
            this.type = this.mode;

            if ('type' in this.options) {
                this.type = this.options.type;
            }

            this.panelList = [];

            this.setupPanels();

            this.wait(true);

            Promise.all([
                new Promise((resolve) => {
                    this.getHelper().layoutManager.get(
                        this.scope,
                        'bottomPanels' + Espo.Utils.upperCaseFirst(this.type),
                        (layoutData) => {
                            this.layoutData = layoutData;

                            resolve();
                        }
                    );
                })
            ]).then(() => {
                var panelNameList = [];

                this.panelList = this.panelList.filter((p) => {
                    panelNameList.push(p.name);

                    if (p.aclScope) {
                        if (!this.getAcl().checkScope(p.aclScope)) {
                            return;
                        }
                    }

                    if (p.accessDataList) {
                        if (!Espo.Utils.checkAccessDataList(p.accessDataList, this.getAcl(), this.getUser())) {
                            return false;
                        }
                    }

                    return true;
                });

                if (this.relationshipPanels) {
                    var linkDefs = (this.model.defs || {}).links || {};

                    if (this.layoutData) {
                        for (var name in this.layoutData) {
                            if (!linkDefs[name]) {
                                continue;
                            }

                            var p = this.layoutData[name];

                            if (!~panelNameList.indexOf(name) && !p.disbled) {
                                this.addRelationshipPanel(name, p);
                            }
                        }
                    }
                }

                this.panelList = this.panelList.map((p) => {
                    var item = Espo.Utils.clone(p);

                    if (this.recordHelper.getPanelStateParam(p.name, 'hidden') !== null) {
                        item.hidden = this.recordHelper.getPanelStateParam(p.name, 'hidden');
                    }
                    else {
                        this.recordHelper.setPanelStateParam(p.name, 'hidden', item.hidden || false);
                    }

                    return item;
                });

                this.panelList.forEach((item) => {
                    item.actionsViewKey = item.name + 'Actions';
                });

                this.alterPanels();

                this.setupPanelsFinal();

                this.setupPanelViews();

                this.wait(false);
            });
        },

        setReadOnly: function () {
            this.readOnly = true;
        },

        addRelationshipPanel: function (name, item) {
            var scope = this.scope;
            var scopesDefs = this.getMetadata().get('scopes') || {};

            var p;

            if (typeof item === 'string' || item instanceof String) {
                p = {name: item};
            }
            else {
                p = Espo.Utils.clone(item || {});
            }

            p.name = p.name || name;
            if (!p.name) {
                return;
            }

            if (typeof p.order === 'undefined') p.order = 5;

            var name = p.name;

            var links = (this.model.defs || {}).links || {};
            if (!(name in links)) {
                return;
            }

            var foreignScope = links[name].entity;

            if ((scopesDefs[foreignScope] || {}).disabled) {
                return;
            }

            if (!this.getAcl().check(foreignScope, 'read')) {
                return;
            }

            var defs = this.getMetadata().get(['clientDefs', scope, 'relationshipPanels', name]) || {};
            defs = Espo.Utils.clone(defs);

            for (var i in defs) {
                if (i in p) {
                    continue;
                }

                p[i] = defs[i];
            }

            if (!p.view) {
                p.view = 'views/record/panels/relationship';
            }

            if (this.recordHelper.getPanelStateParam(p.name, 'hidden') !== null) {
                p.hidden = this.recordHelper.getPanelStateParam(p.name, 'hidden');
            }
            else {
                this.recordHelper.setPanelStateParam(p.name, 'hidden', p.hidden || false);
            }

            this.panelList.push(p);
        },

    });
});
