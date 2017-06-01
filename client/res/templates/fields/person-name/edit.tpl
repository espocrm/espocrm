<div class="row">
    <div class="col-sm-3 col-xs-3">
        <select name="salutation{{ucName}}" class="form-control">
            <option value="" disabled selected hidden>Title</option>
            {{options salutationOptions salutationValue field='salutationName' scope=scope}}
        </select>
    </div>
{{#ifAttrNotEmpty model 'deleted'}}
    <div class="col-sm-4 col-xs-4">
        <input type="text" class="form-control" name="first{{ucName}}" value="{{firstValue}}" placeholder="{{translate 'First Name'}}">
    </div>
    <div class="col-sm-5 col-xs-5">
        <input type="text" class="form-control" name="last{{ucName}}" value="{{lastValue}}" placeholder="{{translate 'Last Name'}}">
    </div>
{{else}}
    <div class="col-sm-9 col-xs-9">
        <input type="text" class="form-control" name="person{{ucName}}" value="{{nameValue}}" placeholder="Full Name">
    </div>
{{/ifAttrNotEmpty}}
</div>
