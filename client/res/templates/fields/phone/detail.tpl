{{#if phoneNumberData}}
    {{#each phoneNumberData}}
        <div>
            {{#if ../doNotCall}}<s>{{/if}}<a href="tel:{{phoneNumber}}" data-phone-number="{{phoneNumber}}" data-action="dial">{{phoneNumber}}</a>{{#if ../doNotCall}}</s>{{/if}}
            <span class="text-muted small">({{translateOption type scope=../../scope field=../../name}})</span>
        </div>
    {{/each}}
{{else}}
    {{#if value}}
    {{#if doNotCall}}<s>{{/if}}<a href="tel:{{value}}" data-phone-number="{{value}}" data-action="dial">{{value}}</a>{{#if ../doNotCall}}</s>{{/if}}
    {{else}}
        {{translate 'None'}}
    {{/if}}
{{/if}}
