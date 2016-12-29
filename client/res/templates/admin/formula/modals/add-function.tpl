<ul class="list-group">
    {{#each functionDataList}}
        <li class="list-group-item clearfix">
            <button class="btn btn-default pull-right btn-sm" data-action="add" data-value="{{insertText}}">
                <span class="glyphicon glyphicon-plus"></span>
            </button>
            {{insertText}}
        </li>
    {{/each}}
</ul>