
<input type="text" class="form-control auto-height" name="{{name}}Country" value="{{countryValue}}" placeholder="{{translate 'Country'}}" autocomplete="espo-country">
<div class="row">
    <div class="col-sm-4 col-xs-4">
        <input type="text" class="form-control" name="{{name}}PostalCode" value="{{postalCodeValue}}" placeholder="{{translate 'PostalCode'}}" autocomplete="espo-postalCode">
    </div>
    <div class="col-sm-4 col-xs-4">
        <input type="text" class="form-control" name="{{name}}State" value="{{stateValue}}" placeholder="{{translate 'State'}}" autocomplete="espo-state">
    </div>
    <div class="col-sm-4 col-xs-4">
        <input type="text" class="form-control" name="{{name}}City" value="{{cityValue}}" placeholder="{{translate 'City'}}" autocomplete="espo-city">
    </div>
</div>
<textarea class="form-control" name="{{name}}Street" rows="1" placeholder="{{translate 'Street'}}" autocomplete="espo-street">{{streetValue}}</textarea>
