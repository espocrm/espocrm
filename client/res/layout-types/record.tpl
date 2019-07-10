<% _.each(layout, function (panel, columnNumber) { %>
    <div class="panel panel-<%= panel.style %><% if (panel.name) { %>{{#if hiddenPanels.<%= panel.name %>}} hidden{{/if}}<% } %>"<% if (panel.name) print(' data-name="'+panel.name+'"') %>
    <% print(' data-mode="'+panel.mode+'"') %>>
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
        <div class="panel-body panel-body-form">
        <% _.each(panel.rows, function (row, rowNumber) { %>
            <div class="row <% if (panel.mode === 'column') print('col-sm-'+parseInt(12 / panel.rows.length)) %>">
            <% _.each(row, function (cell, cellNumber) { %>

                <%
                    var spanClassBase = panel.mode === 'column' ? 'col-sm-12' : 'col-sm-'+parseInt(12 / row.length);
                %>
                <% if (cell != false) { %>
                    <div class="cell <%= spanClassBase %> form-group<% if (cell.field) { %>{{#if hiddenFields.<%= cell.field %>}} hidden-cell{{/if}}<% } %>" data-name="<%= cell.field %>">
                        <% if (!cell.noLabel) { %><label class="control-label<% if (cell.field) { %>{{#if hiddenFields.<%= cell.field %>}} hidden{{/if}}<% } %>" data-name="<%= cell.field %>"><span class="label-text"><%
                            if ('customLabel' in cell) {
                                print (cell.customLabel);
                            } else {
                                print ("{{translate \""+cell.field+"\" scope=\""+model.name+"\" category='fields'}}");
                            }
                        %></span></label><% } %>
                        <div class="field<% if (cell.field) { %>{{#if hiddenFields.<%= cell.field %>}} hidden{{/if}}<% } %>" data-name="<%= cell.field %>"><%
                            if ('customCode' in cell) {
                                print (cell.customCode);
                            } else {
                                print ("{{{this."+cell.name+"}}}");
                            }
                        %></div>
                    </div>
                <% } else { %>
                    <div class="<%= spanClassBase %>"></div>
                <% } %>
            <% }); %>
            </div>
        <% }); %>
        </div>
    </div>
<% }); %>
