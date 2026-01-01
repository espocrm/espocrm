{{#if isNotEmpty~}}
    {{~#if copyToClipboard~}}
        <a
            role="button"
            data-action="copyToClipboard"
            class="pull-right text-soft"
            title="{{translate 'Copy to Clipboard'}}"
        ><span class="far fa-copy"></span></a>
    {{~/if~}}
    <span class="{{#if textClass}}{{textClass}}{{/if}}">{{value}}</span>
{{~else}}
{{#if valueIsSet}}<span class="none-value">{{translate 'None'}}</span>{{else}}
<span class="loading-value"></span>{{/if}}
{{/if}}
