
{{#each optionDataList}}
<div class="checklist-item-container">
    <input
        type="checkbox"
        data-name="{{dataName}}"
        id="{{id}}"
        class="form-checkbox"
        {{#if isChecked}} checked{{/if}}
        disabled="disabled"
    >
    <label for="{{id}}" class="checklist-label">{{label}}</label>
</div>
{{/each}}
{{#unless optionDataList.length}}<span class="none-value">{{translate 'None'}}</span>{{/unless}}
