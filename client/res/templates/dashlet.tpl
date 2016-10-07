<div id="dashlet-{{id}}" class="panel panel-default dashlet{{#if isDoubleHeight}} double-height{{/if}}" data-name="{{name}}" data-id="{{id}}">
    <div class="panel-heading">
        <div class="dropdown pull-right menu-container">
            <button class="dropdown-toggle btn btn-link btn-sm menu-button" data-toggle="dropdown"><span class="caret"></span></button>
            <ul class="dropdown-menu" role="menu">
              {{#each actionList}}
                <li><a data-action="{{name}}" class="action" href="{{#if url}}{{url}}{{else}}javascript:{{/if}}"{{#each data}} data-{{hyphen @key}}="{{./this}}"{{/each}}>{{#if iconHtml}}{{{iconHtml}}} {{/if}}{{#if html}}{{{html}}}{{else}}{{translate label}}{{/if}}</a></li>
              {{/each}}
              </ul>
          </div>
        <h4 class="panel-title">
          <span data-action="refresh" class="action" title="{{translate 'Refresh'}}" style="cursor: pointer;">{{title}}</span>
        </h4>
    </div>
    <div class="dashlet-body panel-body{{#if noPadding}} no-padding{{/if}}">{{{body}}}</div>
</div>
