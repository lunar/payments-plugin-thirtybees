{if isset($lunar_card['action_url']) || isset($lunar_mobilepay['action_url'])}

    {assign var="accepted_cards_rendered" value=""}

    {if isset($lunar_card['accepted_cards_rendered'])}
        {$accepted_cards_rendered=$lunar_card['accepted_cards_rendered']}
    {/if}

    <script>
    {literal}
        document.addEventListener("DOMContentLoaded", function() {

            let renderedCardImages = "{/literal}{$accepted_cards_rendered}{literal}"
            $('img[src*="lunarpayment/views/img/visa.svg"]').replaceWith(`<span>${renderedCardImages}</span>`);

            $('.payment_module').on('click', (e) => {
                $('#lunar-card').prop('hidden', true);
                $('#lunar-mobilepay').prop('hidden', true);
            });

            $('.lunar-payment').on('click', (e) => {
                let $methodInput = $(e.target).parents('div.lunar').find('input');
                let method = $methodInput.val();
                
                $methodInput.prop('checked', true);

                $(`#${method}`).prop('hidden', false);
            });

            /** 
            * Compatibility with Advanced EU compliance module
            */
            $('#HOOK_ADVANCED_PAYMENT p.payment_module').on('click', (e) => {

                $('.cart_navigation form[action*="lunarpayment"]').remove();
                $('#confirmOrder').show();

                let method = $('.payment_selected .payment_option_cta').children('span').data('method');

                if (!method) {
                    return;
                }

                let form = $(`#${method}`).clone()
                form.prop('id', '')
                form.addClass('pull-right')

                if ($('#confirmOrder').length) {
                    $('#confirmOrder').hide();
                }

                $('.cart_navigation').append(form);
                $('.cart_navigation form').prop('hidden', false);
            });

        });
    {/literal}
    </script>

    {if isset($lunar_card['action_url']) }
        <div class="row lunar">
            <div class="col-xs-12 col-md-12">
                <div class="payment_module lunar-payment clearfix"
                        style=" border: 1px solid #d6d4d4; display: block; font-size: 17px; font-weight: bold; padding: 20px; cursor: pointer">

                        <div style="float:left; width:100%">
                            <span style="margin-right: 10px;">{l s={$lunar_card['title']} mod=lunarpayment}</span>
                            <span>
                                <ul class="cards">
                                    {foreach from=$lunar_card['accepted_cards'] item=logo}
                                        <li>
                                            <img src="{$module_path}/views/img/{$logo}" title="{$logo}" alt="{$logo}"/>
                                        </li>
                                    {/foreach}
                                </ul>
                            </span>
                            
                            <label class="lunar-radio">
                                <input type="radio" name="lunar-payment" value="lunar-card" />
                            </label>

                            <small style="font-size: 12px; display: block; font-weight: normal; letter-spacing: 1px; max-width:100%;">
                                {l s={$lunar_card['desc']} mod=lunarpayment}
                            </small>
                        </div>

                </div>
            </div>
        </div>
    {/if}

    {if isset($lunar_mobilepay['action_url']) }
        <div class="row lunar">
            <div class="col-xs-12 col-md-12">
                <div class="payment_module lunar-payment clearfix"
                    style=" border: 1px solid #d6d4d4; display: block; font-size: 17px; font-weight: bold; padding: 20px; cursor: pointer">

                        <div style="float:left; width:100%">
                            <span style="margin-right: 10px;">{l s={$lunar_mobilepay['title']} mod=lunarpayment }</span>
                            <span>
                                <img style="color: red; height:50px;" 
                                    id="mobilepay-logo" src="{$lunar_mobilepay['logo']}" title="mobilepay-logo" alt="mobilepay-logo"/>
                            </span>

                            <label class="lunar-radio">
                                <input type="radio" name="lunar-payment" value="lunar-mobilepay" />
                            </label>

                            <small style="font-size: 12px; display: block; font-weight: normal; letter-spacing: 1px; max-width:100%;">
                                {l s={$lunar_mobilepay['desc']} mod=lunarpayment }
                            </small>
                        </div>

                </div>
            </div>
        </div>
    {/if}


    <form id="lunar-card" action="{$lunar_card['action_url']}" method="POST" hidden>
        <button class="btn btn-lg btn-success pull-right" type="submit">
            <div>Pay with {$lunar_card['title']}</div>
        </button>
    </form>
    <form id="lunar-mobilepay" action="{$lunar_mobilepay['action_url']}" method="POST" hidden>
        <button class="btn btn-lg btn-success pull-right" type="submit">
            <div>Pay with {$lunar_mobilepay['title']}</div>
        </button>
    </form>


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
            margin-right: 5px;
        }

        /** 
        * Style for Advanced EU Compliance module
        */
        .cards-rendered {
            display: inline-flex;
        }
        .cards-rendered li img {
            height: 50px;
            padding: 3px;
        }
    </style>

{/if}