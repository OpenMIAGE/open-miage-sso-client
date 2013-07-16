{literal}
if (typeof(OpenM_APIProxy_AJAXController) === 'undefined') {
    var OpenM_APIProxy_AJAXController = {
        call: function(ajax) {
            if (ajax === undefined)
                return;
            var controller = this;
            var a = ajax;
            var s = a.success;
            if (a.initialized !== true) {
                a.success = function(data) {
                    if (a.callingQueueId !== undefined)
                        controller.callingQueue[a.callingQueueId] = undefined;
                    s(data);
                };
                a.error = function(data, type, error) {
                    controller.error(a, data, type, error);
                };
                a.initialized = true;
            }
            if (!controller.isOK())
                return controller.error(ajax, null, "not connected");
            if (a.called === undefined)
                a.called = 1;
            else
                a.called = a.called + 1;
            $.ajax(a);
        },
        error: function(ajax, data, type, error) {
            if (ajax === undefined)
                return;
            if (ajax.called === undefined)
                return;
            var controller = this;
            controller.onError(data, type);
            if (ajax.called === undefined || ajax.called === 1) {
                if (ajax.called === undefined)
                    ajax.called = 0;
                if (ajax.errors === undefined)
                    ajax.errors = new Array();
                if (ajax.callingQueueId === undefined) {
                    ajax.callingQueueId = controller.callingQueue.length;
                    controller.callingQueue[ajax.callingQueueId] = ajax;
                }
            }
            ajax.errors.push({data: data, type: type, error: error});
            if (!controller.isOK())
                return;
            controller.callingStatus = controller.STATUS_KO;
            controller.reconnect();
        },
        reconnect: function() {
            if (typeof(OpenM_SSOConnectionProxy) === 'undefined')
                return;
            var controller = this;
            if (controller.reconnectTimeOut !== undefined)
                clearTimeout(controller.reconnectTimeOut);
            if (controller.waitingTime === undefined)
                controller.waitingTime = controller.waitingTimeInit;
            controller.reconnectTimeOut = setTimeout(function() {
                if (controller.isOK())
                    return;
                controller.waitingTime = controller.waitingTime * 2;
                controller.reconnect();
            }, controller.waitingTime * 1000);
            controller.onStatusChange(controller.callingStatus, controller.waitingTime);
            OpenM_SSOConnectionProxy.reconnect(function() {
                controller.callingStatus = controller.STATUS_OK;
                controller.waitingTime = controller.waitingTimeInit;
                if (controller.reconnectTimeOut !== undefined)
                    clearTimeout(controller.reconnectTimeOut);
                controller.onStatusChange(controller.callingStatus);
                controller.recallAll();
            });
            controller.onStatusChange(controller.STATUS_WAITING_RECONNECTION);
        },
        recallAll: function() {
            var controller = this;
            if (!controller.isOK())
                return;
            $.each(controller.callingQueue, function(key, value) {
                if (value.called !== undefined) {
                    if (value.called === controller.maxNumberCall)
                        controller.callingQueue[key] = undefined;
                    else {
                        controller.call(value);
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
        onError: function(data, type) {
            $.each(this.errorlisteners, function(key, value) {
                if (typeof(value) === 'function')
                    value(data, type);
            });
        },
        errorlisteners: new Array()
    };
}
{/literal}