{{#if isNotEmpty}}
<div
    class="complex-text-container{{#if isCut}} cut{{/if}}"
    {{#if cutHeight}} style="max-height: {{cutHeight}}px;"{{/if}}
>
    <div class="complex-text">{{#unless displayRawText}}{{#if htmlValue}}{{{htmlValue}}}{{else}}{{complexText value}}{{/if}}{{else}}{{breaklines value}}{{/unless}}</div>
</div>
{{#if isCut}}
<div class="see-more-container hidden">
    <a
        role="button"
        tabindex="0"
        data-action="seeMoreText"
    ><span class="fas fa-sm fa-chevron-down"></span> <span class="text">{{translate 'See more'}}</span></a>
</div>
{{/if}}
{{else}}
    {{#if valueIsSet}}<span class="none-value">{{translate 'None'}}</span>{{else}}
    <span class="loading-value"></span>{{/if}}
{{/if}}
