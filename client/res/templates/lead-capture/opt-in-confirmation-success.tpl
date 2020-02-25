<div class="container content">
    <div class="col-md-6 col-md-offset-2 col-sm-8 col-sm-offset-1">
        <div class="panel panel-success">
            <div class="panel-body">
                {{#if messageField}}
                <div class="field" data-name="message">
                    {{{messageField}}}
                </div>
                {{else}}
                <p>
                    {{defaultMessage}}
                </p>
                {{/if}}
            </div>
        </div>
    </div>
</div>
