var Bull = Bull || {};

(function (Bull, _) {

    var root = this;

    /**
     * Bull.Factory is a factory for views.
     * It has hard dependency from Backbone.js and uses Handlebars.js templating system by default.
     *
     */

    /**
     * @constructor
     * @param {Object} options Configuration options.
     * <ul>
     *  <li>defaultViewName: {String} Default name for views when it is not defined.</li>
     *  <li>viewLoader: {Function} Function that loads view class ({Function} in javascript) by the given view name and callback function as parameters. Here you can load js code using sync XHR request. If not defined it will lookup classes in window object.</li>
     *  <li>helper: {Object} View Helper that will be injected into all views.</li>
     *  <li>resources: {Object} Resources loading options: paths, exts, loaders. Example: <br>
     *    <i>{
     *      paths: { // Custom paths for resource files.
     *        layout: 'resources/layouts',
     *        templates: 'resources/templates',
     *        layoutTemplate: 'resources/templates/layouts',
     *      },
     *      exts: { // Custom extensions of resource files.
     *        layout: 'json',
     *        templates: 'tpl',
     *      },
     *      loaders: { // Custom resources loading functions. Define it if some type of resources needs to be loaded via REST rather than from file.
     *        layout: function (layoutName, callback) {
     *          callback(layoutManager.getLayout(layoutName));
     *        }
     *      },
     *      path: function (type, name) {} // Custom path function. Should return path to the needed resource.
     *    }</i>
     *  </li>
     *  <li>rendering: {Object} Rendering options: method (Method is the custom function for a rendering. Define it if you want to use another templating engine. <i>Function (template, data)</i>).</li>
     *  <li>templating: {Object} Templating options: {bool} compilable (If templates are compilable (like Handlebars). True by default.)</li>
     * </ul>
     */
    Bull.Factory = function (options) {
        var options = options || {};

        this.defaultViewName = options.defaultViewName || this.defaultViewName;

        this._loader = options.customLoader || new Bull.Loader(options.resources || {});
        this._renderer = options.customRenderer || new Bull.Renderer(options.rendering || {});
        this._layouter = options.customLayouter || new Bull.Layouter(_.extend(options.layouting || {}, {
            loader: this._loader,
        }));
        this._templator = options.customTemplator || new Bull.Templator(_.extend(options.templating || {}, {
            loader: this._loader,
            layouter: this._layouter,
        }));

        this._helper = options.helper || null;

        this._viewClassHash = {};
        this._getViewClassFunction = options.viewLoader || this._getViewClassFunction;
        this._viewLoader = this._getViewClassFunction;
    };

    _.extend(Bull.Factory.prototype, {

        defaultViewName: 'View',

        _layouter: null,

        _templator: null,

        _renderer: null,

        _loader: null,

        _helper: null,

        _viewClassHash: null,

        _viewLoader: null,

        _getViewClassFunction: function (viewName, callback) {
            var viewClass = root[viewName];
            if (typeof viewClass !== "function") {
                throw new Error("function \"" + viewClass + "\" not found.");
            }
            callback(viewClass);
        },

        _getViewClass: function (viewName, callback) {
            if (viewName in this._viewClassHash) {
                callback(this._viewClassHash[viewName]);
                return;
            }
            this._getViewClassFunction(viewName, function (viewClass) {
                this._viewClassHash[viewName] = viewClass;
                callback(viewClass);
            }.bind(this));
        },

        /**
         * Create view.
         * @param viewName
         * @param {Object} options
         * @param {Function} callback Will be invoked once view gets ready and view will be passed as an argument.
         * @return {Bull.View}
         */
        create: function (viewName, options, callback) {
            this._getViewClass(viewName, function (viewClass) {
                if (typeof viewClass === 'undefined') {
                    throw new Error("Class for view \"" + viewName + "\" not found.");
                }
                var view = new viewClass(_.extend(options || {}, {
                    factory: this,
                    layouter: this._layouter,
                    templator: this._templator,
                    renderer: this._renderer,
                    helper: this._helper,
                    onReady: callback
                }));
            }.bind(this));
        },
    });

}).call(this, Bull, _);

