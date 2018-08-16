{{#unless optionList}}
    {{translate 'No Data'}}
{{/unless}}
<ul class="list-group array-add-list-group">
{{#each optionList}}
    <li class="list-group-item clearfix">
        {{#if ../translatedOptions}}{{prop ../../translatedOptions this}}{{else}}{{./this}}{{/if}}
        <button class="btn btn-default pull-right" data-value="{{./this}}" data-action="add">{{translate 'Add'}}</button>
    </li>
{{/each}}
</ul>
