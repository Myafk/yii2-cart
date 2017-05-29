<?php

namespace myafk\cart;

use Yii;
use yii\base\Component;
use yii\base\InvalidParamException;
use yii\helpers\ArrayHelper;
use myafk\cart\models\CartItemInterface;
use myafk\cart\storage\StorageInterface;

/**
 * Class Cart provides basic cart functionality (adding, removing, clearing, listing items). You can extend this class and
 * override it in the application configuration to extend/customize the functionality
 *
 * @package myafk\cart
 *
 * @property StorageInterface[] $storage
 */
class Cart extends Component
{
    /**
     * @var string CartItemInterface class name
     */
    const ITEM_PRODUCT = '\myafk\cart\models\CartItemInterface';

    /**
     * Override this to provide custom (e.g. database) storage for cart data
     * Можно писать условие, например, тогда это хранилище будет использоваться только для гостей
     * '\yii2mod\cart\storage\SessionStorage' => [
     *  'condition' => function() { return Yii::$app->user->isGuest; }
     * ]
     *
     * @var array
     */
    public $storageClasses = [
    	'\yii2mod\cart\storage\SessionStorage',
	    '\yii2mod\cart\storage\DatabaseStorage',
    ];

    /**
     * @var array cart items
     */
    protected $items;

    /**
     * @var StorageInterface
     */
    private $_storage;

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->clear(false);
        foreach ($this->storageClasses as $storageClass => $value) {
        	$condition = true;
        	$conditionCallback = ArrayHelper::remove($value, 'condition');
        	if ($conditionCallback && is_callable($conditionCallback))
        		$condition = call_user_func($conditionCallback, [$this]);
        	if ($condition)
	            $this->setStorage(Yii::createObject($storageClass));
        }
        foreach($this->storage as $storage) {
        	if ($data = $storage->load($this)) {
		        $this->items = $data;
		        break;
	        }
        }
    }

    /**
     * Delete all items from the cart
     *
     * @param bool $save
     *
     * @return $this
     */
    public function clear($save = true)
    {
        $this->items = [];
        $save && $this->storage->save($this);

        return $this;
    }

    /**
     * @return mixed
     */
    public function getStorage()
    {
        return $this->_storage;
    }

    /**
     * @param mixed $storage
     */
    public function setStorage($storage)
    {
        $this->_storage[get_class($storage)] = $storage;
    }

    /**
     * Add an item to the cart
     *
     * @param models\CartItemInterface $element
     * @param integer $quantity
     * @param bool $save
     *
     * @return $this
     */
    public function add(CartItemInterface $element, $quantity = 1, $save = true)
    {
        $this->addItem($element, $quantity);
        $save && $this->storage->save($this);

        return $this;
    }

    /**
     * @param \yii2mod\cart\models\CartItemInterface $item
     * @param integer $quantity
     */
    protected function addItem(CartItemInterface $item, $quantity = 1)
    {
        $uniqueId = $item->getUniqueId();
        $item['quantity'] = $quantity;
        $this->items[$uniqueId] = $item;
    }

    /**
     * Removes an item from the cart
     *
     * @param string $uniqueId
     * @param integer $quantity Если количество = 0, то удаляем весь итем с корзины, иначе убираем то кол-во, которое указано
     * @param bool $save
     *
     * @throws \yii\base\InvalidParamException
     *
     * @return $this
     */
    public function remove($uniqueId, $quantity = 0, $save = true)
    {
        if (!isset($this->items[$uniqueId])) {
            throw new InvalidParamException('Item not found');
        }
        $itemQuantity = $this->items[$uniqueId]['quantity'];
        $totalQuantity = $itemQuantity - $quantity;
		if ($quantity === 0 || $totalQuantity <= 0)
			unset($this->items[$uniqueId]);
		else
			$this->items[$uniqueId]['quantity'] = $totalQuantity;

        $save && $this->storage->save($this);

        return $this;
    }

    /**
     * @param string $itemType If specified, only items of that type will be counted
     *
     * @return int
     */
    public function getCount($itemType = null)
    {
        return count($this->getItems($itemType));
    }

    /**
     * Returns all items of a given type from the cart
     *
     * @param string $itemType One of self::ITEM_ constants
     *
     * @return CartItemInterface[]
     */
    public function getItems($itemType = null)
    {
        $items = $this->items;

        if (!is_null($itemType)) {
            $items = array_filter(
                $items,
                function ($item) use ($itemType) {
                    /* @var $item CartItemInterface */
                    return is_subclass_of($item, $itemType);
                }
            );
        }

        return $items;
    }

    /**
     * Finds all items of type $itemType, sums the values of $attribute of all models and returns the sum.
     *
     * @param string $attribute
     * @param string|null $itemType
     *
     * @return int
     */
    public function getAttributeTotal($attribute, $itemType = null)
    {
        $sum = 0;
        foreach ($this->getItems($itemType) as $model) {
            $sum += $model->{$attribute};
        }

        return $sum;
    }
}
