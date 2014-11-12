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
Espo.define('Views.Email.Fields.FromAddressVarchar', 'Views.Fields.Varchar', function (Dep) {

    return Dep.extend({
    
        detailTemplate: 'email.fields.email-address-varchar.detail',        

        
        setup: function () {
            Dep.prototype.setup.call(this);
            
            this.nameHash = {};
            
            this.initAddressList();            
            this.listenTo(this.model, 'change:' + this.name, function () {
                this.initAddressList();
            }, this);            

        },
        
        initAddressList: function () {        
            this.typeHash = this.model.get('typeHash') || {};
            this.idHash = this.model.get('idHash') || {};
            
            _.extend(this.nameHash, this.model.get('nameHash') || {});            
        },
        
        getAttributeList: function () {
            var list = Dep.prototype.getAttributeList.call(this);
            list.push('nameHash');
            return list;
        },
        
        getValueForDisplay: function () {            
            if (this.mode == 'detail') {
                var address = this.model.get(this.name);
                return this.getDetailAddressHtml(address);
            }
            return Dep.prototype.getValueForDisplay.call(this);
        },
        
        getDetailAddressHtml: function (address) {    
            if (!address) {
                return '';
            }
                            
            var name = this.nameHash[address] || null;
            var entityType = this.typeHash[address] || null;
            var id = this.idHash[address] || null;            

            var addressHtml = '<span class="">' + address + '</span>';

            var lineHtml;
            if (id) {
                lineHtml = '<div>' + '<a href="#' + entityType + '/view/' + id + '">' + name + '</a> <span class="text-muted">&#187;</span> ' + addressHtml + '</div>';
            } else {
                lineHtml = '<div>' + addressHtml + '</div>';
            }
            return lineHtml;
        },    
        
    });
    
});
