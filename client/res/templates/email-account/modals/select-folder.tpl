{{#unless folders}}
    {{translate 'No Data'}}
{{/unless}}
<ul class="list-group no-side-margin array-add-list-group">
{{#each folders}}
    <li class="list-group-item">
        <a role="button" data-value="{{./this}}" data-action="select" class="text-bold">
        {{./this}}
        </a>
    </li>
{{/each}}
</ul>
