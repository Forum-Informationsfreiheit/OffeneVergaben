(function(ns) {

    ns.util = {

        // DATATYPE FUNCTIONS ------------------------------------------------------------------------------------------

        isArray: function (test) {
            return Object.prototype.toString.call(test) === '[object Array]';
        },


        // OTHER HELPERS -----------------------------------------------------------------------------------------------

        isValidEmail: function (email) {
            var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
            return regex.test(email);
        },
        brToNl: function(string) {
            var regex = /<br\s*[\/]?>/gi;
            return string.replace(regex, "\n");
        },

        uiNumberToMoney: function(number, decimals, decimal_sep, thousands_sep, truncateFollowZeros) {
            var n = number / 100,
                c = isNaN(decimals) ? 2 : Math.abs(decimals), //if decimal is zero we must take it, it means user does not want to show any decimal
                d = decimal_sep || ',', //if no decimal separator is passed we use the dot as default decimal separator (we MUST use a decimal separator)

                t = (typeof thousands_sep === 'undefined') ? '.' : thousands_sep, //if you don't want to use a thousands separator you can pass empty string as thousands_sep value

                sign = (n < 0) ? '-' : '',

            //extracting the absolute value of the integer part of the number and converting to string
                i = parseInt(n = Math.abs(n).toFixed(c)) + '',

                j = ((j = i.length) > 3) ? j % 3 : 0;

            var money = sign + (j ? i.substr(0, j) + t : '') + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : '');

            return money;
        },

        buildMainViewUrl: function(defaultParams, changedParams) {
            var tempParams = {};

            for (var key in defaultParams) {
                var value = defaultParams[key];

                tempParams[key] = typeof changedParams[key] !== 'undefined' ? changedParams[key] : value;
            }

            var url = '' + tempParams.baseUrl;
            url += '/' + tempParams.ans;
            //url += '?' + 'hh=' + tempParams.hh;
            url += '?' + 'jahr=' + tempParams.year;

            url += (tempParams.direction == "einnahmen") ? "&einnahmen" : "";
            url += (tempParams.valueType == "prokopf") ? "&prokopf" : "";

            return url;
        },

        buildViewUrl: function(view, defaultParams, changedParams) {
            var tempParams = {};
            var url = "";

            for (var key in defaultParams) {
                var value = defaultParams[key];

                tempParams[key] = typeof changedParams[key] !== 'undefined' ? changedParams[key] : value;
            }

            if (view == 'cpv') {
                url = tempParams.baseUrl;

                var paramsArray = [];
                if (tempParams.root != null) {
                    paramsArray.push("node=" + tempParams.root.code);
                }
                if (tempParams.type == 'anzahl') {
                    paramsArray.push("anzahl");
                }

                return url + (paramsArray.length > 0 ? '?' + paramsArray.join('&') : '');
            }
        }
    }

})(__ives);