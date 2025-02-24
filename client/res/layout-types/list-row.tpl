<% _.each(layout, function (defs, key) { %>
    <%
        let width = null;

        if (defs.options && defs.options.defs && defs.options.defs.width !== undefined) {
            width = (defs.options.defs.width + '%') || null;
        }

        if (defs.options && defs.options.defs && defs.options.defs.widthPx !== undefined) {
            width = defs.options.defs.widthPx || null;
        }

        let align = false;

        if (defs.options && defs.options.defs) {
            align = defs.options.defs.align || false;
        }
    %>
    <td
        class="cell"
        data-name="<%= defs.columnName %>"
        <% if (width || align) { %>
        style="<% if (width) print('width: ' + width); %>;<% if (align) print(' text-align: ' + align);%>"
        <% } %>
    >
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
