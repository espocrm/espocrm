<div class="input-group colorpicker-component">
    <input
        type="text"
        class="main-element form-control"
        data-name="{{name}}"
        value="{{value}}"
        {{#if params.maxLength}}maxlength="{{params.maxLength}}"{{/if}}
        autocomplete="espo-{{name}}"
    >
    <span class="btn btn-default input-group-addon"><i></i></span>
</div>
