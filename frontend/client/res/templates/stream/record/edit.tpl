<div class="row">
    <div class="cell col-sm-12 form-group" data-name="post">
        <div class="field" data-name="post">{{{post}}}</div>
    </div>
</div>
<div class="row post-control{{#if interactiveMode}} hidden{{/if}}">

    <div class="col-sm-6 form-group">
        <div>
                {{#if interactiveMode}}
                <button type="button" class="btn btn-primary post pull-left">{{translate 'Post'}}</button>
                {{/if}}
                <div class="field" style="display: inline-block;" data-name="attachments">{{{attachments}}}</div>

        </div>
    </div>
    <div class="col-sm-6">
        <div class="cell form-group" data-name="targetType">
            <div class="field" data-name="targetType">{{{targetType}}}</div>
        </div>
        <div class="cell form-group" data-name="users">
            <div class="field" data-name="users">{{{users}}}</div>
        </div>
        <div class="cell form-group" data-name="teams">
            <div class="field" data-name="teams">{{{teams}}}</div>
        </div>
    </div>
</div>