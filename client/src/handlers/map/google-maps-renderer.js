/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

import MapRenderer from 'handlers/map/renderer';

class GoogleMapsRenderer extends MapRenderer {

    /**
     * @param {module:handlers/map/renderer~addressData} addressData
     */
    render(addressData) {
        if ('google' in window && window.google.maps) {
            this.initMapGoogle(addressData);

            return;
        }

        // noinspection SpellCheckingInspection
        if (typeof window.mapapiloaded === 'function') {
            // noinspection SpellCheckingInspection
            const mapapiloaded = window.mapapiloaded;

            // noinspection SpellCheckingInspection
            window.mapapiloaded = () => {
                this.initMapGoogle(addressData);

                mapapiloaded();
            };

            return;
        }

        // noinspection SpellCheckingInspection
        window.mapapiloaded = () => this.initMapGoogle(addressData);

        let src = 'https://maps.googleapis.com/maps/api/js?callback=mapapiloaded&loading=async&v=weekly&libraries=marker';
        const apiKey = this.view.getConfig().get('googleMapsApiKey');

        if (apiKey) {
            src += '&key=' + apiKey;
        }

        const scriptElement = document.createElement('script');

        scriptElement.setAttribute('defer', 'defer');
        scriptElement.src = src;

        document.head.appendChild(scriptElement);
    }

    /**
     * @param {module:handlers/map/renderer~addressData} addressData
     */
    initMapGoogle(addressData) {
        // noinspection JSUnresolvedReference
        const geocoder = new google.maps.Geocoder();
        let map;

        const mapId = this.view.getConfig().get('googleMapsMapId') || 'DEMO_MAP_ID';

        try {
            // noinspection SpellCheckingInspection,JSUnresolvedReference
            map = new google.maps.Map(this.view.$el.find('.map').get(0), {
                zoom: 15,
                center: {lat: 0, lng: 0},
                scrollwheel: false,
                mapId: mapId,
            });
        }
        catch (e) {
            console.error(e.message);

            return;
        }

        let address = '';

        if (addressData.street) {
            address += addressData.street;
        }

        if (addressData.city) {
            if (address !== '') {
                address += ', ';
            }

            address += addressData.city;
        }

        if (addressData.state) {
            if (address !== '') {
                address += ', ';
            }

            address += addressData.state;
        }

        if (addressData.postalCode) {
            if (addressData.state || addressData.city) {
                address += ' ';
            }
            else {
                if (address) {
                    address += ', ';
                }
            }

            address += addressData.postalCode;
        }

        if (addressData.country) {
            if (address !== '') {
                address += ', ';
            }

            address += addressData.country;
        }

        // noinspection JSUnresolvedReference
        geocoder.geocode({'address': address}, (results, status) => {
            // noinspection JSUnresolvedReference
            if (status === google.maps.GeocoderStatus.OK) {
                // noinspection JSUnresolvedReference
                map.setCenter(results[0].geometry.location);

                // noinspection JSUnresolvedReference
                new google.maps.marker.AdvancedMarkerElement({
                    map: map,
                    position: results[0].geometry.location,
                });
            }
        });
    }
}

// noinspection JSUnusedGlobalSymbols
export default GoogleMapsRenderer;

