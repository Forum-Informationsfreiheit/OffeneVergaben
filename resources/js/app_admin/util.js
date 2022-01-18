(function(ns) {

    console.log('util loaded!!!!!!!!!');

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

        // LOCAL STORAGE FUNCTIONS -------------------------------------------------------------------------------------
        isLocalStorageAvailable: function() {
            var storage;
            try {
                storage = window['localStorage'];
                var x = '__storage_test__';
                storage.setItem(x, x);
                storage.removeItem(x);
                return true;
            }
            catch(e) {
                return e instanceof DOMException && (
                        // everything except Firefox
                    e.code === 22 ||
                        // Firefox
                    e.code === 1014 ||
                        // test name field too, because code might not be present
                        // everything except Firefox
                    e.name === 'QuotaExceededError' ||
                        // Firefox
                    e.name === 'NS_ERROR_DOM_QUOTA_REACHED') &&
                        // acknowledge QuotaExceededError only if there's something already stored
                    (storage && storage.length !== 0);
            }
        },
        removeStorage: function(name) {
            try {
                localStorage.removeItem(name);
                localStorage.removeItem(name + '_expiresIn');
            } catch(e) {
                console.log('removeStorage: Error removing key ['+ key + '] from localStorage: ' + JSON.stringify(e) );
                return false;
            }
            return true;
        },
        getStorage: function(key) {

            var now = Date.now();  //epoch time, lets deal only with integer
            // set expiration for storage
            var expiresIn = localStorage.getItem(key+'_expiresIn');
            if (expiresIn===undefined || expiresIn===null) { expiresIn = 0; }

            if (expiresIn < now) {// Expired
                try {
                    localStorage.removeItem(key);
                    localStorage.removeItem(key + '_expiresIn');
                } catch(e) {
                    console.log('removeStorage: Error removing key ['+ key + '] from localStorage: ' + JSON.stringify(e) );
                    return false;
                }
                return null;
            } else {
                try {
                    var value = localStorage.getItem(key);
                    return value;
                } catch(e) {
                    console.log('getStorage: Error reading key ['+ key + '] from localStorage: ' + JSON.stringify(e) );
                    return null;
                }
            }
        },
        /*  setStorage: writes a key into localStorage setting a expire time
         params:
         key <string>     : localStorage key
         value <string>   : localStorage value
         expires <number> : number of seconds from now to expire the key
         returns:
         <boolean> : telling if operation succeeded
         */
        setStorage: function(key, value, expires) {

            if (expires===undefined || expires===null) {
                expires = (24*60*60);  // default: seconds for 1 day
            } else {
                expires = Math.abs(expires); //make sure it's positive
            }

            var now = Date.now();  //millisecs since epoch time, lets deal only with integer
            var schedule = now + expires*1000;
            try {
                localStorage.setItem(key, value);
                localStorage.setItem(key + '_expiresIn', schedule);
            } catch(e) {
                console.log('setStorage: Error setting key ['+ key + '] in localStorage: ' + JSON.stringify(e) );
                return false;
            }
            return true;
        },
    }

})(__ives);