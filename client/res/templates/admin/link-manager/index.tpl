<div class="page-header">
    <h3>
        <a href="#Admin">{{translate 'Administration'}}</a>
        <span class="breadcrumb-separator"><span class="chevron-right"></span></span>
        <a href="#Admin/entityManager">{{translate 'Entity Manager' scope='Admin'}}</a>
        <span class="breadcrumb-separator"><span class="chevron-right"></span></span>
        {{translate scope category='scopeNames'}}
        <span class="breadcrumb-separator"><span class="chevron-right"></span></span>
        {{translate 'Relationships' scope='EntityManager'}}
    </h3>
</div>

<div class="button-container">
    <button class="btn btn-primary" data-action="createLink">{{translate 'Create Link' scope='Admin'}}</button>
</div>


<table class="table">
    {{#unless linkDataList.length}}
     <tr>
        <td>
            {{translate 'No Data'}}
        </td>
     </tr>
    {{/unless}}
    {{#each linkDataList}}
    <tr data-link="{{link}}">
        <td width="15%" align="left">
            {{translate entity category='scopeNames'}}
        </td>
        <td width="15%" align="left">
            {{linkForeign}}
        </td>
        <td width="10%" align="center">
            <strong>
            {{translateOption type field='linkType' scope='EntityManager'}}
            </strong>
        </td>
        <td width="15%" align="right">
            {{link}}
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


