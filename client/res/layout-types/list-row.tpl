
<% _.each(layout, function (defs, key) { %>
    <%
        var width = null;
        if (defs.options && defs.options.defs && 'width' in defs.options.defs) {
            width = (defs.options.defs.width + '%') || null;
        }
        if (defs.options && defs.options.defs && 'widthPx' in defs.options.defs) {
            width = defs.options.defs.widthPx || null;
        }
        var align = false;
        if (defs.options && defs.options.defs) {
            align = defs.options.defs.align || false;
        }
    %>
    <td class="cell" data-name="<%= defs.columnName %>" <% if (width) print(' width="'+width+'"'); if (align) print(' align="'+align+'"'); %>>
    <%
            var tag = 'tag' in defs ? defs.tag : false;
            if (tag) {
                print( '<' + tag);
                if ('id' in defs) {
                    print(' id="'+defs.id+'"');
                }
                if ('class' in defs) {
                    print(' class="'+defs.class+'"');
                };
                print('>');
            }
        %>{{{this.<%= defs.name %>}}}<%
            if (tag) {
                print( '</' + tag + '>');
            }
    %>
    </td>
<% }); %>
