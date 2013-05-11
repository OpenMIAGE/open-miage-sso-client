var OpenM_IDLoginClient = {
    'url': '',    
    'session_mode': '',
    'api_selected': '',
    'alreadyHaveConnectionOK': false,
    'MODE_PARAMETER': "API_SESSION_MODE",
    'API_SELECTION_PARAMETER' : "API",
    'MODE_API_SELECTION' : "API_SELECTION",
    'MODE_ALL_API' : "ALL_API",
    'MODE_WITHOUT_API' : "WITHOUT_API",
    'ACTION_PARAMETER' : "ACTION",
    'IS_CONNECTED_ACTION' : "isConnected",
    'RETURN_IS_CONNECTED_PARAMETER' : "isConnected",
    'connected': false,
    'frame': undefined,
    'open': function(){
        if(this.connected)
            return;
        this.close();
        this.frame = $(document.createElement('div')).attr("id",'OpenM_IDLoginClient-frame');
        this.frame.addClass("hero-unit OpenM_IDLoginClient-frame");
        var iframe = $(document.createElement("iframe")).attr("id", 'OpenM_IDLoginClient-iframe').attr("src", this.url+"?"+this.MODE_PARAMETER+"="+this.session_mode+"&"+this.API_SELECTION_PARAMETER+"="+this.api_selected);
        var close = $(document.createElement("button")).attr("type", "button").addClass("close").attr("data-dismiss","modal").attr("aria-hidden", true).append("&times;");
        close.addClass("OpenM_IDLoginClient-close");
        close.attr("onclick", "OpenM_IDLoginClient.close();return false;");
        this.frame.append(close);
        iframe.addClass("OpenM_IDLoginClient-iframe");
        iframe.attr("frameborder",0);
        this.frame.append(iframe);
        $("html body").append(this.frame);
        var controller = this;
        this.launchWaitConnectionDaemon(function(){
            controller.frame.remove();
        });
    },
    'reconnectframe' : undefined,
    'reconnect': function(callback_function){
        if(!this.alreadyHaveConnectionOK)
            return;
        this.connected = false;
        this.reconnectframe = $(document.createElement("iframe"));
        this.reconnectframe.attr("src", this.url+"?"+this.MODE_PARAMETER+"="+this.session_mode+"&"+this.API_SELECTION_PARAMETER+"="+this.api_selected);
        this.reconnectframe.addClass("OpenM_IDLoginClient-reconnectframe");
        $("html body").append(this.reconnectframe);
        var controller = this;
        var callback = callback_function;
        this.launchWaitConnectionDaemon(function(){
            controller.reconnectframe.remove();
            if(callback!==undefined)
                callback();
        });
    },
    'close': function(){
        if(this.frame!=undefined)
            this.frame.remove();
        if(this.timer_daemon!==undefined)
            window.clearTimeout(this.timer_daemon);
    },
    'isConnected': function(synchro){
        if(this.connected){
            if(this.frame!=undefined)
                this.frame.remove();
            return true;
        }
        
        var controller = this;
        if(synchro===undefined)
            synchro = false;
        
        var ajax = {
            async: !synchro,
            type: 'POST', 
            data: {}, 
            url: this.url+"?"+this.MODE_PARAMETER+"="+this.session_mode+"&"+this.API_SELECTION_PARAMETER+"="+this.api_selected, 
            dataType: 'json',
            success: function(data){
                if(data[controller.RETURN_IS_CONNECTED_PARAMETER]==1){
                    controller.connected = true;
                    controller.alreadyHaveConnectionOK = true;
                    if(controller.callback_when_connected!==undefined)
                        controller.callback_when_connected();
                }
            }
        };
        ajax.data[this.ACTION_PARAMETER] = this.IS_CONNECTED_ACTION;
        
        $.ajax(ajax);
        return this.connected;
    },
    'timer_daemon': null,
    'timer_interval': 500,
    'callback_when_connected': undefined,
    'launchWaitConnectionDaemon': function(callback_when_connected){
        this.callback_when_connected = callback_when_connected;
        this.checkWaitConnectionDaemon();
    },
    'checkWaitConnectionDaemon': function(){
        if(this.isConnected())
            this.callback_when_connected();
        else{
            var controller = this;
            if(this.timer_daemon!==undefined)
                window.clearTimeout(this.timer_daemon);
            this.timer_daemon = setTimeout(function(){
                controller.checkWaitConnectionDaemon()
            }, this.timer_interval);
        }
    }
}