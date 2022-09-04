<?php

declare(strict_types=1);


use Dealt\Module\Dealtmodule\Utils\DealtTools;
use Dealt\Module\Dealtmodule\Model\DealtOffer;
class DealtCart
{
    private $module;
    private $builder;
    /**
     * @var bool
     */
    private $cartSanitized;

    public function __construct()
    {
        $this->module = Module::getInstanceByName('dealtmodule');
        $this->builder = $this->module->getBuilder('DealtCartProductRef');
    }

    /**
     * Attaches a dealt product to a product
     * currently in the prestashop cart and syncs
     * their quantities
     *
     * @param string $dealtOfferId
     * @param int $productId
     * @param int $productAttributeId
     *
     * @return bool
     */
    public function addDealtOfferToCart($dealtOfferId, $productId, $productAttributeId)
    {

        $offer = DealtOffer::findOneByUUID($dealtOfferId);
        if ($offer == null) {
            $this->module->returnResultToAjax([$this->module->l('Unknown Dealt offer id')]);
        }


        $cart = \Context::getContext()->cart;
        if (!isset($cart) || !$cart->id) {
            if (\Context::getContext()->customer->id) {
                $id_cart = \Db::getInstance()->getValue('SELECT id_cart FROM ' . _DB_PREFIX_ . 'cart WHERE id_customer=' . (int)\Context::getContext()->customer->id . ' ORDER BY id_cart DESC');
                $cart = new Cart($id_cart);
            }

            if (\Context::getContext()->customer->id_guest) {
                $id_cart = \Db::getInstance()->getValue('SELECT id_cart FROM ' . _DB_PREFIX_ . 'cart WHERE id_guest=' . \Context::getContext()->customer->id_guest . ' ORDER BY id_cart DESC');
                $cart = new Cart($id_cart);
            }
        }

        if (!isset($cart) || !$cart->id || $cart->OrderExists()) {
            $cart = new Cart();
            $cart->id_lang = (int)\Context::getContext()->cookie->id_lang;
            $cart->id_currency = (int)(\Context::getContext()->cookie->id_currency ?? ConfigurationCore::get('PS_CURRENCY_DEFAULT'));
            $cart->id_guest = (int)\Context::getContext()->cookie->id_guest;
            $cart->id_shop_group = (int)\Context::getContext()->shop->id_shop_group;
            $cart->id_shop = \Context::getContext()->shop->id;
            if (\Context::getContext()->cookie->id_customer) {
                $cart->id_customer = (int)\Context::getContext()->cookie->id_customer;
                $cart->id_address_delivery = (int)Address::getFirstCustomerAddressId($cart->id_customer);
                $cart->id_address_invoice = (int)$cart->id_address_delivery;
            } else {
                $cart->id_address_delivery = 0;
                $cart->id_address_invoice = 0;
            }
            // Needed if the merchant want to give a free product to every visitors
            \Context::getContext()->cart = $cart;
            CartRule::autoAddToCart(\Context::getContext());
            $cart->id_currency = (int)(\Context::getContext()->cookie->id_currency ?? ConfigurationCore::get('PS_CURRENCY_DEFAULT'));
            $cart->add();
            \Context::getContext()->cart = $cart;
            \Context::getContext()->cookie->__set('id_cart', (int)$cart->id);
        }

        \Db::getInstance()->execute('DELETE FROM ' . _DB_PREFIX_ . 'dealt_cart_product_ref
                   WHERE id_cart=' . (int)$cart->id . '
                  AND id_offer=' . (int)$offer->id);


        $cartProduct = DealtTools::getProductFromCart($cart, $productId, $productAttributeId);
        $quantity = (int)Tools::getValue('qty');
        if ($cartProduct == null) {
            try {
                $cart->updateQty(
                    Tools::getValue('qty'),
                    $productId,
                    $productAttributeId,
                    false,
                    'up',
                    0,
                    null,
                    false
                );

            } catch (Throwable $e) {
                DealtModuleLogger::log(
                    'Cannot update cart',
                    DealtModuleLogger::TYPE_ERROR,
                    ['Error' => $e]
                );
            }
        }else{
            $quantity = (int)$cartProduct['quantity'];
        }
        for ($x = 1; $x <= $quantity; $x++) {
            $this->builder->createOrUpdate(
                [
                    'id_cart' => $cart->id,
                    'id_product' => $productId,
                    'id_product_attribute' => $productAttributeId,
                    'id_offer' => $offer->id
                ]
            );
        }
        $cartProduct = DealtTools::getProductFromCart($cart, $offer->id_dealt_product);

        if ($cartProduct == null) {
            $cart->updateQty(
                (isset($cartProduct['quantity']) && $cartProduct['quantity'] > 1) ? $cartProduct['quantity'] : 1,
                $offer->id_dealt_product,
                null,
                false
            );
        }

        return true;

    }

    /**
     * Sanitization of prestashop cart against dealt constraints
     * - get all dealt cart products
     *
     * @param int $cartId
     *
     * @return void
     */
    public function sanitizeDealtCart($cartId)
    {
        if ($this->cartSanitized) {
            return;
        }

        $this->cartSanitized = true;

        $cart = new Cart($cartId);
        $offers = DealtTools::getDealtOffersFromCart($cart);
        $cartProductsIndex = DealtTools::indexCartProducts($cart);

        /*
                 * If we have dealt offers present in the cart
                 * we need to ensure their quantities match their
                 * attached products
                 */
        foreach ($offers as $offer) {
            $quantity=(int)\Db::getInstance()->getValue(
                "SELECT COUNT(1) FROM "._DB_PREFIX_."dealt_cart_product_ref cpf
                    INNER JOIN "._DB_PREFIX_."cart_product cp ON (cp.id_product=cpf.id_product AND  cpf.id_product_attribute=cp.id_product_attribute)
                     WHERE cpf.id_cart=".(int)$cart->id. " AND cp.id_cart=".(int)$cart->id."
                    AND id_offer=".(int)$offer->id."
                 "
            );
            $offerProductId = $offer->id_dealt_product;
            $newQty = (int)$quantity;
            $currentQty = (int)$cartProductsIndex[$offerProductId][0]['quantity'];

            if ($newQty != $currentQty) {
                $delta = abs($newQty - $currentQty);

                $cart->updateQty($delta, $offerProductId, null, false, ($newQty >  $currentQty) ? 'up' : 'down');
            }
        }
    }



    /**
     * @param $data
     * @return bool
     */
    public function sanitizeDealtCartQuantities($data)
    {
        $cart=$data['cart'];
        $product=$data['product'];
        $id_product=$product->id;
        $id_product_attribute=$data['id_product_attribute'];
        $quantity=$data['quantity'];
        $id_offer=(int)\Db::getInstance()->getValue(
            "SELECT id_offer FROM "._DB_PREFIX_."dealt_cart_product_ref
                 WHERE id_cart=".(int)$cart->id. "
                AND id_product=".(int)$id_product."
                AND id_product_attribute=".(int)$id_product_attribute."
                 "
        );
        if($id_offer && $data['operator']==='up'){
            for ($x = 1; $x <= $quantity; $x++) {
                $this->builder->createOrUpdate(
                    [
                        'id_cart' => $cart->id,
                        'id_product' => $id_product,
                        'id_product_attribute' => $id_product_attribute,
                        'id_offer' => $id_offer
                    ]
                );
            }
            return true;
        }
        if($id_offer && $data['operator']==='down'){
            for ($x = 1; $x <= $quantity; $x++) {
                $this->builder->removeRow(
                    [
                        'id_cart' => $cart->id,
                        'id_product' => $id_product,
                        'id_product_attribute' => $id_product_attribute,
                        'id_offer' => $id_offer
                    ]
                );
            }
            return true;
        }
        return false;
    }
}