
<div>
{{#each phoneNumberData}}
    <div class="input-group phone-number-block">
        <span class="input-group-btn">
            <select data-property-type="type" class="form-control">{{options ../params.typeList type scope=../scope field=../name}}</select>        
        </span>
        <input type="input" class="form-control phone-number" value="{{phoneNumber}}" autocomplete="off">
        <span class="input-group-btn">
            <button class="btn btn-default phone-property{{#if primary}} active{{/if}} hidden" type="button" tabindex="-1" data-action="switchPhoneProperty" data-property-type="primary" data-toggle="tooltip" data-placement="top" title="{{translate 'Primary' scope='PhoneNumber'}}">
                <span class="glyphicon glyphicon-star{{#unless primary}} text-muted{{/unless}}"></span>
            </button>
            <button class="btn btn-link hidden" style="margin-left: 5px;" type="button" tabindex="-1" data-action="removePhoneNumber" data-property-type="invalid" data-toggle="tooltip" data-placement="top" title="{{translate 'Remove'}}">
                <span class="glyphicon glyphicon-remove"></span>
            </button>
        </span>
    </div>
{{/each}}
</div>

<button class="btn btn-default" type="button" data-action="addPhoneNumber"><span class="glyphicon glyphicon-plus"></span></button>

