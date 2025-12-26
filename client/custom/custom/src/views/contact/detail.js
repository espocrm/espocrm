define('custom:views/contact/detail', ['views/record/detail'], function (Dep) {
    return Dep.extend({
        
        setup: function () {
            // 1. Run standard setup FIRST to populate lists from metadata
            Dep.prototype.setup.call(this);

            // 2. Clear existing side panels to remove default ones
            this.sidePanelList = [];

            // 3. Inject Side Panel (Banking Tiles)
            this.sidePanelList.push({
                name: 'customBankingSide',
                label: 'Banking Products',
                view: 'custom:views/contact/side-banking-tiles',
                style: 'default'
            });

            // 4. Inject Bottom Panel (Banking Grid)
            this.bottomPanelList.unshift({
                name: 'bankingGridPanel',
                label: 'Banking Overview',
                view: 'custom:views/contact/banking-grid'
            });
        }
    });
});