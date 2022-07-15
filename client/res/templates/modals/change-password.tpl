<div class="panel panel-default no-side-margin">
    <div class="panel-body">
        <div class="row">
            <div class="cell form-group col-md-6" data-name="currentPassword">
                <label
                    class="control-label"
                    data-name="currentPassword"
                >{{translate 'currentPassword' scope='User' category='fields'}}</label>
                <div class="field" data-name="currentPassword">{{{currentPassword}}}</div>
            </div>
        </div>
        <div class="row">
            <div class="cell form-group col-md-6" data-name="password">
                <label
                    class="control-label"
                    data-name="password"
                >{{translate 'newPassword' scope='User' category='fields'}}</label>
                <div class="field" data-name="password">{{{password}}}</div>
            </div>
        </div>
        <div class="row">
            <div class="cell form-group col-md-6" data-name="passwordConfirm">
                <label
                    class="control-label"
                    data-name="passwordConfirm"
                >{{translate 'passwordConfirm' scope='User' category='fields'}}</label>
                <div class="field" data-name="passwordConfirm">{{{passwordConfirm}}}</div>
            </div>
        </div>
    </div>
</div>
