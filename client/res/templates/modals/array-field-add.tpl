{{#unless optionList}}
    {{translate 'No Data'}}
{{/unless}}
<ul class="list-group array-add-list-group no-side-margin">
{{#each optionList}}
    <li class="list-group-item clearfix">
        <a href="javascript:" class="add text-bold" data-value="{{./this}}">
            {{#if ../translatedOptions}}{{prop ../../translatedOptions this}}{{else}}{{./this}}{{/if}}
        </a>
    </li>
{{/each}}
</ul>
