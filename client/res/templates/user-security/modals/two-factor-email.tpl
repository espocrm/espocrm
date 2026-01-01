<div class="panel no-side-margin">
    <div class="panel-body">
        <p class="p-info">
            {{translate 'choose2FaEmailAddress' category='messages' scope='User'}}
        </p>
        <p class="p-button">
            <button class="btn btn-default" data-action="sendCode">{{translate 'Send Code' scope='User'}}</button>
        </p>
        <p class="p-info-after hidden">
            {{translate 'enterCodeSentInEmail' category='messages' scope='User'}}
        </p>
    </div>
</div>

<div class="record no-side-margin">{{{record}}}</div>
