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

describe('collection', () => {
	let collection;

    /** @type {typeof import('collection').default} */
    let CollectionClass;
    /** @type {typeof import('model').default} */
    let ModelClass;

	beforeEach(done => {
		Espo.loader.require(['collection', 'model'], (Collection, Model) => {
            CollectionClass = Collection;
            ModelClass = Model;

            const m1 = new Model({id: '1'});
            const m2 = new Model({id: '2'});
            const m3 = new Model({id: '3'});

            collection = new Collection([m1, m2, m3], {
                entityType: 'Some',
            });

			collection.maxSize = 5;

			spyOn(collection, 'fetch').and.returnValue(true);

			done();
		});
	});

    it('should have correct length', () => {
        expect(collection.length).toBe(3);

        const collection2 = new CollectionClass();
        expect(collection2.length).toBe(0);

        const collection3 = new CollectionClass([new ModelClass({id: '1'})]);
        expect(collection3.length).toBe(1);
    });

    it('should add array', () => {
        const model = new ModelClass({id: '4'});

        collection.add([model]);

        expect(collection.length).toBe(4);
        expect(collection.at(3)).toBe(model);
        expect(collection.get('4')).toBe(model);
        expect(collection.has('4')).toBeTrue();
        expect(collection.indexOf(model)).toBe(3);
    });

    it('should add one', () => {
        const model = new ModelClass({id: '4'});

        collection.add(model, {at: 1});

        expect(collection.length).toBe(4);
        expect(collection.at(1)).toBe(model);
    });

    it('should reset', () => {
        collection.reset();

        expect(collection.length).toBe(0);
        expect(collection.at(3)).toBeUndefined();
        expect(collection.get('3')).toBeUndefined();
    });

    it('should remove', () => {
        collection.remove(collection.get('2'));

        expect(collection.length).toBe(2);
        expect(collection.get('2')).toBeUndefined();

        collection.remove('1');

        expect(collection.length).toBe(1);
        expect(collection.get('1')).toBeUndefined();
    });

    it('should push', () => {
        const model = new ModelClass({id: '4'});

        collection.push(model);

        expect(collection.length).toBe(4);
        expect(collection.at(3).id).toBe('4');
    });

    it('should unshift', () => {
        const model = new ModelClass({id: '4'});

        collection.unshift(model);

        expect(collection.length).toBe(4);
        expect(collection.at(0).id).toBe('4');
    });

    it('should pop', () => {
        const model = collection.pop();

        expect(collection.length).toBe(2);
        expect(model.id).toBe('3');
        expect(collection.has('3')).toBeFalse();
    });

    it('should shift', () => {
        const model = collection.shift();

        expect(collection.length).toBe(2);
        expect(model.id).toBe('1');
    });

    it('should for-each', () => {
        const ids = [];

        collection.forEach(m => ids.push(m.id));

        expect(ids).toEqual(['1', '2', '3']);
    });

    it('should have entityType', () => {
        expect(collection.entityType).toBe('Some');
        expect(collection.name).toBe('Some');
    });

    it('should be created with models', () => {
        expect(collection.length).toBe(3);
    });

	it('#sort should set order params', () => {
		collection.sort('test', true);
		expect(collection.orderBy).toBe('test');
		expect(collection.order).toBe('desc');
	});

	it('#nextPage and #previousPage should change offset to the next and previous pages', () => {
		collection.total = 16;

		collection.add([
            {id: '1'},
            {id: '2'},
            {id: '3'},
            {id: '4'},
            {id: '5'},
        ]);

		collection.nextPage();
		expect(collection.offset).toBe(5);

		collection.nextPage();
		collection.previousPage();
		expect(collection.offset).toBe(5);

		collection.previousPage();
		expect(collection.offset).toBe(0);
	});

	it('#firstPage and #lastPage should change offset appropriate way', () => {
		collection.total = 16;

		collection.firstPage();
		expect(collection.offset).toBe(0);

		collection.lastPage();
		expect(collection.offset).toBe(15);
	});
});
