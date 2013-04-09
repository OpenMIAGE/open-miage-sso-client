function {$api}({if $min!=true}root_path{else}r{/if}){if $min!=true} {/if}={if $min!=true} {/if}{literal}{{/literal}{if $min!=true} 
{/if}{foreach from=$constants item=constant}{if $min!=true}
    {/if}this.{$constant.name}{if $min!=true} {/if}={if $min!=true} {/if}{if is_int($constant.value)}{$constant.value}{else}'{$constant.value|replace:"'":"\'"}'{/if};{if $min!=true} {/if}{if $min!=true} 
{/if}{/foreach}{if $min!=true}
    {/if}this.r{if $min!=true} {/if}={if $min!=true} {/if}{if $min!=true}root_path{else}r{/if}+'?api={$api}&method=';{if $min!=true} 
{/if}{foreach from=$methods item=method}{if $min!=true}
    {/if}this.{$method.name}{if $min!=true} {/if}={if $min!=true} {/if}function({foreach from=$method.args item=arg}{if $min!=true}{$arg.name}{else}{$arg.parameterName|replace:'arg':'c'}{/if},{if $min!=true} {/if}{/foreach}{if $min!=true}callback_function{else}cb{/if}){literal}{{/literal}{if $min!=true} 
        {/if}$.post(this.r+'{$method.name}',{if $min!=true} {/if}{literal}{{/literal}{foreach from=$method.args item=arg}{$arg.parameterName}:{if $min!=true} {/if}{if $min!=true}{$arg.name}{else}{$arg.parameterName|replace:'arg':'c'}{/if},{if $min!=true} {/if}{/foreach}{if $min!=true} {/if}ok:1{literal}}{/literal},{if $min!=true} {/if}{if $min!=true} 
            {/if}function(r){literal}{{/literal}{if $min!=true}callback_function(r){else}cb(r){/if}{literal}}{/literal}{if $min!=true} 
        {/if});{if $min!=true} 
    {/if}{literal}}{/literal}{if $min!=true} 
{/if}{/foreach}{if $min!=true}
{/if}{literal}}{/literal}