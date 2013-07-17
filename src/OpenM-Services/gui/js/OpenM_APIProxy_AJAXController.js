if (typeof(OpenM_APIProxy_AJAXController) === 'undefined') {
    var OpenM_APIProxy_AJAXController = {
        initialized: false,
        call: function(ajax) {
            var c = this;
            if (!this.initialized && typeof(OpenM_SSOConnectionProxy) !== 'undefined') {
                OpenM_SSOConnectionProxy.addReconnectionOKListener(function() {
                    c.reconnectionOK();
                });
                c.initialized = true;
            }
            if (ajax === undefined)
                return;
            var s = ajax.success;
            ajax.called = 0;
            if (ajax.initialized !== true) {
                ajax.success = function(data) {
                    if (ajax.callingQueueId !== undefined)
                        c.callingQueue.splice(ajax.callingQueueId, 1);
                    s(data);
                };
                ajax.error = function(data, type, error) {
                    c.error(ajax, data, type, error);
                };
                ajax.initialized = true;
            }
            if (!c.isOK())
                return c.error(ajax, null, "not connected");
            ajax.called = ajax.called + 1;
            $.ajax(ajax);
        },
        error: function(ajax, data, type, error) {
            if (ajax === undefined)
                return;
            if (ajax.called === undefined)
                return;
            var c = this;
            if (type === 'parsererror')
                c.onError(c.INTERNAL_ERROR);
            if (type === 'timeout')
                c.onError(c.TIME_OUT);
            if (ajax.called === 0 || ajax.called === 1) {
                if (ajax.errors === undefined)
                    ajax.errors = new Array();
                if (ajax.callingQueueId === undefined) {
                    ajax.callingQueueId = c.callingQueue.length;
                    c.callingQueue[ajax.callingQueueId] = ajax;
                }
            }
            ajax.errors.push({data: data, type: type, error: error});
            if (!c.isOK())
                return;
            c.callingStatus = c.STATUS_KO;
            if (type === 'error' && typeof(error) === 'string' && error.search("ERRNO:-1") >= 0) {
                c.onError(c.CONNECTION_LOST);
                c.reconnect();
            }
        },
        reconnect: function() {
            if (typeof(OpenM_SSOConnectionProxy) === 'undefined')
                return;
            var c = this;
            if (c.reconnectTimeOut !== undefined)
                clearTimeout(c.reconnectTimeOut);
            if (c.waitingTime === undefined)
                c.waitingTime = c.waitingTimeInit;
            c.reconnectTimeOut = setTimeout(function() {
                if (c.isOK())
                    return;
                c.waitingTime = c.waitingTime * 2;
                c.reconnect();
            }, c.waitingTime * 1000);
            c.onStatusChange(c.callingStatus, c.waitingTime);
            OpenM_SSOConnectionProxy.reconnect(function() {
                c.reconnectionOK();
            });
            c.onStatusChange(c.STATUS_WAITING_RECONNECTION);
        },
        reconnectionOK: function() {
            this.callingStatus = this.STATUS_OK;
            this.waitingTime = this.waitingTimeInit;
            if (this.reconnectTimeOut !== undefined)
                clearTimeout(this.reconnectTimeOut);
            this.onStatusChange(this.callingStatus);
            this.recallAll();
        },
        recallAll: function() {
            var c = this;
            if (!c.isOK())
                return;
            $.each(c.callingQueue, function(key, value) {
                if (value !== undefined && value.called !== undefined) {
                    if (value.called === c.maxNumberCall)
                        c.callingQueue.splice(key, 1);
                    else {
                        c.call(value);
                    }
                }
            });
        },
        maxNumberCall: 2,
        reconnectTimeOut: undefined,
        callingQueue: new Array(),
        STATUS_OK: 0,
        STATUS_WAITING_RECONNECTION: 1,
        STATUS_KO: 2,
        waitingTimeInit: 10,
        waitingTime: undefined,
        callingStatus: undefined,
        INTERNAL_ERROR: -1,
        CONNECTION_ERROR: -2,
        TIME_OUT: -3,
        CONNECTION_LOST: -4,
        isOK: function() {
            if (this.callingStatus === undefined)
                this.callingStatus = this.STATUS_OK;
            return (this.callingStatus === this.STATUS_OK);
        },
        addChangeStatusListener: function(listener) {
            this.changeListeners.push(listener);
        },
        onStatusChange: function(status, waitingTime) {
            $.each(this.changeListeners, function(key, value) {
                if (typeof(value) === 'function')
                    value(status, waitingTime);
            });
        },
        changeListeners: new Array(),
        addErrorListener: function(listener) {
            this.errorlisteners.push(listener);
        },
        onError: function(errno) {
            $.each(this.errorlisteners, function(key, value) {
                if (typeof(value) === 'function')
                    value(errno);
            });
        },
        errorlisteners: new Array()
    };
}