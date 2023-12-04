<div class="link-container list-group"></div>

<div class="input-group add-team">
    <input
        class="main-element form-control"
        type="text"
        value=""
        autocomplete="espo-{{name}}"
        placeholder="{{translate 'Select'}}"
        spellcheck="false"
    >
    <span class="input-group-btn">
        {{#if createButton}}
            <button
                data-action="createLink"
                class="btn btn-default btn-icon"
                type="button"
                title="{{translate 'Create'}}"
            ><i class="fas fa-plus"></i></button>
        {{/if}}
        <button
            data-action="selectLink"
            class="btn btn-default btn-icon"
            type="button"
            title="{{translate 'Select'}}"
        ><span class="fas fa-angle-up"></span></button>
    </span>
</div>
