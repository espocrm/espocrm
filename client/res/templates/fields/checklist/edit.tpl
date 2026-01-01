
{{#each optionDataList}}
<div class="checklist-item-container">
    <input
        type="checkbox"
        data-name="{{dataName}}"
        id="{{id}}"
        class="form-checkbox"
        {{#if isChecked}}checked{{/if}}
    >
    <label for="{{id}}" class="checklist-label">{{label}}</label>
</div>
{{/each}}
{{#unless optionDataList.length}}{{translate 'None'}}{{/unless}}
