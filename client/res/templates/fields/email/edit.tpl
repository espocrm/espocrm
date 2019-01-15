
<div>
{{#each emailAddressData}}
    <div class="input-group email-address-block">
        <input type="email" class="form-control email-address" value="{{emailAddress}}" autocomplete="espo-{{../name}}" maxlength={{../itemMaxLength}}>
        <span class="input-group-btn">
            <button class="btn btn-default btn-icon email-property{{#if primary}} active{{/if}} hidden" type="button" tabindex="-1" data-action="switchEmailProperty" data-property-type="primary" data-toggle="tooltip" data-placement="top" title="{{translate 'Primary' scope='EmailAddress'}}">
                <span class="fas fa-star fa-sm{{#unless primary}} text-muted{{/unless}}"></span>
            </button>
            <button class="btn btn-default btn-icon email-property{{#if optOut}} active{{/if}}" type="button" tabindex="-1" data-action="switchEmailProperty" data-property-type="optOut" data-toggle="tooltip" data-placement="top" title="{{translate 'Opted Out' scope='EmailAddress'}}">
                <span class="fa fa-ban{{#unless optOut}} text-muted{{/unless}}"></span>
            </button>
            <button class="btn btn-default btn-icon email-property{{#if invalid}} active{{/if}}" type="button" tabindex="-1" data-action="switchEmailProperty" data-property-type="invalid" data-toggle="tooltip" data-placement="top" title="{{translate 'Invalid' scope='EmailAddress'}}">
                <span class="fa fa-exclamation-circle{{#unless invalid}} text-muted{{/unless}}"></span>
            </button>
            <button class="btn btn-link btn-icon hidden" type="button" tabindex="-1" data-action="removeEmailAddress" data-property-type="invalid" data-toggle="tooltip" data-placement="top" title="{{translate 'Remove'}}">
                <span class="fas fa-times"></span>
            </button>
        </span>
    </div>
{{/each}}
</div>

<button class="btn btn-default btn-icon" type="button" data-action="addEmailAddress"><span class="fa fa-plus"></span></button>
