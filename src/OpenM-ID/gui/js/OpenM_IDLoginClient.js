var OpenM_IDLoginClient = {
    'url': '',    
    'connected': false,
    'frame': undefined,
    'open': function(){
        if(this.connected)
            return;
        this.frame = $(document.createElement('div')).attr("id",'OpenM_IDLoginClient-frame');
        this.frame.addClass("hero-unit OpenM_IDLoginClient-frame");
        var iframe = $(document.createElement("iframe")).attr("id", 'OpenM_IDLoginClient-iframe').attr("src", this.url);
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
        this.connected = false;
        this.reconnectframe = $(document.createElement("iframe"));
        this.reconnectframe.attr("src", this.url+"?reconnect");
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
        this.frame.remove();
    },
    'isConnected': function(){
        if(this.connected){
            if(this.frame!=undefined)
                this.frame.remove();
            return true;
        }
        
        var controller = this;
        $.get(this.url, {
            isConnected:''
        }, function(data){
            if(data.isConnected!==undefined && data.isConnected==1){
                controller.connected = true;
                if(controller.callback_when_connected!==undefined)
                    controller.callback_when_connected();
            }
        }, "json");
        
        return false;
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