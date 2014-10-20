{{#if isNew}}
<div class="scope-switcher">
{{#each scopeList}}
    <label>
        <input type="radio" name="scope"{{#ifEqual this ../scope}} checked{{/ifEqual}} value="{{./this}}">        
        {{translate this category='scopeNames'}}
    </label>&nbsp;
{{/each}}
</div>
{{/if}}

<div class="edit-container">{{{edit}}}</div>