(function (Bull, Backbone, _) {

    Bull.View = Backbone.View.extend({

        /**
         * @property {String} Template name.
         */
        template: null,

        /**
         * @property {String} Layout name. Used if template is not specified to build template.
         */
        layout: null,

        /**
         * @property {String} Name of View. If template name is not defined it will be used to cache built template and layout. Otherwise they won't be cached. Name it unique.
         */
        name: null,

        /**
         * @property {Object} or {function} Data that will be passed into template.
         */
        data: null,

        /**
         * @property {bool} Not to use cache for layouts. Use it if layouts are dynamic.
         */
        noCache: false,

        /**
         * @property {bool} Not to rended view automatical when build view tree. Afterwards it can be rendered manually.
         */
        notToRender: false,

        /**
         * @property {String} Template itself.
         */
        _template: null,

        /**
         * @property {Object} Layout itself.
         */
        _layout: null,

        layoutData: null,

        isReady: false,

        /**
         * @property {Object} Definitions for nested views that should be automaticaly created. Example: {body: {view: 'Body', selector: '> .body'}}.
         */
        views: null,

        nestedViews: null,

        _nestedViewDefs: null,

        _factory: null,

        factory: null,

        _templator: null,

        _renderer: null,

        _layouter: null,

        _helper: null,

        _templateCompiled: null,

        _parentView: null,

        _path: '',

        _wait: false,

        _waitViewList: null,

        optionsToPass: null,

        _nestedViewsFromLayoutLoaded: false,

        _readyConditionList: null,

        _isRendered: false,

        _isFullyRendered: false,

        _isBeingRendered: false,

        _isRemoved: false,

        _isRenderCanceled: false,

        initialize: function (options) {
            this.options = options || {};

            this._factory = this.factory = this.options.factory || null;
            this._renderer = this.options.renderer || null;
            this._templator = this.options.templator || null;
            this._layouter = this.options.layouter || null;

            this._helper = this.options.helper || null;

            if ('noCache' in this.options) {
                this.noCache = this.options.noCache;
            }

            this.name = this.options.name || this.name;

            this.notToRender = ('notToRender' in this.options) ? this.options.notToRender : this.notToRender;

            this.data = this.options.data || this.data;

            this.nestedViews = {};
            this._nestedViewDefs = {};

            if (this._waitViewList == null) {
                this._waitViewList = [];
            }

            this._waitPromiseCount = 0;

            if (this._readyConditionList == null) {
                this._readyConditionList = [];
            }

            this.optionsToPass = this.options.optionsToPass || this.optionsToPass || [];

            var merge = function (target, source) {
                for (var prop in source) {
                    if (typeof target == 'object') {
                        if (prop in target) {
                            merge(target[prop], source[prop]);
                        } else {
                            target[prop] = source[prop];
                        }
                    }
                }
                return target;
            }

            if (this.views || this.options.views) {
                this.views = merge(this.options.views || {}, this.views || {});
            }

            this.init();
            this.setup();
            this.setupFinal();

            this.template = this.options.template || this.template;
            this.layout = this.options.layout || this.layout;
            this._layout = this.options._layout || this._layout;
            this.layoutData = this.options.layoutData || this.layoutData;

            this._template = this.templateContent || this._template;

            if (this._template != null && this._templator.compilable) {
                this._templateCompiled = this._templator.compileTemplate(this._template);
            }

            if (this.options.el) {
                this.setElementInAdvance(this.options.el);
            }

            var _layout = this._getLayout();

            var loadNestedViews = function () {
                this._loadNestedViews(function () {
                    this._nestedViewsFromLayoutLoaded = true;
                    this._tryReady();
                }.bind(this));
            }.bind(this);

            if (this.layout != null || _layout !== null) {
                if (_layout === null) {
                    this._layouter.getLayout(this.layout, function (_layout) {
                        this._layout = _layout;
                        loadNestedViews();
                    }.bind(this));
                    return;
                }
                loadNestedViews();
                return;
            } else {
                if (this.views != null) {
                    loadNestedViews();
                    return;
                }
            }
            this._nestedViewsFromLayoutLoaded = true;

            this._tryReady();
        },

        /**
         * Init view. Empty method by default. Is run before #setup.
         */
        init: function () {},

        /**
         * Setup view. Empty method by default. Is run after #init.
         */
        setup: function () {},

        setupFinal: function () {},

        /**
         * Set view container element if doesn't exist yet. It will call setElement after render.
         */
        setElementInAdvance: function (el) {
            if (this._setElementInAdvancedInProcess) return;
            this._setElementInAdvancedInProcess = true;

            this.on("after:render-internal", function () {
                this.setElement(el);
                this._setElementInAdvancedInProcess = false;
            }.bind(this));
        },

        getSelector: function () {
            return this.options.el || null;
        },

        setSelector: function (selector) {
            this.options.el = selector;
        },

        /**
         * Checks whether view has been already rendered.
         * @return {Bool}
         */
        isRendered: function () {
            return this._isRendered;
        },

        /**
         * Checks whether view has been fully rendered (afterRender has been executed).
         * @return {Bool}
         */
        isFullyRendered: function () {
            return this._isFullyRendered
        },

        isBeingRendered: function () {
            return this._isBeingRendered;
        },

        isRemoved: function () {
            return this._isRemoved;
        },

        /**
         * Get HTML of view but don't render it.
         */
        getHtml: function (callback) {
            this._getHtml(callback);
        },

        cancelRender: function () {
            if (this.isBeingRendered()) {
                this._isRenderCanceled = true;
            }
        },

        uncancelRender: function () {
            this._isRenderCanceled = false;
        },

        /**
         * Render view.
         */
        render: function (callback) {
            this._isRendered = false;
            this._isFullyRendered = false;

            return new Promise(function (resolve, reject) {
                this._getHtml(function (html) {
                    if (this._isRenderCanceled) {
                        this._isRenderCanceled = false;
                        this._isBeingRendered = false;
                        reject();
                        return;
                    }
                    if (this.$el.size()) {
                        this.$el.html(html);
                    } else {
                        if (this.options.el) {
                           this.setElement(this.options.el);
                        }
                        this.$el.html(html);
                    }
                    this._afterRender();
                    if (typeof callback === 'function') {
                        callback();
                    }
                    resolve(this);
                }.bind(this));
            }.bind(this));
        },

        /**
         * Re-render view.
         */
        reRender: function (force) {
            if (this.isRendered()) {
                return this.render();
            } else if (this.isBeingRendered()) {
                return new Promise(function (resolve, reject) {
                    this.once('after:render', function () {
                        this.render().then(resolve).catch(reject);
                    }, this);
                }.bind(this));
            } else {
                if (force) {
                    return this.render();
                }
            }
        },

        _afterRender: function () {
            this._isBeingRendered = false;
            this._isRendered = true;
            this.trigger("after:render-internal", this);
            for (var key in this.nestedViews) {
                var nestedView = this.nestedViews[key];
                if (!nestedView.notToRender) {
                    nestedView._afterRender();
                }
            }
            this.afterRender();
            this.trigger("after:render", this);
            this._isFullyRendered = true;
        },

        /**
         * Executed after render. Empty method by default.
         */
        afterRender: function () {},

        _tryReady: function () {
            if (this.isReady) return;

            if (this._wait) return;

            if (!this._nestedViewsFromLayoutLoaded) return;

            for (var i = 0; i < this._waitViewList.length; i++) {
                if (!this.hasView(this._waitViewList[i])) return;
            }

            if (this._waitPromiseCount) return;

            for (var i = 0; i < this._readyConditionList.length; i++) {
                if (typeof this._readyConditionList[i] === 'function') {
                    if (!this._readyConditionList[i]()) return;
                } else {
                    if (!this._readyConditionList) return;
                }
            }

            this._makeReady();
        },

        /**
         * Run checking for view is ready.
         */
        tryReady: function () {
            this._tryReady();
        },

        _makeReady: function () {
            this.isReady = true;
            this.trigger('ready');
            if (typeof this.options.onReady === 'function') {
                this.options.onReady(this);
            }
        },

        _addDefinedNestedViewDefs: function (list) {
            for (var name in this.views) {
                var o = _.clone(this.views[name]);
                o.name = name;
                list.push(o);
                this._nestedViewDefs[name] = o;
            }
            return list
        },

        _getNestedViewsFromLayout: function () {
            var nestedViewDefs = this._layouter.findNestedViews(this._getLayoutName(), this._getLayout() || null, this.noCache);

            if (Object.prototype.toString.call(nestedViewDefs) !== '[object Array]') {
                throw new Error("Bad layout. It should be an Array.");
            }

            var nestedViewDefsFiltered = [];
            for (var i in nestedViewDefs) {
                var key = nestedViewDefs[i].name;

                this._nestedViewDefs[key] = nestedViewDefs[i];

                if ('view' in nestedViewDefs[i] && nestedViewDefs[i].view === true) {
                    if (!('layout' in nestedViewDefs[i] || 'template' in nestedViewDefs[i])) {
                        continue;
                    }
                }
                nestedViewDefsFiltered.push(nestedViewDefs[i]);
            }

            return nestedViewDefsFiltered;
        },


        _loadNestedViews: function (callback) {
            var nestedViewDefs = [];
            if (this._layout != null) {
                nestedViewDefs = this._getNestedViewsFromLayout();
            }

            this._addDefinedNestedViewDefs(nestedViewDefs);

            var count = nestedViewDefs.length;
            var loaded = 0;

            var tryReady = function () {
                if (loaded == count) {
                    callback();
                    return true
                }
            };

            tryReady();
            nestedViewDefs.forEach(function (def, i) {
                var key = nestedViewDefs[i].name;
                var viewName = this._factory.defaultViewName;
                if ('view' in nestedViewDefs[i]) {
                    viewName = nestedViewDefs[i].view;
                }

                if (viewName === false) {
                    loaded++;
                    tryReady();
                } else {
                    var options = {};
                    if ('layout' in nestedViewDefs[i]) {
                        options.layout = nestedViewDefs[i].layout;
                    }
                    if ('template' in nestedViewDefs[i]) {
                        options.template = nestedViewDefs[i].template;
                    }

                    if ('el' in nestedViewDefs[i]) {
                        options.el = nestedViewDefs[i].el;
                    }

                    if ('options' in nestedViewDefs[i]) {
                        options = _.extend(options, nestedViewDefs[i].options);
                    }
                    if (this.model) {
                        options.model = this.model;
                    }
                    if (this.collection) {
                        options.collection = this.collection;
                    }

                    for (var k in this.optionsToPass) {
                        var name = this.optionsToPass[k];
                        options[name] = this.options[name];
                    }
                    this._factory.create(viewName, options, function (view) {
                        if ('notToRender' in nestedViewDefs[i]) {
                            view.notToRender = nestedViewDefs[i].notToRender;
                        }
                        this.setView(key, view);
                        loaded++;
                        tryReady();
                    }.bind(this));
                }
            }, this);
        },

        _getData: function () {
            if (typeof this.data === 'function') {
                return this.data();
            }
            return this.data;
        },

        _getNestedViewsAsArray: function (nestedViews) {
            var nestedViewsArray = [];
            var i = 0;
            for (var key in this.nestedViews) {
                nestedViewsArray.push({
                    key: key,
                    view: this.nestedViews[key]
                });
                i++;
            }
            return nestedViewsArray;

        },

        _getNestedViewsHtmlList: function (callback) {
            var data = {};
            var nestedViewsArray = this._getNestedViewsAsArray();


            var loaded = 0;
            var count = nestedViewsArray.length;

            var tryReady = function () {
                if (loaded == count) {
                    callback(data);
                    return true
                }
            };

            tryReady();
            nestedViewsArray.forEach(function (d, i) {
                var key = nestedViewsArray[i].key;
                var view = nestedViewsArray[i].view;

                if (!view.notToRender) {
                    view.getHtml(function (html) {
                        data[key] = html;
                        loaded++;
                        tryReady();
                    });
                } else {
                    loaded++;
                    tryReady();
                }
            }, this);
        },

        handleDataBeforeRender: function (data) {},

        _getHtml: function (callback) {
            this._isBeingRendered = true;
            this.trigger("render", this);
            this._getNestedViewsHtmlList(function (nestedViewsHtmlList) {
                var data = _.extend(this._getData() || {}, nestedViewsHtmlList);
                if (this.collection || null) {
                    data.collection = this.collection;
                }
                if (this.model || null) {
                    data.model = this.model;
                }
                data.viewObject = this;
                this.handleDataBeforeRender(data);
                this._getTemplate(function (template) {
                    var html = this._renderer.render(template, data);
                    callback(html);
                }.bind(this));
            }.bind(this));
        },

        _getTemplateName: function () {
            return this.template || null;
        },

        _getLayoutName: function () {
            return this.layout || this.name || null;
        },

        _getLayoutData: function () {
            return this.layoutData;
        },

        _getLayout: function () {
            if (typeof this._layout === 'function') {
                return this._layout();
            }
            return this._layout;
        },

        _getTemplate: function (callback) {
            if (this._templator.compilable && this._templateCompiled !== null) {
                callback(this._templateCompiled);
                return;
            }

            var _template = this._template || null;

            if (_template !== null) {
                callback(_template);
                return;
            }

            var templateName = this._getTemplateName();

            var noCache = false;
            var layoutOptions = {};

            if (!templateName) {
                noCache = this.noCache;

                var layoutName = this._getLayoutName();

                if (!layoutName) {
                    noCache = true;
                } else {
                    if (layoutName) {
                        templateName = 'built-' + layoutName;
                    } else {
                        templateName = null;
                    }
                }
                layoutOptions = {
                    name: layoutName,
                    data: this._getLayoutData(),
                    layout: this._getLayout(),
                }
            }

            this._templator.getTemplate(templateName, layoutOptions, noCache, callback);
        },

        _updatePath: function (parentPath, viewKey) {
            this._path = parentPath + '/' + viewKey;
            for (var key in this.nestedViews) {
                this.nestedViews[key]._updatePath(this._path, key);
            }
        },

        _getSelectorForNestedView: function (key) {
            var el = false;

            if (key in this._nestedViewDefs) {
                if ('id' in this._nestedViewDefs[key]) {
                    el = '#' + this._nestedViewDefs[key].id;
                } else {
                    if ('el' in this._nestedViewDefs[key]) {
                        el = this._nestedViewDefs[key].el;
                    } else if ('selector' in this._nestedViewDefs[key]) {
                        var currentEl = this.getSelector();
                        if (currentEl) {
                            el = currentEl + ' ' + this._nestedViewDefs[key].selector;
                        }
                    } else {
                        var currentEl = this.getSelector();
                        if (currentEl) {
                            el = currentEl + ' [data-view="'+key+'"]';
                        }
                    }
                }
            }
            return el;
        },

        /**
         * Whether this view has nested view.
         * @param {String} key
         * @return {Bool}
         */
        hasView: function (key) {
            if (key in this.nestedViews) {
                return true;
            }
            return false;
        },

        /**
         * Get nested view.
         * @param {String} key
         * @return {Bull.View}
         */
        getView: function (key) {
            if (key in this.nestedViews) {
                return this.nestedViews[key];
            }
        },

        /**
         * Create nested view. The important method.
         * @param {String} key Key.
         * @param {String} viewName View name.
         * @param {Object} options View options.
         * @param {Function} callback Callback function. Will be invoiked once nested view is ready (loaded).
         * @param {Bool} wait True be default. Set false if no need parent view wait for nested view loaded.
         */
        createView: function (key, viewName, options, callback, wait) {
            this.clearView(key);
            return new Promise(function (resolve) {
                wait = (typeof wait === 'undefined') ? true : wait;
                var context = this;
                if (wait) {
                    this.waitForView(key);
                }
                options = options || {};
                if (!options.el) {
                    options.el = this.getSelector() + ' [data-view="'+key+'"]';
                }
                this._factory.create(viewName, options, function (view) {
                    var isSet = false;
                    if (this._isRendered || options.setViewBeforeCallback) {
                        this.setView(key, view);
                        isSet = true;
                    }
                    if (typeof callback === 'function') {
                        callback.call(context, view);
                    }
                    resolve(view);
                    if (!this._isRendered && !options.setViewBeforeCallback && !isSet) {
                        this.setView(key, view);
                    }
                }.bind(this));
            }.bind(this));
        },

        /**
         * Set nested view.
         * @param {String} key
         * @param {Bull.View} view
         * @param {String} el Selector for view container.
         */
        setView: function (key, view, el) {
            var el = el || this._getSelectorForNestedView(key) || view.options.el || false;

            if (el) {
                if (this.isRendered()) {
                    view.setElement(el);
                } else {
                    view.setElementInAdvance(el);
                }
            }

            if (key in this.nestedViews) {
                this.clearView(key);
            }
            this.nestedViews[key] = view;
            view._parentView = this;
            view._updatePath(this._path, key);

            this._tryReady();
        },

        /**
         * Clear nested view.
         * @param {String} key
         */
        clearView: function (key) {
            if (key in this.nestedViews) {
                this.nestedViews[key].remove();
                delete this.nestedViews[key];
            }
        },

        /**
         * Removes nested view but supposed that this view can be re-used in future.
         * @param {String} key
         */
        unchainView: function (key) {
            if (key in this.nestedViews) {
                this.nestedViews[key]._parentView = null;
                this.nestedViews[key].undelegateEvents();
                delete this.nestedViews[key];
            }
        },

        /**
         * Get parent view.
         * @return {Bull.View}
         */
        getParentView: function () {
            return this._parentView;
        },

        /**
         * Has parent view.
         * @return {bool}
         */
        hasParentView: function () {
            return !!this._parentView;
        },

        /**
         * Add condition for view getting ready.
         * @param {Function} or {Bool}
         */
        addReadyCondition: function (condition) {
            this._readyConditionList.push(condition);
        },

        /**
         * Wait for nested view.
         * @param {String} key
         */
        waitForView: function (key) {
            this._waitViewList.push(key);
        },

        /**
         * Make view wait for promise if Promise is passed as a parameter.
         * Add wait condition if true is passed. Remove wait condition if false.
         * @param wait Promise | Function | {Bool}
         */
        wait: function (wait) {
            if (typeof wait === 'object' && (wait instanceof Promise || typeof wait.then === 'function')) {
                this._waitPromiseCount++;
                wait.then(function () {
                    this._waitPromiseCount--;
                    this._tryReady();
                }.bind(this));
                return;
            }
            if (typeof wait == 'function') {
                this._waitPromiseCount++;
                var promise = new Promise(function (resolve) {
                    resolve(wait.call(this));
                }.bind(this))
                promise.then(function () {
                    this._waitPromiseCount--;
                    this._tryReady();
                }.bind(this));
                return promise;
            }
            if (wait) {
                this._wait = true;
            } else {
                this._wait = false;
                this._tryReady();
            }
        },

        /**
         * Remove view and all nested tree. Removes contents of el. Triggers 'remove' event.
         */
        remove: function (dontEmpty) {
            for (var key in this.nestedViews) {
                this.clearView(key);
            }
            this.trigger('remove');
            this.off();
            if (!dontEmpty) {
                this.$el.empty();
            }
            this.stopListening();
            this.undelegateEvents();
            if (this.model) {
                this.model.off(null, null, this);
            }
            if (this.collection) {
                this.collection.off(null, null, this);
            }
            this._isRendered = false;
            this._isFullyRendered = false;
            this._isBeingRendered = false;
            this._isRemoved = true;
            return this;
        },

        _setElement: function (el) {
            if (typeof el === 'string') {
                var parentView = this.getParentView();
                if (parentView && parentView.isRendered()) {
                    if (parentView.$el && parentView.$el.size() && parentView.getSelector()) {
                        if (el.indexOf(parentView.getSelector()) === 0) {
                            var subEl = el.substr(parentView.getSelector().length, el.length - 1);
                            this.$el = $(subEl, parentView.$el).eq(0);
                            this.el = this.$el[0];
                            return;
                        }
                    }
                }
            }

            this.$el = $(el).eq(0);
            this.el = this.$el[0];
        }

    });

}).call(this, Bull, Backbone, _);

