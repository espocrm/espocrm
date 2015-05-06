{{#unless folders}}
    {{translate 'No Data'}}
{{/unless}}
<ul class="list-group">
{{#each folders}}
    <li class="list-group-item clearfix">
        {{./this}}
        <button class="btn btn-default pull-right" data-value="{{./this}}" data-action="select">{{translate 'Select'}}</button>
    </li>
{{/each}}
</ul>
