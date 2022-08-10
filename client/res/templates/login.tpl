<div class="container content">
    <div class="container-centering">
    <div id="login" class="panel panel-default block-center-sm">
        <div class="panel-heading">
            <div class="logo-container">
                <img src="{{logoSrc}}" class="logo">
            </div>
        </div>
        <div class="panel-body">
            <div>
                <form id="login-form">
                    <div class="form-group">
                        <label for="field-username">{{translate 'Username'}}</label>
                        <input
                            type="text"
                            name="username"
                            id="field-userName"
                            class="form-control"
                            autocapitalize="off"
                            spellcheck="false"
                            tabindex="1"
                            autocomplete="username"
                            maxlength="255"
                        >
                    </div>
                    <div class="form-group">
                        <label for="login">{{translate 'Password'}}</label>
                        <input
                            type="password"
                            name="password"
                            id="field-password"
                            class="form-control"
                            tabindex="2"
                            autocomplete="current-password"
                            maxlength="255"
                        >
                    </div>
                    <div class="margin-top-2x">
                        {{#if showForgotPassword}}
                        <a
                            role="button"
                            class="btn btn-link btn-text btn-text-hoverable btn-sm pull-right margin-top-sm"
                            data-action="passwordChangeRequest"
                            tabindex="4"
                        >{{translate 'Forgot Password?' scope='User'}}</a>{{/if}}
                        <button
                            type="submit"
                            class="btn btn-primary btn-s-wide"
                            id="btn-login"
                            tabindex="3"
                        >{{translate 'Login' scope='User'}}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </div>
</div>
<footer>{{{footer}}}</footer>
