<div class="page-header"><h3><a href="#Admin">{{translate 'Administration'}}</a> &raquo {{translate 'Entity Manager' scope='Admin'}}</h3></div>

<div class="button-container">
    <button class="btn btn-primary" data-action="createEntity">{{translate 'Create Entity' scope='Admin'}}</button>
</div>

<table class="table table-hover">
    <thead>
        <tr>
            <th>{{translate 'name' scope='EntityManager' category='fields'}}</th>
            <th>{{translate 'label' scope='EntityManager' category='fields'}}</th>
            <th>{{translate 'type' scope='EntityManager' category='fields'}}</th>
            <th>&nbsp;</th>
            <th>&nbsp;</th>
            <th>&nbsp;</th>
            <th>&nbsp;</th>
        </tr>
    </thead>
    <tbody>
    {{#each scopeDataList}}
        <tr data-scope="{{name}}">
            <td width="25%">
                {{name}}
            </td>
            <td width="25%">
                {{label}}
            </td>
            <td width="10%">
                {{#if type}}
                {{translateOption type field='type' scope='EntityManager'}}
                {{/if}}
            </td>
            <td width="10%">
                {{#if customizable}}
                <a href="#Admin/fieldManager/scope={{name}}">{{translate 'Fields' scope='EntityManager'}}</a>
                {{/if}}
            </td>
            <td width="10%">
                {{#if customizable}}
                <a href="#Admin/linkManager/scope={{name}}">{{translate 'Relationships' scope='EntityManager'}}</a>
                {{/if}}
            </td>
            <td align="right" width="10%">
                {{#if customizable}}
                <a href="javascript:" data-action="editEntity" data-scope="{{name}}" title="{{translate 'Edit'}}">
                    {{translate 'Edit'}}
                </a>
                {{/if}}
            </td>
            <td align="right" width="10%">
                {{#if isCustom}}
                <a href="javascript:" data-action="removeEntity" data-scope="{{name}}">
                    {{translate 'Remove'}}
                </a>
                {{/if}}
            </td>
        </tr>
    {{/each}}
    </tbody>
</table>

