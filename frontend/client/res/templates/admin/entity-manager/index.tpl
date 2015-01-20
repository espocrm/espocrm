<div class="page-header"><h3><a href="#Admin">{{translate 'Administration'}}</a> &raquo {{translate 'Entity Manager' scope='Admin'}}</h3></div>

<div class="button-container">
    <button class="btn btn-primary" data-action="createEntity">{{translate 'Create Entity' scope='Admin'}}</button>
</div>

<table class="table table-bordered table-hover">
    <tr>
        <th>{{translate 'systemName' scope='EntityManager' category='fields'}}</th>
        <th>{{translate 'name' scope='EntityManager' category='fields'}}</th>
        <th>{{translate 'type' scope='EntityManager' category='fields'}}</th>
    </tr>
{{#each scopeDataList}}
    <tr data-scope="{{name}}">
        <td width="20%">
            <a href="javascript:" data-action="editEntity" data-scope="{{name}}">
            {{name}}
            </a>
        </td>
        <td width="20%">
            {{translate name category='scopeNames'}}
        </td>
        <td width="15%">
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
            <a href="#Admin/relationshipManager/scope={{name}}">{{translate 'Relationships' scope='EntityManager'}}</a>
            {{/if}}
        </td>
        <td align="right" width="10%">
            {{#if isCustom}}
            <a href="javascript:" class="btn btn-danger btn-sm" data-action="removeEntity" data-scope="{{name}}">{{translate 'Remove'}}</a>
            {{/if}}
        </td>
    </tr>
{{/each}}
</table>

