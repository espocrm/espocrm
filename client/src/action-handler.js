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

/** @module action-handler */

import {View as BullView} from 'lib!bullbone';

/**
 * An action handler. To be extended by specific action handlers.
 */
class ActionHandler {

    /**
     * @param {module:view} view A view.
     */
    constructor(view) {
        /**
         * @protected
         */
        this.view = view;
    }

    /**
     * @deprecated Use `this.view`.
     */
    getConfig() {
        return this.view.getConfig();
    }

    /**
     * @deprecated Use `this.view`.
     */
    getMetadata() {
        return this.view.getMetadata();
    }

    /**
     * @deprecated Use `this.view`.
     */
    getAcl() {
        return this.view.getAcl();
    }

    /**
     * @deprecated Use `this.view`.
     */
    getUser() {
        return this.view.getUser();
    }

    /**
     * @deprecated Use `this.view`.
     */
    getRouter() {
        return this.view.getRouter();
    }

    /**
     * @deprecated Use `this.view`.
     */
    getHelper() {
        return this.view.getHelper();
    }

    /**
     * @deprecated Use `this.view`.
     */
    getLanguage() {
        return this.view.getLanguage();
    }

    /**
     * @deprecated Use `this.view`.
     */
    getModelFactory() {
        return this.view.getModelFactory();
    }

    /**
     * @deprecated Use `this.view`.
     */
    getCollectionFactory() {
        return this.view.getCollectionFactory();
    }

    /**
     * @deprecated Use `Espo.Ajax`.
     */
    ajaxPostRequest() {
        return Espo.Ajax.postRequest.apply(this.view, arguments);
    }

    /**
     * @deprecated Use `Espo.Ajax`.
     */
    ajaxPutRequest() {
        return Espo.Ajax.putRequest.apply(this.view, arguments);
    }

    /**
     * @deprecated Use `Espo.Ajax`.
     */
    ajaxGetRequest() {
        return Espo.Ajax.getRequest.apply(this.view, arguments);
    }

    /**
     * @deprecated Use `this.view`.
     */
    confirm() {
        return this.view.confirm.apply(this.view, arguments);
    }
}

ActionHandler.extend = BullView.extend;

export default ActionHandler;
