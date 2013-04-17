var {$api}{if $min!=true} {/if}={if $min!=true} {/if}{literal}{{/literal}{if $min!=true} 
{/if}{foreach from=$constants item=constant}{if $min!=true}
    {/if}'{$constant.name}':{if $min!=true} {/if}{if is_int($constant.value)}{$constant.value}{else}'{$constant.value|replace:"'":"\'"}'{/if},{if $min!=true} {/if}{if $min!=true} 
{/if}{/foreach}{if $min!=true}
    {/if}'r':{if $min!=true} {/if}'{$api_url}?api={$api}&method=',{if $min!=true} 
{/if}{foreach from=$methods item=method}{if $min!=true}
    {/if}'{$method.name}':{if $min!=true} {/if}function({foreach from=$method.args item=arg}{if $min!=true}{$arg.name}{else}{$arg.parameterName|replace:'arg':'c'}{/if},{if $min!=true} {/if}{/foreach}{if $min!=true}callback_function{else}cb{/if}){literal}{{/literal}{if $min!=true} 
        {/if}var ajax{if $min!=true} {/if}={if $min!=true} {/if}{literal}{{/literal}{if $min!=true} 
            {/if}type:{if $min!=true} {/if}"POST",{if $min!=true} 
            {/if}url:{if $min!=true} {/if}this.r+'{$method.name}',{if $min!=true} 
            {/if}data:{if $min!=true} {/if}{literal}{{/literal}{foreach from=$method.args item=arg}{$arg.parameterName}:{if $min!=true} {/if}{if $min!=true}{$arg.name}{else}{$arg.parameterName|replace:'arg':'c'}{/if},{if $min!=true} {/if}{/foreach}ok:1{literal}}{/literal},{if $min!=true} 
            {/if}dataType:{if $min!=true} {/if}"json"{if $min!=true} 
        {/if}{literal}}{/literal};{if $min!=true} 
        {/if}if({if $min!=true}callback_function{else}cb{/if}===undefined){if $min!=true} 
            {/if}ajax.async{if $min!=true} {/if}={if $min!=true} {/if}false;{if $min!=true} 
        {/if}else {if $min!=true} 
            {/if}ajax.success{if $min!=true} {/if}={if $min!=true} {/if}function(r){literal}{{/literal}{if $min!=true}callback_function(r){else}cb(r){/if}{literal}}{/literal};{if $min!=true} 
        {/if}$.ajax(ajax);{if $min!=true} 
    {/if}{literal}}{/literal},{if $min!=true} 
{/if}{/foreach}{if $min!=true}  {/if}'ok':1{if $min!=true} 
{/if}{literal}}{/literal};