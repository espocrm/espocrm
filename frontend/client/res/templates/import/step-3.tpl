    <div class="panel panel-default">
        <div class="panel-heading"><h5 class="panel-title">{{translate 'Result' scope='Import'}}</h5></div>
        <div class="panel-body">
            <h4>{{translate scope category='scopeNamesPlural'}}</h4>
            <div id="result-container">
                {{translate 'Created' scope='Import'}}: {{result.countCreated}}
                <br>
                {{translate 'Updated' scope='Import'}}: {{result.countUpdated}}    
            </div>
            
            <div style="margin-top: 10px;">
                <a class="btn btn-link" href="#Import">{{translate 'Return to Import' scope='Import'}}</a>
                <a class="btn btn-link" href="#{{scope}}">{{translate 'Show records' scope='Import'}}</a>
                {{#if result.countCreated}}
                <button class="btn btn-danger" data-action="revert">{{translate 'Revert' scope='Import'}}</button>
                {{/if}}
            </div>
        </div>
    </div>

