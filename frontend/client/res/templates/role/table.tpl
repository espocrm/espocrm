
<div class="panel panel-default">
    <div class="panel-heading">
        <h4 class="panel-title">{{translate 'Scope Level' scope='Role'}}</h4>
    </div>
    <div class="panel-body">
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
    </div>
</div>

{{#if fieldTableDataList.length}}
<div class="panel panel-default">
    <div class="panel-heading">
        <h4 class="panel-title">{{translate 'Field Level' scope='Role'}}</h4>
    </div>
    <div class="panel-body">
        <table class="table table-bordered">
            <tr>
                <th></th>
                <th width="20%"></th>
                {{#each fieldActionList}}
                    <th width="13%">{{translate this scope='Role' category='actions'}}</th>
                {{/each}}
                <th width="26%"></th>
            </tr>
            {{#each fieldTableDataList}}
                {{#if list.length}}
                <tr>
                    <td><b>{{translate name category='scopeNamesPlural'}}</b></td>
                    <td></td>
                    <td colspan="3"></td>
                </tr>
                {{/if}}
                {{#each list}}
                <tr>
                    <td></td>
                    <td><b>{{translate name category='fields' scope=../name}}</b></td>
                    {{#each list}}
                    <td>
                        <span style="color: {{prop ../../../colors value}};">{{translateOption value scope='Role' field='accessList'}}</span>
                    </td>
                    {{/each}}
                    <td colspan="2"></td>
                </tr>
                {{/each}}
            {{/each}}
        </table>
    </div>
</div>
{{/if}}
