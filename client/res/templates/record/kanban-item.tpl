<div class="panel panel-default {{#if isStarred}} starred {{~/if}} ">
    <div class="panel-body">
        {{#each layoutDataList}}
        <div>
            {{#if isFirst}}
            {{#unless rowActionsDisabled}}
            <div class="pull-right item-menu-container fix-overflow">{{{../itemMenu}}}</div>
            {{/unless}}
            {{/if}}
            <div class="form-group">
                <div
                    class="field{{#if isAlignRight}} field-right-align{{/if}}{{#if isLarge}} field-large{{/if}}{{#if isMuted}} text-muted{{/if}}"
                    data-name="{{name}}"
                >{{{var key ../this}}}</div>
            </div>
        </div>
        {{/each}}
    </div>
</div>
