define('admin-panel:views/company/detail', ['views/record/detail'], function (Dep) {

    return Dep.extend({
        __name__: 'AdminPanel.Company.Detail',

        template: 'admin-panel:company/detail',
    });
});
