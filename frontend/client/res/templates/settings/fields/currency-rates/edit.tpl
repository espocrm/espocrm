{{#each rateValues}}        
    <div class="input-group">
        <span class="input-group-addon">{{@key}}</span>
        <input class="form-control" type="text" data-currency="{{@key}}" value="{{./this}}">
    </div>
{{/each}}
