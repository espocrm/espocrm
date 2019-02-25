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

Espo.define('views/import/detail', 'views/detail', function (Dep) {

    return Dep.extend({

        getHeader: function () {
        	var dt = this.model.get('createdAt');
        	dt = this.getDateTime().toDisplay(dt);
            var name = Handlebars.Utils.escapeExpression(dt);

            return this.buildHeaderHtml([
                '<a href="#' + this.model.name + '/list">' + this.getLanguage().translate(this.model.name, 'scopeNamesPlural') + '</a>',
                name
            ]);
        },

        setup: function () {
            Dep.prototype.setup.call(this);

            this.setupMenu();

            this.listenTo(this.model, 'change', function () {
                this.setupMenu();
                if (this.isRendered()) {
                    this.getView('header').reRender();
                }
            }, this);
        },

        setupMenu: function () {
            if (this.model.get('importedCount')) {
                var i = 0;
                this.menu.buttons.forEach(function (item) {
                    if (item.action == 'revert') {
                        i = 1;
                    }
                }, this);
                if (!i) {
                    this.menu.buttons.unshift({
                       "label": "Revert Import",
                       "action": "revert",
                       "style": "danger",
                       "acl": "edit",
                        title: this.translate('revert', 'messages', 'Import')
                    });
                }
            }
            if (this.model.get('duplicateCount')) {
                var i = 0;
                this.menu.buttons.forEach(function (item) {
                    if (item.action == 'removeDuplicates') {
                        i = 1;
                    }
                }, this);
                if (!i) {
                    this.menu.buttons.unshift({
                       "label": "Remove Duplicates",
                       "action": "removeDuplicates",
                       "style": "default",
                       "acl": "edit",
                        title: this.translate('removeDuplicates', 'messages', 'Import')
                    });
                }
            }

            this.addMenuItem('buttons', {
                label: "Remove Import Log",
                action: "removeImportLog",
                name: 'removeImportLog',
                style: "default",
                acl: "delete",
                title: this.translate('removeImportLog', 'messages', 'Import')
            }, true);
        },

        actionRemoveImportLog: function () {
            this.confirm(this.translate('confirmRemoveImportLog', 'messages', 'Import'), function () {
                this.disableMenuItem('removeImportLog');

                Espo.Ui.notify(this.translate('pleaseWait', 'messages'));
                this.model.destroy({
                    wait: true
                }).then(function () {
                    Espo.Ui.notify(false);
                    var collection = this.model.collection;
                    if (collection) {
                        if (collection.total > 0) {
                            collection.total--;
                        }
                    }
                    this.getRouter().navigate('#Import/list', {trigger: true});

                    this.removeMenuItem('removeImportLog', true);
                }.bind(this));

            }, this);
        },

        actionRevert: function () {
        	this.confirm(this.translate('confirmRevert', 'messages', 'Import'), function () {
                this.disableMenuItem('revert');
                Espo.Ui.notify(this.translate('pleaseWait', 'messages'));

	        	$.ajax({
	        		type: 'POST',
	        		url: 'Import/action/revert',
	        		data: JSON.stringify({
	        			id: this.model.id
	        		})
	        	}).done(function () {
                    Espo.Ui.notify(false);

	        		this.getRouter().navigate('#Import/list', {trigger: true});
	        	}.bind(this));
        	}, this);
        },

        actionRemoveDuplicates: function () {
        	this.confirm(this.translate('confirmRemoveDuplicates', 'messages', 'Import'), function () {
                this.disableMenuItem('removeDuplicates');

                Espo.Ui.notify(this.translate('pleaseWait', 'messages'));

	        	$.ajax({
	        		type: 'POST',
	        		url: 'Import/action/removeDuplicates',
	        		data: JSON.stringify({
	        			id: this.model.id
	        		})
	        	}).done(function () {
                    this.removeMenuItem('removeDuplicates', true);

                    this.model.fetch();
                    this.model.trigger('update-all');
                    Espo.Ui.success(this.translate('duplicatesRemoved', 'messages', 'Import'))
	        	}.bind(this));
        	}, this);
        }

    });
});