(function (Bull, _) {

    Bull.Loader = function (options) {
        var options = options || {};
        this._paths = _.extend(this._paths, options.paths || {});
        this._exts = _.extend(this._exts, options.exts || {});
        this._normalize = _.extend(this._normalize, options.normalize || {});
        this._isJson = _.extend(this._isJson, options.isJson || {});

        this._externalLoaders = _.extend(this._externalLoaders, options.loaders || {});

        this._externalPathFunction = options.path || null;

    };

    _.extend(Bull.Loader.prototype, {

        _exts: {
            layout: 'json',
            template: 'tpl',
            layoutTemplate: 'tpl',
        },

        _paths: {
            layout: 'layouts',
            template: 'templates',
            layoutTemplate: 'templates/layouts',
        },

        _isJson: {
            layout: true,
        },

        _externalLoaders: {
            layout: null,
            template: null,
            layoutTemplate: null,
        },

        _externalPathFunction: null,

        _normalize: {
            layouts: function (name) {
                return name;
            },
            templates: function (name) {
                return name;
            },
            layoutTemplates: function (name) {
                return name;
            },
        },

        getFilePath: function (type, name) {
            if (!(type in this._paths) || !(type in this._exts)) {
                throw new TypeError("Unknown resource type \"" + type + "\" requested in Bull.Loader.");
            }

            var namePart = name;
            if (type in this._normalize) {
                namePart = this._normalize[type](name);
            }

            var pathPart = this._paths[type];
            if (pathPart.substr(-1) == '/') {
                pathPart = pathPart.substr(0, pathPart.length - 1);
            }

            return pathPart + '/' + namePart + '.' + this._exts[type];
        },

        _callExternalLoader: function (type, name, callback) {
            if (type in this._externalLoaders && this._externalLoaders[type] !== null) {
                if (typeof this._externalLoaders[type] === 'function') {
                    this._externalLoaders[type](name, callback);
                    return true;
                } else {
                    throw new Error("Loader for \"" + type + "\" in not a Function.");
                }
            }
            return null;
        },

        load: function (type, name, callback) {
            var customCalled = this._callExternalLoader(type, name, callback);
            if (customCalled) {
                return;
            }

            var response, filePath ;

            if (this._externalPathFunction != null) {
                filePath = this._externalPathFunction.call(this, type, name);
            } else {
                filePath = this.getFilePath(type, name);
            }

            filePath += '?_=' + new Date().getTime();

            var xhr = new XMLHttpRequest();
            xhr.open('GET', filePath, true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    response = xhr.responseText;
                    if (type in this._isJson) {
                        if (this._isJson[type]) {
                            var obj;
                            if (xhr.status == 404 || xhr.status == 403) {
                                throw new Error("Could not load " + type + " \"" + name + "\".");
                            }

                            try {
                                obj = JSON.parse(String(response));
                            } catch (e) {
                                throw new SyntaxError("Error while parsing " + type + " \"" + name + "\": (" + e.message + ").");
                            }
                            callback(obj);
                            return;
                        }
                    }
                    callback(response);
                }
            }.bind(this);

            xhr.send(null);
        },
    });

}).call(this, Bull, _);

