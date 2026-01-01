<textarea
    class="form-control auto-height"
    data-name="{{name}}Street"
    rows="1" placeholder="{{translate 'Street'}}"
    autocomplete="espo-street"
    maxlength="{{streetMaxLength}}"
    style="resize: none;"
>{{streetValue}}</textarea>
<input type="text" class="form-control" data-name="{{name}}City" value="{{cityValue}}" placeholder="{{translate 'City'}}" autocomplete="espo-city" maxlength="{{cityMaxLength}}">
<div class="row">
    <div class="col-sm-5 col-xs-5">
        <input type="text" class="form-control" data-name="{{name}}Country" value="{{countryValue}}" placeholder="{{translate 'Country'}}" autocomplete="espo-country" maxlength="{{countryMaxLength}}">
    </div>
    <div class="col-sm-3 col-xs-3">
        <input type="text" class="form-control" data-name="{{name}}State" value="{{stateValue}}" placeholder="{{translate 'State'}}" autocomplete="espo-state" maxlength="{{stateMaxLength}}">
    </div>
    <div class="col-sm-4 col-xs-4">
        <input type="text" class="form-control" data-name="{{name}}PostalCode" value="{{postalCodeValue}}" placeholder="{{translate 'PostalCode'}}" autocomplete="espo-postalCode" maxlength="{{postalCodeMaxLength}}" spellcheck="false">
    </div>
</div>
