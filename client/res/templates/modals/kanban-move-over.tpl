<ul class="list-group">
{{#each optionDataList}}
    <li class="list-group-item">
        <a href="javascript:" data-action="move" data-value="{{value}}">{{label}}</a>
    </li>
{{/each}}
</ul>
