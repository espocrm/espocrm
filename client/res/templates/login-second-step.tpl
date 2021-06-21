<div class="container content">
    <div class="col-md-4 col-md-offset-4 col-sm-8 col-sm-offset-2">
    <div id="login" class="panel panel-default">
        <div class="panel-body">
            <div>
                <p>{{message}}</p>
                <form id="login-form" onsubmit="return false;">
                    <div class="form-group">
                        <label for="field-code" max-length="7">{{translate 'Code' scope='User'}}</label>
                        <input
                            type="text"
                            data-name="field-code"
                            class="form-control"
                            autocapitalize="off"
                            autocorrect="off"
                            tabindex="1"
                            autocomplete="code"
                        >
                    </div>
                    <div>
                        <a
                            href="javascript:"
                            class="btn btn-link pull-right"
                            data-action="backToLogin"
                            tabindex="4"
                        >{{translate 'Back to login form' scope='User'}}</a>
                        <button type="submit" class="btn btn-primary" id="btn-send" tabindex="2">{{translate 'Submit'}}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </div>
</div>
<footer class="container">{{{footer}}}</footer>
