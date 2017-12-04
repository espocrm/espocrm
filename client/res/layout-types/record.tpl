<% _.each(layout, function (panel, columnNumber) { %>
    <div class="panel panel-<%= panel.style %><% if (panel.name) { %>{{#if hiddenPanels.<%= panel.name %>}} hidden{{/if}}<% } %>"<% if (panel.name) print(' data-name="'+panel.name+'"') %>>
        <%
            var panelLabelString = null;
            if ('customLabel' in panel) {
                if (panel.customLabel) {
                    panelLabelString = panel.customLabel;
                }
            } else {
                if (panel.label) {
                    panelLabelString = "{{translate \"" + panel.label + "\" scope=\""+model.name+"\"}}";
                }
            }
        %>
        <% if (panelLabelString) { %>
        <div class="panel-heading"><h4 class="panel-title"><%= panelLabelString %></h4></div>
        <% } %>
        <div class="panel-body">
        <% _.each(panel.rows, function (row, rowNumber) { %>
            <div class="row">
            <% _.each(row, function (cell, cellNumber) { %>
                <% if (cell != false) { %>
                    <div class="cell<% if (columnCount == 1 || cell.fullWidth) { %> col-sm-12<% } else {%> col-sm-6<% } %> form-group<% if (cell.name) { %>{{#if hiddenFields.<%= cell.name %>}} hidden-cell{{/if}}<% } %>" data-name="<%= cell.name %>">
                        <% if (!cell.noLabel) { %><label class="control-label<% if (cell.name) { %>{{#if hiddenFields.<%= cell.name %>}} hidden{{/if}}<% } %>" data-name="<%= cell.name %>"><span class="label-text"><%
                            if ('customLabel' in cell) {
                                print (cell.customLabel);
                            } else {
                                print ("{{translate \""+cell.name+"\" scope=\""+model.name+"\" category='fields'}}");
                            }
                        %></span></label><% } %>
                        <div class="field<% if (cell.name) { %>{{#if hiddenFields.<%= cell.name %>}} hidden{{/if}}<% } %>" data-name="<%= cell.name %>"><%
                            if ('customCode' in cell) {
                                print (cell.customCode);
                            } else {
                                print ("{{{"+cell.name+"}}}");
                            }
                        %></div>
                    </div>
                <% } else { %>
                    <div class="col-sm-6"></div>
                <% } %>
            <% }); %>
            </div>
        <% }); %>
        </div>
    </div>
<% }); %>
