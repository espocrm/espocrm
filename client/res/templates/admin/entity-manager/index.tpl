<div class="page-header"><h3><a href="#Admin">{{translate 'Administration'}}</a>
<span class="breadcrumb-separator"><span></span></span>
{{translate 'Entity Manager' scope='Admin'}}</h3></div>

<div class="button-container">
    <div class="btn-group">
        <button class="btn btn-default" data-action="createEntity">
            <span class="fas fa-plus fa-sm"></span><span>{{translate 'Create Entity' scope='Admin'}}</span>
        </button>
        <button
            class="btn btn-default dropdown-toggle"
            data-toggle="dropdown"
        ><span class="fas fa-ellipsis-h"></span></button>
        <ul class="dropdown-menu pull-right">
            <li>
                <a
                    role="button"
                    data-action="export"
                    tabindex="0"
                >{{translate 'Export'}}</a>
            </li>
        </ul>
    </div>
</div>

<div class="row">
<div class="col-md-11">

<div class="margin-bottom-2x margin-top">
    <input
        type="text"
        maxlength="64"
        placeholder="{{translate 'Search'}}"
        data-name="quick-search"
        class="form-control"
        spellcheck="false"
    >
</div>
<table class="table table-hover table-panel scopes-table">
    <thead>
        <tr>
            <th>{{translate 'label' scope='EntityManager' category='fields'}}</th>
            <th style="width: 27%">{{translate 'name' scope='EntityManager' category='fields'}}</th>
            <th style="width: 19%">{{translate 'type' scope='EntityManager' category='fields'}}</th>
            <th style="width: 19%">{{translate 'module' scope='EntityManager' category='fields'}}</th>
        </tr>
    </thead>
    <tbody>
    {{#each scopeDataList}}
        <tr data-scope="{{name}}" class="scope-row">
            <td>
                {{#if hasView}}
                <a href="#Admin/entityManager/scope={{name}}">{{label}}</a>
                {{else}}
                {{label}}
                {{/if}}
            </td>
            <td>
                {{name}}
            </td>
            <td>
                {{#if type}}
                {{translateOption type field='type' scope='EntityManager'}}
                {{/if}}
            </td>
            <td>
                {{#if module}}
                    {{translateOption module field='module' scope='EntityManager'}}
                {{/if}}
            </td>
        </tr>
    {{/each}}
    </tbody>
</table>
<div class="no-data hidden">{{translate 'No Data'}}</div>
</div>
</div>
