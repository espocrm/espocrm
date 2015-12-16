<table class="table table-bordered">
    <tr>
        <th></th>
        <th width="18%">{{translate 'Access' scope='Role'}}</th>
        {{#each actionList}}
            <th width="12%">{{translate this scope='Role' category='actions'}}</th>
        {{/each}}
    </tr>
    {{#each aclTable}}
    <tr>
        <td><b>{{translate @key category='scopeNamesPlural'}}</b></td>

        <td>
            <span style="color: {{prop ../colors access}};">{{translateOption access scope='Role' field='accessList'}}</span>
        </td>

        {{#ifNotEqual type 'boolean'}}
            {{#each ../acl}}
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
