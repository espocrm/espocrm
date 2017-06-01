<h4>{{translate 'duplicate' category="messages"}}</h4>

<div class="list-container" style="margin-top: 20px;">
    <div class="list-expanded">
        <ul class="list-group">
        {{#each duplicates}}
            <li data-id="{{id}}" class="list-group-item list-row">
                <div class="pull-right right cell" data-name="buttons">
                    <span class="badge">{{entityType}}</span>
                </div>
                <div class="expanded-row">
                    <span class="cell" data-name="name"><a href="#{{entityType}}/view/{{id}}" target="_BLANK">{{name}}</a>
                    </span>
                    <span class="cell" data-name="accountName">{{accountName}}</span>
                    <span class="cell" data-name="email">{{emailAddress}}</span>
                </div>
            </li>
        {{/each}}
        </ul>
    </div>
</div>
