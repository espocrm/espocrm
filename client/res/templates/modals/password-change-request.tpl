<div class="panel panel-default no-side-margin">
<div class="panel-body">

<div class="cell form-group" data-name="userName">
    <label class="control-label" data-name="userName">{{translate 'Username' scope='User'}}</label>
    <div class="field" data-name="userName">
        <input
            type="text"
            name="username"
            class="form-control"
            autocomplete="username"
            autocorrect="off"
            autocapitalize="off"
            spellcheck="off"
        >
    </div>
</div>
<div class="cell form-group" data-name="emailAddress">
    <label class="control-label" data-name="emailAddress">{{translate 'Email Address' scope='User'}}</label>
    <div class="field" data-name="emailAddress">
        <input
            type="text"
            name="emailAddress"
            class="form-control"
            autocomplete="espo-change-password-emailAddress"
            autocorrect="off"
            autocapitalize="off"
            spellcheck="off"
        >
    </div>
</div>

<div class="msg-box hidden"></div>

</div>
</div>
