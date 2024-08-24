<div class="phone-number-block-container">
{{#each phoneNumberData}}
    <div class="input-group phone-number-block">
        <span class="input-group-item">
            <select
                data-property-type="type"
                class="form-control radius-left"
            >{{options ../params.typeList type scope=../scope field=../name}}</select>
        </span>
        <span class="input-group-item input-group-item-middle">
            <input
                type="input"
                class="form-control phone-number no-margin-shifting{{#if optOut}} text-strikethrough{{/if}}{{#if invalid}} text-danger{{/if}}"
                value="{{phoneNumber}}"
                autocomplete="espo-{{../name}}"
                maxlength={{../itemMaxLength}}
            >
        </span>
        <span class="input-group-btn">
            <button
                class="btn btn-default btn-icon phone-property{{#if primary}} active{{/if}} hidden"
                type="button"
                data-action="switchPhoneProperty"
                data-property-type="primary"
                data-toggle="tooltip"
                data-placement="top"
                title="{{translate 'Primary' scope='PhoneNumber'}}"
            >
                <span class="fas fa-star fa-sm{{#unless primary}} text-muted{{/unless}}"></span>
            </button>
            <button
                class="btn btn-default btn-icon phone-property{{#if optOut}} active{{/if}}"
                type="button"
                data-action="switchPhoneProperty"
                data-property-type="optOut"
                data-toggle="tooltip"
                data-placement="top"
                title="{{translate 'Opted Out' scope='EmailAddress'}}"
            >
                <span class="fa fa-ban{{#unless optOut}} text-muted{{/unless}}"></span>
            </button>
            <button
                class="btn btn-default btn-icon radius-right phone-property{{#if invalid}} active{{/if}}"
                type="button"
                data-action="switchPhoneProperty"
                data-property-type="invalid"
                data-toggle="tooltip"
                data-placement="top"
                title="{{translate 'Invalid' scope='EmailAddress'}}"
            >
                <span class="fa fa-exclamation-circle{{#unless invalid}} text-muted{{/unless}}"></span>
            </button>
            <button
                class="btn btn-link btn-icon hidden"
                type="button"
                tabindex="-1"
                data-action="removePhoneNumber"
                data-toggle="tooltip"
                data-placement="top"
                title="{{translate 'Remove'}}"
            >
                <span class="fas fa-times"></span>
            </button>
        </span>
    </div>
{{/each}}
</div>

<button class="btn btn-default btn-icon" type="button" data-action="addPhoneNumber"><span class="fa fa-plus"></span></button>
