{{#if isNotEmpty}}
    <div>
        <div class="top-group-string-container">
            {{{conditionGroup}}}
        </div>
    </div>
{{else}}
    {{#if isSet}}
        <span class="none-value">{{translate 'None'}}</span>
    {{else}}
        <span class="loading-value"></span>
    {{/if}}
{{/if}}
