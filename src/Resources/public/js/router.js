window.Splot = window.Splot || {};
window.Splot.Router = (function(undefined) {
    'use strict';

    return {
        generate : function(pattern, parameters) {
            parameters = parameters || {};
            return pattern.replace(/\{([\w\d]+)\}/gi, function(match, key) {
                return parameters[key] !== undefined ? parameters[key] : '';
            });
        }
    };
})();