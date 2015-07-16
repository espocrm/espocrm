{{#if value}}
    {{{value}}}
    {{#if showCreate}}
    <span class="dorpdown">
        <button class="dropdown-toggle btn btn-link btn-sm" data-toggle="dropdown">
            <span class="caret text-muted"></span>
        </button>
        <ul class="dropdown-menu" role="menu">
            <li><a href="javascript:" data-action="createContact">{{translate 'Create Contact' scope='Email'}}</a></li>
        </ul>
    </span>
    {{/if}}
{{else}}
    {{{translate 'None'}}}
{{/if}}
