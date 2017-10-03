{{#if notifications}}
    <div class="panel panel-default panel-default" data-name="default">
        <div class="panel-body" data-name="default">
            {{#each notifications}}
                <div data-id="{{this.id}}">
                    <p class="text-danger">
                        {{{this.data.message}}}
                    </p>
                </div>
            {{/each}}
        </div>
    </div>
{{/if}}