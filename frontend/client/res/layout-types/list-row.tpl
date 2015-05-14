<tr data-id="{{model.id}}" class="list-row">
<% _.each(layout, function (defs, key) { %>
    <%
        var width = '';
        if (defs.options && defs.options.defs && defs.options.defs.params) {
            width = defs.options.defs.params.width || '';
        }
        var align = false;
        if (defs.options && defs.options.defs && defs.options.defs.params) {
            align = defs.options.defs.params.align || false;
        }
    %>
    <td class="cell cell-<%= defs.name %>" <% if (width) print(' width="'+width+'"'); if (align) print(' align="'+align+'"'); %>>
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
        %>{{{<%= defs.name %>}}}<%
            if (tag) {
                print( '</' + tag + '>');
            }
    %>
    </td>
<% }); %>
</tr>
