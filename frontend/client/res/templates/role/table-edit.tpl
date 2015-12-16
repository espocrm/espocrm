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
                <select name="{{name}}" class="form-control" data-type="access">{{options ../accessList access scope='Role' field='accessList'}}</select>
            </td>

            {{#ifNotEqual type 'boolean'}}
                {{#each ../acl}}
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



