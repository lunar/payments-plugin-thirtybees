{if isset($lunar_card_action_url) || isset($lunar_mobilepay_action_url)}

<script>
{literal}
    document.addEventListener("DOMContentLoaded", function() {

        $('.lunar-payment').on('click', (e) => {
            let buttonMethodTitles = {
                "lunar-card": "{/literal}{$lunar_card_title}{literal}",
                "lunar-mobilepay": "{/literal}{$lunar_mobilepay_title}{literal}",
            }

            let $methodInput = $(e.target).parents('div.lunar').find('input');
            let url = $methodInput.data('url');

            $methodInput.prop('checked', true);

            $('#lunar-form').prop('action', url);
            $('#lunar-form').prop('hidden', false);

            $('.lunar-method').text(buttonMethodTitles[$methodInput.val()]);
        });
    });
{/literal}
</script>

<style type="text/css">
    .lunar-payment {
        margin-bottom: 1rem;
    }

    .lunar-radio {
        float: right;
        top: 1rem;
        right: 1.5rem;
    }

    .lunar-radio > input {
        opacity: 1;
        height: 2rem;
        width: 2rem;
    }

    .cards {
        display: inline-flex;
    }
    .cards li {
        width: 25%;
        padding: 3px;
    }
    .cards li img {
        vertical-align: middle;
        max-width: 100%;
        height: 40px;
    }
    
    #mobilepay-logo {
        height: 40px;
        margin-left: 5px;
    }
</style>

{if isset($lunar_card_action_url) }
    <div class="row lunar">
        <div class="col-xs-12 col-md-12">
            <div class="payment_module lunar-payment clearfix"
                    style=" border: 1px solid #d6d4d4; display: block; font-size: 17px; font-weight: bold; padding: 20px;cursor: pointer">

                    <div style="float:left; width:100%">
                        <span style="margin-right: 10px;">{l s={$lunar_card_title} mod=lunarpayment}</span>
                        <span>
                            <ul class="cards">
                                {foreach from=$accepted_cards item=logo}
                                    <li>
                                        <img src="{$module_path}/views/img/{$logo}" title="{$logo}" alt="{$logo}"/>
                                    </li>
                                {/foreach}
                            </ul>
                        </span>
                        
                        <label class="lunar-radio">
                            <input
                                type="radio" 
                                name="lunar-payment" 
                                value="lunar-card" 
                                data-url="{$lunar_card_action_url}"
                            />
                        </label>

                        <small style="font-size: 12px; display: block; font-weight: normal; letter-spacing: 1px; max-width:100%;">
                            {l s={$lunar_card_desc} mod=lunarpayment}
                        </small>
                    </div>

            </div>
        </div>
    </div>
{/if}

{if isset($lunar_mobilepay_action_url) }
    <div class="row lunar">
        <div class="col-xs-12 col-md-12">
            <div class="payment_module lunar-payment clearfix"
                  style=" border: 1px solid #d6d4d4; display: block; font-size: 17px; font-weight: bold; padding: 20px;"cursor: pointer>

                    <div style="float:left; width:100%">
                        <span style="margin-right: 10px;">{l s={$lunar_mobilepay_title} mod=lunarpayment }</span>
                        <span>
                            <img style="color: red; height:50px;" 
                            id="mobilepay-logo" src="{$module_path}views/img/mobilepay-logo.png" title="mobilepay-logo" alt="mobilepay-logo"/>
                        </span>

                        <label class="lunar-radio">
                            <input
                                type="radio" 
                                name="lunar-payment" 
                                value="lunar-mobilepay" 
                                data-url="{$lunar_mobilepay_action_url}"
                            />
                        </label>

                        <small style="font-size: 12px; display: block; font-weight: normal; letter-spacing: 1px; max-width:100%;">
                            {l s={$lunar_mobilepay_desc} mod=lunarpayment }
                        </small>
                    </div>

            </div>
        </div>
    </div>
{/if}


    <form id="lunar-form" action="" method="POST" hidden>
        <button class="btn btn-lg btn-success pull-right" type="submit">
            <div>
                Pay with <span class="lunar-method"></span>
            </div>
        </button>
    </form>
{/if}