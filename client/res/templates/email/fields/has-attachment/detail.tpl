{{#if value~}}
<a
    role="button"
    tabindex="0"
    data-action="show"
    class="text-soft"
><span
    class="fas fa-paperclip{{#if isSmall}} small{{/if}}"
    title="{{translate 'View Attachments' scope='Email'}}"
></span></a>
{{~/if~}}
