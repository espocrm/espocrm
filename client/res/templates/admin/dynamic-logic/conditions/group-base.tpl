
<div class="group-head" data-level="{{level}}">
    {{#ifNotEqual level 0}}
    <a class="pull-right" role="button" data-action="remove"><span class="fas fa-times"></span></a>
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
        <a class="dropdown-toggle small" role="button" data-toggle="dropdown">{{translate groupOperator category='logicalOperators' scope='Admin'}} <span class="fas fa-plus"></span></a>
        <ul class="dropdown-menu">
            <li><a role="button" data-action="addField">{{translate 'Field' scope='DynamicLogic'}}</a></li>
            <li><a role="button" data-action="addAnd">(... {{translate 'and' category='logicalOperators' scope='Admin'}} ...)</a></li>
            <li><a role="button" data-action="addOr">(... {{translate 'or' category='logicalOperators' scope='Admin'}} ...)</a></li>
            <li><a role="button" data-action="addNot">{{translate 'not' category='logicalOperators' scope='Admin'}} (...)</a></li>
        </ul>
    </div>
</div>

{{#ifNotEqual level 0}}
<div>)</div>
{{/ifNotEqual}}
