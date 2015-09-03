<div class="row">
    <div class="col-sm-6 col-xs-6">
        <input type="text" class="form-control" name="from{{ucName}}" value="{{fromValue}}" placeholder="{{translate 'From' scope=scope}}">
    </div>
    <div class="col-sm-6 col-xs-6">
        <input type="text" class="form-control" name="to{{ucName}}" value="{{toValue}}" placeholder="{{translate 'To' scope=scope}}">
    </div>
    <div class="col-sm-12 col-xs-12">
        <select name="{{currencyField}}" class="form-control">
            {{{options currencyList currencyValue}}}
        </select>
    </div>
</div>
