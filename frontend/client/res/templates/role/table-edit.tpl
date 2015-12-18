<table class="table table-bordered">
    <tr>
        <th></th>
        <th width="20%">{{translate 'Access' scope='Role'}}</th>
        {{#each actionList}}
            <th width="13%">{{translate this scope='Role' category='actions'}}</th>
        {{/each}}
    </tr>
    {{#each tableDataList}}
    <tr>
        <td><b>{{translate name category='scopeNamesPlural'}}</b></td>

        <td>
            <select name="{{name}}" class="form-control" data-type="access">{{options ../accessList access scope='Role' field='accessList'}}</select>
        </td>

        {{#ifNotEqual type 'boolean'}}
            {{#each ../list}}
                <td>
                    <select name="{{name}}" class="form-control" data-scope="{{../name}}"{{#ifNotEqual ../../access 'enabled'}} disabled{{/ifNotEqual}}>
                    {{options ../levelList level field='levelList' scope='Role'}}
                    </select>
                </td>
            {{/each}}
        {{/ifNotEqual}}
    </tr>
    {{/each}}
</table>

{{#if fieldTableDataList.length}}
<table class="table table-bordered" style="margin-top: 20px;">
    <tr>
        <th></th>
        <th width="20%"></th>
        {{#each fieldActionList}}
            <th width="26%">{{translate this scope='Role' category='actions'}}</th>
        {{/each}}
    </tr>
    {{#each fieldTableDataList}}
        {{#each list}}
        <tr>
            <td>{{#ifEqual @index 0}}<b>{{translate ../../name category='scopeNamesPlural'}}</b>{{/ifEqual}}</td>
            <td><b>{{translate name category='fields' scope=../name}}</b></td>
            {{#each list}}
            <td>
                <select name="field-{{../../name}}-{{../name}}" class="form-control" data-field="{{../name}}" data-scope="{{../../name}}" data-action="{{name}}">{{options ../../../fieldLevelList value scope='Role' field='accessList'}}</select>
            </td>
            {{/each}}
        </tr>
        {{/each}}
    {{/each}}
</table>
{{/if}}