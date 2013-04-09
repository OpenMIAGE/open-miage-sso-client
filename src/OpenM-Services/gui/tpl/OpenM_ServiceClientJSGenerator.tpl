var {$api}{if $min!=true} {/if}={if $min!=true} {/if}{literal}{{/literal}{if $min!=true} 
{/if}{foreach from=$constants item=constant}{if $min!=true}
    {/if}{$constant.name}:{if $min!=true} {/if}{if is_int($constant.value)}{$constant.value}{else}'{$constant.value|replace:"'":"\'"}'{/if},{if $min!=true} {/if}{if $min!=true} 
{/if}{/foreach}{if $min!=true}
{/if}{foreach from=$methods item=method}{if $min!=true}
    {/if}{$method.name}:{if $min!=true} {/if}function({foreach from=$method.args item=arg}{if $min!=true}{$arg.name}{else}{$arg.parameterName|replace:'arg':'c'}{/if},{if $min!=true} {/if}{/foreach}{if $min!=true}callback_function{else}cb{/if}){literal}{{/literal}{if $min!=true} 
        {/if}$.post('{$api_url}?api={$api}&method={$method.name}',{if $min!=true} {/if}{literal}{{/literal}{foreach from=$method.args item=arg}{$arg.parameterName}:{if $min!=true} {/if}{if $min!=true}{$arg.name}{else}{$arg.parameterName|replace:'arg':'c'}{/if},{if $min!=true} {/if}{/foreach}{if $min!=true} {/if}ok:1{literal}}{/literal},{if $min!=true} {/if}{if $min!=true} 
            {/if}function(r){literal}{{/literal}{if $min!=true}callback_function(r){else}cb(r){/if}{literal}}{/literal}{if $min!=true} 
        {/if});{if $min!=true} 
    {/if}{literal}}{/literal},{if $min!=true} 
{/if}{/foreach}{if $min!=true}
{/if}{literal}}{/literal};