(function (Bull, _, Handlebars) {

    Bull.Templator = function (data) {
        var data = data || {};
        this._templates = {};
        this._layoutTemplates = {};
        this._loader = data.loader || null;
        this._layouter = data.layouter || null;
        if ('compilable' in data) {
            this.compilable = data.compilable;
        }
    };

    _.extend(Bull.Templator.prototype, {

        compilable: true,

        _templates: null,

        _layoutTemplates: null,

        _loader: null,

        _layouter: null,

        addTemplate: function (name, template) {
            this._templates[name] = template;
        },

        getTemplate: function (name, layoutOptions, noCache, callback) {
            var layoutOptions = layoutOptions || {};
            var template = null;

            if (!layoutOptions.name && !layoutOptions.layout && !name) {
                throw new Error("Can not get template. Not enough data passed.");
            }

            if (!noCache && name) {
                template = this._getCachedTemplate(name);
                if (template) {
                    callback(template);
                    return;
                }
            }

            var layout = layoutOptions.layout || null;

            var then = function (template) {
                if (this.compilable) {
                    template = this.compileTemplate(template);
                }
                this._templates[name] = template;
                callback(template);
            }.bind(this);

            var proceedWithLayout = function (layout) {
                if (layout == null) {
                    throw new Error("Could not get layout '" + layoutOptions.name + "'.");
                }
                this._buildTemplate(layout, layoutOptions.data, then);
            }.bind(this);

            if (!template) {
                if (!layoutOptions.name && !layoutOptions.layout) {
                    this._loader.load('template', name, then);
                } else {
                    if (!layout) {
                        this._layouter.getLayout(layoutOptions.name, proceedWithLayout);
                    } else {
                        proceedWithLayout(layout);
                    }
                }
            }
        },

        compileTemplate: function (template) {
            if (typeof Handlebars !== 'undefined') {
                return Handlebars.compile(template);
            }
            return template;
        },

        _getCachedTemplate: function (templateName) {
            if (templateName in this._templates) {
                return this._templates[templateName];
            }
            return false;
        },


        _getCachedLayoutTemplate: function (layoutType) {
            if (layoutType in this._layoutTemplates) {
                return this._layoutTemplates[layoutType];
            }
            return false;
        },

        _cacheLayoutTemplate: function (layoutType, layoutTemplate) {
            this._layoutTemplates[layoutType] = layoutTemplate;
        },

        _buildTemplate: function (layoutDefs, data, callback) {
            var layoutType = layoutDefs.type || 'default';

            var proceed = function (layoutTemplate) {
                var injection = _.extend(layoutDefs, data || {});
                var template = _.template(layoutTemplate, injection);
                if (typeof template === 'function') {
                    template = template(injection);
                }
                callback(template);
            }.bind(this);

            var layoutTemplate = this._getCachedLayoutTemplate(layoutType);
            if (!layoutTemplate) {
                this._loader.load('layoutTemplate', layoutType, function (layoutTemplate) {
                    this._cacheLayoutTemplate(layoutType, layoutTemplate);
                    proceed(layoutTemplate);
                }.bind(this));
                return;
            }
            proceed(layoutTemplate);
        },
    });

}).call(this, Bull, _, Handlebars);

