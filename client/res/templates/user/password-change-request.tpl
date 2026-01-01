<div class="container content">
    <div class="block-center">
        <div class="panel panel-default password-change">
            <div class="panel-heading">
                <h4 class="panel-title">{{translate 'Change Password' scope='User'}}</h4>
            </div>
            <div class="panel-body">
                {{#unless notFound}}
                <div class="row">
                    <div class="cell form-group col-sm-6">
                        <label
                            for="login"
                            class="control-label"
                        >{{translate 'newPassword' category='fields' scope='User'}}</label>
                        <div class="field" data-name="password">{{{password}}}</div>
                    </div>
                    <div class="cell form-group col-sm-6">
                        <label class="control-label"></label>
                        <div class="field" data-name="generatePassword">{{{generatePassword}}}</div>
                    </div>
                </div>
                <div class="row">
                    <div class="cell form-group col-sm-6">
                        <label
                            for="login"
                            class="control-label"
                        >{{translate 'newPasswordConfirm' category='fields' scope='User'}}</label>
                        <div class="field" data-name="passwordConfirm">{{{passwordConfirm}}}</div>
                    </div>
                    <div class="cell form-group col-sm-6">
                        <label class="control-label"></label>
                        <div class="field" data-name="passwordPreview">{{{passwordPreview}}}</div>
                    </div>
                </div>
                <div>
                    <button
                        type="button"
                        class="btn btn-danger btn-s-wide"
                        id="btn-submit"
                    >{{translate 'Submit'}}</button>
                </div>
                {{else}}
                <p class="complex-text">{{complexText notFoundMessage}}</p>
                {{/unless}}
            </div>
        </div>
        <div class="msg-box hidden"></div>
    </div>
</div>
