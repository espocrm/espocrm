<div class="panel panel-default">
    <div class="panel-body">
        {{#each layoutDataList}}
        <div>
            {{#if isFirst}}
            {{#unless rowActionsDisabled}}
            <div class="pull-right item-menu-container">{{{../itemMenu}}}</div>
            {{/unless}}
            {{/if}}
            <div class="form-group">
                <div
                    class="field{{#if isAlignRight}} field-right-align{{/if}}{{#if isLarge}} field-large{{/if}}"
                    data-name="{{name}}"
                >{{{var key ../this}}}</div>
            </div>
        </div>
        {{/each}}
    </div>
</div>
