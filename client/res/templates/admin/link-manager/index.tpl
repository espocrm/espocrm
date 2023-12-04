<div class="page-header">
    <h3>
        <a href="#Admin">{{translate 'Administration'}}</a>
        <span class="breadcrumb-separator"><span class="chevron-right"></span></span>
        <a href="#Admin/entityManager">{{translate 'Entity Manager' scope='Admin'}}</a>
        <span class="breadcrumb-separator"><span class="chevron-right"></span></span>
        <a href="#Admin/entityManager/scope={{scope}}">{{translate scope category='scopeNames'}}</a>
        <span class="breadcrumb-separator"><span class="chevron-right"></span></span>
        {{translate 'Relationships' scope='EntityManager'}}
    </h3>
</div>

<div class="button-container">
    {{#if isCreatable}}
        <button class="btn btn-default btn-wide" data-action="createLink">
            <span class="fas fa-plus"></span>
            {{translate 'Create Link' scope='Admin'}}
        </button>
    {{/if}}
</div>

{{#if linkDataList.length}}
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
{{/if}}

<table class="table table-panel table-hover">
    {{#unless linkDataList.length}}
    <tr>
        <td>
            {{translate 'No Data'}}
        </td>
    </tr>
    {{else}}
    <thead>
        <tr>
            <th style="width: 15%; text-align: left">{{translate 'entity' category='fields' scope='EntityManager'}}</th>
            <th style="width: 15%; text-align: left">{{translate 'linkForeign' category='fields' scope='EntityManager'}}</th>
            <th style="width: 10%; text-align: center">
                {{translate 'linkType' category='fields' scope='EntityManager'}}
            </th>
            <th style="width: 15%; text-align: right">
                {{translate 'link' category='fields' scope='EntityManager'}}
            </th>
            <th style="width: 15%; text-align: right">
                {{translate 'entityForeign' category='fields' scope='EntityManager'}}
            </th>
            <th style="width: 10%"></th>
            <th style="width: 10%"></th>
        </tr>
    </thead>
    {{/unless}}
    {{#each linkDataList}}
    <tr data-link="{{link}}" class="link-row">
        <td style="width: 15%; text-align: left">
            {{translate entity category='scopeNames'}}
        </td>
        <td style="width: 15%; text-align: left">
            <span title="{{translate linkForeign category='links' scope=entityForeign}}">
                {{linkForeign}}
            </span>
        </td>
        <td style="width: 10%; text-align: center">
            <strong>
            {{translateOption type field='linkType' scope='EntityManager'}}
            </strong>
        </td>
        <td style="width: 15%; text-align: right">
            <span title="{{translate link category='links' scope=entity}}">
                {{link}}
            </span>
        </td>
        <td style="width: 15%; text-align: right">
            {{translate entityForeign category='scopeNames'}}
        </td>
        <td style="width: 10%; text-align: right">
            {{#if isEditable}}
                <a role="button" tabindex="0" data-action="editLink" data-link="{{link}}">
                    {{translate 'Edit'}}
                </a>
            {{/if}}
        </td>
        <td style="width: 10%; ; text-align: right">
            {{#if isRemovable}}
            <a role="button" tabindex="0" data-action="removeLink" data-link="{{link}}">
                {{translate 'Remove'}}
            </a>
            {{/if}}
        </td>
    </tr>
    {{/each}}
</table>

<div class="no-data hidden">{{translate 'No Data'}}</div>
