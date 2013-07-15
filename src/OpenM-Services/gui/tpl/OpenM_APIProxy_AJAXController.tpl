{literal}
    if (typeof(OpenM_APIProxy_AJAXController) === 'undefined') {
    var OpenM_APIProxy_AJAXController = {
        call: function(ajax) {
            if (ajax === undefined)
                return;
            var controller = this;
            var a = ajax;
            var s = a.success;
            a.success = function(data) {
                if (a.callingQueueId !== undefined)
                    controller.callingQueue[a.callingQueueId] = undefined;
                s(data);
            };
            a.error = function(data, type, error) {
                controller.error(a, data, type, error);
            };
            if (a.called === undefined)
                a.called = 1;
            else
                a.called++;
            $.ajax(a);
        },
        error: function(ajax, data, type, error) {
            if (ajax === undefined)
                return;
            if (ajax.called === undefined)
                return;
            var controller = this;
            if (ajax.called === 1) {
                ajax.errors_messages = new Array();
                ajax.callingQueueId = controller.callingQueue.length;
                controller.callingQueue[ajax.callingQueueId] = ajax;
            }
            ajax.errors_messages.push({data: data, type: type, error: error});
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
            }, controller.waitingTime*1000);
            OpenM_SSOConnectionProxy.reconnect(function() {
                controller.callingStatus = controller.STATUS_OK;
                controller.waitingTime = controller.waitingTimeInit;
                if (controller.reconnectTimeOut !== undefined)
                    clearTimeout(controller.reconnectTimeOut);
                controller.recallAll();
            });
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
        waitingTimeInit: 20,
        waitingTime: undefined,
        callingStatus: undefined,
        isOK: function() {
            if (this.callingStatus === undefined)
                this.callingStatus = this.STATUS_OK;
            return (this.callingStatus === this.STATUS_OK);
        }
    };
}
{/literal}