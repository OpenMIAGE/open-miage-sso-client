{literal}
if(OpenM_APIProxy_AJAXController == undefined){
    var OpenM_APIProxy_AJAXController = {
        call: function(ajax){
            ajax.error = function(data, type, error){
                
            }
            $.ajax(ajax)
        },
        callingQueue: new Array(),
        STATUS_OK: 0,
        STATUS_WAITING_RECONNECTION: 1,
        STATUS_KO: 2,
        waitingTimeInit: 5,
        waitingTime: this.waitingTimeInit,        
        callingStatus: this.STATUS_OK,
        getStatus: function(){}        
    };
}
{/literal}