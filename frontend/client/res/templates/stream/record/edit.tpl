<div class="row">
    <div class="cell cell-post col-sm-12 form-group">
        <div class="field field-post">{{{post}}}</div>
    </div>
</div>
<div class="row post-control{{#if interactiveMode}} hidden{{/if}}">

    <div class="col-sm-6 form-group">
        <div>
                {{#if interactiveMode}}
                <button type="button" class="btn btn-primary post pull-left">{{translate 'Post'}}</button>
                {{/if}}
                <div class="field field-attachments" style="display: inline-block;">{{{attachments}}}</div>

        </div>
    </div>
    <div class="col-sm-6">
        <div class="cell cell-targetType form-group">
            <div class="field field-targetType">{{{targetType}}}</div>
        </div>
        <div class="cell cell-users form-group">
            <div class="field field-users">{{{users}}}</div>
        </div>
        <div class="cell cell-teams form-group">
            <div class="field field-teams">{{{teams}}}</div>
        </div>
    </div>
</div>