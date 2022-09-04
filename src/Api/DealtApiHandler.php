<?php
declare(strict_types=1);

namespace Dealt\Module\Dealtmodule\Api;
use Dealt\DealtSDK\DealtClient;
use Dealt\DealtSDK\DealtEnvironment;
use Dealt\DealtSDK\Exceptions\GraphQLException;
use Dealt\DealtSDK\Exceptions\GraphQLFailureException;
use Dealt\DealtSDK\GraphQL\Types\Object\Mission;
use Dealt\DealtSDK\GraphQL\Types\Object\OfferAvailabilityQuerySuccess;
use Dealt\Module\Dealtmodule\Api\DealtAPIAction;
use Dealt\Module\Dealtmodule\Model\DealtMission;
use Dealt\Module\Dealtmodule\Model\DealtOffer;
use Order;
use Dealt\Module\Dealtmodule\Utils\DealtTools;
use Dealt\Module\Dealtmodule\Model\DealtCartProductRef;
class DealtApiHandler extends DealtGenericClient
{
    /**
     * Checks the availability of a Dealt offer
     *
     * @param string $offer_id
     * @param string $zip_code
     * @param string $country
     *
     * @return array
     */
    public function checkAvailability($offer_id, $zip_code, $country = 'France')
    {
        try {
            $offer = $this->getClient()->offers->availability([
                'offer_id' => $offer_id,
                'address' => [
                    'country' => $country,
                    'zip_code' => substr($zip_code, 0, 5),
                ],
            ]);

            if ($offer !== null) {
                return $this->handleResponse(
                    "Offer is available",
                    'availability',

                    [
                        'id_offer' => $offer_id,
                        'zip_code' => $zip_code,
                        'country' => $country
                    ],

                    array_merge(
                        [
                            'available' => $offer->available,
                            'net_price' => $offer->net_price,
                            'gross_price' => $offer->gross_price,
                            'vat_price' => $offer->vat_price
                        ],
                        $offer->available ? [] : ['reason' => $this->module->l('Offer unavailable for the requested zip code')]
                    )

                );
            }
        } catch (GraphQLFailureException $e) {
            $this->handleException($e);
        } catch (GraphQLException $e) {
            $this->handleException($e);
        } catch (\Exception $e) {
            $this->handleException($e);
        } catch (\Throwable $e) {
            $this->handleException($e);
        }
        return null;
    }

