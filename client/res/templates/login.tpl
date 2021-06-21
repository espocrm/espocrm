<div class="container content">
    <div class="col-md-4 col-md-offset-4 col-sm-8 col-sm-offset-2">
    <div id="login" class="panel panel-default">
        <div class="panel-heading">
            <div class="logo-container">
                <img src="{{logoSrc}}" class="logo">
            </div>
        </div>
        <div class="panel-body">
            <div>
                <form id="login-form" onsubmit="return false;">
                    <div class="form-group">
                        <label for="field-username">{{translate 'Username'}}</label>
                        <input
                            type="text"
                            name="username"
                            id="field-userName"
                            class="form-control"
                            autocapitalize="off"
                            autocorrect="off"
                            tabindex="1"
                            autocomplete="username"
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
                        >
                    </div>
                    <div>
                        {{#if showForgotPassword}}
                        <a
                            href="javascript:"
                            class="btn btn-link pull-right"
                            data-action="passwordChangeRequest"
                            tabindex="4"
                        >{{translate 'Forgot Password?' scope='User'}}</a>{{/if}}
                        <button
                            type="submit"
                            class="btn btn-primary"
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
<footer class="container">{{{footer}}}</footer>
