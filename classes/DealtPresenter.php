<?php

declare(strict_types=1);

use Dealt\Module\Dealtmodule\Model\DealtCartProductRef;
use Dealt\Module\Dealtmodule\Model\DealtOffer;
use Dealt\Module\Dealtmodule\Model\DealtOfferCategory;
use Dealt\Module\Dealtmodule\Utils\DealtTools;

class DealtPresenter
{
    const imageType='jpg';
    /**
     * Present dealt offer data for a product
     * id / attribute_id pair
     *
     * @param Cart $cart
     * @param int $productId
     * @param int|null $productAttributeId
     * @param $id_lang
     * @param int|null $orderId
     *
     * @return mixed
     */
    public static function present(\Cart $cart, $productId, $productAttributeId, $id_lang, $id_currency,  $orderId = null)
    {
        try{
            $offer=self::getOfferFromProductCategories($productId);
            if($offer === null){
                return [];
            }

            $cartProduct = DealtTools::getProductFromCart($cart, $productId, $productAttributeId);

            \StockAvailable::setQuantity($offer->id_dealt_product, 0, 300);

            $quantity = Tools::getValue('qty', (isset($cartProduct['quantity']) ? $cartProduct['quantity'] : null));
            $cover=\Product::getCover((int)$offer->id_dealt_product);
            $image_url = \Context::getContext()->link->getImageLink((int)$offer->id_dealt_product, $cover['id_image'], 'medium_default');
            $id_lang=\Context::getContext()->language->id ?? (int) Configuration::get('PS_LANG_DEFAULT');
            return [
                'offer' => array_merge([
                    'title' => $offer->title_offer[$id_lang],
                    'description' => $offer->getDealtProduct()->description_short[$id_lang],
                    'dealtOfferId' => $offer->id,
                    'dealtOfferUUIDV4' => $offer->dealt_id_offer,
                    'dealtOfferProduct' => $offer->id_dealt_product,
                    'price' => DealtTools::getFormattedPrice($offer, $id_currency, $quantity),
                    'unitPriceFormatted' => DealtTools::getFormattedPrice($offer, $id_currency),
                    'unitPrice' => DealtTools::getPrice($productId, $productAttributeId),
                    'image' => ($image_url && file_exists($image_url)) ? $image_url : _PS_BASE_URL_.'/modules/dealtmodule/views/img/default.png',
                    'product' => $offer->getDealtProduct(),
                ], []),
                'binding' => [
                    'productId' => $productId,
                    'productAttributeId' => $productAttributeId,
                    'cartProduct' => DealtTools::getProductFromCart($cart, $productId, $productAttributeId),
                    'cartOffer' => DealtTools::getProductFromCart($cart, $offer->id_dealt_product),
                    'data' => array_merge(
                        [
                            'cartId' => $cart->id,
                            'productId' => $productId,
                            'offer' => $offer,
                        ],
                        $productAttributeId != null ? ['productAttributeId' => $productAttributeId] : []
                    ),
                    'cartRef' => DealtCartProductRef::searchByCriteria($cart->id, $productId, $offer->id, $productAttributeId),
                ],
            ];
        }catch (Throwable $e){
            DealtModuleLogger::log(
                'Could not install module',
                DealtModuleLogger::TYPE_ERROR,
                [
                    'message' => $e->getMessage(),
                    'line'=>$e->getLine(),
                    'code'=>$e->getCode(),
                    'trace'=>$e->getTrace()
                ]
            );
        }
        return [];

    }

    /**
     * @param $productId
     * @return DealtOffer|null
     */
    private static function getOfferFromProductCategories($productId)
    {
        $product = new \Product($productId);

        if (!\Validate::isLoadedObject($product)) {
            return null;
        }
        $id_offer=DealtOfferCategory::getProductOffer($product);
        if(empty($id_offer)){
            return null;
        }
        $offer= new DealtOffer($id_offer);

        if (!\Validate::isLoadedObject($offer)) {
            return null;
        }

        return $offer;
    }
}