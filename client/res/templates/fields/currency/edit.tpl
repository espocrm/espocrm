<div class="input-group input-group-currency">
    <span class="input-group-item">
        <input
            type="text"
            class="main-element form-control radius-left numeric-text"
            data-name="{{name}}"
            value="{{value}}"
            autocomplete="espo-{{name}}"
            pattern="[\-]?[0-9,.]*"
            {{#if params.maxLength}} maxlength="{{params.maxLength}}"{{/if}}
        >
    </span>
    {{#if multipleCurrencies}}
    <span class="input-group-item">
        <select
            data-name="{{currencyFieldName}}"
            class="form-control radius-right"
        >{{{options currencyList currencyValue}}}</select>
    </span>
    {{else}}
    <span class="input-group-addon radius-right">{{defaultCurrency}}</span>
    {{/if}}
</div>
