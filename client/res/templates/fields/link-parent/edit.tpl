<div class="input-group input-group-link-parent">
    {{#if foreignScopeList.length}}
    <span class="input-group-item">
        <select class="form-control radius-left" data-name="{{typeName}}">
            {{options foreignScopeList foreignScope category='scopeNames'}}
        </select>
    </span>
    <span class="input-group-item input-group-item-middle">
        <input
            class="main-element form-control"
            type="text"
            data-name="{{nameName}}"
            value="{{nameValue}}"
            autocomplete="espo-{{name}}"
            placeholder="{{translate 'Select'}}"
            spellcheck="false"
        >
    </span>
    <span class="input-group-btn">
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
    {{else}}
    {{translate 'None'}}
    {{/if}}
</div>
<input type="hidden" data-name="{{idName}}" value="{{idValue}}">
