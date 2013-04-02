var {$api} = {literal}{{/literal}
{foreach from=$constants item=constant}
{$constant.name}: {if is_int($constant.value)}{$constant.value}{else}'{$constant.value}'{/if},
{/foreach}
{foreach from=$methods item=method}
{$method.name}: function({foreach from=$method.args item=arg}{$arg.name}, {/foreach}callback_function){literal}{{/literal}
$.post('{$api_url}?api={$api}&method={$method.name}', {literal}{{/literal}{foreach from=$method.args item=arg}{$arg.parameterName}: {$arg.name}, {/foreach}
{literal}ok:1}{/literal}, function(r){literal}{{/literal}callback_function(r){literal}}{/literal});
{literal}}{/literal},
{/foreach}ok: 1
{literal}}{/literal};