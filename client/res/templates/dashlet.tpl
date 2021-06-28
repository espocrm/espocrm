<div
  id="dashlet-{{id}}"
  class="panel panel-default dashlet{{#if isDoubleHeight}} double-height{{/if}}"
  data-name="{{name}}"
  data-id="{{id}}"
>
    <div class="panel-heading">
        <div class="btn-group pull-right">
            {{#each buttonList}}
            <button
              type="button"
              class="btn btn-{{#if ../style}}{{../style}}{{else}}link{{/if}} btn-sm action{{#if hidden}} hidden{{/if}}"
              data-action="{{name}}"
              title="{{#if title}}{{translate title}}{{/if}}"
            >{{#if html}}{{{html}}}{{else}}{{translate label}}{{/if}}</button>
            {{/each}}
            <button
              class="dropdown-toggle btn btn-link btn-sm menu-button"
              data-toggle="dropdown"
            ><span class="fas fa-ellipsis-h"></span></button>
            <ul class="dropdown-menu dropdown-menu-with-icons" role="menu">
              {{#each actionList}}
                <li>
                    <a
                      data-action="{{name}}"
                      class="action"
                      href="{{#if url}}{{url}}{{else}}javascript:{{/if}}"
                      {{#each data}} data-{{hyphen @key}}="{{./this}}"{{/each}}
                    >
                        {{#if iconHtml}}{{{iconHtml}}}
                        {{else}}
                        <span class="empty-icon">&nbsp;</span>
                        {{/if}}
                        <span class="item-text">{{#if html}}{{{html}}}{{else}}{{translate label}}{{/if}}</span>
                    </a>
                </li>
              {{/each}}
              </ul>
          </div>
        <h4 class="panel-title">
            <span
                data-action="refresh"
                class="action"
                title="{{translate 'Refresh'}}"
                style="cursor: pointer;"
            >{{#if title}}{{title}}{{else}}&nbsp;{{/if}}</span>
        </h4>
    </div>
    <div class="dashlet-body panel-body{{#if noPadding}} no-padding{{/if}}">{{{body}}}</div>
</div>
