<div>
{{#each emailAddressData}}
    <div class="input-group email-address-block {{#if ../onlyPrimary}} only-primary {{/if}}">
        <input
            type="email"
            class="form-control email-address{{#if optOut}} text-strikethrough{{/if}}{{#if invalid}} text-danger{{/if}}"
            value="{{emailAddress}}" autocomplete="espo-{{../name}}"
            maxlength={{../itemMaxLength}}
        >
        {{#unless ../onlyPrimary}}
        <span class="input-group-btn">
            <button
                class="btn btn-default btn-icon email-property{{#if primary}} active{{/if}} hidden"
                type="button"
                data-action="switchEmailProperty"
                data-property-type="primary"
                data-toggle="tooltip"
                data-placement="top"
                title="{{translate 'Primary' scope='EmailAddress'}}"
            >
                <span class="fas fa-star fa-sm{{#unless primary}} text-muted{{/unless}}"></span>
            </button>
            <button
                class="btn btn-default btn-icon email-property{{#if optOut}} active{{/if}}"
                type="button"
                data-action="switchEmailProperty"
                data-property-type="optOut"
                data-toggle="tooltip"
                data-placement="top"
                title="{{translate 'Opted Out' scope='EmailAddress'}}"
            >
                <span class="fas fa-ban{{#unless optOut}} text-muted{{/unless}}"></span>
            </button>
            <button
                class="btn btn-default btn-icon radius-right email-property{{#if invalid}} active{{/if}}"
                type="button"
                data-action="switchEmailProperty"
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
                data-action="removeEmailAddress"
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
        data-action="addEmailAddress"
    ><span class="fas fa-plus"></span></button>
{{/unless}}
