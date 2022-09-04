<div style="color: {$dealt_module_log_color}; border: solid 1px #000000; padding: 5px; background: #EEE; margin-bottom: 10px;">
	<b>[{$dealt_module_log_date|escape:'htmlall':'UTF-8'}] :: [{$dealt_module_log_type|escape:'htmlall':'UTF-8'}]</b><br />
    {$dealt_module_log_content}
    {if $dealt_module_log_details}
        <br />
        <b>{l s='Details:' mod='dealtmodule'}</b>
        {foreach from=$dealt_module_log_details item=detail key=name name=log_detail}
            {$name|escape:'htmlall':'UTF-8'}: {$detail|escape:'htmlall':'UTF-8'}{if !$smarty.foreach.log_detail.last};{/if}
        {/foreach}
    {/if}
</div>