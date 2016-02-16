{{#if phoneNumberData}}
    {{#each phoneNumberData}}
        <div>
            <a href="tel:{{phoneNumber}}" data-phone-number="{{phoneNumber}}" data-action="dial">{{phoneNumber}}</a>
            <span class="text-muted small">({{translateOption type scope=../../scope field=../../name}})</span>
        </div>
    {{/each}}
{{else}}
	{{#if value}}
    <a href="tel:{{value}}" data-phone-number="{{value}}" data-action="dial">{{value}}</a>
    {{else}}
        {{translate 'None'}}
    {{/if}}
{{/if}}
