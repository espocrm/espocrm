<div class="input-group has-feedback">
    <input
        type="search"
        class="form-control global-search-input"
        placeholder="{{translate 'Search'}}"
        autocomplete="espo-global-search"
        spellcheck="false"
    >
    {{#if hasSearchButton}}
    <div class="input-group-btn">
        <a
            class="btn btn-link global-search-button"
            data-action="search"
            title="{{translate 'Search'}}"
        ><span class="fas fa-search icon"></span></a>
    </div>
    {{/if}}
</div>
<div class="global-search-panel-container"></div>
