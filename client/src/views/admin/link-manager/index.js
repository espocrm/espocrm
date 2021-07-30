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

define('views/admin/link-manager/index', 'view', function (Dep) {

    return Dep.extend({

        template: 'admin/link-manager/index',

        scopeDataList: null,

        scope: null,

        data: function () {
            return {
                linkDataList: this.linkDataList,
                scope: this.scope,
            };
        },

        events: {
            'click a[data-action="editLink"]': function (e) {
                var link = $(e.currentTarget).data('link');
                this.editLink(link);
            },
            'click button[data-action="createLink"]': function (e) {
                this.createLink();
            },
            'click [data-action="removeLink"]': function (e) {
                var link = $(e.currentTarget).data('link');
                this.confirm(this.translate('confirmation', 'messages'), function () {
                    this.removeLink(link);
                }, this);
            },
            'keyup input[data-name="quick-search"]': function (e) {
                this.processQuickSearch(e.currentTarget.value);
            },
        },

        computeRelationshipType: function (type, foreignType) {
            if (type == 'hasMany') {
                if (foreignType == 'hasMany') {
                    return 'manyToMany';
                }
                else if (foreignType == 'belongsTo') {
                    return 'oneToMany';
                }
                else {
                    return;
                }
            }
            else if (type == 'belongsTo') {
                if (foreignType == 'hasMany') {
                    return 'manyToOne';
                }
                else if (foreignType == 'hasOne') {
                    return 'oneToOneRight';
                }
                else {
                    return;
                }
            }
            else if (type == 'belongsToParent') {
                if (foreignType == 'hasChildren') {
                    return 'childrenToParent'
                }

                return;
            }
            else if (type == 'hasChildren') {
                if (foreignType == 'belongsToParent') {
                    return 'parentToChildren'
                }

                return;
            }
            else if (type === 'hasOne') {
                if (foreignType == 'belongsTo') {
                    return 'oneToOneLeft';
                }

                return;
            }
        },

        setupLinkData: function () {
            this.linkDataList = [];

            var links = this.getMetadata().get('entityDefs.' + this.scope + '.links');

            var linkList = Object.keys(links).sort((v1, v2) => {
                return v1.localeCompare(v2);
            });

            linkList.forEach((link) => {
                var d = links[link];

                var linkForeign = d.foreign;

                if (d.type === 'belongsToParent') {
                    var type = 'childrenToParent';
                }
                else {
                    if (!d.entity) {
                        return;
                    }

                    if (!linkForeign) {
                        return;
                    }

                    var foreignType = this.getMetadata()
                        .get('entityDefs.' + d.entity + '.links.' + d.foreign + '.type');

                    var type = this.computeRelationshipType(d.type, foreignType);
                }

                if (!type) {
                    return;
                }

                this.linkDataList.push({
                    link: link,
                    isCustom: d.isCustom,
                    isRemovable: d.isCustom,
                    customizable: d.customizable,
                    type: type,
                    entityForeign: d.entity,
                    entity: this.scope,
                    labelEntityForeign: this.getLanguage().translate(d.entity, 'scopeNames'),
                    linkForeign: linkForeign,
                    label: this.getLanguage().translate(link, 'links', this.scope),
                    labelForeign: this.getLanguage().translate(d.foreign, 'links', d.entity),
                });
            });
        },

        setup: function () {
            this.scope = this.options.scope || null;

            this.setupLinkData();

            this.on('after:render', () => {
                this.renderHeader();
            });
        },

        afterRender: function () {
            this.$noData = this.$el.find('.no-data');
        },

        createLink: function () {
            this.createView('edit', 'views/admin/link-manager/modals/edit', {
                scope: this.scope
            }, (view) => {
                view.render();

                this.listenTo(view, 'after:save', () => {
                    this.clearView('edit');
                    this.setupLinkData();
                    this.render();
                });

                this.listenTo(view, 'close', () => {
                    this.clearView('edit');
                });
            });
        },

        editLink: function (link) {
            this.createView('edit', 'views/admin/link-manager/modals/edit', {
                scope: this.scope,
                link: link,
            }, (view) => {
                view.render();

                this.listenTo(view, 'after:save', () => {
                    this.clearView('edit');
                    this.setupLinkData();
                    this.render();
                });

                this.listenTo(view, 'close', () => {
                    this.clearView('edit');
                }, this);
            });
        },

        removeLink: function (link) {
            $.ajax({
                url: 'EntityManager/action/removeLink',
                type: 'POST',
                data: JSON.stringify({
                    entity: this.scope,
                    link: link,
                })
            }).then(() => {
                this.$el.find('table tr[data-link="'+link+'"]').remove();

                this.getMetadata().loadSkipCache().then(() => {
                    this.setupLinkData();

                    this.render();
                });
            });
        },

        renderHeader: function () {
            if (!this.scope) {
                $('#scope-header').html('');
                return;
            }

            $('#scope-header').show().html(this.getLanguage().translate(this.scope, 'scopeNames'));
        },

        updatePageTitle: function () {
            this.setPageTitle(this.getLanguage().translate('Entity Manager', 'labels', 'Admin'));
        },

        processQuickSearch: function (text) {
            text = text.trim();

            let $noData = this.$noData;

            $noData.addClass('hidden');

            if (!text) {
                this.$el.find('table tr.link-row').removeClass('hidden');

                return;
            }

            let matchedList = [];

            let lowerCaseText = text.toLowerCase();

            this.linkDataList.forEach(item => {
                let matched = false;

                if (
                    item.label.toLowerCase().indexOf(lowerCaseText) === 0 ||
                    item.link.toLowerCase().indexOf(lowerCaseText) === 0 ||
                    item.entityForeign.toLowerCase().indexOf(lowerCaseText) === 0 ||
                    item.labelEntityForeign.toLowerCase().indexOf(lowerCaseText) === 0
                ) {
                    matched = true;
                }

                if (!matched) {
                    let wordList = item.link.split(' ')
                        .concat(
                            item.label.split(' ')
                        )
                        .concat(
                            item.entityForeign.split(' ')
                        )
                        .concat(
                            item.labelEntityForeign.split(' ')
                        );

                    wordList.forEach((word) => {
                        if (word.toLowerCase().indexOf(lowerCaseText) === 0) {
                            matched = true;
                        }
                    });
                }

                if (matched) {
                    matchedList.push(item.link);
                }
            });

            if (matchedList.length === 0) {
                this.$el.find('table tr.link-row').addClass('hidden');

                $noData.removeClass('hidden');

                return;
            }

            this.linkDataList
                .map(item => item.link)
                .forEach(scope => {
                    if (!~matchedList.indexOf(scope)) {
                        this.$el.find('table tr.link-row[data-link="'+scope+'"]').addClass('hidden');

                        return;
                    }

                    this.$el.find('table tr.link-row[data-link="'+scope+'"]').removeClass('hidden');
                });
        },
    });
});
