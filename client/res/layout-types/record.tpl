<% var hasHiddenPanel = false; %>

<% _.each(layout, function (panel, columnNumber) { %>
    <% hasHiddenPanel = panel.hidden || hasHiddenPanel; %>
    <div
        class="panel panel-<%= panel.style %><%= panel.label ? ' headered' : '' %><%= panel.tabNumber ? ' tab-hidden' : '' %><% if (panel.name) { %>{{#if hiddenPanels.<%= panel.name %>}} hidden{{/if}}<% } %>"
        <% if (panel.name) print('data-name="'+panel.name+'"') %>
        <% if (panel.style) print('data-style="'+panel.style+'"') %>
        data-tab="<%= panel.tabNumber %>"
    >
        <% if (panel.label) { %>
        <div class="panel-heading"><h4 class="panel-title"><%= panel.label %></h4></div>
        <% } %>
        <div class="panel-body panel-body-form">

        <% if (panel.noteText) { %>
        <div class="alert alert-<%= panel.noteStyle %>"><%= panel.noteText %></div>
        <% } %>

        <% var rows = panel.rows || [] %>
        <% var columns = panel.columns || [] %>

        <% _.each(rows, function (row, rowNumber) { %>
            <div class="row">
            <% var columnCount = row.length; %>
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
                    <div
                        class="cell <%= spanClass %> form-group<% if (cell.field) { %>{{#if hiddenFields.<%= cell.field %>}} hidden-cell{{/if}}<% } %>"
                        data-name="<%= cell.field %>"
                        tabindex="-1"
                    >
                        <% if (!cell.noLabel) { %><label class="control-label<% if (cell.field) { %>{{#if hiddenFields.<%= cell.field %>}} hidden{{/if}}<% } %>" data-name="<%= cell.field %>"><span class="label-text"><%
                            if ('customLabel' in cell) {
                                print (cell.customLabel);
                            } else {
                                var label = cell.label || cell.field;
                                print ("{{translate \""+label+"\" scope=\""+model.name+"\" category='fields'}}");
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

        <%
            var columnCount = columns.length;
            if (columnCount) {
                %>
            <div class="row">
                <%
            }
        %>
        <% _.each(columns, function (column, columnNumber) { %>
            <%
                var spanClass;
                if (!columnCount) return;

                if (columnCount === 1 || column.fullWidth) {
                    spanClass = 'col-sm-12';
                } else if (columnCount === 2) {
                    if (column.span === 2) {
                        spanClass = 'col-sm-12';
                    } else {
                        spanClass = 'col-sm-6';
                    }
                } else if (columnCount === 3) {
                    if (column.span === 2) {
                        spanClass = 'col-sm-8';
                    } else if (column.span === 3) {
                        spanClass = 'col-sm-12';
                    } else {
                        spanClass = 'col-sm-4';
                    }
                } else if (columnCount === 4) {
                    if (column.span === 2) {
                        spanClass = 'col-sm-6';
                    } else if (column.span === 3) {
                        spanClass = 'col-sm-9';
                    } else if (column.span === 4) {
                        spanClass = 'col-sm-12';
                    } else {
                        spanClass = 'col-md-3 col-sm-6';
                    }
                } else {
                    spanClass = 'col-sm-12';
                }
            %>
            <div class="column <%= spanClass %>">
                <% _.each(column, function (cell, cellNumber) { %>
                    <div class="cell form-group<% if (cell.field) { %>{{#if hiddenFields.<%= cell.field %>}} hidden-cell{{/if}}<% } %>" data-name="<%= cell.field %>">
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
                <% }); %>
            </div>
        <% }); %>
        <%
            if (columnCount) {
                %>
            </div>
                <%
            }
        %>
        </div>
    </div>
<% }); %>

<%
if (hasHiddenPanel) {
%>
<div class="panel panel-default panels-show-more-delimiter" data-name="showMoreDelimiter" data-tab="0">
    <a role="button" tabindex="0" data-action="showMoreDetailPanels" title="{{translate 'Show more'}}">
        <span class="fas fa-ellipsis-h fa-lg"></span>
    </a>
</div>
<%
}
%>
