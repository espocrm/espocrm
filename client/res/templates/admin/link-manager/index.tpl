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
    <button class="btn btn-default" data-action="createLink">
        <span class="fas fa-plus"></span>
        {{translate 'Create Link' scope='Admin'}}
    </button>
</div>


<table class="table">
    {{#unless linkDataList.length}}
    <tr>
        <td>
            {{translate 'No Data'}}
        </td>
    </tr>
    {{else}}
    <thead>
        <tr>
            <th width="15%" align="left">{{translate 'entity' category='fields' scope='EntityManager'}}</th>
            <th width="15%" align="left">{{translate 'linkForeign' category='fields' scope='EntityManager'}}</th>
            <th width="10%" align="center" style="text-align: center">
                {{translate 'linkType' category='fields' scope='EntityManager'}}
            </th>
            <th width="15%" align="right" style="text-align: right">
                {{translate 'link' category='fields' scope='EntityManager'}}
            </th>
            <th width="15%" align="right" style="text-align: right">
                {{translate 'entityForeign' category='fields' scope='EntityManager'}}
            </th>
        </tr>
    </thead>

    </tr>
    {{/unless}}
    {{#each linkDataList}}
    <tr data-link="{{link}}">
        <td width="15%" align="left">
            {{translate entity category='scopeNames'}}
        </td>
        <td width="15%" align="left">
            <span title="{{translate linkForeign category='links' scope=entityForeign}}">
                {{linkForeign}}
            </span>
        </td>
        <td width="10%" align="center">
            <strong>
            {{translateOption type field='linkType' scope='EntityManager'}}
            </strong>
        </td>
        <td width="15%" align="right">
            <span title="{{translate link category='links' scope=entity}}">
                {{link}}
            </span>
        </td>
        <td width="15%" align="right">
            {{translate entityForeign category='scopeNames'}}
        </td>
        <td align="right" width="10%">
            <a href="javascript:" data-action="editLink" data-link="{{link}}">
                {{translate 'Edit'}}
            </a>
        </td>
        <td align="right" width="10%">
            {{#if isRemovable}}
            <a href="javascript:" data-action="removeLink" data-link="{{link}}">
                {{translate 'Remove'}}
            </a>
            {{/if}}
        </td>
    </tr>
    {{/each}}
</table>


