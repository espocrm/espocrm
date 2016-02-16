<div class="container content">
    <div class="col-md-4 col-md-offset-4 col-sm-8 col-sm-offset-2">
        <div class="panel panel-default password-change">
            <div class="panel-heading">
                <h4 class="panel-title">{{translate 'Change Password' scope='User'}}</h4>
            </div>
            <div class="panel-body">
                <div>
                        <div class="cell form-group">
                            <label for="login" class="control-label">{{translate 'newPassword' category="fields" scope='User'}}</label>
                            <input type="password" name="password" class="form-control">
                        </div>
                        <div class="cell form-group">
                            <label for="login" class="control-label">{{translate 'newPasswordConfirm' category="fields" scope='User'}}</label>
                            <input type="password" name="passwordConfirm" class="form-control">
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
