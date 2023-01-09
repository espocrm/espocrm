{{#if value~}}
<a
    role="button"
    tabindex="0"
    data-action="show"
><span
    class="fas fa-paperclip text-soft{{#if isSmall}} small{{/if}}"
    title="{{translate 'hasAttachment' category='fields' scope='Email'}}"
></span></a>
{{~/if~}}
