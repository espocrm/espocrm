/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

describe('email-helper', () => {
    let EmailHelper;
    let diModule;
    let LanguageClass, UserClass, DateTimeClass, AclManagerClass;

    beforeAll(done => {
        require(
            ['email-helper', 'di', 'language', 'models/user', 'date-time', 'acl-manager'],
            (EmailHelperClass, di, LanguageClassVal, UserClassVal, DateTimeClassVal, AclManagerClassVal) => {
                // The EspoCRM AMD loader unwraps the `default` export, so the
                // module value is the class itself, not a {default: Class} wrapper.
                EmailHelper = EmailHelperClass;
                diModule = di;
                LanguageClass = LanguageClassVal;
                UserClass = UserClassVal;
                DateTimeClass = DateTimeClassVal;
                AclManagerClass = AclManagerClassVal;
                done();
            }
        );
    });

    /** @returns {EmailHelper} */
    function createHelper(userAttrs = {}, userGetMap = {}, aclMap = {}) {
        const mockUser = {
            attributes: {emailAddressList: [], ...userAttrs},
            get: key => userGetMap[key] !== undefined ? userGetMap[key] : null,
        };
        const mockLanguage = {
            translate: (key) => key,
        };
        const mockDateTime = {
            getReadableShortDateTimeFormat: () => 'MM/DD/YYYY HH:mm',
            toMoment: () => ({
                format: () => '01/01/2026 12:00',
                year: () => 2026,
            }),
            getNowMoment: () => ({year: () => 2026}),
        };
        const mockAcl = {
            checkTeamAssignmentPermission: teamId => aclMap[teamId] !== false,
        };

        diModule.container.set(LanguageClass, mockLanguage);
        diModule.container.set(UserClass, mockUser);
        diModule.container.set(DateTimeClass, mockDateTime);
        diModule.container.set(AclManagerClass, mockAcl);

        const helper = new EmailHelper();

        diModule.container.clear();

        return helper;
    }

    /** @returns {{attributes: Object, get: function(string): *}} */
    function createModel(attrs = {}) {
        const defaults = {
            name: 'Test Subject',
            from: 'sender@example.com',
            to: '',
            cc: '',
            isHtml: false,
            nameHash: {},
            replyTo: null,
            replyToString: null,
            messageId: '<msg-id@example.com>',
        };
        const merged = {...defaults, ...attrs};
        return {
            id: 'model-id-1',
            attributes: merged,
            get: key => merged[key] !== undefined ? merged[key] : null,
        };
    }

    describe('#parseNameFromStringAddress', () => {
        it('parses name from formatted address', () => {
            const helper = createHelper();
            expect(helper.parseNameFromStringAddress('John Doe <john@example.com>')).toBe('John Doe');
        });

        it('parses name from quoted formatted address', () => {
            const helper = createHelper();
            expect(helper.parseNameFromStringAddress('"Jane Smith" <jane@example.com>')).toBe('Jane Smith');
        });

        it('returns null when no angle brackets', () => {
            const helper = createHelper();
            expect(helper.parseNameFromStringAddress('john@example.com')).toBeNull();
        });
    });

    describe('#parseAddressFromStringAddress', () => {
        it('extracts address from formatted address', () => {
            const helper = createHelper();
            expect(helper.parseAddressFromStringAddress('John Doe <john@example.com>')).toBe('john@example.com');
        });

        it('returns plain address unchanged', () => {
            const helper = createHelper();
            expect(helper.parseAddressFromStringAddress('john@example.com')).toBe('john@example.com');
        });

        it('trims whitespace from plain address', () => {
            const helper = createHelper();
            expect(helper.parseAddressFromStringAddress('  john@example.com  ')).toBe('john@example.com');
        });
    });

    describe('#getReplyAttributes', () => {
        it('returns draft status', () => {
            const helper = createHelper();
            const attrs = helper.getReplyAttributes(createModel(), false);
            expect(attrs.status).toBe('Draft');
        });

        it('prefixes subject with Re:', () => {
            const helper = createHelper();
            const attrs = helper.getReplyAttributes(createModel({name: 'Hello'}), false);
            expect(attrs.name).toBe('Re: Hello');
        });

        it('does not double-prefix Re: subject', () => {
            const helper = createHelper();
            const attrs = helper.getReplyAttributes(createModel({name: 'RE: Already a reply'}), false);
            expect(attrs.name).toBe('RE: Already a reply');
        });

        it('sets to from model from address when not in user email list', () => {
            const helper = createHelper(
                {emailAddressList: ['me@example.com']},
                {emailAddressList: ['me@example.com']}
            );
            const attrs = helper.getReplyAttributes(
                createModel({from: 'sender@example.com'}),
                false
            );
            expect(attrs.to).toBe('sender@example.com');
        });

        it('sets to empty when from address is in user email list (reply on sent)', () => {
            const helper = createHelper(
                {emailAddressList: ['me@example.com']},
                {emailAddressList: ['me@example.com']}
            );
            const attrs = helper.getReplyAttributes(
                createModel({from: 'me@example.com', to: 'recipient@example.com'}),
                false
            );
            expect(attrs.to).toBe('');
        });

        it('uses replyTo address when present', () => {
            const helper = createHelper();
            const attrs = helper.getReplyAttributes(
                createModel({replyTo: 'replyto@example.com;other@example.com'}),
                false
            );
            expect(attrs.to).toBe('replyto@example.com;other@example.com');
        });

        it('does not set cc when cc flag is false', () => {
            const helper = createHelper();
            const attrs = helper.getReplyAttributes(createModel({cc: 'cc@example.com'}), false);
            expect(attrs.cc).toBeUndefined();
        });

        it('sets cc from model cc when cc flag is true', () => {
            const helper = createHelper(
                {emailAddressList: ['me@example.com']},
                {
                    emailAddress: 'me@example.com',
                    excludeFromReplyEmailAddressList: [],
                    userEmailAddressList: ['me@example.com'],
                }
            );
            const attrs = helper.getReplyAttributes(
                createModel({
                    from: 'sender@example.com',
                    to: 'other@example.com',
                    cc: 'cc@example.com',
                }),
                true
            );
            expect(attrs.cc).toContain('cc@example.com');
        });

        it('excludes user own address from cc on reply-all', () => {
            const helper = createHelper(
                {emailAddressList: ['me@example.com']},
                {
                    emailAddress: 'me@example.com',
                    excludeFromReplyEmailAddressList: [],
                    userEmailAddressList: ['me@example.com'],
                }
            );
            const attrs = helper.getReplyAttributes(
                createModel({
                    from: 'sender@example.com',
                    to: 'me@example.com;other@example.com',
                    cc: '',
                }),
                true
            );
            const ccList = (attrs.cc || '').split(';').filter(Boolean);
            expect(ccList).not.toContain('me@example.com');
        });

        it('sets repliedId to model id', () => {
            const helper = createHelper();
            const model = createModel();
            const attrs = helper.getReplyAttributes(model, false);
            expect(attrs.repliedId).toBe('model-id-1');
        });

        it('sets inReplyTo from model messageId', () => {
            const helper = createHelper();
            const attrs = helper.getReplyAttributes(
                createModel({messageId: '<test-message-id@example.com>'}),
                false
            );
            expect(attrs.inReplyTo).toBe('<test-message-id@example.com>');
        });

        it('copies parent fields when model has parentId', () => {
            const helper = createHelper();
            const attrs = helper.getReplyAttributes(
                createModel({
                    parentId: 'account-1',
                    parentName: 'ACME Corp',
                    parentType: 'Account',
                }),
                false
            );
            expect(attrs.parentId).toBe('account-1');
            expect(attrs.parentName).toBe('ACME Corp');
            expect(attrs.parentType).toBe('Account');
        });

        it('propagates isHtml flag', () => {
            const helper = createHelper();
            const attrs = helper.getReplyAttributes(createModel({isHtml: true}), false);
            expect(attrs.isHtml).toBe(true);
        });
    });
});