    /**
     * @param $orderId
     */
    public function handleOrderPayment($orderId)
    {

        try {
            $order = new Order($orderId);
            $cartId = Order::getCartIdStatic($order->id, $order->id_customer);
            $cart = new \Cart($cartId);

            $deliveryAddress = new \Address($order->id_address_delivery);
            $zipCode = $deliveryAddress->postcode;
            $country = $deliveryAddress->country;
            $offers = DealtTools::getDealtOffersFromCart($cart);

            $results = [];

            /*
             * Iterate over every offer in the current order cart
             * and construct the necessary data for communicating
             * with the Dealt API
             */
            foreach ($offers as $offer) {
                try {
                    /** @var DealtCartProductRef */
                    $ref = DealtTools::getDealtCartRef($cartId, $offer);
                    $results[$offer->dealt_id_offer] = [
                        'ref' => $ref,
                        'cartProduct' => DealtTools::getProductFromCart(
                            $cart,
                            $ref->id_product,
                            $ref->id_product_attribute
                        ),
                        'offerAvailability' => $this->checkAvailability(
                            $offer->dealt_id_offer,
                            $zipCode,
                            $country
                        ),
                        'offer' => $offer,
                    ];

                    if (!empty($subResult = $results[$offer->dealt_id_offer])) {
                        $quantity = $subResult['cartProduct']['quantity'];
                        $productId = $subResult['cartProduct']['id_product'];
                        $productAttributeId = $subResult['cartProduct']['id_product_attribute'];
                        $offer = $subResult['offer'];
                        $offerAvailability = (object)$subResult['offerAvailability']['response'];
                        $checked = $offerAvailability != null;

                        $available = $checked ? $offerAvailability->available : false;
                        $vatPrice = $checked ? $offerAvailability->vat_price->amount : 0;
                        $grossPrice = $checked ? $offerAvailability->gross_price->amount : 0;
                        $netPrice = $checked ? $offerAvailability->net_price->amount : 0;
                        $match = (bool)\Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                            "SELECT 1 FROM " . _DB_PREFIX_ . "dealt_mission
                      WHERE id_offer=" . (int)$offer->id . "
                      AND   id_product=" . (int)$productId . "
                      AND   id_product_attribute=" . (int)$productAttributeId . "
                      AND   id_order=" . (int)$orderId
                        );

                        $product = new \Product($productId);

                        /* make sure missions have not already been submitted */
                        if (!$match) {
                            foreach (range(1, $quantity) as $_) {
                                $mission = $available ? $this->submitMission($offer, $order, $product) : null;
                                $status = $mission != null ? $mission->status : 'ERROR_UNABLE_TO_SUBMIT';
                                $dealtMissionId = $mission != null ? $mission->id : '-';
                                $missionObject = new DealtMission();
                                $missionObject->id_order = $orderId;
                                $missionObject->id_product = $productId;
                                $missionObject->id_product_attribute = $productAttributeId;
                                $missionObject->id_dealt_product = $offer->id_dealt_product;
                                $missionObject->id_offer = $offer->id;
                                $missionObject->dealt_gross_price_mission = $grossPrice;
                                $missionObject->dealt_id_mission = $dealtMissionId;
                                $missionObject->dealt_net_price_mission = $netPrice;
                                $missionObject->dealt_vat_price_mission = $vatPrice;
                                $missionObject->dealt_status_mission = $status;
                                $missionObject->save();
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    $this->handleException($e);
                }

            }
        } catch (\Throwable $e) {
            $this->handleException($e);
        }


    }

    /**
     * @param DealtOffer $offer
     *
     * @return Mission|null
     */
    public function submitMission(DealtOffer $offer, \Order $order, \Product $product)
    {
        $customer = $order->getCustomer();
        $address = new \Address((int)$order->id_address_delivery);
        $countryCode = (new \Country($address->id_country))->iso_code;

        $phone = DealtTools::formatPhoneNumberE164($address->phone, $countryCode);
        $phoneMobile = DealtTools::formatPhoneNumberE164($address->phone_mobile, $countryCode);

        if (!$phone && !$phoneMobile) {
            DealtModuleLogger::log('invalid phone number supplied', DealtModuleLogger::TYPE_ERROR, [
                'status' => false,
                'phone' => $address->phone,
                'countryCode' => $countryCode,
                'phone_mobile' => $address->phone_mobile
            ]);
            return null;
        }
        if ($phone && !\Validate::isPhoneNumber($phone)) {
            DealtModuleLogger::log('invalid phone number supplied', DealtModuleLogger::TYPE_ERROR, [
                'status' => false,
                'phone' => $address->phone,
                'countryCode' => $countryCode,
                'phone_mobile' => $address->phone_mobile
            ]);
            return null;
        }
        if ($phoneMobile && !\Validate::isPhoneNumber($phoneMobile)) {
            DealtModuleLogger::log('invalid mobile phone number supplied', DealtModuleLogger::TYPE_ERROR, [
                'status' => false,
                'phone' => $address->phone,
                'countryCode' => $countryCode,
                'phone_mobile' => $address->phone_mobile
            ]);
            return null;
        }
        try {
            $result = $this->getClient()->missions->submit([
                'offer_id' => $offer->dealt_id_offer,
                'address' => [
                    'country' => $address->postcode,
                    'zip_code' => $address->country,
                    'city' => $address->city,
                    'street1' => $address->address1,
                    'street2' => $address->address2,
                ],
                'customer' => [
                    'first_name' => $customer->firstname,
                    'last_name' => $customer->lastname,
                    'email_address' => $customer->email,
                    'phone_number' => $phone != false ? $phone : $phoneMobile,
                    'customerProductPrice' => \Product::getPriceStatic($product->id, false),
                    'customerServicePrice' => \Product::getPriceStatic($offer->id_dealt_product, false),
                ],
                'webHookUrl' => \Context::getContext()->link->getModuleLink(
                    strtolower(DealtModule::class),
                    'api',
                    ['ajax' => true, 'action' => DealtApiAction::$MISSION_WEBHOOK, 'token'=>sha1(_COOKIE_KEY_ . $this->module->name)]
                ),
                'extraDetails' => (new \Link())->getProductLink($product),
            ]);

            $this->handleResponse(
                'Successfully mission submit',
                'submitMission',
                [
                    'id_offer' => $offer,
                    'id_order'=>$order->id,
                    'id_product'=>$product->id

                ],
                $result->mission
            );
            return $result->mission;
        } catch (GraphQLFailureException $e) {
            $this->handleException($e);
        } catch (GraphQLException $e) {
            $this->handleException($e);
        } catch (\Exception $e) {
            $this->handleException($e);
        } catch (\Throwable $e) {
            $this->handleException($e);
        }
        return null;
    }


    /**
     * @param string $missionId
     *
     * @return Mission|null
     */
    public function cancelMission($missionId)
    {
        try {
            $result = $this->getClient()->missions->cancel($missionId);
            return $result->mission;
        } catch (GraphQLFailureException $e) {
            $this->handleException($e);
            return $e->getMessage();
        } catch (GraphQLException $e) {
            $this->handleException($e);
            return $e->getMessage();
        } catch (\Exception $e) {
            $this->handleException($e);
            return $e->getMessage();
        }
    }


}
