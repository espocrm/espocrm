define('admin-panel:views/company/edit', ['views/record/edit'], function (Dep) {

    return Dep.extend({
        __name__: 'AdminPanel.Company.Edit',

        template: 'admin-panel:company/edit',
    });
});
