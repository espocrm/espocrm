<div class="link-container list-group"></div>
{{#if hasSelectAddress}}
    <div class="input-group">
        <input
            class="form-control"
            type="email"
            autocomplete="espo-{{name}}"
            spellcheck="false"
            maxlength="{{maxLength}}"
        >
        <div class="input-group-btn">
            <button
                data-action="selectAddress"
                class="btn btn-default btn-icon"
                type="button"
                tabindex="-1"
                title="{{translate 'Select'}}"
            ><i class="fas fa-angle-up"></i></button>
        </div>
    </div>
{{else}}
    <input
        class="form-control"
        type="email"
        autocomplete="espo-{{name}}"
        spellcheck="false"
        maxlength="{{maxLength}}"
        title="{{translate 'Select'}}"
    >
{{/if}}
