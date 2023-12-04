{{#unless optionList}}
    {{translate 'No Data'}}
{{/unless}}

{{#if optionList}}
    <div class="margin-bottom-2x margin-top">
        <input
            type="text"
            maxlength="64"
            placeholder="{{translate 'Search'}}"
            data-name="quick-search"
            class="form-control"
            spellcheck="false"
        >
    </div>
    <ul class="list-group list-group-panel array-add-list-group no-side-margin">
    {{#each optionList}}
        <li class="list-group-item clearfix" data-name="{{./this}}">
            <input
                class="cell form-checkbox form-checkbox-small"
                type="checkbox"
                data-value="{{./this}}"
            >
            <a role="button" tabindex="0" class="add text-bold" data-value="{{./this}}">
                {{#if ../translatedOptions}}{{prop ../translatedOptions this}}{{else}}{{./this}}{{/if}}
            </a>
        </li>
    {{/each}}
    </ul>
{{/if}}

<div class="no-data hidden">{{translate 'No Data'}}</div>
