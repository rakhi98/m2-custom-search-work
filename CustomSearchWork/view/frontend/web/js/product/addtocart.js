/**
 * CedCommerce
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the End User License Agreement (EULA)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://cedcommerce.com/license-agreement.txt
 *
 * @category    Ced
 * @package     Ced_CsMarketplace
 * @author 		CedCommerce Core Team <connect@cedcommerce.com>
 * @copyright   Copyright CedCommerce (http://cedcommerce.com/)
 * @license      http://cedcommerce.com/license-agreement.txt
 */
require(
    [
    'jquery',
    'Magento_Ui/js/modal/modal',
    'mage/validation',
    ], function ($, modal) {
        'use strict';

        let dataForm = $('[data-role="tocart-form"]');
	    dataForm.mage('validation', {}).find('input:text').attr('autocomplete', 'off');

        $('.dec_qty').on('click', function () {
            let currentValue = $(this).parent().find('.qty').val();
            if (currentValue > 1) {
                $(this).parent().find('.qty').val(parseInt(currentValue) - 1);
            } else {
                $(this).parent().parent().find('.qty').val(1);
            }
        });

        $('.inc_qty').on('click', function () {
            let currentValue = $(this).parent().find('.qty').val();
            $(this).parent().find('.qty').val(parseInt(currentValue) + 1);
        });

        $('.tocart').on('click', function () {
        let cartForm = $(this).parent().parent();
        let cartFormUrl = cartForm.attr('action');
        if (~cartFormUrl.indexOf('cart/add')) {
            if (cartForm.validation('isValid'))
            {
                cartForm.submit();
            }
        } else {
            $('#product-view-iframe').html('');
            let viewUrl = cartForm.find('.product_view_url').val() + '&qty='+cartForm.find('.qty').val();
            var options = {
                type: 'popup',
                responsive: true,
                innerScroll: true,
                buttons: [{
                    text: $.mage.__('close'),
                    class: 'product-view-modal',
                    click: function () {
                        window.location.reload();
                    },
                }],
                closed: function () {
                    window.location.reload();
                },
            };
            var popup = modal(options, $('#product-view-iframe'));
            $("#product-view-iframe").modal("openModal");
            $('<iframe/>', {
                id: 'productview',
                src: viewUrl,
                width: '100%',
                height: '300px'
            }).appendTo('#product-view-iframe');
            $('#product-view-iframe').trigger('contentUpdated');
            $('[data-block="minicart"]').trigger('contentUpdated');
        }
    });
});