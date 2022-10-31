{{#if isNew}}
<div class="scope-switcher radio-container">
{{#each scopeList}}
    <div>
        <label>
            <input type="radio" name="scope"{{#ifEqual this ../scope}} checked{{/ifEqual}} value="{{./this}}">
            {{translate this category='scopeNames'}}
        </label>
    </div>
{{/each}}
</div>
{{/if}}

<div class="edit-container record no-side-margin">{{{edit}}}</div>
