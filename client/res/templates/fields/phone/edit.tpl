
<div>
{{#each phoneNumberData}}
    <div class="input-group phone-number-block">
        <span class="input-group-btn">
            <select data-property-type="type" class="form-control">{{options ../params.typeList type scope=../scope field=../name}}</select>
        </span>
        <input type="input" class="form-control phone-number no-margin-shifting" value="{{phoneNumber}}" autocomplete="off">
        <span class="input-group-btn">
            <button class="btn btn-default btn-icon phone-property{{#if primary}} active{{/if}} hidden" type="button" tabindex="-1" data-action="switchPhoneProperty" data-property-type="primary" data-toggle="tooltip" data-placement="top" title="{{translate 'Primary' scope='PhoneNumber'}}">
                <span class="fa fa-star fa-sm{{#unless primary}} text-muted{{/unless}}"></span>
            </button>
            <button class="btn btn-link btn-icon hidden" style="margin-left: 5px;" type="button" tabindex="-1" data-action="removePhoneNumber" data-property-type="invalid" data-toggle="tooltip" data-placement="top" title="{{translate 'Remove'}}">
                <span class="fas fa-times"></span>
            </button>
        </span>
    </div>
{{/each}}
</div>

<button class="btn btn-default btn-icon" type="button" data-action="addPhoneNumber"><span class="fa fa-plus"></span></button>

