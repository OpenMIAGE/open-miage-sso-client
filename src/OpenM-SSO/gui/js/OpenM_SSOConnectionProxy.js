if (typeof(OpenM_SSOConnectionProxy) === 'undefined') {
    var OpenM_SSOConnectionProxy = {
        'url': '',
        'session_mode': '',
        'api_selected': '',
        'alreadyHaveConnectionOK': false,
        'MODE_PARAMETER': "API_SESSION_MODE",
        'API_SELECTION_PARAMETER': "API",
        'MODE_API_SELECTION': "API_SELECTION",
        'MODE_ALL_API': "ALL_API",
        'MODE_WITHOUT_API': "WITHOUT_API",
        'ACTION_PARAMETER': "ACTION",
        'IS_CONNECTED_ACTION': "isConnected",
        'RETURN_IS_CONNECTED_PARAMETER': "isConnected",
        'connected': false,
        'frame': undefined,
        'open': function() {
            if (this.connected)
                return;
            this.frame = window.open(this.url + "?" + this.MODE_PARAMETER + "=" + this.session_mode + "&" + this.API_SELECTION_PARAMETER + "=" + this.api_selected, "popup", "toolbar=0, location=0, directories=0, status=0, scrollbars=0, resizable=0, copyhistory=0, width=450, height=350,screenX=200,screenY=200");
            var c = this;
            this.launchWaitConnectionDaemon(function() {
                c.frame.close();
            });
        },
        'reconnectframe': undefined,
        'reconnect': function(callback) {
            if (!this.alreadyHaveConnectionOK)
                return;
            if (this.isReconnectionInProgress)
                return;
            this.connected = false;
            this.isReconnectionInProgress = true;
            this.reconnectframe = $(document.createElement("iframe"));
            this.reconnectframe.attr("src", this.url + "?" + this.MODE_PARAMETER + "=" + this.session_mode + "&" + this.API_SELECTION_PARAMETER + "=" + this.api_selected);
            this.reconnectframe.attr("position", "absolute");
            this.reconnectframe.attr("right", 0);
            this.reconnectframe.attr("bottom", 0);
            this.reconnectframe.attr("width", 1);
            this.reconnectframe.attr("height", 1);
            this.reconnectframe.attr("border", 0);
            this.reconnectframe.attr("background-color", "transparent");
            $("html body").append(this.reconnectframe);
            var c = this;
            var cb = callback;
            setTimeout(function() {
                c.clearReconnection();
                if (cb !== undefined)
                    cb();
            }, this.timeOutOfReconnection);
            this.launchWaitReConnectionDaemon(function() {
                c.reconnectframe.remove();
                c.isReconnectionInProgress = false;
                if (cb !== undefined)
                    cb();
            });
        },
        'close': function() {
            if (this.frame !== undefined)
                this.frame.close();
            if (this.timer_daemon !== undefined)
                window.clearTimeout(this.timer_daemon);
        },
        'isConnected': function(callBack, synchro) {
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
        'timer_daemon': null,
        'timer_interval': 500,
        'callback_when_connected': undefined,
        'launchWaitConnectionDaemon': function(callback_when_connected) {
            this.callback_when_connected = callback_when_connected;
            this.checkWaitConnectionDaemon();
        },
        'checkWaitConnectionDaemon': function() {
            var c = this;
            if (this.isConnected(function() {
                if (c.connected) {
                    if (c.timer_daemon !== undefined)
                        window.clearTimeout(c.timer_daemon);
                    c.callback_when_connected();
                }
            })) {
                return;
            }
            else {
                if (this.timer_daemon !== undefined)
                    window.clearTimeout(this.timer_daemon);
                this.timer_daemon = setTimeout(function() {
                    c.checkWaitConnectionDaemon();
                }, this.timer_interval);
            }
        },
        'timer_daemon_reconnection': null,
        'timer_interval_reconnection': 3000,
        'callback_when_reconnected': undefined,
        'launchWaitReConnectionDaemon': function(callback_when_reconnected) {
            this.callback_when_reconnected = callback_when_reconnected;
            var c = this;
            this.clearTimerReconnection();
            this.timer_daemon_reconnection = setTimeout(function() {
                c.checkWaitReConnectionDaemon();
            }, this.timer_interval_reconnection);
        },
        'checkWaitReConnectionDaemon': function() {
            var c = this;
            if (this.isConnected(function() {
                if (c.connected) {
                    c.clearTimerReconnection();
                    c.callback_when_reconnected();
                }
            })) {
                return;
            }
            else {
                this.clearTimerReconnection();
                this.timer_daemon_reconnection = setTimeout(function() {
                    c.checkWaitReConnectionDaemon();
                }, this.timer_interval);
            }
        },
        'isReconnectionInProgress': false,
        'timeOutOfReconnection': 60000,
        'timeOutOfReconnectionTimer': undefined,
        'clearTimerReconnection': function() {
            if (this.timer_daemon_reconnection !== undefined)
                window.clearTimeout(this.timer_daemon_reconnection);
        },
        'clearReconnection': function() {
            this.clearTimerReconnection();
            if (this.timeOutOfReconnectionTimer !== undefined)
                window.clearTimeout(this.timeOutOfReconnectionTimer);
            this.reconnectframe.remove();
            this.isReconnectionInProgress = false;
        }
    };
}