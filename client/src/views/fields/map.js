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
 ************************************************************************/

define('views/fields/map', 'views/fields/base', function (Dep) {

    return Dep.extend({

        type: 'map',

        detailTemplate: 'fields/map/detail',

        listTemplate: 'fields/map/detail',

        addressField: null,

        provider: null,

        height: 300,

        data: function () {
            var data = Dep.prototype.data.call(this);
            data.hasAddress = this.hasAddress();
            return data;
        },

        setup: function () {
            this.addressField = this.name.substr(0, this.name.length - 3);

            this.provider = this.options.provider || this.params.provider;
            this.height = this.options.height || this.params.height || this.height;

            var addressAttributeList = Object.keys(this.getMetadata().get('fields.address.fields') || {}).map(
                function (a) {
                    return this.addressField + Espo.Utils.upperCaseFirst(a);
                },
                this
            );

            this.listenTo(this.model, 'sync', function (model) {
                var isChanged = false;
                addressAttributeList.forEach(function (attribute) {
                    if (model.hasChanged(attribute)) {
                        isChanged = true;
                    }
                }, this);

                if (isChanged && this.isRendered()) {
                    this.reRender();
                }
            }, this);

            this.listenTo(this.model, 'after:save', function () {
                if (this.isRendered()) {
                    this.reRender();
                }
            }, this);
        },

        hasAddress: function () {
            return !!this.model.get(this.addressField + 'City') || !!this.model.get(this.addressField + 'PostalCode');
        },

        onRemove: function () {
            $(window).off('resize.' + this.cid);
        },

        afterRender: function () {
            this.addressData = {
                city: this.model.get(this.addressField + 'City'),
                street: this.model.get(this.addressField + 'Street'),
                postalCode: this.model.get(this.addressField + 'PostalCode'),
                country: this.model.get(this.addressField + 'Country'),
                state: this.model.get(this.addressField + 'State')
            };

            this.$map = this.$el.find('.map');

            if (this.hasAddress()) {
                this.processSetHeight(true);

                if (this.height === 'auto') {
                    $(window).off('resize.' + this.cid);
                    $(window).on('resize.' + this.cid, this.processSetHeight.bind(this));
                }

                var methodName = 'afterRender' + this.provider.replace(/\s+/g, '');
                if (typeof this[methodName] === 'function') {
                    this[methodName]();
                } else {
                    var implClassName = this.getMetadata().get(['clientDefs', 'AddressMap', 'implementations', this.provider]);
                    if (implClassName) {
                        require(implClassName, function (impl) {
                            impl.render(this);
                        }.bind(this));
                    }
                }
            }
        },

        afterRenderGoogle: function () {
            if (window.google && window.google.maps) {
                this.initMapGoogle();
            } else if (typeof window.mapapiloaded === 'function') {
                var mapapiloaded = window.mapapiloaded;
                window.mapapiloaded = function() {
                  this.initMapGoogle();
                  mapapiloaded();
                }.bind(this);
            } else {
                window.mapapiloaded = function () {
                    this.initMapGoogle();
                }.bind(this);
                var src = 'https://maps.googleapis.com/maps/api/js?callback=mapapiloaded';
                var apiKey = this.getConfig().get('googleMapsApiKey');
                if (apiKey) {
                    src += '&key=' + apiKey;
                }

                var s = document.createElement('script');
                s.setAttribute('async', 'async');
                s.src = src;
                document.head.appendChild(s);
            }
        },

        processSetHeight: function (init) {
            var height = this.height;
            if (this.height === 'auto') {
                height = this.$el.parent().height();

                if (init && height <= 0) {
                    setTimeout(function () {
                        this.processSetHeight(true);
                    }.bind(this), 50);
                    return;
                }
            }

            this.$map.css('height', height + 'px');
        },

        initMapGoogle: function () {
            var geocoder = new google.maps.Geocoder();

            try {
                var map = new google.maps.Map(this.$el.find('.map').get(0), {
                    zoom: 15,
                    center: {lat: 0, lng: 0},
                    scrollwheel: false
                });
            } catch (e) {
                console.error(e.message);
                return;
            }

            var address = '';

            if (this.addressData.street) {
                address += this.addressData.street;
            }

            if (this.addressData.city) {
                if (address != '') {
                    address += ', ';
                }
                address += this.addressData.city;
            }

            if (this.addressData.state) {
                if (address != '') {
                    address += ', ';
                }
                address += this.addressData.state;
            }

            if (this.addressData.postalCode) {
                if (this.addressData.state || this.addressData.city) {
                    address += ' ';
                } else {
                    if (address) {
                        address += ', ';
                    }
                }
                address += this.addressData.postalCode;
            }

            if (this.addressData.country) {
                if (address != '') {
                    address += ', ';
                }
                address += this.addressData.country;
            }

            geocoder.geocode({'address': address}, function (results, status) {
                if (status === google.maps.GeocoderStatus.OK) {
                    map.setCenter(results[0].geometry.location);
                    var marker = new google.maps.Marker({
                        map: map,
                        position: results[0].geometry.location
                    });
                }
            }.bind(this));
        },

    });
});
