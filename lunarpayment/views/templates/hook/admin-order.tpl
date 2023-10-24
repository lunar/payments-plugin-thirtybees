<script>

{literal}
    /* Load php data */
    const captured = '{/literal}{$lunartransaction["captured"]}{literal}';
    const refunded = '{/literal}{$lunartransaction["payed_amount"] == $lunartransaction["refunded_amount"]}{literal}';
    const module_payment_not_captured = '{/literal}{$not_captured_text}{literal}';
    const already_refunded_text = '{/literal}{$already_refunded_text}{literal}';
    const payment_select_refund = '{/literal}{$checkbox_text}{literal}';

    /* Add Checkbox */
    $(document).ready(() => {
        /* Display message if transaction is not captured or it's refunded */
        let messageBox = `<p id="doRefundLunar" style="color:red">` + module_payment_not_captured + `</p>`;
        let refundedMessage = `<p id="doRefundLunar" style="color:red">` + already_refunded_text + `</p>`;

        /* Make partial order refund in Order page */
        let appendEl = $('select[name=id_order_state]').parents('form').after($('<div/>'));
        $(`#lunar`).appendTo(appendEl);
        $(`#lunar_action`).bind('change', modulePaymentActionChangeHandler);
        $(`#submit_lunar_action`).bind('click', submitModulePaymentActionClickHandler);

        $(document).bind('click', '#desc-order-partial_refund', function(){
            /* Create checkbox and insert for payment refund */
            if ($(`#doRefundLunar`).length == 0) {
                let newCheckBox = `<p class="checkbox">
                                        <label for="doRefundLunar">
                                            <input type="checkbox" id="doRefundLunar" name="doRefundLunar" value="1" checked>
                                            ${payment_select_refund}
                                        </label>
                                    </p>`;
                if(refunded){
                    newCheckBox = refundedMessage;
                } else if(captured == "NO"){
                    newCheckBox = messageBox;
                }
                $('button[name=partialRefund]').parent('.partial_refund_fields').prepend(newCheckBox);
            }
        });
    });

    function initLinkedCheckboxes(slipCheckboxId, checkboxId){
        /* Skip if "Generate a credit slip" is not present */
        if(!$(slipCheckboxId).length) {
            return false;
        }

        /* Make "Refund" checkbox dependent on "Generate a credit slip" checkbox */
        $(checkboxId).change(function() {
            if(this.checked) {
                $(slipCheckboxId).prop("checked", 1);
            }
        });

        /* Make "Generate a credit slip" checkbox dependent on "Refund" checkbox */
        $(slipCheckboxId).change(function() {
            if(!this.checked) {
                $(checkboxId).prop("checked", 0);
            }
        });
    }

    function modulePaymentActionChangeHandler(e) {
        var option_value = $(`#lunar_action option:selected`).val();
        if (option_value == 'refund') {
            $(`input[name="lunar_amount_to_refund"]`).show();
        } else {
            $(`input[name="lunar_amount_to_refund"]`).hide();
        }
    }

    function dysplayAlertMessage(html, type = 'success') {
        $('#alert').html(html);
        $('#alert').removeClass('alert-success')
            .removeClass('alert-info')
            .removeClass('alert-warning')
            .removeClass('alert-danger')
            .addClass(`alert-${type}`);
        $('#alert').show();
    }

    function submitModulePaymentActionClickHandler(e) {
        e.preventDefault();
        $('#alert').hide();
        var payment_action = $(`#lunar_action`).val();
        var errorFlag = false;
        if (payment_action == '') {
            var html = '<strong>Warning!</strong> Please select an action.';
            errorFlag = true;
        } else if (payment_action == 'refund') {
            var refund_amount = $(`input[name="lunar_amount_to_refund"]`).val();
            var html = '';
            if (refund_amount == '') {
                var html = '<strong>Warning!</strong> Please provide the refund amount.';
                errorFlag = true;
            }
        }
        if (errorFlag) {
            dysplayAlertMessage(html, 'warning');
            return false;
        }
        /* Make an AJAX call for payment action */
        $(e.currentTarget).button('loading');
        var url = $(`#lunar_form`).attr('action');
        $.ajax({
            url: url,
            type: 'POST',
            data: $(`#lunar_form`).serializeArray(),
            dataType: 'JSON',
            success: function (response) {
                $(e.currentTarget).button('reset');
                console.log(response);
                if (response.hasOwnProperty('success') && response.hasOwnProperty('message')) {
                    var message = response.message;
                    var html = '<strong>Success!</strong> ' + message;
                    
                    dysplayAlertMessage(html, 'success');

                    setTimeout(function () {
                        console.log('page reloaded');
                        location.reload();
                    }, 1500)
                } else if (response.hasOwnProperty('warning') && response.hasOwnProperty('message')) {
                    var message = response.message;
                    var html = '<strong>Warning!</strong> ' + message;
                    dysplayAlertMessage(html, 'warning');

                } else if (response.hasOwnProperty('error') && response.hasOwnProperty('message')) {
                    var message = response.message;
                    var html = '<strong>Error!</strong> ' + message;
                    dysplayAlertMessage(html, 'danger');
                }
            },
            error: function (response) {
                console.log(response);
            }
        });
    }
{/literal}
</script>


<div id="lunar" class="row" style="margin-top:5%;">
    <div class="panel">
        <form id="lunar_form"
                action="{$form_action_url}"
                method="post">
            <fieldset>
                <legend class="panel-heading">
                    <img src="../img/admin/money.gif" alt=""/>{l s='Process Lunar Payment' mod=lunarpayment }
                </legend>
                <div id="alert" class="alert" style="display: none;"></div>
                <div class="form-group margin-form">
                    <select class="form-control" id="lunar_action" name="lunar_action">
                        <option value="">{l s='-- Select Lunar Action --' mod=lunarpayment }</option>
                        {if $lunartransaction['captured'] == "NO"}
                            <option value="capture">{l s='Capture' mod=lunarpayment }</option>
                        {/if}
                        {if $lunartransaction['payed_amount'] != $lunartransaction['refunded_amount']}
                            <option value="refund">{l s='Refund' mod=lunarpayment }</option>
                        {/if}
                        {if $lunartransaction['captured'] == "NO"}
                            <option value="cancel">{l s='Cancel' mod=lunarpayment }</option>
                        {/if}
                    </select>
                </div>

                <div class="form-group margin-form">
                    <div class="col-md-12">
                        <input class="form-control" name="lunar_amount_to_refund" style="display: none;"
                            placeholder="{l s='Amount to refund' mod=lunarpayment }" type="text"/>
                    </div>
                </div>

                <div class="form-group margin-form">
                    <input class="pull-right btn btn-default" name="submit_lunar_action" id="submit_lunar_action"
                        type="submit" class="btn btn-primary" value="{l s='Process Action' mod=lunarpayment }"/>
                </div>
            </fieldset>
        </form>
    </div>
</div>
