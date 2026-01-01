<% _.each(layout, function (row, rowNumber) { %>
    <div class="col-sm-6">
    <% _.each(row, function (defs, key) { %>
        <%
            var tag = 'tag' in defs ? defs.tag : 'div';
            print( '<' + tag);
            if ('id' in defs) {
                print(' id="'+defs.id+'"');
            }
            print(' class="');
            if ('class' in defs) {
                print(defs.class);
            };
            print('"');
            print('>');
        %>
            {{{this.<%= defs.name %>}}}
            <%= '</' + tag + '>' %>
    <% }); %>
    </div>
<% }); %>
