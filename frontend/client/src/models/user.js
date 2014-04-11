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
Espo.define('Models.User', 'Model', function (Dep) {
	
	return Dep.extend({
	
		name: "User",
	
		isAdmin: function () {			
			return this.get('isAdmin');
		},

		isOwner: function (model) {
			return this.id === model.get('assignedUserId') || this.id === model.get('createdById');
		},

		inTeam: function (model) {
		
			var userTeamIds = this.getTeamIds();

			if (model.name == 'Team') {
				return (userTeamIds.indexOf(model.id) != -1);
			} else {
				var teamIds = model.getTeamIds();
				for (var i in userTeamIds) {
					if (teamIds.indexOf(i) != -1) {
						return true;
					}
				}
			}
			return false;
		},

	});

});
