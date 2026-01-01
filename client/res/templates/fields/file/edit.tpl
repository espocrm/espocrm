<div class="attachment-upload">
    <div class="attachment-button{{#if id}} hidden{{/if}} clearfix ">
        <div class="pull-left">
        <label class="attach-file-label" title="{{translate 'Attach File'}}" tabindex="0">
            <span class="btn btn-default btn-icon"><span class="fas fa-paperclip"></span></span>
            <input
                type="file"
                class="file pull-right"
                {{#if acceptAttribute}}accept="{{acceptAttribute}}"{{/if}}
                tabindex="-1"
            >
        </label>
        </div>
        {{#unless id}}
        {{#if sourceList.length}}
        <div class="pull-left dropdown">
            <button class="btn btn-default btn-icon dropdown-toggle" type="button" data-toggle="dropdown">
                <span class="fas fa-file"></span>
            </button>
            <ul class="dropdown-menu" role="menu">
            {{#each sourceList}}
                <li><a
                        role="button"
                        tabindex="0"
                        class="action"
                        data-action="insertFromSource"
                        data-name="{{./this}}"
                    >{{translate this category='insertFromSourceLabels' scope='Attachment'}}</a></li>
            {{/each}}
            </ul>
        </div>
        {{/if}}
        {{/unless}}
    </div>

    <div class="attachment"></div>
</div>
