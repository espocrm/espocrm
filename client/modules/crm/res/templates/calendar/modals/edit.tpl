{{#if isNew}}
<div class="scope-switcher radio-container">
{{#each scopeList}}
    <label>
        <input type="radio" name="scope"{{#ifEqual this ../scope}} checked{{/ifEqual}} value="{{./this}}">
        {{translate this category='scopeNames'}}
    </label>
{{/each}}
</div>
{{/if}}

<div class="edit-container record">{{{edit}}}</div>
