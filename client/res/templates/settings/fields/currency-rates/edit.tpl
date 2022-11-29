{{#each rateValues}}
    <div class="input-group">
        <span class="input-group-addon radius-left">1 {{@key}} = </span>
        <input class="form-control" type="text" data-currency="{{@key}}" value="{{./this}}" style="text-align: right;">
        <span class="input-group-addon radius-right">{{../baseCurrency}}</span>
    </div>
{{/each}}
