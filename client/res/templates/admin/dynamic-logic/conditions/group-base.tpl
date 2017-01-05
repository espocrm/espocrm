
<div class="group-head">
    {{#ifNotEqual level 0}}
    <a class="pull-right" href="javascript:" data-action="remove"><span class="glyphicon glyphicon-remove"></span></a>
    {{/ifNotEqual}}
    <span>{{translate operator category='logicalOperators' scope='Admin'}}</span>
    <div class="btn-group">
        <button class="btn btn-link btn-sm dropdown-toggle" data-toggle="dropdown"><span class="glyphicon glyphicon-plus"></span></button>
        <ul class="dropdown-menu">
            <li><a href="javascript:" data-action="addField">{{translate 'Field' scope='DynamicLogic'}}</a></li>
            <li><a href="javascript:" data-action="addAnd">{{translate 'and' category='logicalOperators' scope='Admin'}}</a></li>
            <li><a href="javascript:" data-action="addOr">{{translate 'or' category='logicalOperators' scope='Admin'}}</a></li>
            <li><a href="javascript:" data-action="addNot">{{translate 'not' category='logicalOperators' scope='Admin'}}</a></li>
        </ul>
    </div>
</div>
<div class="item-list">
{{#each viewDataList}}
    <div data-view-key="{{key}}">{{{var key ../this}}}</div>
{{/each}}
</div>
