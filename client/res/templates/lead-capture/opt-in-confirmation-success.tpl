<div class="container content">
    <div class="block-center-md">
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
