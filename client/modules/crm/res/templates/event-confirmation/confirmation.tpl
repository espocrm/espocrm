<div class="container content">
    <div class="block-center-md">
        <div class="panel panel-default">
            <div class="panel-body">
                <h4 class="margin-bottom-2x">{{actionData.translatedEntityType}}: {{actionData.eventName}}</h4>
                <div class="margin-bottom-2x">
                    {{#if dateStartChanged}}
                    <div style="text-decoration: line-through;">{{sentDateStart}}</div>
                    {{/if}}
                    <div>{{dateStart}}</div>
                </div>
                <div>
                    <span class="label label-{{style}} label-md">{{actionData.translatedStatus}}</span>
                    &nbsp;<div class="btn-group">
                        <a role="button" class="dropdown-toggle text-soft" data-toggle="dropdown">
                            <span class="fas fa-ellipsis-h"></span>
                        </a>
                        <ul class="dropdown-menu">
                            {{#each actionDataList}}
                            <li>
                                <a {{#if link}}href="{{link}}"{{/if}}>{{label}}
                                    {{#if active}}<span class="fas fa-check pull-right"></span>{{/if}}
                                </a>
                            </li>
                            {{/each}}
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
