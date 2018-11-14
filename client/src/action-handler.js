/**LICENSE**/

define('action-handler', [], function () {

    var ActionHandler = function (view) {
        this.view = view;
    }

    _.extend(ActionHandler.prototype, {

        getConfig: function () {
            return this.view.getConfig();
        },

        getMetadata: function () {
            return this.view.getMetadata();
        },

        getAcl: function () {
            return this.view.getAcl();
        },

        getUser: function () {
            return this.view.getUser();
        },

        getRouter: function () {
            return this.view.getRouter();
        },

        getHelper: function () {
            return this.view.getHelper();
        },

        getLanguage: function () {
            return this.view.getLanguage();
        },

        getModelFactory: function () {
            return this.view.getModelFactory();
        },

        getCollectionFactory: function () {
            return this.view.getCollectionFactory();
        },

        ajaxPostRequest: function () {
            return this.view.ajaxPostRequest.apply(this.view, arguments);
        },

        ajaxPutRequest: function () {
            return this.view.ajaxPutRequest.apply(this.view, arguments);
        },

        ajaxGetRequest: function () {
            return this.view.ajaxGetRequest.apply(this.view, arguments);
        },

        confirm: function () {
            return this.view.confirm.apply(this.view, arguments);
        }
    });

    ActionHandler.extend = Backbone.Router.extend;

    return ActionHandler;
});
