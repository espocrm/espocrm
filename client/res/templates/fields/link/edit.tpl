<div class="input-group">
    <input
        class="main-element form-control"
        type="text"
        data-name="{{nameName}}"
        value="{{nameValue}}"
        autocomplete="espo-{{name}}"
        placeholder="{{translate 'Select'}}"
        spellcheck="false"
    >
    <span class="input-group-btn">
        {{#if createButton}}
        <button
            data-action="createLink"
            class="btn btn-default btn-icon{{#if idValue}} hidden{{/if}}"
            type="button"
            title="{{translate 'Create'}}"
        ><i class="fas fa-plus"></i></button>
        {{/if}}
        <button
            data-action="selectLink"
            class="btn btn-default btn-icon"
            type="button"
            title="{{translate 'Select'}}"
        ><i class="fas fa-angle-up"></i></button>
        <button
            data-action="clearLink"
            class="btn btn-default btn-icon"
            type="button"
        ><i class="fas fa-times"></i></button>
    </span>
</div>
<input type="hidden" data-name="{{idName}}" value="{{idValue}}">
