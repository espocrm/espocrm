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

define('views/import/record/detail', 'views/record/detail', function (Dep) {

    return Dep.extend({

        readOnly: true,

        returnUrl: '#Import/list',

        checkInterval: 5,

        resultPanelFetchLimit: 10,

        setup: function () {
            Dep.prototype.setup.call(this);

            this.fetchCounter = 0;

            this.setupChecking();

            this.hideActionItem('delete');
        },

        setupChecking: function () {
            if (!this.model.has('status')) {
                this.listenToOnce(this.model, 'sync', this.setupChecking.bind(this));

                return;
            }

            if (!~['In Process', 'Pending', 'Standby'].indexOf(this.model.get('status'))) {
                return;
            }

            setTimeout(this.runChecking.bind(this), this.checkInterval * 1000);

            this.on('remove', () => {
                this.stopChecking = true;
            },);
        },

        runChecking: function () {
            if (this.stopChecking) {
                return;
            }

            this.model.fetch().then(() => {
                let isFinished = !~['In Process', 'Pending', 'Standby'].indexOf(this.model.get('status'));

                if (this.fetchCounter < this.resultPanelFetchLimit && !isFinished) {
                    this.fetchResultPanels();
                }

                if (isFinished) {
                    this.fetchResultPanels();

                    return;
                }

                setTimeout(this.runChecking.bind(this), this.checkInterval * 1000);
            });

            this.fetchCounter++;
        },

        fetchResultPanels: function () {
            var bottomView = this.getView('bottom');

            if (!bottomView) {
                return;
            }

            var importedView = bottomView.getView('imported');

            if (importedView && importedView.collection) {
                importedView.collection.fetch();
            }

            var duplicatesView = bottomView.getView('duplicates');

            if (duplicatesView && duplicatesView.collection) {
                duplicatesView.collection.fetch();
            }

            var updatedView = bottomView.getView('updated');

            if (updatedView && updatedView.collection) {
                updatedView.collection.fetch();
            }
        },
    });
});
