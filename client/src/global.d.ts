export {};

import Ui from 'ui';

/**
 * A callback with resolved dependencies passed as parameters.
 * Should return a value to define a module.
 *
 * @param {...any} arguments Resolved dependencies.
 */
type RequireCallback = (any) => void;

type RequireErrorCallback = () => void;

declare global {
    namespace Espo {
        // noinspection ES6ConvertVarToLetConst
        var loader: {
            require: (id: string, callback: RequireCallback, errorCallback?: RequireErrorCallback) => void;
            requirePromise: (id: string) => Promise<unknown>;
        }

        // noinspection ES6ConvertVarToLetConst
        var Ui: Ui
    }
}
