
<div class="group-head" data-level="{{level}}">
    <a class="pull-right" role="button" data-action="remove"><span class="fas fa-times"></span></a>
    <div><span class="not-operator">{{translate 'not' category='logicalOperators' scope='Admin'}}</span> (</div>
</div>

<div class="item-list" data-level="{{level}}">
    <div data-view-key="{{viewKey}}">{{#if hasItem}}{{{var viewKey this}}}{{/if}}</div>
</div>

<div class="group-bottom" data-level="{{level}}">
    <div class="btn-group">
        <a class="dropdown-toggle small" role="button" data-toggle="dropdown"><span class="fas fa-plus"></span></a>
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

<div>)</div>
