{literal}
if(OpenM_APIProxy_AJAXController == undefined){
    var OpenM_APIProxy_AJAXController = {
        call: function(ajax){$.ajax(ajax)},
        callingQueue: new Array(),
    };
}
{/literal}