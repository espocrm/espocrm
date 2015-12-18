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
            <span style="color: {{prop ../colors access}};">{{translateOption access scope='Role' field='accessList'}}</span>
        </td>

        {{#ifNotEqual type 'boolean'}}
            {{#each ../list}}
                <td>
                    {{#ifNotEqual ../../access 'not-set'}}
                        <span style="color: {{prop ../../../../colors level}};">{{translateOption level field='levelList' scope='Role'}}</span>
                    {{/ifNotEqual}}
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
                <span style="color: {{prop ../../../colors value}};">{{translateOption value scope='Role' field='accessList'}}</span>
            </td>
            {{/each}}
        </tr>
        {{/each}}
    {{/each}}
</table>
{{/if}}
