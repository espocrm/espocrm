{{#if phoneNumberData}}
    {{#each phoneNumberData}}
        <div>
            {{#unless invalid}}
            {{#unless erased}}
            <a
                href="tel:{{valueForLink}}"
                data-phone-number="{{valueForLink}}"
                data-action="dial"
                style="display: inline-block;"
                class="selectable"
            >
            {{/unless}}
            {{/unless}}
            <span {{#if lineThrough}}style="text-decoration: line-through"{{/if}}>{{phoneNumber}}</span>
            {{#unless invalid}}
            {{#unless erased}}
            </a>
            {{/unless}}
            {{/unless}}
            {{#if type}}
            <span class="text-muted small">{{translateOption type scope=../scope field=../name}}</span>
            {{/if}}
        </div>
    {{/each}}
{{else}}
    {{#if value}}
    {{#if lineThrough}}<s>{{/if}}<a
            href="tel:{{valueForLink}}"
            data-phone-number="{{valueForLink}}"
            data-action="dial"
            class="selectable"
        >{{value}}</a>{{#if lineThrough}}</s>{{/if}}
    {{else}}
        {{#if valueIsSet}}<span class="none-value">{{translate 'None'}}</span>{{else}}
        <span class="loading-value"></span>{{/if}}
    {{/if}}
{{/if}}
