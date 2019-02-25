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
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

describe('utils', function () {
    var Utils;

    beforeEach(function (done) {
        require(['utils'], function (UtilsD) {
            Utils = UtilsD;
            done();
        });
    });

	it('#upperCaseFirst should make first letter upercase', function () {
		expect(Utils.upperCaseFirst('someTest')).toBe('SomeTest');
	});

    it('#checkAccessDataList should check access 1', function () {
        var user = {
            isPortal:  function () {},
            getLinkMultipleIdList: function () {},
            isAdmin: function () {}
        };
        var acl = {
            check: function () {},
            checkScope: function () {}
        };
        var entity = {
            name: 'Account'
        };
        spyOn(user, 'getLinkMultipleIdList').and.callFake(function (link) {
            if (link === 'teams') {
                return ['team1', 'team2'];
            }
            if (link === 'portals') {
                return ['portal1', 'portal2'];
            }
        });
        spyOn(user, 'isPortal').and.returnValue(false);
        spyOn(user, 'isAdmin').and.returnValue(false);

        spyOn(acl, 'checkScope').and.callFake(function (scope) {
            if (scope === 'Account') {
                return true;
            }
        });
        spyOn(acl, 'check').and.callFake(function (obj, action) {
            if (obj === 'Account') {
                return {
                    create: true,
                    read: true,
                    edit: true,
                    delete: false
                }[action];
            }
            if (obj.name) {
                return {
                    create: true,
                    read: true,
                    edit: false,
                    delete: false
                }[action];
            }
            return false;
        });

        expect(Utils.checkAccessDataList([
        ], acl, user)).toBe(true);

        expect(Utils.checkAccessDataList([
            {
                scope: 'Account'
            }
        ], acl, user)).toBe(true);

        expect(Utils.checkAccessDataList([
            {
                scope: 'Account',
                action: 'delete'
            }
        ], acl, user)).toBe(false);

        expect(Utils.checkAccessDataList([
            {
                scope: 'Account',
                action: 'read'
            },
            {
                inPortalDisabled: true
            }
        ], acl, user)).toBe(true);

        expect(Utils.checkAccessDataList([
            {
                action: 'read'
            },
            {
                teamIdList: ['team1', 'team3']
            }
        ], acl, user, entity)).toBe(true);

        expect(Utils.checkAccessDataList([
            {
                isPortalOnly: true
            }
        ], acl, user)).toBe(false);

        expect(Utils.checkAccessDataList([
            {
                isAdminOnly: true
            }
        ], acl, user)).toBe(false);

        expect(Utils.checkAccessDataList([
            {
                action: 'edit'
            }
        ], acl, user, entity)).toBe(false);
    });

    it('#checkAccessDataList should check access 2', function () {
        var user = {
            isPortal:  function () {},
            getLinkMultipleIdList: function () {},
            isAdmin: function () {}
        };
        var acl = {
            check: function () {},
            checkScope: function () {}
        };
        var entity = {
            name: 'Account'
        };
        spyOn(user, 'getLinkMultipleIdList').and.callFake(function (link) {
            if (link === 'teams') {
                return ['team1', 'team2'];
            }
            if (link === 'portals') {
                return ['portal1', 'portal2'];
            }
        });
        spyOn(user, 'isPortal').and.returnValue(true);
        spyOn(user, 'isAdmin').and.returnValue(false);

        expect(Utils.checkAccessDataList([
            {
                isPortalOnly: true
            }
        ], acl, user)).toBe(true);

        expect(Utils.checkAccessDataList([
            {
                portalIdList: ['portal2', 'portal3']
            }
        ], acl, user)).toBe(true);
    });


    it('#checkAccessDataList should check access 3', function () {
        var user = {
            isPortal:  function () {},
            getLinkMultipleIdList: function () {},
            isAdmin: function () {}
        };
        var acl = {
            check: function () {},
            checkScope: function () {}
        };
        var entity = {
            name: 'Account'
        };
        spyOn(user, 'getLinkMultipleIdList').and.callFake(function (link) {
            if (link === 'teams') {
                return ['team1', 'team2'];
            }
            if (link === 'portals') {
                return ['portal1', 'portal2'];
            }
        });
        spyOn(user, 'isPortal').and.returnValue(false);
        spyOn(user, 'isAdmin').and.returnValue(true);

        expect(Utils.checkAccessDataList([
            {
                portalIdList: ['portal3']
            }
        ], acl, user, null, true)).toBe(true);

        expect(Utils.checkAccessDataList([
            {
                teamIdList: ['team3']
            }
        ], acl, user, null, true)).toBe(true);
    });
});
