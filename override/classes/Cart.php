<?php

class Cart extends CartCore
{

    /**
     * Check if the Cart contains the given Product (Attribute).
     *
     * @param int $idProduct Product ID
     * @param int $idProductAttribute ProductAttribute ID
     * @param int $idCustomization Customization ID
     * @param int $idAddressDelivery Delivery Address ID
     *
     * @return array quantity index     : number of product in cart without counting those of pack in cart
     *               deep_quantity index: number of product in cart counting those of pack in cart
     */
    public function getProductQuantity($idProduct, $idProductAttribute = 0, $idCustomization = 0, $idAddressDelivery = 0)
    {
        $productIsPack = Pack::isPack($idProduct);
        $defaultPackStockType = Configuration::get('PS_PACK_STOCK_TYPE');
        $packStockTypesAllowed = [
            Pack::STOCK_TYPE_PRODUCTS_ONLY,
            Pack::STOCK_TYPE_PACK_BOTH,
        ];
        $packStockTypesDefaultSupported = (int) in_array($defaultPackStockType, $packStockTypesAllowed);
        $firstUnionSql = 'SELECT cp.`quantity` as first_level_quantity, 0 as pack_quantity
          FROM `' . _DB_PREFIX_ . 'cart_product` cp';
        $secondUnionSql = 'SELECT 0 as first_level_quantity, cp.`quantity` * p.`quantity` as pack_quantity
          FROM `' . _DB_PREFIX_ . 'cart_product` cp' .
            ' JOIN `' . _DB_PREFIX_ . 'pack` p ON cp.`id_product` = p.`id_product_pack`' .
            ' JOIN `' . _DB_PREFIX_ . 'product` pr ON p.`id_product_pack` = pr.`id_product`';

        if ($idCustomization) {
            $customizationJoin = '
                LEFT JOIN `' . _DB_PREFIX_ . 'customization` c ON (
                    c.`id_product` = cp.`id_product`
                    AND c.`id_product_attribute` = cp.`id_product_attribute`
                )';
            $firstUnionSql .= $customizationJoin;
            $secondUnionSql .= $customizationJoin;
        }
        $commonWhere = '
            WHERE cp.`id_product_attribute` = ' . (int) $idProductAttribute . '
            AND cp.`id_customization` = ' . (int) $idCustomization . '
            AND cp.`id_cart` = ' . (int) $this->id;

        if (Configuration::get('PS_ALLOW_MULTISHIPPING') && $this->isMultiAddressDelivery()) {
            $commonWhere .= ' AND cp.`id_address_delivery` = ' . (int) $idAddressDelivery;
        }

        if ($idCustomization) {
            $commonWhere .= ' AND c.`id_customization` = ' . (int) $idCustomization;
        }
        $firstUnionSql .= $commonWhere;
        $firstUnionSql .= ' AND cp.`id_product` = ' . (int) $idProduct;
        $secondUnionSql .= $commonWhere;
        $secondUnionSql .= ' AND p.`id_product_item` = ' . (int) $idProduct;
        $secondUnionSql .= ' AND (pr.`pack_stock_type` IN (' . implode(',', $packStockTypesAllowed) . ') OR (
            pr.`pack_stock_type` = ' . Pack::STOCK_TYPE_DEFAULT . '
            AND ' . $packStockTypesDefaultSupported . ' = 1
        ))';
        $parentSql = 'SELECT
            COALESCE(SUM(first_level_quantity) + SUM(pack_quantity), 0) as deep_quantity,
            COALESCE(SUM(first_level_quantity), 0) as quantity
          FROM (' . $firstUnionSql . ' UNION ' . $secondUnionSql . ') as q';

        return Db::getInstance()->getRow($parentSql);
    }
    
    /**
     * Update Product quantity.
     *
     * @param int $quantity Quantity to add (or substract)
     * @param int $id_product Product ID
     * @param int $id_product_attribute Attribute ID if needed
     * @param string $operator Indicate if quantity must be increased or decreased
     *
     * @return bool Whether the quantity has been succesfully updated
     */
    public function updateQty(
        $quantity,
        $id_product,
        $id_product_attribute = null,
        $id_customization = false,
        $operator = 'up',
        $id_address_delivery = 0,
        Shop $shop = null,
        $auto_add_cart_rule = true,
        $skipAvailabilityCheckOutOfStock = false
    ) {
        if (!$shop) {
            $shop = \Context::getContext()->shop;
        }

        if (\Context::getContext()->customer->id) {
            if ($id_address_delivery == 0 && (int) $this->id_address_delivery) {
                // The $id_address_delivery is null, use the cart delivery address
                $id_address_delivery = $this->id_address_delivery;
            } elseif ($id_address_delivery == 0) {
                // The $id_address_delivery is null, get the default customer address
                $id_address_delivery = (int) Address::getFirstCustomerAddressId(
                    (int) \Context::getContext()->customer->id
                );
            } elseif (!Customer::customerHasAddress(\Context::getContext()->customer->id, $id_address_delivery)) {
                // The $id_address_delivery must be linked with customer
                $id_address_delivery = 0;
            }
        }

        $quantity = (int) $quantity;
        $id_product = (int) $id_product;
        $id_product_attribute = (int) $id_product_attribute;
        $product = new Product($id_product, false, Configuration::get('PS_LANG_DEFAULT'), $shop->id);

        if ($id_product_attribute) {
            $combination = new Combination((int) $id_product_attribute);
            if ($combination->id_product != $id_product) {
                return false;
            }
        }

        /* If we have a product combination, the minimal quantity is set with the one of this combination */
        if (!empty($id_product_attribute)) {
            $minimal_quantity = (int) AttributeCore::getAttributeMinimalQty($id_product_attribute);
        } else {
            $minimal_quantity = (int) $product->minimal_quantity;
        }

        if (!Validate::isLoadedObject($product)) {
            die(Tools::displayError());
        }

        if (isset(self::$_nbProducts[$this->id])) {
            unset(self::$_nbProducts[$this->id]);
        }

        if (isset(self::$_totalWeight[$this->id])) {
            unset(self::$_totalWeight[$this->id]);
        }

        $data = array(
            'cart' => $this,
            'product' => $product,
            'id_product_attribute' => $id_product_attribute,
            'id_customization' => $id_customization,
            'quantity' => $quantity,
            'operator' => $operator,
            'id_address_delivery' => $id_address_delivery,
            'shop' => $shop,
            'auto_add_cart_rule' => $auto_add_cart_rule,
        );

        /* @deprecated deprecated since 1.6.1.1 */
        // Hook::exec('actionBeforeCartUpdateQty', $data);
        Hook::exec('actionCartUpdateQuantityBefore', $data);

        if ((int) $quantity <= 0) {
            return $this->deleteProduct($id_product, $id_product_attribute, (int) $id_customization);
        }

        if (!$product->available_for_order
            || (
                Configuration::isCatalogMode()
                && !defined('_PS_ADMIN_DIR_')
            )
        ) {
            return false;
        }

        /* Check if the product is already in the cart */
        $cartProductQuantity = $this->getProductQuantity(
            $id_product,
            $id_product_attribute,
            (int) $id_customization,
            (int) $id_address_delivery
        );

        /* Update quantity if product already exist */
        if (!empty($cartProductQuantity['quantity'])) {
            $productQuantity = Product::getQuantity($id_product, $id_product_attribute, null, $this);
            $availableOutOfStock = Product::isAvailableWhenOutOfStock($product->out_of_stock);

            if ($operator == 'up') {
                $updateQuantity = '+ ' . $quantity;
                $newProductQuantity = $productQuantity - $quantity;

                if ($newProductQuantity < 0 && !$availableOutOfStock && !$skipAvailabilityCheckOutOfStock) {
                    return false;
                }
            } elseif ($operator == 'down') {
                $cartFirstLevelProductQuantity = $this->getProductQuantity(
                    (int) $id_product,
                    (int) $id_product_attribute,
                    $id_customization
                );
                $updateQuantity = '- ' . $quantity;
                $newProductQuantity = $productQuantity + $quantity;

                if ($cartFirstLevelProductQuantity['quantity'] <= 1
                    || $cartProductQuantity['quantity'] - $quantity <= 0
                ) {
                    return $this->deleteProduct((int) $id_product, (int) $id_product_attribute, (int) $id_customization);
                }
            } else {
                return false;
            }

            \Db::getInstance()->execute(
                'UPDATE `' . _DB_PREFIX_ . 'cart_product`
                    SET `quantity` = `quantity` ' . $updateQuantity . '
                    WHERE `id_product` = ' . (int) $id_product .
                ' AND `id_customization` = ' . (int) $id_customization .
                (!empty($id_product_attribute) ? ' AND `id_product_attribute` = ' . (int) $id_product_attribute : '') . '
                    AND `id_cart` = ' . (int) $this->id . (Configuration::get('PS_ALLOW_MULTISHIPPING') && $this->isMultiAddressDelivery() ? ' AND `id_address_delivery` = ' . (int) $id_address_delivery : '') . '
                    LIMIT 1'
            );
        } elseif ($operator == 'up') {
            /* Add product to the cart */

            $sql = 'SELECT stock.out_of_stock, IFNULL(stock.quantity, 0) as quantity
                        FROM ' . _DB_PREFIX_ . 'product p
                        ' . Product::sqlStock('p', $id_product_attribute, true, $shop) . '
                        WHERE p.id_product = ' . $id_product;

            $result2 = \Db::getInstance()->getRow($sql);

            // Quantity for product pack
            if (Pack::isPack($id_product)) {
                $result2['quantity'] = Pack::getQuantity($id_product, $id_product_attribute, null, $this);
            }

            if (!Product::isAvailableWhenOutOfStock((int) $result2['out_of_stock']) && !$skipAvailabilityCheckOutOfStock) {
                if ((int) $quantity > $result2['quantity']) {
                    return false;
                }
            }

            if ((int) $quantity < $minimal_quantity) {
                return -1;
            }

            $result_add = \Db::getInstance()->insert('cart_product', array(
                'id_product' => (int) $id_product,
                'id_product_attribute' => (int) $id_product_attribute,
                'id_cart' => (int) $this->id,
                'id_address_delivery' => (int) $id_address_delivery,
                'id_shop' => $shop->id,
                'quantity' => (int) $quantity,
                'date_add' => date('Y-m-d H:i:s'),
                'id_customization' => (int) $id_customization,
            ));

            if (!$result_add) {
                return false;
            }
        }

        // refresh cache of self::_products
        $this->_products = $this->getProducts(true);
        $this->update();
        $context = \Context::getContext()->cloneContext();
        $context->cart = $this;
        Cache::clean('getContextualValue_*');
        if ($auto_add_cart_rule) {
            CartRule::autoAddToCart($context);
        }

        if ($product->customizable) {
            return $this->_updateCustomizationQuantity(
                (int) $quantity,
                (int) $id_customization,
                (int) $id_product,
                (int) $id_product_attribute,
                (int) $id_address_delivery,
                $operator
            );
        }

        return true;
    }
}
