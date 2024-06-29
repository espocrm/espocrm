<div class="container content">
    <div class="block-center-md">
        <div class="panel panel-default">
            <div class="panel-body">
                <p>
                    {{#if isSubscribed}}
                        <a
                            class="btn btn-primary{{#if inProcess}} disabled{{/if}}"
                            data-action="unsubscribe"
                        >{{translate 'Unsubscribe' scope='Campaign'}}</a>
                    {{else}}
                        <a
                            class="btn btn-default{{#if inProcess}} disabled{{/if}}"
                            data-action="subscribe"
                        >{{translate 'Subscribe again' scope='Campaign'}}</a>
                    {{/if}}
                </p>
            </div>
        </div>
    </div>
</div>
