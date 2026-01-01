<div class="container content">
    <div class="container-centering">
    <div id="login" class="panel panel-default block-center-sm">
        <div class="panel-body">
            <div>
                <p>{{message}}</p>
                <form id="login-form">
                    <div class="form-group cell">
                        <label for="field-code">{{translate 'Code' scope='User'}}</label>
                        <input
                            type="text"
                            data-name="field-code"
                            id="field-code"
                            class="form-control"
                            autocapitalize="off"
                            spellcheck="false"
                            tabindex="1"
                            autocomplete="one-time-code"
                            maxlength="7"
                        >
                    </div>
                    <div class="margin-top-2x">
                        <a
                            role="button"
                            class="btn btn-link pull-right"
                            data-action="backToLogin"
                            tabindex="4"
                        >{{translate 'Back to login form' scope='User'}}</a>
                        <button
                            type="submit"
                            class="btn btn-primary btn-s-wide"
                            id="btn-send"
                            tabindex="2"
                        >{{translate 'Submit'}}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </div>
</div>
<footer>{{{footer}}}</footer>
