<div class="button-container">
    <div class="btn-group">
    {{#each buttonList}}
        {{button name label=label scope='Admin' style=style className='btn-xs-wide'}}
    {{/each}}
    </div>
</div>

<div id="layout" class="row">
    <div class="col-md-8">
        <div class="well enabled-well" tabindex="-1">
            <header>{{translate 'Layout' scope='LayoutManager'}}</header>
            <ul class="panels">
                {{#each panelDataList}}
                    <li data-number="{{number}}" class="panel-layout" data-tab-break="{{tabBreak}}">
                        {{{var viewKey ../this}}}
                    </li>
                {{/each}}
            </ul>

            <div><a role="button" tabindex="0" data-action="addPanel">{{translate 'Add Panel' scope='Admin'}}</a></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="well">
            <header>{{translate 'Available Fields' scope='Admin'}}</header>
            <ul class="disabled cells clearfix">
                {{#each disabledFields}}
                <li class="cell" data-name="{{./this}}" title="{{translate this scope=../scope category='fields'}}">
                    <div class="left" style="width: calc(100% - 14px);">
                        {{translate this scope=../scope category='fields'}}
                    </div>
                    <div class="right" style="width: 14px;">
                        <a
                            role="button"
                            tabindex="0"
                            data-action="removeField"
                            class="remove-field"
                        ><i class="fas fa-times"></i></a>
                    </div>
                </li>
                {{/each}}
            </ul>
        </div>
    </div>
</div>

<div id="layout-row-tpl" style="display: none;">
    <li data-cell-count="{{columnCount}}">
        <div class="row-actions clear-fix">
            <a role="button" tabindex="0" data-action="removeRow" class="remove-row"><i class="fas fa-times"></i></a>
            <a role="button" tabindex="0" data-action="plusCell" class="add-cell"><i class="fas fa-plus"></i></a>
        </div>
        <ul class="cells" data-cell-count="{{columnCount}}">
            <% for (var i = 0; i < {{columnCount}}; i++) { %>
                <li class="empty cell">
                <div class="right" style="width: 14px;">
                    <a
                        role="button"
                        tabindex="0"
                        data-action="minusCell"
                        class="remove-field"
                    ><i class="fas fa-minus"></i></a>
                </div>
                </li>
            <% } %>
        </ul>
    </li>
</div>

<div id="empty-cell-tpl" style="display: none;">
    <li class="empty cell disabled">
        <div class="right" style="width: 14px;">
            <a
                role="button"
                tabindex="0"
                data-action="minusCell"
                class="remove-field"
            ><i class="fas fa-minus"></i></a>
        </div>
    </li>
</div>
