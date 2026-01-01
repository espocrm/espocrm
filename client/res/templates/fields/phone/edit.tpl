<div class="phone-number-block-container">
{{#each phoneNumberData}}
    <div class="input-group phone-number-block {{#if ../onlyPrimary}} only-primary {{/if}}">
        {{#unless ../onlyPrimary}}
        <span class="input-group-item">
            <select
                data-property-type="type"
                class="form-control radius-left"
            >{{options ../params.typeList type scope=../scope field=../name}}</select>
        </span>
        {{/unless}}
        <span class="input-group-item input-group-item-middle input-phone-number-item">
            <input
                type="text"
                class="form-control phone-number numeric-text no-margin-shifting {{#if optOut}} text-strikethrough {{/if}} {{#if invalid}} text-danger {{/if}}"
                value="{{phoneNumber}}"
                autocomplete="espo-{{../name}}"
                maxlength={{../itemMaxLength}}
            >
        </span>
        {{#unless ../onlyPrimary}}
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
                <span class="fas fa-ban{{#unless optOut}} text-muted{{/unless}}"></span>
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
                <span class="fas fa-exclamation-circle{{#unless invalid}} text-muted{{/unless}}"></span>
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
        {{/unless}}
    </div>
{{/each}}
</div>

{{#unless onlyPrimary}}
    <button
        class="btn btn-default btn-icon"
        type="button"
        data-action="addPhoneNumber"
    ><span class="fas fa-plus"></span></button>
{{/unless}}
