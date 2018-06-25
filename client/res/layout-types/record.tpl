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
        <div class="panel-body panel-body-form">
        <% _.each(panel.rows, function (row, rowNumber) { %>
            <div class="row">
            <% _.each(row, function (cell, cellNumber) { %>

                <%
                    var spanClassBase;
                    if (columnCount === 1) {
                        spanClassBase = 'col-sm-12';
                    } else if (columnCount === 2) {
                        spanClassBase = 'col-sm-6';
                    } else if (columnCount === 3) {
                        spanClassBase = 'col-sm-4';
                    } else if (columnCount === 4) {
                        spanClassBase = 'col-md-3 col-sm-6';
                    } else {
                        spanClass = 'col-sm-12';
                    }
                %>
                <% if (cell != false) { %>
                    <%
                        var spanClass;
                        if (columnCount === 1 || cell.fullWidth) {
                            spanClass = 'col-sm-12';
                        } else if (columnCount === 2) {
                            if (cell.span === 2) {
                                spanClass = 'col-sm-12';
                            } else {
                                spanClass = 'col-sm-6';
                            }
                        } else if (columnCount === 3) {
                            if (cell.span === 2) {
                                spanClass = 'col-sm-8';
                            } else if (cell.span === 3) {
                                spanClass = 'col-sm-12';
                            } else {
                                spanClass = 'col-sm-4';
                            }
                        } else if (columnCount === 4) {
                            if (cell.span === 2) {
                                spanClass = 'col-sm-6';
                            } else if (cell.span === 3) {
                                spanClass = 'col-sm-9';
                            } else if (cell.span === 4) {
                                spanClass = 'col-sm-12';
                            } else {
                                spanClass = 'col-md-3 col-sm-6';
                            }
                        } else {
                            spanClass = 'col-sm-12';
                        }
                    %>
                    <div class="cell <%= spanClass %> form-group<% if (cell.field) { %>{{#if hiddenFields.<%= cell.field %>}} hidden-cell{{/if}}<% } %>" data-name="<%= cell.field %>">
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
