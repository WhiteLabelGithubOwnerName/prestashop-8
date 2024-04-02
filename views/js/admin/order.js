/**
 * WhiteLabelName Prestashop
 *
 * This Prestashop module enables to process payments with WhiteLabelName (https://whitelabel-website.com).
 *
 * @author customweb GmbH (http://www.customweb.com/)
 * @copyright 2017 - 2024 customweb GmbH
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache Software License (ASL 2.0)
 */
jQuery(function ($) {

    function getOrderIdFromUrl(string)
    {
        let urlSegment = string.split('whitelabelmachinename')[1];
        return urlSegment.split('/')[1]
    }
    
    function initialiseDocumentButtons()
    {
        if ($('[data-original-title="Download WhiteLabelMachineName Invoice"]').length) {
            $('[data-original-title="Download Packing Slip"]').click(function (e) {
                e.preventDefault();
                let id_order = getOrderIdFromUrl($(this).attr('href'));
                window.open(whitelabelmachinename_admin_token + "&action=whiteLabelMachineNamePackingSlip&id_order=" + id_order, "_blank");
            });
        
            $('[data-original-title="Download WhiteLabelMachineName Invoice"]').click(function (e) {
                e.preventDefault();
                let id_order = getOrderIdFromUrl($(this).attr('href'));
                window.open(whitelabelmachinename_admin_token + "&action=whiteLabelMachineNameInvoice&id_order=" + id_order, "_blank");
            });
        
            $('#order_grid_table tr').each(function () {
                let $this = $(this);
                let $row = $this.closest('tr');
                let isWPayment = "0";
                let $paymentStatusCol = $row.find('.column-osname');
                let isWPaymentCol = $row.find('.column-is_w_payment').html();
                if (isWPaymentCol) {
                    isWPayment = isWPaymentCol.trim();
                }
                let paymentStatusText = $paymentStatusCol.find('.btn').text();
                if (!paymentStatusText.includes("Payment accepted") || isWPayment.includes("0")) {
                    $row.find('[data-original-title="Download WhiteLabelMachineName Invoice"]').hide();
                    $row.find('[data-original-title="Download Packing Slip"]').hide();
                }
            });
        }
    }

    function hideIsWPaymentColumn()
    {
        $('th').each(function () {
            let $this = $(this);
            if ($this.html().includes("is_w_payment")) {
                $('table tr').find('td:eq(' + $this.index() + '),th:eq(' + $this.index() + ')').remove();
                return false;
            }
        });
    }
    
    function moveWhiteLabelMachineNameDocuments()
    {
        var documentsTab = $('#whitelabelmachinename_documents_tab');
        documentsTab.children('a').addClass('nav-link');
    }
    
    function moveWhiteLabelMachineNameActionsAndInfo()
    {
        var managementBtn = $('a.whitelabelmachinename-management-btn');
        var managementInfo = $('span.whitelabelmachinename-management-info');
        var orderActions = $('div.order-actions');
        var panel = $('div.panel');
        
        managementBtn.each(function (key, element) {
            $(element).detach();
            orderActions.find('.order-navigation').before(element);
        });
        managementInfo.each(function (key, element) {
            $(element).detach();
            orderActions.find('.order-navigation').before(element);
        });
        //to get the styling of prestashop we have to add this
        managementBtn.after("&nbsp;\n");
        managementInfo.after("&nbsp;\n");
    }
    
    function registerWhiteLabelMachineNameActions()
    {
        $('#whitelabelmachinename_update').off('click.whitelabelmachinename').on(
            'click.whitelabelmachinename',
            updateWhiteLabelMachineName
        );
        $('#whitelabelmachinename_void').off('click.whitelabelmachinename').on(
            'click.whitelabelmachinename',
            showWhiteLabelMachineNameVoid
        );
        $("#whitelabelmachinename_completion").off('click.whitelabelmachinename').on(
            'click.whitelabelmachinename',
            showWhiteLabelMachineNameCompletion
        );
        $('#whitelabelmachinename_completion_submit').off('click.whitelabelmachinename').on(
            'click.whitelabelmachinename',
            executeWhiteLabelMachineNameCompletion
        );
    }
    
    function showWhiteLabelMachineNameInformationSuccess(msg)
    {
        showWhiteLabelMachineNameInformation(msg, whitelabelmachinename_msg_general_title_succes, whitelabelmachinename_btn_info_confirm_txt, 'dark_green', function () {
            window.location.replace(window.location.href);});
    }
    
    function showWhiteLabelMachineNameInformationFailures(msg)
    {
        showWhiteLabelMachineNameInformation(msg, whitelabelmachinename_msg_general_title_error, whitelabelmachinename_btn_info_confirm_txt, 'dark_red', function () {
            window.location.replace(window.location.href);});
    }
    
    function showWhiteLabelMachineNameInformation(msg, title, btnText, theme, callback)
    {
        $.jAlert({
            'type': 'modal',
            'title': title,
            'content': msg,
            'theme': theme,
            'replaceOtherAlerts': true,
            'closeOnClick': false,
            'closeOnEsc': false,
            'closeBtn': false,
            'btns': [
            {
                'text': btnText,
                'closeAlert': true,
                'theme': 'blue',
                'onClick': callback
            }
            ],
            'onClose': callback
        });
    }
    
    function updateWhiteLabelMachineName()
    {
        $.ajax({
            type:   'POST',
            dataType:   'json',
            url:    whiteLabelMachineNameUpdateUrl,
            success:    function (response, textStatus, jqXHR) {
                if ( response.success === 'true' ) {
                    location.reload();
                    return;
                } else if ( response.success === 'false' ) {
                    if (response.message) {
                        showWhiteLabelMachineNameInformation(response.message, msg_whitelabelmachinename_confirm_txt);
                    }
                    return;
                }
                showWhiteLabelMachineNameInformation(whitelabelmachinename_msg_general_error, msg_whitelabelmachinename_confirm_txt);
            },
            error:  function (jqXHR, textStatus, errorThrown) {
                showWhiteLabelMachineNameInformation(whitelabelmachinename_msg_general_error, msg_whitelabelmachinename_confirm_txt);
            }
        });
    }
    
        
    function showWhiteLabelMachineNameVoid(e)
    {
        e.preventDefault();
        $.jAlert({
            'type': 'modal',
            'title': whitelabelmachinename_void_title,
            'content': $('#whitelabelmachinename_void_msg').text(),
            'class': 'multiple_buttons',
            'closeOnClick': false,
            'closeOnEsc': false,
            'closeBtn': false,
            'btns': [
            {
                'text': whitelabelmachinename_void_btn_deny_txt,
                'closeAlert': true,
                'theme': 'black'
            },
            {
                'text': whitelabelmachinename_void_btn_confirm_txt,
                'closeAlert': true,
                'theme': 'blue',
                'onClick':  executeWhiteLabelMachineNameVoid

            }
            ],
            'theme':'blue'
        });
        return false;
    }

    function executeWhiteLabelMachineNameVoid()
    {
        showWhiteLabelMachineNameSpinner();
        $.ajax({
            type:   'POST',
            dataType:   'json',
            url:    whiteLabelMachineNameVoidUrl,
            success:    function (response, textStatus, jqXHR) {
                if ( response.success === 'true' ) {
                    showWhiteLabelMachineNameInformationSuccess(response.message);
                    return;
                } else if ( response.success === 'false' ) {
                    if (response.message) {
                        showWhiteLabelMachineNameInformationFailures(response.message);
                        return;
                    }
                }
                showWhiteLabelMachineNameInformationFailures(whitelabelmachinename_msg_general_error);
            },
            error:  function (jqXHR, textStatus, errorThrown) {
                showWhiteLabelMachineNameInformationFailures(whitelabelmachinename_msg_general_error);
            }
        });
        return false;
    }
    
    
    function showWhiteLabelMachineNameSpinner()
    {
        $.jAlert({
            'type': 'modal',
            'title': false,
            'content': '<div class="whitelabelmachinename-loader"></div>',
            'closeOnClick': false,
            'closeOnEsc': false,
            'closeBtn': false,
            'class': 'unnoticeable',
            'replaceOtherAlerts': true
        });
    
    }
    
    function showWhiteLabelMachineNameCompletion(e)
    {
        e.preventDefault();
        $.jAlert({
            'type': 'modal',
            'title': whitelabelmachinename_completion_title,
            'content': $('#whitelabelmachinename_completion_msg').text(),
            'class': 'multiple_buttons',
            'closeOnClick': false,
            'closeOnEsc': false,
            'closeBtn': false,
            'btns': [
            {
                'text': whitelabelmachinename_completion_btn_deny_txt,
                'closeAlert': true,
                'theme': 'black'
            },
            {
                'text': whitelabelmachinename_completion_btn_confirm_txt,
                'closeAlert': true,
                'theme': 'blue',
                'onClick': executeWhiteLabelMachineNameCompletion
            }
            ],
            'theme':'blue'
        });

        return false;
    }
    
    
    function executeWhiteLabelMachineNameCompletion()
    {
        showWhiteLabelMachineNameSpinner();
        $.ajax({
            type:   'POST',
            dataType:   'json',
            url:    whiteLabelMachineNameCompletionUrl,
            success:    function (response, textStatus, jqXHR) {
                if ( response.success === 'true' ) {
                    showWhiteLabelMachineNameInformationSuccess(response.message);
                    return;
                } else if ( response.success === 'false' ) {
                    if (response.message) {
                        showWhiteLabelMachineNameInformationFailures(response.message);
                        return;
                    }
                }
                showWhiteLabelMachineNameInformationFailures(whitelabelmachinename_msg_general_error);
            },
            error:  function (jqXHR, textStatus, errorThrown) {
                showWhiteLabelMachineNameInformationFailures(whitelabelmachinename_msg_general_error);
            }
        });
        return false;
    }
    
    function whiteLabelMachineNameTotalRefundChanges()
    {
        var generateDiscount =  $('.standard_refund_fields').find('#generateDiscount').attr("checked") === 'checked';
        var sendOffline = $('#whitelabelmachinename_refund_offline_cb_total').attr("checked") === 'checked';
        whiteLabelMachineNameRefundChanges('total', generateDiscount, sendOffline);
    }
    
    function whiteLabelMachineNamePartialRefundChanges()
    {
    
        var generateDiscount = $('.partial_refund_fields').find('#generateDiscountRefund').attr("checked") === 'checked';
        var sendOffline = $('#whitelabelmachinename_refund_offline_cb_partial').attr("checked")  === 'checked';
        whiteLabelMachineNameRefundChanges('partial', generateDiscount, sendOffline);
    }
    
    function whiteLabelMachineNameRefundChanges(type, generateDiscount, sendOffline)
    {
        if (generateDiscount) {
            $('#whitelabelmachinename_refund_online_text_'+type).css('display','none');
            $('#whitelabelmachinename_refund_offline_span_'+type).css('display','block');
            if (sendOffline) {
                $('#whitelabelmachinename_refund_offline_text_'+type).css('display','block');
                $('#whitelabelmachinename_refund_no_text_'+type).css('display','none');
            } else {
                $('#whitelabelmachinename_refund_no_text_'+type).css('display','block');
                $('#whitelabelmachinename_refund_offline_text_'+type).css('display','none');
            }
        } else {
            $('#whitelabelmachinename_refund_online_text_'+type).css('display','block');
            $('#whitelabelmachinename_refund_no_text_'+type).css('display','none');
            $('#whitelabelmachinename_refund_offline_text_'+type).css('display','none');
            $('#whitelabelmachinename_refund_offline_span_'+type).css('display','none');
            $('#whitelabelmachinename_refund_offline_cb_'+type).attr('checked', false);
        }
    }
    
    function handleWhiteLabelMachineNameLayoutChanges()
    {
        var addVoucher = $('#add_voucher');
        var addProduct = $('#add_product');
        var editProductChangeLink = $('.edit_product_change_link');
        var descOrderStandardRefund = $('#desc-order-standard_refund');
        var standardRefundFields = $('.standard_refund_fields');
        var partialRefundFields = $('.partial_refund_fields');
        var descOrderPartialRefund = $('#desc-order-partial_refund');

        if ($('#whitelabelmachinename_is_transaction').length > 0) {
            addVoucher.remove();
        }
        if ($('#whitelabelmachinename_remove_edit').length > 0) {
            addProduct.remove();
            addVoucher.remove();
            editProductChangeLink.closest('div').remove();
            $('.panel-vouchers').find('i.icon-minus-sign').closest('a').remove();
        }
        if ($('#whitelabelmachinename_remove_cancel').length > 0) {
            descOrderStandardRefund.remove();
        }
        if ($('#whitelabelmachinename_changes_refund').length > 0) {
            $('#refund_total_3').closest('div').remove();
            standardRefundFields.find('div.form-group').after($('#whitelabelmachinename_refund_online_text_total'));
            standardRefundFields.find('div.form-group').after($('#whitelabelmachinename_refund_offline_text_total'));
            standardRefundFields.find('div.form-group').after($('#whitelabelmachinename_refund_no_text_total'));
            standardRefundFields.find('#spanShippingBack').after($('#whitelabelmachinename_refund_offline_span_total'));
            standardRefundFields.find('#generateDiscount').off('click.whitelabelmachinename').on('click.whitelabelmachinename', whiteLabelMachineNameTotalRefundChanges);
            $('#whitelabelmachinename_refund_offline_cb_total').on('click.whitelabelmachinename', whiteLabelMachineNameTotalRefundChanges);
        
            $('#refund_3').closest('div').remove();
            partialRefundFields.find('button').before($('#whitelabelmachinename_refund_online_text_partial'));
            partialRefundFields.find('button').before($('#whitelabelmachinename_refund_offline_text_partial'));
            partialRefundFields.find('button').before($('#whitelabelmachinename_refund_no_text_partial'));
            partialRefundFields.find('#generateDiscountRefund').closest('p').after($('#whitelabelmachinename_refund_offline_span_partial'));
            partialRefundFields.find('#generateDiscountRefund').off('click.whitelabelmachinename').on('click.whitelabelmachinename', whiteLabelMachineNamePartialRefundChanges);
            $('#whitelabelmachinename_refund_offline_cb_partial').on('click.whitelabelmachinename', whiteLabelMachineNamePartialRefundChanges);
        }
        if ($('#whitelabelmachinename_completion_pending').length > 0) {
            addProduct.remove();
            addVoucher.remove();
            editProductChangeLink.closest('div').remove();
            descOrderPartialRefund.remove();
            descOrderStandardRefund.remove();
        }
        if ($('#whitelabelmachinename_void_pending').length > 0) {
            addProduct.remove();
            addVoucher.remove();
            editProductChangeLink.closest('div').remove();
            descOrderPartialRefund.remove();
            descOrderStandardRefund.remove();
        }
        if ($('#whitelabelmachinename_refund_pending').length > 0) {
            descOrderStandardRefund.remove();
            descOrderPartialRefund.remove();
        }
        moveWhiteLabelMachineNameDocuments();
        moveWhiteLabelMachineNameActionsAndInfo();
    }
    
    function init()
    {
        handleWhiteLabelMachineNameLayoutChanges();
        registerWhiteLabelMachineNameActions();
        initialiseDocumentButtons();
        hideIsWPaymentColumn();
    }
    
    init();
});
