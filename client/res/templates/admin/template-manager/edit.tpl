<div class="page-header">
    <h4>{{{title}}}</h4>
</div>

<div class="button-container">
    <div class="btn-group">
        <button class="btn btn-primary btn-xs-wide" data-action="save">{{translate 'Save'}}</button>
        <button class="btn btn-default btn-xs-wide" data-action="cancel">{{translate 'Cancel'}}</button>
        <button class="btn btn-default btn-xs-wide" data-action="resetToDefault"
            >{{translate 'Reset to Default' scope='Admin'}}</button>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-body panel-body-form">
        {{#if hasSubject}}
            <div class="row">
                <div class="cell col-sm-12 form-group">
                    <div class="field subject-field">{{{subjectField}}}</div>
                </div>
            </div>
        {{/if}}
        <div class="row">
            <div class="cell col-sm-12 form-group">
                <div class="field body-field">{{{bodyField}}}</div>
            </div>
        </div>
    </div>
</div>