(function (Bull, _) {

    Bull.Layouter = function (data) {
        var data = data || {};
        this._layouts = {};
        this._loader = data.loader || null;
        this._cachedNestedViews = {};
    };

    _.extend(Bull.Layouter.prototype, {

        _layouts: null,

        _loader: null,

        _cachedNestedViews: null,

        addLayout: function (layoutName, layout) {
            this._layouts[layoutName] = layout;
        },

        getLayout: function (layoutName, callback) {
            if (layoutName in this._layouts) {
                callback(this._layouts[layoutName]);
                return;
            }

            if (!layout) {
                this._loader.load('layout', layoutName, function (layout) {
                    this.addLayout(layoutName, layout);
                    callback(layout);
                }.bind(this));
                return;
            }
        },

        _getCachedNestedViews: function (layoutName) {
            if (layoutName in this._cachedNestedViews) {
                return this._cachedNestedViews[layoutName];
            }
            return false;
        },

        _cacheNestedViews: function (layoutName, nestedViews) {
            if (!(layoutName in this._cachedNestedViews)) {
                this._cachedNestedViews[layoutName] = nestedViews;
            }
        },

        findNestedViews: function (layoutName, layoutDefs, noCache) {
            if (!layoutName && !layoutDefs) {
                throw new Error("Can not find nested views. No layout data and name.");
            }

            if (layoutName && !noCache) {
                var cached = this._getCachedNestedViews(layoutName);
                if (cached) {
                    return cached;
                }
            }

            if (typeof layoutDefs == 'undefined') {
                if (layoutName in this._layouts) {
                    layoutDefs = this._layouts[layoutName];
                }
                if (!('layout' in layoutDefs)) {
                    throw new Error("Layout \"" + layoutName + "\"" + " is bad.");
                }
            }
            var layout = layoutDefs.layout;
            var viewPathList = [];

            var uniqName = function (name, count) {
                var modName = name;
                if (typeof count !== 'undefined') {
                    modName = modName + '_' + count;
                } else {
                    var count = 0;
                }
                for (var i in viewPathList) {
                    if (viewPathList[i].name == modName) {
                        return uniqName(name, count + 1);
                    }
                }
                return modName;
            }

            var getDefsForNestedView = function (defsInLayout) {
                var defs = {};
                var params = ['view', 'layout', 'notToRender', 'options', 'template', 'id', 'selector', 'el'];
                for (var i in params) {
                    var param = params[i];
                    if (param in defsInLayout) {
                        defs[param] = defsInLayout[param];
                    }
                }
                if ('name' in defsInLayout) {
                    defs.name = uniqName(defsInLayout.name);
                }
                return defs;
            }

            var seekForViews = function (tree) {
                for (var key in tree) {
                    if (tree[key] != null && typeof tree[key] === 'object') {
                        if ('view' in tree[key] || 'layout' in tree[key] || 'template' in tree[key]) {
                            var def = getDefsForNestedView(tree[key]);
                            if ('name' in def) {
                                viewPathList.push(def);
                            }
                        } else {
                            seekForViews(tree[key]);
                        }
                    }
                }
            }
            seekForViews(layout);
            if (layoutName && !noCache) {
                this._cacheNestedViews(layoutName, viewPathList);
            }
            return viewPathList;
        }
    });

}).call(this, Bull, _);

(function (Bull, _) {

    Bull.Renderer = function (options) {
        var options = options || {};
        this._render = options.method || this._render;
    };

    _.extend(Bull.Renderer.prototype, {

        _render: function (template, data) {
            return template(data);
        },

        render: function (template, data) {
            return this._render.call(this, template, data);
        },
    });

}).call(this, Bull, _);
