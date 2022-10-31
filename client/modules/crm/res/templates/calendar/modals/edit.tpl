{{#if isNew}}
<div class="scope-switcher radio-container">
{{#each scopeList}}
    <div>
        <label class="radio-label">
            <input
                type="radio"
                name="scope"
                class="form-radio"
                {{#ifEqual this ../scope}} checked{{/ifEqual}}
                value="{{./this}}"
            >
            {{translate this category='scopeNames'}}
        </label>
    </div>
{{/each}}
</div>
{{/if}}

<div class="edit-container record no-side-margin">{{{edit}}}</div>
