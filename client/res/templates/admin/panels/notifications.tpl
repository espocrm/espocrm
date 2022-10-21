{{#if notificationList}}
    <div class="panel panel-danger">
        <div class="panel-body">
            <div class="list-container">
                <div class="list-group list list-expanded">
                {{#each notificationList}}
                    <div data-id="{{id}}" class="list-group-item notification-item">
                        <div class="text-danger complex-text">{{complexText message}}</div>
                    </div>
                {{/each}}
                </div>
            </div>
        </div>
    </div>
{{/if}}
