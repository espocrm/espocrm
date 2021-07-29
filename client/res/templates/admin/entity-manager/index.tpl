<div class="page-header"><h3><a href="#Admin">{{translate 'Administration'}}</a>
<span class="breadcrumb-separator"><span class="chevron-right"></span></span>
{{translate 'Entity Manager' scope='Admin'}}</h3></div>

<div class="button-container">
    <button class="btn btn-default" data-action="createEntity">
        <span class="fas fa-plus"></span>
        {{translate 'Create Entity' scope='Admin'}}
    </button>
</div>

<div class="row">
<div class="col-md-9">

<div class="margin-bottom-2x margin-top">
    <input
        type="text"
        maxlength="64"
        placeholder="{{translate 'Search'}}"
        data-name="quick-search"
        class="form-control"
    >
</div>
<table class="table table-hover scopes-table">
    <thead>
        <tr>
            <th>{{translate 'name' scope='EntityManager' category='fields'}}</th>
            <th>{{translate 'label' scope='EntityManager' category='fields'}}</th>
            <th>{{translate 'type' scope='EntityManager' category='fields'}}</th>
        </tr>
    </thead>
    <tbody>
    {{#each scopeDataList}}
        <tr data-scope="{{name}}" class="scope-row">
            <td>
                {{#if customizable}}
                <a href="#Admin/entityManager/scope={{name}}">{{name}}</a>
                {{else}}
                {{name}}
                {{/if}}
            </td>
            <td width="33%">
                {{label}}
            </td>
            <td width="30%">
                {{#if type}}
                {{translateOption type field='type' scope='EntityManager'}}
                {{/if}}
            </td>
        </tr>
    {{/each}}
    </tbody>
</table>
<div class="no-data hidden">{{translate 'No Data'}}</div>
</div>
</div>
