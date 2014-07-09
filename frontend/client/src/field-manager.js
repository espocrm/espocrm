/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014  Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

Espo.FieldManager = function (defs, metadata) {
	this.defs = defs;
	this.metadata = metadata;
};

_.extend(Espo.FieldManager.prototype, {

	defs: null,
	
	metadata: null,

	getSearchParams: function (fieldType) {
		if (fieldType in this.defs) {
			var res = this.defs[fieldType].search || {};
			if (!('basic' in res)) {
				res.basic = true;
			}
			if (!('advanced' in res)) {
				res.advanced = true;
			}
			return res;
		}
		return false;
	},

	getParams: function (fieldType) {
		if (fieldType in this.defs) {
			return this.defs[fieldType].params || [];
		}
		return [];
	},
	
	isMergable: function (fieldType) {
		if (fieldType in this.defs) {
			if ('mergable' in this.defs[fieldType]) {
				return this.defs[fieldType].mergable;
			} else {
				return true;
			}
		}
		return false;
	},
	
	getEntityAttributes: function (entityName) {
		var list = [];
		
		var defs = this.metadata.get('entityDefs.' + entityName + '.fields') || {};		
		Object.keys(defs).forEach(function (field) {
			this.getAttributes(defs[field]['type'], field).forEach(function (attr) {
				if (!~list.indexOf(attr)) {
					list.push(attr);				
				}
			});					
		}, this);
		return list;
	},

	getActualAttributes: function (fieldType, fieldName) {
		var fieldNames = [];
		if (fieldType in this.defs) {
			if ('actualFields' in this.defs[fieldType]) {
				var actualfFields = this.defs[fieldType].actualFields;

				var naming = 'suffix';
				if ('naming' in this.defs[fieldType]) {
					naming = this.defs[fieldType].naming;
				}
				if (naming == 'prefix') {
					actualfFields.forEach(function (f) {
						fieldNames.push(f + Espo.Utils.upperCaseFirst(fieldName));
					});
				} else {
					actualfFields.forEach(function (f) {
						fieldNames.push(fieldName + Espo.Utils.upperCaseFirst(f));
					});
				}
			} else {
				fieldNames.push(fieldName);
			}
		}
		return fieldNames;
	},

	getNotActualAttributes: function (fieldType, fieldName) {
		var fieldNames = [];
		if (fieldType in this.defs) {
			if ('notActualFields' in this.defs[fieldType]) {
				var notActualFields = this.defs[fieldType].notActualFields;

				var naming = 'suffix';
				if ('naming' in this.defs[fieldType]) {
					naming = this.defs[fieldType].naming;
				}
				if (naming == 'prefix') {
					notActualFields.forEach(function (f) {
						fieldNames.push(f + Espo.Utils.upperCaseFirst(fieldName));
					});
				} else {
					notActualFields.forEach(function (f) {
						fieldNames.push(fieldName + Espo.Utils.upperCaseFirst(f));
					});
				}
			}
		}
		return fieldNames;
	},

	getAttributes: function (fieldType, fieldName) {
		return _.union(this.getActualAttributes(fieldType, fieldName), this.getNotActualAttributes(fieldType, fieldName));
	},

	getViewName: function (fieldType) {
		if (fieldType in this.defs) {
			if ('module' in this.defs[fieldType]) {
				return this.defs[fieldType].module + ':Fields.' + Espo.Utils.upperCaseFirst(fieldType);
			}
		}
		return 'Fields.' + Espo.Utils.upperCaseFirst(fieldType);
	},
});


