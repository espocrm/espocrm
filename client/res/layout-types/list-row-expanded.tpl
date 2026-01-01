<% if (layout.right) { %>
<div class="pull-right right cell" data-name="buttons">
    {{{<%= layout.right.name %>}}}
</div>
<% } %>
<% _.each(layout.rows, function (row, key) { %><div class="expanded-row"><% _.each(row, function (defs, key) { %><span class="cell<%= defs.align ? ' pull-right' : '' %><%= defs.small ? ' small' : '' %><%= defs.soft ? ' text-soft' : '' %>" data-name="<%= defs.field %>"><%
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
        %></span><% }); %></div>
<% }); %>
