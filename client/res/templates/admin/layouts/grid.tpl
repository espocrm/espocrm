<div class="button-container">
{{#each buttonList}}
    {{button name label=label scope='Admin' style=style}}
{{/each}}
</div>

<style>
    #layout ul {
        list-style-type: none;
        padding: 0;
        margin: 0;
    }
    #layout ul > li {
        background-color: #FFF;
    }
    #layout ul.panels > li {
        padding: 5px;
        margin: 5px;
        {{#ifEqual columnCount 1}}

        {{else}}

        {{/ifEqual}}
        min-height: 80px;
        border: 1px solid #CCC;
        list-style: none;
    }
    #layout ul.rows {
        min-height: 80px;
    }
    #layout ul.rows > li  {
        list-style: none;
        border: 1px solid #CCC;
        margin: 5px;
        padding: 5px;
        height: 72px;
    }

    #layout ul.cells  {
        min-height: 30px;
        margin-top: 12px;
    }
    #layout ul.panels ul.cells > li {
        {{#ifEqual columnCount 1}}
        width: 92%;
        {{else}}
        width: 46%;
        {{/ifEqual}}
        float: left;
    }
    #layout ul.panels ul.cells > li[data-full-width="true"] {
        width: 94%;
    }
    #layout ul.cells > li {
        list-style: none;
        border: 1px solid #CCC;
        margin: 5px;
        padding: 5px;
        height: 32px;
    }
    #layout ul.rows > li > div {
        width: auto;
    }
    #layout ul.cells > li a {
        float: right;
        margin-left: 5px;
    }
    #layout ul.disabled {
        min-height: 200px;
        width: 100%;
    }
    #layout ul.disabled > li a {
        display: none;
    }
    #layout header {
        font-weight: bold;
    }
    #layout ul.panels > li label {
        display: inline;
    }
    #layout ul.panels > li header a {
        font-weight: normal;
    }
    #layout ul.panels > li > div {
        width: auto;
        text-align: left;
        margin-left: 5px;
    }
    ul.cells li.cell a.remove-field {
        display: none;
    }
    ul.cells li.cell:hover a.remove-field {
        display: block;
    }
    ul.panels > li a.remove-panel {
        display: none;
    }
    ul.panels > li:hover a.remove-panel {
        display: block;
    }
    ul.rows > li a.remove-row {
        display: none;
    }
    ul.rows > li:hover a.remove-row {
        display: block;
    }
    ul.panels > li a.edit-panel-label {
        display: none;
    }
    ul.panels > li:hover a.edit-panel-label {
        display: inline-block;
    }
</style>

<div id="layout" class="row">
    <div class="col-md-8">
        <div class="well">
            <header>{{translate 'Layout' scope='LayoutManager'}}</header>
            <a href="javascript:;" data-action="addPanel">{{translate 'Add Panel' scope='Admin'}}</a>
            <ul class="panels">
            {{#each panelDataList}}
            <li data-number="{{number}}" class="panel-layout">
            {{{var viewKey ../this}}}
            </li>
            {{/each}}
            </ul>
        </div>
    </div>
    <div class="col-md-4">
        <div class="well">
            <header>{{translate 'Available Fields' scope='Admin'}}</header>
            <ul class="disabled cells clearfix">
                {{#each disabledFields}}
                    <li class="cell" data-name="{{./this}}">{{translate this scope=../scope category='fields'}}
                        &nbsp;<a href="javascript:" data-action="removeField" class="remove-field"><i class="glyphicon glyphicon-remove"></i></a>
                    </li>
                {{/each}}
            </ul>
        </div>
    </div>
</div>

<div id="layout-panel-tpl" style="display: none;">
    <li>
        <header data-style="<%= style %>" data-name="<%= name %>">
            <label data-is-custom="<%= isCustomLabel ? 'true' : '' %>"><%= label %></label>&nbsp;
            <a href="javascript:" data-action="edit-panel-label" class="edit-panel-label"><i class="glyphicon glyphicon-pencil"></i></a>
            <a href="javascript:" style="float: right;" data-action="removePanel" class="remove-panel"><i class="glyphicon glyphicon-remove"></i></a>
        </header>
        <ul class="rows">
        <%
            for (var i in rows) {
                var row = rows[i];
        %>
            <li>
                <div><a href="javascript:" data-action="removeRow" class="remove-row pull-right"><i class="glyphicon glyphicon-remove"></i></a></div>
                <ul class="cells">
                <%
                    for (var j in row) {
                        if (j == {{columnCount}}) {
                            break;
                        }
                        var cell = row[j];
                        if (cell !== false) {

                %>
                        <li class="cell"
                            data-name="<%= cell.name %>"
                            data-full-width="<%= cell.fullWidth || '' %>"
                        ><%= cell.label %>
                            <a href="javascript:" data-action="removeField" class="remove-field"><i class="glyphicon glyphicon-remove"></i></a>
                        </li>
                <%
                        } else {
                %>
                        <li class="empty cell">
                            <a href="javascript:" data-action="minusCell" class="remove-field"><i class="glyphicon glyphicon-minus"></i></a>
                        </li>
                <%
                        }
                    }
                %>
                </ul>
            </li>
        <%
            }
        %>
        </ul>
        <div>
            <a href="javascript:" data-action="addRow"><i class="glyphicon glyphicon-plus"></i></a>
        </div>
    </li>
</div>

<div id="layout-row-tpl" style="display: none;">
    <li>
        <div><a href="javascript:" data-action="removeRow" class="remove-row pull-right"><i class="glyphicon glyphicon-remove"></i></a></div>
        <ul class="cells">
            <% for (var i = 0; i < {{columnCount}}; i++) { %>
                <li class="empty cell"><a href="javascript:" data-action="minusCell" class="remove-field"><i class="glyphicon glyphicon-minus"></i></a></li>
            <% } %>
        </ul>
    </li>
</div>
