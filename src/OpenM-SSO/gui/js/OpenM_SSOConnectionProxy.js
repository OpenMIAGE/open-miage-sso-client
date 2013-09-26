if (typeof(OpenM_SSOConnectionProxy) === 'undefined') {
    var OpenM_SSOConnectionProxy = {
        url: "",
        session_mode: "",
        api_selected: "",
        alreadyHaveConnectionOK: false,
        alreadyHaveTryingReconnection: false,
        MODE_PARAMETER: "API_SESSION_MODE",
        API_SELECTION_PARAMETER: "API",
        MODE_API_SELECTION: "API_SELECTION",
        MODE_ALL_API: "ALL_API",
        MODE_WITHOUT_API: "WITHOUT_API",
        ACTION_PARAMETER: "ACTION",
        IS_CONNECTED_ACTION: "isConnected",
        RETURN_IS_CONNECTED_PARAMETER: "isConnected",
        LOGIN_ACTION: "login",
        RETURN_TO_PARAMETER: "proxy_return_to",
        DASH: "! !",
        connected: false,
        frame: undefined,
        waitingConnectionInterval: 1000,
        waitingConnectionTimeOut: 120,
        waitingReConnectionTimeOut: 30,
        waitingConnectionInProgress: false,
        waitingReConnectionInProgress: false,
        redirectionToLoginFormEnabled: false,
        login: function() {
            if (this.connected)
                return;
            var u = this.url + "?" + this.MODE_PARAMETER + "=" + this.session_mode + "&" + this.API_SELECTION_PARAMETER + "=" + this.api_selected + "&" + this.ACTION_PARAMETER + "=" + this.LOGIN_ACTION + "&" + this.RETURN_TO_PARAMETER + "=" + encodeURI(window.location.href.replace("#", this.DASH));
            window.location = u;
        },
        open: function() {
            if (this.redirectionToLoginFormEnabled)
                return this.login();
            if (this.connected)
                return;
            if (this.waitingConnectionInProgress && typeof(this.frame) !== 'undefined' && !this.frame.closed)
                return;
            var width = 450;
            var height = 450;
            var left = (screen.width - width) / 2;
            var top = (screen.height - height) / 2;
            this.frame = window.open(this.url + "?" + this.MODE_PARAMETER + "=" + this.session_mode + "&" + this.API_SELECTION_PARAMETER + "=" + this.api_selected, "popup", "toolbar=0, location=0, directories=0, status=0, resizable=0, copyhistory=0, height=" + height + ", width=" + width + ", top=" + top + ", left=" + left + "");
            if (this.waitingConnectionInProgress)
                return;
            var c = this;
            var t;
            c.waitingConnectionInProgress = true;
            var i = setInterval(function() {
                if (typeof(c.frame) !== 'undefined' && c.frame.closed) {
                    if (typeof(t) !== 'undefined')
                        clearTimeout(t);
                    c.waitingConnectionInProgress = false;
                    if (typeof(i) !== 'undefined')
                        clearInterval(i);
                }
                c.isConnected(function() {
                    if (c.connected) {
                        clearInterval(i);
                        if (typeof(t) !== 'undefined')
                            clearTimeout(t);
                        if (c.frame !== undefined && typeof(c.frame.close) === 'function')
                            c.frame.close();
                        c.waitingConnectionInProgress = false;
                        c.onReconnectionOK();
                    }
                });
            }, c.waitingConnectionInterval);
            t = setTimeout(function() {
                if (typeof(i) !== 'undefined')
                    clearInterval(i);
                if (c.frame !== undefined && typeof(c.frame.close) === 'function')
                    c.frame.close();
                c.waitingConnectionInProgress = false;
            }, c.waitingConnectionTimeOut * 1000);
        },
        reconnectframe: undefined,
        reconnectionCheckInterval: undefined,
        reconnectionTimeOut: undefined,
        reconnect: function(loginIfNotConnected) {
            if (!this.alreadyHaveConnectionOK && loginIfNotConnected !== true)
                return;
            if (!this.alreadyHaveConnectionOK && loginIfNotConnected === true && this.alreadyHaveTryingReconnection === true)
                return this.open();
            if (this.waitingReConnectionInProgress)
                return;
            this.connected = false;
            this.alreadyHaveTryingReconnection = true;
            clearInterval(this.reconnectionCheckInterval);
            clearTimeout(this.reconnectionTimeOut);
            if (this.reconnectframe !== undefined)
                this.reconnectframe.remove();
            this.reconnectframe = $(document.createElement("iframe"));
            this.reconnectframe.attr("src", this.url + "?" + this.MODE_PARAMETER + "=" + this.session_mode + "&" + this.API_SELECTION_PARAMETER + "=" + this.api_selected);
            this.reconnectframe.attr("position", "absolute");
            this.reconnectframe.attr("right", 0);
            this.reconnectframe.attr("bottom", 0);
            this.reconnectframe.attr("width", 1);
            this.reconnectframe.attr("height", 1);
            this.reconnectframe.attr("border", 0);
            this.reconnectframe.attr("background-color", "transparent");
            this.reconnectframe.attr("z-index", 0);
            $("html body").append(this.reconnectframe);
            var c = this;
            var t = c.reconnectionTimeOut;
            c.waitingReConnectionInProgress = true;
            var i = c.reconnectionCheckInterval;
            i = setInterval(function() {
                c.isConnected(function() {
                    if (c.connected) {
                        clearInterval(i);
                        if (typeof(t) !== 'undefined')
                            clearTimeout(t);
                        if (c.reconnectframe !== undefined)
                            c.reconnectframe.remove();
                        c.waitingReConnectionInProgress = false;
                        c.onReconnectionOK();
                    }
                });
            }, c.waitingConnectionInterval);
            t = setTimeout(function() {
                if (typeof(i) !== 'undefined')
                    clearInterval(i);
                if (c.reconnectframe !== undefined && typeof(c.reconnectframe.close) === 'function')
                    c.reconnectframe.close();
                c.waitingReConnectionInProgress = false;
            }, c.waitingReConnectionTimeOut * 1000);
        },
        isConnected: function(callBack, synchro) {
            var c = this;
            if (synchro === undefined)
                synchro = false;
            var cb = callBack;
            var a = {
                async: !synchro,
                type: 'POST',
                data: {},
                url: this.url + "?" + this.MODE_PARAMETER + "=" + this.session_mode + "&" + this.API_SELECTION_PARAMETER + "=" + this.api_selected,
                dataType: 'json',
                success: function(data) {
                    if (data[c.RETURN_IS_CONNECTED_PARAMETER] === 1) {
                        c.connected = true;
                        c.alreadyHaveConnectionOK = true;
                    }
                    if (cb !== undefined)
                        cb();
                }
            };
            a.data[this.ACTION_PARAMETER] = this.IS_CONNECTED_ACTION;
            $.ajax(a);
            return this.connected;
        },
        reconnectionOkListeners: new Array(),
        addReconnectionOKListener: function(listener) {
            this.reconnectionOkListeners.push(listener);
        },
        onReconnectionOK: function() {
            $.each(this.reconnectionOkListeners, function(key, value) {
                if (typeof(value) === 'function')
                    value();
            });
        }
    };
}