window.Splot = window.Splot || {};
window.Splot.DataBridge = (function(undefined) {
    "use strict";

    var _data = {};

    return {
        setData : function(data) {
            _data = data;
        },

        get : function(key) {
            return _data[key];
        }
    };
})();