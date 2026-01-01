
<div class="group-head" data-level="{{level}}">
    {{#ifNotEqual level 0}}
    <a class="pull-right" role="button" tabindex="0" data-action="remove"><span class="fas fa-times"></span></a>
    {{/ifNotEqual}}
    {{#ifNotEqual level 0}}
    <div>(</div>
    {{else}}
    &nbsp;
    {{/ifNotEqual}}
</div>

<div class="item-list" data-level="{{level}}">
{{#each viewDataList}}
    <div data-view-key="{{key}}">{{{var key ../this}}}</div>
    <div class="group-operator" data-view-ref-key="{{key}}">{{translate ../groupOperator category='logicalOperators' scope='Admin'}}</div>
{{/each}}
</div>

<div class="group-bottom" data-level="{{level}}">
    <div class="btn-group">
        <a
            class="dropdown-toggle small"
            role="button"
            tabindex="0"
            data-toggle="dropdown"
        >{{translate groupOperator category='logicalOperators' scope='Admin'}} <span class="fas fa-plus"></span></a>
        <ul class="dropdown-menu">
            <li><a
                    role="button"
                    tabindex="0"
                    data-action="addField"
                >{{translate 'Field' scope='DynamicLogic'}}</a></li>
            <li class="divider"></li>
            <li><a
                    role="button"
                    tabindex="0"
                    data-action="addAnd"
                >(... {{translate 'and' category='logicalOperators' scope='Admin'}} ...)</a></li>
            <li><a
                    role="button"
                    tabindex="0"
                    data-action="addOr"
                >(... {{translate 'or' category='logicalOperators' scope='Admin'}} ...)</a></li>
            <li><a
                    role="button"
                    tabindex="0"
                    data-action="addNot"
                >{{translate 'not' category='logicalOperators' scope='Admin'}} (...)</a></li>
            <li class="divider"></li>
            <li><a
                role="button"
                tabindex="0"
                data-action="addCurrentUser"
            >${{translate 'User' scope='scopeNames'}}</a></li>
            <li><a
                role="button"
                tabindex="0"
                data-action="addCurrentUserTeams"
            >${{translate 'User' scope='scopeNames'}}.{{translate 'teams' category='fields' scope='User'}}</a></li>
        </ul>
    </div>
</div>

{{#ifNotEqual level 0}}
<div>)</div>
{{/ifNotEqual}}
