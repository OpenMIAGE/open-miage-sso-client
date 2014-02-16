if (typeof(OpenM_SSOConnectionProxy) === 'undefined') {
    var OpenM_SSOConnectionProxy = {
        url: "",
        resource_dir: "./resources/",
        session_mode: "",
        api_selected: "",
        alreadyHaveConnectionOK: false,
        alreadyHaveTryingReconnection: false,
        MODE_PARAMETER: "API_SESSION_MODE",
        REDIRECT_TO_LOGIN: "REDIRECT_TO_LOGIN",
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
        waitingConnectionTimeOut: 120,
        waitingReConnectionTimeOut: 120,
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
            this.frame = window.open(this.url + "?" + this.MODE_PARAMETER + "=" + this.session_mode + "&" + this.API_SELECTION_PARAMETER + "=" + this.api_selected + "&" + this.REDIRECT_TO_LOGIN + "=1", "popup", "toolbar=0, location=0, directories=0, status=0, resizable=0, copyhistory=0, height=" + height + ", width=" + width + ", top=" + top + ", left=" + left + "");
            if (this.waitingConnectionInProgress)
                return;
            var c = this;
            var t;
            c.waitingConnectionInProgress = true;
            t = setTimeout(function() {
                if (c.frame !== undefined && typeof(c.frame.close) === 'function')
                    c.frame.close();
                c.waitingConnectionInProgress = false;
            }, c.waitingConnectionTimeOut * 1000);
        },
        reconnectFail: function() {
            window.location.reload();
        },
        reconnectframe: undefined,
        reconnectionCheckInterval: undefined,
        reconnectionTimeOut: undefined,
        reconnectionMessage: " reconnection in progress... ",
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
            var timerA = $(document.createElement("span"));
            this.reconnectframe = $(document.createElement("iframe"))
                    .attr("src", this.url + "?" + this.MODE_PARAMETER + "=" + this.session_mode + "&" + this.API_SELECTION_PARAMETER + "=" + this.api_selected);
            $("html body").append($(document.createElement("div"))
                    .addClass("OpenM_SSOConnectionProxy")
                    .append(this.reconnectframe)
                    .append($(document.createElement("div"))
                    .append(this.reconnectionMessage)
                    .addClass("OpenM_SSOConnectionProxy_Message")
                    .append(timerA))
                    .append($(document.createElement("img"))
                    .attr("src", this.resource_dir + "OpenM-SSO/gui/img/loader.gif")));
            var timerTimeOut = this.waitingReConnectionTimeOut;
            timerA.text("(" + timerTimeOut + ")");
            var timerInterval = setInterval(function() {
                if (timerA !== undefined && typeof(timerA.text) === "function") {
                    if (timerTimeOut > 0)
                        timerTimeOut--;
                    else
                        clearInterval(timerInterval);
                    timerA.text("(" + timerTimeOut + ")");
                }
                else
                    clearInterval(timerInterval);
            }, 1000);
            var c = this;
            var t = c.reconnectionTimeOut;
            c.waitingReConnectionInProgress = true;
            var i = c.reconnectionCheckInterval;
            t = setTimeout(function() {
                if (typeof(i) !== 'undefined')
                    clearInterval(i);
                if (c.reconnectframe !== undefined && typeof(c.reconnectframe.close) === 'function')
                    setTimeout(function() {
                        if (c.reconnectframe !== undefined && typeof(c.reconnectframe.close) === 'function')
                            c.reconnectframe.close();
                    }, 2000);
                c.waitingReConnectionInProgress = false;
                if (!c.connected && typeof(c.reconnectFail) === "function")
                    c.reconnectFail();
            }, c.waitingReConnectionTimeOut * 1000);
        },
        isConnected: function(callBackConnected, synchro) {
            var c = this;
            if (synchro === undefined)
                synchro = false;
            var cb = callBackConnected;
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
        },
        reconnection_process_trigger: function() {
            var c = this;
            this.isConnected(function() {
                if (c.reconnectframe !== undefined) {
                    if (typeof(c.reconnectframe.close) === 'function')
                        c.reconnectframe.close();
                    if (typeof(c.reconnectframe.parent().remove) === 'function')
                        c.reconnectframe.parent().remove();
                }
                if (c.frame !== undefined) {
                    if (typeof(c.frame.close) === 'function')
                        c.frame.close();
                }
                if (c.connected)
                    c.onReconnectionOK();
                else
                    c.reconnectFail();
            });
        }
    };
}

if (window.openm_id_connection_trigger === undefined) {
    window.openm_id_connection_trigger = function() {
        OpenM_SSOConnectionProxy.reconnection_process_trigger();
    };
}