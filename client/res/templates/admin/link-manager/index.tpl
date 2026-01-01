<div class="page-header">
    <h3>
        <a href="#Admin">{{translate 'Administration'}}</a>
        <span class="breadcrumb-separator"><span></span></span>
        <a href="#Admin/entityManager">{{translate 'Entity Manager' scope='Admin'}}</a>
        <span class="breadcrumb-separator"><span></span></span>
        <a href="#Admin/entityManager/scope={{scope}}">{{translate scope category='scopeNames'}}</a>
        <span class="breadcrumb-separator"><span></span></span>
        {{translate 'Relationships' scope='EntityManager'}}
    </h3>
</div>

<div class="button-container">
    {{#if isCreatable}}
        <button class="btn btn-default btn-wide" data-action="createLink">
            <span class="fas fa-plus fa-sm"></span><span>{{translate 'Create Link' scope='Admin'}}</span>
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
            <th style="width: 20%;">{{translate 'linkForeign' category='fields' scope='EntityManager'}}</th>
            <th style="width: 20%;">
                {{translate 'linkType' category='fields' scope='EntityManager'}}
            </th>
            <th style="width: 20%;">
                {{translate 'link' category='fields' scope='EntityManager'}}
            </th>
            <th style="width: 20%;">
                {{translate 'entityForeign' category='fields' scope='EntityManager'}}
            </th>
            <th style="width: 10%"></th>
        </tr>
    </thead>
    {{/unless}}
    {{#each linkDataList}}
    <tr data-link="{{link}}" class="link-row">
        <td style="">
            <span title="{{translate linkForeign category='links' scope=entityForeign}}">
                {{linkForeign}}
            </span>
        </td>
        <td>
            <span style="color: var(--gray-soft); font-weight: 600;">
            {{translateOption type field='linkType' scope='EntityManager'}}
            </span>
        </td>
        <td>
            <span title="{{translate link category='links' scope=entity}}">
                {{link}}
            </span>
        </td>
        <td>
            {{translate entityForeign category='scopeNames'}}
        </td>
        <td style="text-align: right">
            {{#if hasDropdown}}
                <div class="btn-group row-dropdown-group">
                    <button
                        class="btn btn-link btn-sm dropdown-toggle"
                        data-toggle="dropdown"
                    ><span class="caret"></span></button>
                    <ul class="dropdown-menu pull-right">
                        {{#if isEditable}}
                            <li>
                                <a
                                    role="button"
                                    tabindex="0"
                                    data-action="editLink"
                                    data-link="{{link}}"
                                >{{translate 'Edit'}}</a>
                            </li>
                        {{/if}}
                        {{#if hasEditParams}}
                            <li>
                                <a
                                    role="button"
                                    tabindex="0"
                                    data-action="editParams"
                                    data-link="{{link}}"
                                >{{translate 'Parameters' scope='EntityManager'}}</a>
                            </li>
                        {{/if}}
                        {{#if isRemovable}}
                        <li>
                            <a
                                role="button"
                                tabindex="0"
                                data-action="removeLink"
                                data-link="{{link}}"
                            >{{translate 'Remove'}}</a>
                        </li>
                        {{/if}}
                    </ul>
                </div>
            {{/if}}
        </td>
    </tr>
    {{/each}}
</table>

<div class="no-data hidden">{{translate 'No Data'}}</div>
