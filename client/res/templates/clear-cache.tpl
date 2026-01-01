<div class="row">
    <div class="col-md-6 col-sm-offset-2">
        <div class="panel">
            <div class="panel-body">
                {{#if cacheIsEnabled}}
                <button class="btn btn-default action" data-action="clearLocalCache">{{translate 'Clear Local Cache'}}</button>
                {{else}}
                    <div style="margin-bottom: 10px;">
                        <span class="text-danger">
                        {{translate 'Cache is not enabled'}}
                        </span>
                    </div>
                {{/if}}

                <div class="hidden message-container margin-bottom"><span class="text-success"></span></div>
                <div>
                    <button class="btn btn-default action {{#if cacheIsEnabled}}hidden{{/if}}" data-action="returnToApplication">{{translate 'Return to Application'}}</button>
                </div>
            </div>
        </div>
    </div>
</div>
