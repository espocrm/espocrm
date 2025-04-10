<div class="formula-edit-container clearfix">
    <div>
        <div id="{{containerId}}">{{value}}</div>
    </div>
    {{#if hasSide}}
    <div>
        <div class="button-container">
            <div class="btn-group pull-right">
                {{#if hasCheckSyntax}}
                <button
                    type="button"
                    class="btn btn-text btn-sm btn-icon"
                    data-action="checkSyntax"
                    title="{{translate 'Check Syntax' scope='Formula'}}"
                ><span class="far fa-circle"></span></button>
                {{/if}}
                {{#if hasInsert}}
                <button
                    type="button"
                    class="btn btn-text btn-sm dropdown-toggle btn-icon"
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
