
<div class="row">
    <div class="{{#if hasSide}}col-md-10 col-sm-10 col-xs-12{{else}}col-md-12{{/if}}">
        <div id="{{containerId}}">{{value}}</div>
    </div>
    {{#if hasSide}}
    <div class="col-md-2 col-sm-2 col-xs-12">
        <div class="button-container">
            <div class="btn-group pull-right">
                {{#if hasCheckSyntax}}
                <button
                    type="button"
                    class="btn btn-default btn-sm btn-icon"
                    data-action="checkSyntax"
                    title="{{translate 'Check Syntax' scope='Formula'}}"
                ><span class="far fa-circle"></span></button>
                {{/if}}
                {{#if hasInsert}}
                <button
                    type="button"
                    class="btn btn-default btn-sm dropdown-toggle btn-icon"
                    data-toggle="dropdown"
                ><span class="fas fa-plus"></span></button>
                <ul class="dropdown-menu pull-right">
                    {{#if targetEntityType}}
                    <li><a role="button" tabindex="0" data-action="addAttribute">{{translate 'Attribute'}}</a></li>
                    {{/if}}
                    <li><a role="button" tabindex="0" data-action="addFunction">{{translate 'Function'}}</a></li>
                </ul>
                {{/if}}
            </div>
        </div>
    </div>
    {{/if}}
</div>
