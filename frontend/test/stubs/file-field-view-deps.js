// Minimal stubs for views/fields/file dependencies.
// Must be loaded (as a srcFile) BEFORE client/lib/transpiled/src/views/fields/file.js.
// Without these, the AMD loader tries to dynamically fetch the real view-stack
// files which are not available at the Jasmine server's URL structure.

(function () {
    class LinkFieldStub {
        data() {
            return {};
        }
    }

    define('views/fields/link', ['exports'], function (_exports) {
        _exports.__esModule = true;
        _exports.default = LinkFieldStub;
    });

    define('helpers/file-upload', ['exports'], function (_exports) {
        _exports.__esModule = true;
        _exports.default = class {};
    });

    define('helpers/misc/attachment-insert-from-source', ['exports'], function (_exports) {
        _exports.__esModule = true;
        _exports.default = class {};
    });
}());
