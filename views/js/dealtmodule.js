
$(document).ready(function () {
    $('#dealt_id_offer').closest('.form-group').append(
        '<div class="col-lg-4"><button type="button" name="generate" class="btn btn-default" onclick="uuidv4()" ">Generate UUID v4</button></div>'
    );
});
function uuidv4() {
    let uuidv4= ([1e7]+-1e3+-4e3+-8e3+-1e11).replace(/[018]/g, c =>
        (c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> c / 4).toString(16)
    );
    $("#dealt_id_offer").empty().val(uuidv4);
    return true;
}


function actionAjaxAddOffer(elm) {
    let offer_id = $(elm).attr('data-dealt-offer-uuid');
    let zip_code = $(elm).closest('div').find('#dealt-zipcode-autocomplete').val();
    let offer_product_id=$(elm).attr('data-dealt-offer-product-id');

    if (zip_code) {
        $.ajax({
            type: "POST",
            async: true,
            dataType: "json",
            url: dealt_module_ajax_uri,
            data: 'advdealttoken=' + dealt_module_ajax_token + '&action=check_offer_availability' +
                '&id_offer=' + offer_id + '&zip_code=' + zip_code,
            success: function (resp) {
                if (resp.status == 200 && resp.response.available === true) {

                    actionAjaxAddToCart(resp.arguments.id_offer, offer_product_id);
                }
                if (typeof resp.response.reason !== 'undefined') {
                    $('#dealt-offer-error').text(resp.response.reason).show();
                }
            }
        });
    }
    return false;
}

function actionAjaxAddToCart(id_offer, offer_product_id) {
    var $form = $('input[name=id_customization]').closest('form');
    const ps = window.prestashop;
    let idProduct=$('#dealt-offer-submit').attr('data-dealt-product-id');
    let idProductAttribute=$('#dealt-offer-submit').attr('data-dealt-product-attribute-id');
    $.ajax({
        type: "POST",
        async: true,
        dataType: "json",
        url: dealt_module_ajax_uri,
        data: $form.serialize()+'&advdealttoken=' + dealt_module_ajax_token + '&action=add_to_cart' +
            '&id_offer=' + id_offer+'&id_cart=' + dealt_module_cart + '&id_product_attribute=' +idProductAttribute,
        success: function (resp) {
            ps.emit("updateCart", {
                reason: {
                    idCustomization: 0,
                    idProduct: idProduct,
                    idProductAttribute: idProductAttribute,
                    linkAction: "add-to-cart",
                },
                resp: { success: true },
            })
        }
    });
}
