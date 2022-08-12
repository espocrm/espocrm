    {{#if isEnabled}}
    <div class="list-row-buttons pull-right right">
        {{#if acl.edit}}
        <div class="btn-group">
        <button
            type="button"
            class="btn btn-link btn-sm dropdown-toggle"
            data-toggle="dropdown"
        >
            <span class="caret"></span>
        </button>
        <ul class="dropdown-menu pull-right">
            {{#if isEditable}}
            <li><a
                role="button"
                class="action"
                tabindex="0"
                data-action="quickEdit"
                data-id="{{model.id}}"
                data-no-full-form="true"
            >{{translate 'Edit'}}</a></li>
            {{/if}}
            {{#if isRemovable}}
            <li><a
                role="button"
                class="action"
                tabindex="0"
                data-action="quickRemove"
                data-id="{{model.id}}"
            >{{translate 'Remove'}}</a></li>
            {{/if}}
        </ul>
        </div>
        {{/if}}
    </div>
{{/if}}
