{{#each rateValues}}
    <div class="input-group">
        <span class="input-group-addon radius-left" style="width: 25%">1 {{@key}} = </span>
        <span class="input-group-item">
            <input
                class="form-control"
                type="text"
                data-currency="{{@key}}"
                value="{{./this}}"
                style="text-align: right;"
            >
        </span>
        <span class="input-group-addon radius-right" style="width: 22%">{{../baseCurrency}}</span>
    </div>
{{/each}}
