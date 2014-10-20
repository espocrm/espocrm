<div class="row">
    <div class="col-sm-3">
        <select name="salutation{{ucName}}" class="form-control">
            {{options salutationOptions salutationValue field='salutation'}}
        </select>        
    </div>
    <div class="col-sm-4">    
        <input type="text" class="form-control" name="first{{ucName}}" value="{{firstValue}}" placeholder="{{translate 'First Name'}}">
    </div>
    <div class="col-sm-5">    
        <input type="text" class="form-control" name="last{{ucName}}" value="{{lastValue}}" placeholder="{{translate 'Last Name'}}">
    </div>
</div>
