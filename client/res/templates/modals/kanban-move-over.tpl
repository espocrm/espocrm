<ul class="list-group no-side-margin">
{{#each optionDataList}}
    <li class="list-group-item">
        <a role="button" tabindex="0" data-action="move" data-value="{{value}}">{{label}}</a>
    </li>
{{/each}}
</ul>
