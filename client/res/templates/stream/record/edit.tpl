<div class="row">
    <div class="cell col-sm-12 form-group" data-name="post">
        <div class="field" data-name="post">{{{postField}}}</div>
    </div>
</div>
<div class="row post-control{{#if interactiveMode}} hidden{{/if}}">
    <div class="col-sm-7 form-group">
        <div class="floated-row clearfix">
            {{#if interactiveMode}}
            <div>
                <button
                    type="button"
                    class="btn btn-primary btn-xs-wide post pull-left"
                >{{translate 'Post'}}</button>
            </div>
            {{/if}}
            <div>
                <div class="field" style="display: inline-block;" data-name="attachments">{{{attachmentsField}}}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-5">
        <div class="form-group">
            <div class="cell" data-name="targetType">
                <label class="control-label">{{translate 'to' category='otherFields' scope='Note'}}</label>
                <div class="field" data-name="targetType">{{{targetTypeField}}}</div>
            </div>
            <div class="cell" data-name="users">
                <div class="field" data-name="users">{{{usersField}}}</div>
            </div>
            <div class="cell" data-name="teams">
                <div class="field" data-name="teams">{{{teamsField}}}</div>
            </div>
            <div class="cell" data-name="portals">
                <div class="field" data-name="portals">{{{portalsField}}}</div>
            </div>
        </div>
    </div>
</div>
