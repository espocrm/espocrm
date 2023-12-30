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

import SelectRecordsModal from 'views/modals/select-records';
import ListWithCategories from 'views/list-with-categories';

class SelectRecordsWithCategoriesModalView extends SelectRecordsModal {

    template = 'modals/select-records-with-categories'

    // Used in applyCategoryToCollection.
    // noinspection JSUnusedGlobalSymbols
    categoryField = 'category'
    // noinspection JSUnusedGlobalSymbols
    categoryFilterType = 'inCategory'
    categoryScope = ''
    isExpanded = true

    data() {
        return {
            ...super.data(),
            categoriesDisabled: this.categoriesDisabled,
        };
    }

    setup() {
        this.scope = this.entityType = this.options.scope || this.scope;
        this.categoryScope = this.categoryScope || this.scope + 'Category';

        this.categoriesDisabled = this.categoriesDisabled ||
           this.getMetadata().get(['scopes',  this.categoryScope, 'disabled']) ||
           !this.getAcl().checkScope(this.categoryScope);

        super.setup();
    }

    setupList() {
        if (!this.categoriesDisabled) {
            this.setupCategories();
        }

        super.setupList();
    }

    setupCategories() {
        this.getCollectionFactory().create(this.categoryScope, collection => {
            this.treeCollection = collection;

            collection.url = collection.entityType + '/action/listTree';
            collection.data.onlyNotEmpty = true;

            collection.fetch()
                .then(() => this.createCategoriesView());
        });
    }

    createCategoriesView() {
        this.createView('categories', 'views/record/list-tree', {
            collection: this.treeCollection,
            selector: '.categories-container',
            selectable: true,
            readOnly: true,
            showRoot: true,
            rootName: this.translate(this.scope, 'scopeNamesPlural'),
            buttonsDisabled: true,
            checkboxes: false,
            isExpanded: this.isExpanded,
        }, view => {
            if (this.isRendered()) {
                view.render();
            } else {
                this.listenToOnce(this, 'after:render', () => view.render());
            }

            this.listenTo(view, 'select', model => {
                this.currentCategoryId = null;
                this.currentCategoryName = '';

                if (model && model.id) {
                    this.currentCategoryId = model.id;
                    this.currentCategoryName = model.get('name');
                }

                this.applyCategoryToCollection();

                Espo.Ui.notify(' ... ');

                this.collection.fetch()
                    .then(() => Espo.Ui.notify(false));
            });
        });
    }

    applyCategoryToCollection() {
        ListWithCategories.prototype.applyCategoryToCollection.call(this);
    }

    // noinspection JSUnusedGlobalSymbols
    isCategoryMultiple() {
        ListWithCategories.prototype.isCategoryMultiple.call(this);
    }
}

// noinspection JSUnusedGlobalSymbols
export default SelectRecordsWithCategoriesModalView;
