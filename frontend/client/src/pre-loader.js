/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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
 ************************************************************************/ 

(function (Espo, _, $) {

    Espo.PreLoader = function (cache, viewFactory) {
        this.cache = cache;
        this.viewFactory = viewFactory;
    }

    _.extend(Espo.PreLoader.prototype, {

        configUrl: 'client/cfg/pre-load.json',

        cache: null,

        viewFactory: null,

        load: function (callback, app) {

            var bar = $('<div class="progress pre-loading"><div class="progress-bar" id="loading-progress-bar" role="progressbar" aria-valuenow="0" style="width: 0%;"></div></div>').prependTo('body');;
            bar = bar.children();
            bar.css({
                'transition': 'width .1s linear',
                '-webkit-transition': 'width .1s linear'
            });

            var self = this;

            var count = 0;
            var countLoaded = 0;
            var classesLoaded = 0;
            var templatesLoaded = 0;
            var layoutTypesLoaded = 0;

            var updateBar = function () {
                var percents = countLoaded / count * 100;
                bar.css('width', percents + '%').attr('aria-valuenow', percents);
            }

            var checkIfReady = function () {
                if (countLoaded >= count) {
                    clearInterval(timer);
                    callback.call(app, app);
                }
            };
            var timer = setInterval(checkIfReady, 100);

            var load = function (data) {
                count = data.templates.length + data.layoutTypes.length+ data.classes.length;

                var loadTemplates = function () {
                    data.templates.forEach(function (name) {
                        self.viewFactory._loader.load('template', name, function () {
                            layoutTypesLoaded++;
                            countLoaded++;
                            updateBar();
                        });
                    });
                }
                var loadLayoutTypes = function () {
                    data.layoutTypes.forEach(function (name) {
                        self.viewFactory._loader.load('layoutTemplate', name, function () {
                            layoutTypesLoaded++;
                            countLoaded++;
                            updateBar();
                        });
                    });
                }
                var loadClasses = function () {                    
                    data.classes.forEach(function (name) {                        
                        Espo.loader.load(name, function () {
                            classesLoaded++;
                            countLoaded++;
                            updateBar();
                        });
                    });
                }

                loadTemplates();
                loadLayoutTypes();
                loadClasses();
            };

            $.ajax({
                url: this.configUrl,
                dataType: 'json',
                local: true,
                success: function (data) {
                    load(data);
                }
            });
        }
    });

}).call(this, Espo, _, $);
