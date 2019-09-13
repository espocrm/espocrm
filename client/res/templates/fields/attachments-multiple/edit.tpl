<div class="attachment-upload">
    <div class="clearfix attachment-control">
        {{#unless uploadFromFileSystemDisabled}}
        <div class="pull-left">
            <label class="attach-file-label" title="{{translate 'Attach File'}}">
                <span class="btn btn-default btn-icon"><span class="fas fa-paperclip"></span></span>
                <input type="file" class="file pull-right" multiple {{#if acceptAttribute}}accept="{{acceptAttribute}}"{{/if}}>
            </label>
        </div>
        {{/unless}}

        {{#if sourceList.length}}
        <div class="pull-left dropdown">
            <button class="btn btn-default btn-icon dropdown-toggle" type="button" data-toggle="dropdown">
                <span class="fas fa-file"></span>
            </button>
            <ul class="dropdown-menu" role="menu">
            {{#each sourceList}}
                <li><a href="javascript:" class="action" data-action="insertFromSource" data-name="{{./this}}">{{translate this category='insertFromSourceLabels' scope='Attachment'}}</a></li>
            {{/each}}
            </ul>
        </div>
        {{/if}}
    </div>
    <div class="attachments"></div>
</div>
