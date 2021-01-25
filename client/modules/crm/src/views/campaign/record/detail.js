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

define('crm:views/campaign/record/detail', 'views/record/detail', function (Dep) {

    return Dep.extend({

        duplicateAction: true,

        bottomView: 'crm:views/campaign/record/detail-bottom',

        setupActionItems: function () {
            Dep.prototype.setupActionItems.call(this);
            this.dropdownItemList.push({
                'label': 'Generate Mail Merge PDF',
                'name': 'generateMailMergePdf',
                'hidden': !this.isMailMergeAvailable()
            });

            this.listenTo(this.model, 'change', function () {
                if (this.isMailMergeAvailable()) {
                    this.showActionItem('generateMailMergePdf');
                } else {
                    this.hideActionItem('generateMailMergePdf');
                }
            }, this);
        },

        afterRender: function () {
        	Dep.prototype.afterRender.call(this);
        },

        isMailMergeAvailable: function () {
            if (this.model.get('type') !== 'Mail') return false;

            if (!this.model.get('targetListsIds') || !this.model.get('targetListsIds').length) return false;

            if (
                !this.model.get('leadsTemplateId')
                &&
                !this.model.get('contactsTemplateId')
                &&
                !this.model.get('accountsTemplateId')
                &&
                !this.model.get('usersTemplateId')
            ) return false;

            return true;
        },

        actionGenerateMailMergePdf: function () {
            this.createView('dialog', 'crm:views/campaign/modals/mail-merge-pdf', {
                model: this.model
            }, function (view) {
                view.render();
                this.listenToOnce(view, 'proceed', function (link) {
                    this.clearView('dialog');
                    this.ajaxPostRequest('Campaign/action/generateMailMergePdf', {
                        campaignId: this.model.id,
                        link: link
                    }).then(function (response) {
                        window.open('?entryPoint=download&id=' + response.id, '_blank');
                    }.bind(this));
                }, this);
            });
        },

    });
});
