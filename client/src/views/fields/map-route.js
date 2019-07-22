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
 ************************************************************************/

Espo.define('views/fields/map-route', 'views/fields/map', function (Dep) {

    return Dep.extend({
        type: 'mapRoute',
        directionsDisplay: null,
        directionsService: null,

        setupMapRoute: function (origin, destination) {
            var request = {
                origin: origin,
                destination: destination,
                travelMode: google.maps.DirectionsTravelMode.DRIVING
            };

            this.directionsService.route(request, function(response, status) {
                if (status == google.maps.DirectionsStatus.OK) {
                    this.directionsDisplay.setDirections(response);
                }
            }.bind(this));
        },
        getConfigOrigin: function () {
            return this.addressToString({
                city: this.getConfig().get('googleMapsOriginCity'),
                street: this.getConfig().get('googleMapsOriginStreet'),
                postalCode: this.getConfig().get('googleMapsOriginPostalCode'),
                country: this.getConfig().get('googleMapsOriginCountry'),
                state: this.getConfig().get('googleMapsOriginState')
            });
        },
        initMapGoogle: function () {
            this.$el.find('.map').css('height', this.height + 'px');

            var geocoder = new google.maps.Geocoder();
            this.directionsDisplay = new google.maps.DirectionsRenderer();
            this.directionsService = new google.maps.DirectionsService();

            try {
                var map = new google.maps.Map(this.$el.find('.map').get(0), {
                    zoom: 7,
                    mapTypeId: google.maps.MapTypeId.ROADMAP,
                    center: {lat: 0, lng: 0},
                    scrollwheel: false
                });
                this.directionsDisplay.setMap(map);
            } catch (e) {
                console.error(e.message);
                return;
            }

            var origin = this.getConfigOrigin();
            var address = this.addressToString(this.addressData);

            if (navigator.geolocation
                && this.getConfig().get('googleMapsUseLocationService')
                   === true) {

                navigator.geolocation.getCurrentPosition(function(position) {
                    this.setupMapRoute({
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    }, address);
                }.bind(this), function() {
                  this.setupMapRoute(origin, address);
                }.bind(this));
            } else {
                this.setupMapRoute(origin, address);
            }
        }
    });
});