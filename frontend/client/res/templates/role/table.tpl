    <table class="table table-bordered">
        <tr>
            <th></th>
            <th>{{translate 'Access' scope='Role'}}</th>
            {{#each actionList}}
                <th>{{translate this scope='Role' category='actions'}}</th>
            {{/each}}
        </tr>
    {{#each aclTable}}
        <tr>
            <td><b>{{translate @key category='scopeNamesPlural'}}</b></td>

            <td>
                {{#if ../editMode}}
                    <select name="{{name}}" class="form-control" data-type="access">{{options ../../accessList access scope='Role' field='accessList'}}</select>
                {{else}}
                    <span style="color: {{prop ../../colors access}};">{{translateOption access scope='Role' field='accessList'}}</span>
                {{/if}}
            </td>

            {{#ifNotEqual type 'boolean'}}
                {{#each ../acl}}
                    <td>
                        {{#if ../../../editMode}}
                            <select name="{{../name}}" class="form-control" data-scope="{{../../name}}" {{#ifNotEqual ../../../access 'enabled'}}disabled{{/ifNotEqual}}>
                            {{options ../../levelList level field='levelList' scope='Role'}}
                            </select>
                        {{else}}
                            {{#ifNotEqual ../../../access 'not-set'}}
                                <span style="color: {{prop ../../../../../colors level}};">{{translateOption level field='levelList' scope='Role'}}</span>
                            {{/ifNotEqual}}
                        {{/if}}
                    </td>
                {{/each}}
            {{/ifNotEqual}}
        </tr>
    {{/each}}
    </table>



