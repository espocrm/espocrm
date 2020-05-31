<div class="container content">
    <div class="col-md-4 col-md-offset-3 col-sm-8 col-sm-offset-2">
        <div class="panel panel-default password-change">
            <div class="panel-heading">
                <h4 class="panel-title">{{translate 'Change Password' scope='User'}}</h4>
            </div>
            <div class="panel-body">
                <div>
                        <div class="cell form-group">
                            <label for="login" class="control-label">{{translate 'newPassword' category='fields' scope='User'}}</label>
                            <div class="field" data-name="password">{{{password}}}</div>
                        </div>
                        <div class="cell form-group">
                            <label for="login" class="control-label">{{translate 'newPasswordConfirm' category='fields' scope='User'}}</label>
                            <div class="field" data-name="passwordConfirm">{{{passwordConfirm}}}</div>
                        </div>
                        <div>
                            <button type="button" class="btn btn-danger" id="btn-submit">{{translate 'Submit'}}</button>
                        </div>
                </div>
            </div>
        </div>
        <div class="msg-box hidden"></div>
    </div>
</div>
