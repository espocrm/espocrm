<% _.each(layout, function (defs, key) {
        var tag = 'tag' in defs ? defs.tag : 'div';
        print( '<' + tag);
        if ('id' in defs) {
            print(' id="'+defs.id+'"');
        }
        if ('class' in defs) {
            print(' class="'+defs.class+'"');
        }
        print('>');
    %>
        {{{this.<%= defs.name %>}}}
        <%= '</' + tag + '>' %>
<% }); %>
