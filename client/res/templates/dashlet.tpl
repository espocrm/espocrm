<div
  id="dashlet-{{id}}"
  class="panel panel-default headered dashlet{{#if isDoubleHeight}} double-height{{/if}}"
  data-name="{{name}}"
  data-id="{{id}}"
>
    <div class="panel-heading">
        <div class="btn-group pull-right">
            {{#each buttonList}}
            <button
              type="button"
              class="btn btn-{{#if ../style}}{{../style}}{{else}}default{{/if}} dashlet-action btn-sm action{{#if hidden}} hidden{{/if}}"
              data-action="{{name}}"
              data-name="{{name}}"
              title="{{#if title}}{{translate title}}{{/if}}"
            >{{#if html}}{{{html}}}{{else}}{{#if text}}{{text}}{{else}}{{translate label}}{{/if}}{{/if}}</button>
            {{/each}}
            <button
              class="dropdown-toggle btn btn-{{#if ../style}}{{../style}}{{else}}default{{/if}} btn-sm menu-button"
              data-toggle="dropdown"
            ><span class="fas fa-ellipsis-h"></span></button>
            <ul class="dropdown-menu dropdown-menu-with-icons" role="menu">
            {{#each actionList}}
                {{#if this}}
                    <li>
                        <a
                          data-action="{{name}}"
                          data-name="{{name}}"
                          class="action dashlet-action"
                          {{#if url}}href="{{url}}"{{else}}role="button"{{/if}}
                          tabindex="0"
                          {{#each data}} data-{{hyphen @key}}="{{./this}}"{{/each}}
                        >
                            {{#if iconHtml}}{{{iconHtml}}}
                            {{else}}
                            <span class="empty-icon">&nbsp;</span>
                            {{/if}}
                            <span class="item-text">{{#if html}}{{{html}}}{{else}}{{#if text}}{{text}}{{else}}{{translate label}}{{/if}}{{/if}}</span>
                        </a>
                    </li>
                {{else}}
                    <li class="divider"></li>
                {{/if}}
            {{/each}}
            </ul>
        </div>
        <h4 class="panel-title">
            <span
                data-action="refresh"
                class="action"
                title="{{translate 'Refresh'}}"
            >
                {{~#if color}}<span class="color-icon fas fa-square" style="color: {{color}}"></span><span>&nbsp;</span>{{/if~}}
                {{~#if title}}{{title}}{{else}}&nbsp;{{/if~}}
            </span>
        </h4>
    </div>
    <div class="dashlet-body panel-body{{#if noPadding}} no-padding{{/if}}">{{{body}}}</div>
</div>
