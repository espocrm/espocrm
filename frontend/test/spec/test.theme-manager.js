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

describe('theme-manager', () => {
    /** @type {module:models/settings} */
	let config;
    /** @type {module:models/preferences} */
    let preferences;
    /** @type {module:metadata} */
    let metadata;
    /** @type {module:theme-manager} */
    let themeManager;

	beforeEach(done => {
        require(['models/settings', 'models/preferences', 'metadata', 'theme-manager'],
        (Config, Preferences, Metadata, ThemeManager) => {
            config = new Config();
            preferences = new Preferences();
            metadata = new Metadata();
            themeManager = new ThemeManager(config, preferences, metadata)

            metadata.data = {
                themes: {
                    "Espo": {
                        "params": {
                            "navbar": {
                                "type": "enum",
                                "default": "side",
                                "options": [
                                    "side",
                                    "top"
                                ]
                            }
                        },
                        "mappedParams": {
                            "navbarHeight": {
                                "param": "navbar",
                                "valueMap": {
                                    "side": 30,
                                    "top": 43
                                }
                            }
                        },
                        "someParam": "someValue"
                    }
                }
            };

            done();
        });
	});

	it('name', () => {
        spyOn(preferences, 'get').and.callFake(name => {
           if (name === 'theme') {
               return 'Test';
           }
        });

		expect(themeManager.getName()).toBe('Test');
	});

    it('param', () => {
        spyOn(preferences, 'get').and.callFake(name => {
            if (name === 'theme') {
                return 'Espo';
            }
        });

        expect(themeManager.getParam('someParam')).toBe('someValue');
    });

    it('parent param', () => {
        spyOn(preferences, 'get').and.callFake(name => {
            if (name === 'theme') {
                return 'Test';
            }
        });

        expect(themeManager.getParam('someParam')).toBe('someValue');
    });

    it('default param', () => {
        spyOn(preferences, 'get').and.callFake(name => {
            if (name === 'theme') {
                return 'Test';
            }
        });

        expect(themeManager.getParam('navbar')).toBe('side');
    });

    it('param from preferences', () => {
        spyOn(preferences, 'get').and.callFake(name => {
            if (name === 'theme') {
                return 'Test';
            }

            if (name === 'themeParams') {
                return {
                    navbar: 'top',
                };
            }
        });

        expect(themeManager.getParam('navbar')).toBe('top');
    });

    it('param from config', () => {
        spyOn(preferences, 'get').and.callFake(name => {
            if (name === 'theme') {
                return null;
            }
        });

        spyOn(config, 'get').and.callFake(name => {
            if (name === 'themeParams') {
                return {
                    navbar: 'top',
                };
            }
        });

        expect(themeManager.getParam('navbar')).toBe('top');
    });

    it('mapped param 1', () => {
        spyOn(preferences, 'get').and.callFake(name => {
            if (name === 'theme') {
                return 'Test';
            }

            if (name === 'themeParams') {
                return {
                    navbar: 'top',
                };
            }
        });

        expect(themeManager.getParam('navbarHeight')).toBe(43);
    });

    it('mapped param 2', () => {
        spyOn(preferences, 'get').and.callFake(name => {
            if (name === 'theme') {
                return 'Test';
            }

            if (name === 'themeParams') {
                return {
                    navbar: 'side',
                };
            }
        });

        expect(themeManager.getParam('navbarHeight')).toBe(30);
    });
});
