<?php

namespace fostercommerce\klaviyoconnect\services;

use Craft;
use craft\base\ElementInterface;
use craft\base\FieldInterface;
use craft\commerce\base\PurchasableInterface;
use craft\commerce\elements\Order;
use craft\commerce\elements\Product;
use craft\commerce\elements\Variant;
use craft\commerce\enums\LineItemType;
use craft\commerce\events\LineItemEvent;
use craft\commerce\events\OrderStatusEvent;
use craft\commerce\events\RefundTransactionEvent;
use craft\commerce\models\LineItem;
use craft\elements\Address;
use craft\elements\Asset;
use craft\elements\db\ElementQueryInterface;
use craft\elements\User;
use craft\fields\data\MultiOptionsFieldData;
use craft\fields\data\OptionData;
use craft\fields\data\SingleOptionFieldData;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use DateTime;
use fostercommerce\klaviyoconnect\events\AddCustomPropertiesEvent;
use fostercommerce\klaviyoconnect\events\AddLineItemCustomPropertiesEvent;
use fostercommerce\klaviyoconnect\events\AddOrderCustomPropertiesEvent;
use fostercommerce\klaviyoconnect\events\AddProfilePropertiesEvent;
use fostercommerce\klaviyoconnect\helpers\EmailAddress;
use fostercommerce\klaviyoconnect\helpers\ImageUrl;
use fostercommerce\klaviyoconnect\helpers\PhoneNumber;
use fostercommerce\klaviyoconnect\helpers\SandboxedTwig;
use fostercommerce\klaviyoconnect\models\EventProperties;
use fostercommerce\klaviyoconnect\Plugin;
use fostercommerce\klaviyoconnect\queue\jobs\SendToKlaviyo;
use fostercommerce\klaviyoconnect\queue\jobs\SyncOrders;
use Illuminate\Support\Collection;
use Stringable;
use yii\base\Event;
use yii\caching\CacheInterface;

class Track extends Base
{
	public const ADD_CUSTOM_PROPERTIES = 'addCustomProperties';

	public const ADD_ORDER_CUSTOM_PROPERTIES = 'addOrderCustomProperties';

	public const ADD_LINE_ITEM_CUSTOM_PROPERTIES = 'addLineItemCustomProperties';

	public const ADD_PROFILE_PROPERTIES = 'addProfileProperties';

	public function onSaveUser(Event $event): void
	{
		/** @var User $user */
		$user = $event->sender;
		$selectedGroupIds = array_map(intval(...), Plugin::getInstance()->getSettings()->klaviyoAvailableGroups);
		if (array_intersect($selectedGroupIds, ArrayHelper::getColumn($user->getGroups(), 'id')) !== []) {
			// Users belong to every site, so sync them to each Klaviyo account the sites use
			$this->identifyUser(Plugin::getInstance()->map->mapUser($user), $this->siteIdPerApiKey());
		}
	}

	/**
	 * Sends the profile to the Klaviyo account of each given site, or of the current site.
	 *
	 * @param array<string, mixed> $params
	 * @param int[] $siteIds
	 */
	public function identifyUser(array $params, array $siteIds = []): void
	{
		// Klaviyo Connect identifies profiles by email, so a phone-only signup skips the profile update
		$profile = $this->withValidEmail($this->createProfile($params), 'profile update');
		if (! isset($profile['email'])) {
			return;
		}

		$profile = $this->withValidPhone($profile, 'profile update');

		foreach ($siteIds !== [] ? $siteIds : [Craft::$app->getSites()->getCurrentSite()->id] as $siteId) {
			$this->queue(new SendToKlaviyo([
				'action' => SendToKlaviyo::ACTION_PROFILE,
				'profile' => $profile,
				'siteId' => $siteId,
			]));
		}
	}

	public function onCartUpdated(Event $event): void
	{
		/** @var Order $order */
		$order = $event->sender;
		if ($order->isCompleted) {
			return;
		}

		// Skip a new cart Commerce saves before adding its first item, but send a cart emptied after an Updated Cart
		/** @var CacheInterface $cache */
		$cache = Craft::$app->getCache();
		if ($order->getLineItems() === [] && ! $cache->exists($this->cartFingerprintKey($order))) {
			return;
		}

		$this->trackOrder('Updated Cart', $order);
	}

	/**
	 * Includes every order placed on `$toDate`.
	 */
	public function queueOrderSync(DateTime $fromDate, DateTime $toDate): int
	{
		$orderIds = Order::find()
			->isCompleted()
			->dateOrdered([
				'and',
				'>= ' . (clone $fromDate)->setTime(0, 0)->format(DATE_ATOM),
				'< ' . (clone $toDate)->setTime(0, 0)->modify('+1 day')->format(DATE_ATOM),
			])
			->ids();

		foreach ($orderIds as $orderId) {
			Craft::$app->getQueue()->push(new SyncOrders([
				'orderId' => $orderId,
			]));
		}

		return count($orderIds);
	}

	/**
	 * Returns Klaviyo's Viewed Product properties, matching the line items in order events.
	 *
	 * @return array<string, mixed>
	 */
	public function viewedProductProperties(Product $product, ?Variant $variant = null): array
	{
		$variant ??= $product->getDefaultVariant();
		$price = $variant?->getSalePrice();
		$regularPrice = $variant?->getPrice();

		$properties = [
			'ProductName' => $product->title,
			'ProductID' => $this->catalogItemId($product, $variant),
			'VariantID' => $variant?->id,
			'SKU' => $variant?->getSku(),
			'URL' => $product->getUrl(),
			'ImageURL' => $this->itemImageUrl($variant, $product),
			'Brand' => $this->productBrand($product, $variant),
			'Price' => $price,
			'CompareAtPrice' => $regularPrice > $price ? $regularPrice : null,
		];

		if (Plugin::getInstance()->getSettings()->sendCategories) {
			$properties['Categories'] = $this->productCategories($product, $variant);
		}

		return $properties;
	}

	public function onAddedToCart(LineItemEvent $event): void
	{
		$order = $event->lineItem->getOrder();
		if ($order instanceof Order && ! $order->isCompleted) {
			$this->trackOrder('Added to Cart', $order, null, null, $event);
		}
	}

	public function onStartedCheckout(Event $event): void
	{
		/** @var Order $order */
		$order = $event->sender;
		if ($order->isCompleted || ! $order->getEmail() || $order->getLineItems() === []) {
			return;
		}

		/** @var CacheInterface $cache */
		$cache = Craft::$app->getCache();
		$startedCheckoutKey = 'klaviyoconnect:started-checkout:' . $order->number;
		if ($cache->exists($startedCheckoutKey)) {
			return;
		}

		// Mark the cart only once the event is queued, so a corrected email still sends it
		// The Started Checkout unique_id still dedupes the event if this key expires
		if ($this->queueOrderEvent('Started Checkout', $order)) {
			$cache->set($startedCheckoutKey, true);
		}
	}

	public function onOrderCompleted(Event $event): void
	{
		/** @var Order $order */
		$order = $event->sender;
		$this->trackOrder('Placed Order', $order);
	}

	public function onStatusChanged(OrderStatusEvent $event): void
	{
		// Skip the default status a completed order starts with, since Placed Order already covers it
		if ($event->orderHistory->prevStatusId === null) {
			return;
		}

		$order = $event->orderHistory->getOrder();
		if ($order instanceof Order) {
			$this->trackOrder('Status Changed', $order, null, null, $event);
		}
	}

	public function onOrderRefunded(RefundTransactionEvent $event): void
	{
		$order = $event->transaction->getOrder();
		if ($order instanceof Order) {
			$this->trackOrder('Refunded Order', $order, null, null, $event);
		}
	}

	/**
	 * @param string[] $listIds
	 * @param array<string, mixed> $profile
	 * @param string[]|null $consentChannels `email` and/or `sms`; null subscribes every channel the profile has
	 */
	public function addToLists(array $listIds, array $profile, bool $subscribe = false, ?array $consentChannels = null): void
	{
		$profile = $this->withValidEmail($profile, 'list signup');
		$profile = $this->withValidPhone($profile, 'list signup');
		if (! isset($profile['email']) && ! isset($profile['phone_number'])) {
			Craft::warning('Klaviyo Connect skipped a list signup, since it has no valid email or phone number.', 'klaviyoconnect');

			return;
		}

		$this->queue(new SendToKlaviyo([
			'action' => SendToKlaviyo::ACTION_LISTS,
			'listIds' => $listIds,
			'profile' => $profile,
			'subscribe' => $subscribe,
			'consentChannels' => $consentChannels,
			'siteId' => Craft::$app->getSites()->getCurrentSite()->id,
		]));
	}

	/**
	 * @param array<string, mixed> $profileParams
	 */
	public function trackEvent(string $eventName, array $profileParams, EventProperties $eventProperties, ?string $timestamp = null): void
	{
		$profile = $this->createProfile($profileParams, $eventName);

		$addCustomPropertiesEvent = new AddCustomPropertiesEvent([
			'event' => $eventName,
		]);

		Event::trigger(static::class, self::ADD_CUSTOM_PROPERTIES, $addCustomPropertiesEvent);

		if (count($addCustomPropertiesEvent->properties) > 0) {
			$eventProperties->setCustomProperties($addCustomPropertiesEvent->properties);
		}

		$this->queueEvent($eventName, $profile, $eventProperties, $timestamp, Craft::$app->getSites()->getCurrentSite()->id);
	}

	/**
	 * @param array<string, mixed>|null $profile
	 */
	public function trackOrder(string $eventName, Order $order, ?array $profile = null, ?string $timestamp = null, ?Event $fullEvent = null, ?string $uniqueId = null): void
	{
		$this->queueOrderEvent($eventName, $order, $profile, $timestamp, $fullEvent, $uniqueId);
	}

	/**
	 * @param array<string, mixed> $profile
	 * @return array<string, mixed>
	 */
	protected function createProfile(array $profile, ?string $eventName = null, mixed $context = null): array
	{
		$event = new AddProfilePropertiesEvent([
			'profile' => $profile,
			'event' => $eventName,
			'context' => $context,
		]);
		Event::trigger(static::class, self::ADD_PROFILE_PROPERTIES, $event);

		$profile = $event->profile;
		if ($event->properties !== []) {
			$profile['properties'] = [...(is_array($profile['properties'] ?? null) ? $profile['properties'] : []), ...$event->properties];
		}

		return $profile;
	}

	/**
	 * @return array<string, mixed>
	 */
	protected function getOrderDetails(Order $order, string $event = ''): array
	{
		$lineItemsProperties = [];

		foreach ($order->lineItems as $lineItem) {
			$lineItemProperties = [];
			if ($lineItem->type !== LineItemType::Custom) {
				$lineItemProperties = $this->populateLineItemProperties($lineItem);
			}

			// Add any additional user-defined properties
			$addLineItemCustomPropertiesEvent = new AddLineItemCustomPropertiesEvent([
				'properties' => $lineItemProperties,
				'order' => $order,
				'lineItem' => $lineItem,
				'event' => $event,
			]);

			Event::trigger(static::class, self::ADD_LINE_ITEM_CUSTOM_PROPERTIES, $addLineItemCustomPropertiesEvent);

			$lineItemsProperties[] = $addLineItemCustomPropertiesEvent->properties;
		}

		$lineItemsProperties = array_values(array_filter($lineItemsProperties));

		$settings = Plugin::getInstance()->getSettings();

		$customProperties = [
			'OrderID' => $order->id,
			'OrderNumber' => $order->number,
			'OrderReference' => $order->reference,
			'TotalPrice' => $order->totalPrice,
			'TotalQuantity' => $order->totalQty,
			'Items' => $lineItemsProperties,
			'ItemNames' => array_values(array_filter(array_column($lineItemsProperties, 'ProductName'))),
			'DiscountCode' => $order->couponCode,
			'DiscountValue' => abs($order->getTotalDiscount()),
			'Currency' => $order->currency,
			'DateOrdered' => $order->dateOrdered?->format(DATE_ATOM),
		];

		if (! $order->isCompleted) {
			$customProperties['CheckoutURL'] = Plugin::getInstance()->cart->restoreUrl($order);
		}

		if ($settings->sendCategories) {
			$customProperties['Categories'] = array_values(array_unique(array_merge(...array_map(
				static fn (array $lineItemProperties): array => is_array($lineItemProperties['Categories'] ?? null) ? $lineItemProperties['Categories'] : [],
				$lineItemsProperties,
			))));
		}

		if ($settings->sendPricingDetail) {
			$customProperties['Subtotal'] = $order->getItemSubtotal();
			$customProperties['ShippingTotal'] = $order->getTotalShippingCost();
			$customProperties['TaxTotal'] = $order->getTotalTax();
			$customProperties['ShippingMethod'] = $order->shippingMethodName;
		}

		if ($settings->sendAddresses) {
			$customProperties['BillingAddress'] = $this->addressProperties($order->getBillingAddress());
			$customProperties['ShippingAddress'] = $this->addressProperties($order->getShippingAddress());
		}

		if ($settings->sendSiteContext) {
			$customProperties['SiteHandle'] = $order->getOrderSite()?->handle;
			$customProperties['StoreHandle'] = $order->getStore()->handle;
		}

		$addOrderCustomPropertiesEvent = new AddOrderCustomPropertiesEvent([
			'properties' => $customProperties,
			'order' => $order,
			'event' => $event,
		]);
		Event::trigger(static::class, self::ADD_ORDER_CUSTOM_PROPERTIES, $addOrderCustomPropertiesEvent);

		return $addOrderCustomPropertiesEvent->properties;
	}

	/**
	 * Returns whether the event was queued.
	 *
	 * @param array<string, mixed>|null $profile
	 */
	private function queueOrderEvent(string $eventName, Order $order, ?array $profile = null, ?string $timestamp = null, ?Event $fullEvent = null, ?string $uniqueId = null): bool
	{
		if ($order->email !== null) {
			$profile = $this->orderProfile($order);
		}

		// Placed Order's unique_id is the order number, so an early send would block the real one
		if ($eventName === 'Placed Order' && ! $order->isCompleted) {
			return false;
		}

		if ($profile === null || $profile === []) {
			return false;
		}

		$orderDetails = $this->getOrderDetails($order, $eventName);
		$eventProperties = new EventProperties([
			'unique_id' => $uniqueId ?? $this->orderEventUniqueId($eventName, $order, $fullEvent),
			'value' => (string) $order->totalPrice,
			'value_currency' => $order->currency,
		]);
		$eventProperties->setCustomProperties($orderDetails);

		$eventName = $this->addOrderEventProperties($eventProperties, $eventName, $fullEvent);

		$success = ! $fullEvent instanceof RefundTransactionEvent || $fullEvent->refundTransaction->status === 'success';
		if (! $success) {
			return false;
		}

		$profile = $this->createProfile(
			$profile,
			$eventName,
			[
				'order' => $order,
				'eventProperties' => $eventProperties,
			]
		);

		// Format the phone once, so each Ordered Product event doesn't log the same warning
		$profile = $this->withValidPhone($profile, "\"{$eventName}\" event", $order, $order->orderSiteId);

		/** @var CacheInterface $cache */
		$cache = Craft::$app->getCache();
		$cartFingerprint = null;
		if ($eventName === 'Updated Cart') {
			$cartFingerprint = $this->cartFingerprint($profile, $eventProperties);
			if ($cache->get($this->cartFingerprintKey($order)) === $cartFingerprint) {
				return false;
			}
		}

		$queued = $this->queueEvent($eventName, $profile, $eventProperties, $timestamp, $order->orderSiteId, $order);

		if ($queued && $cartFingerprint !== null) {
			$cache->set($this->cartFingerprintKey($order), $cartFingerprint);
		}

		if ($eventName === 'Placed Order') {
			$this->queueOrderedProducts($order, $orderDetails, $profile, $timestamp);
		}

		return $queued;
	}

	/**
	 * @return array<string, mixed>
	 */
	private function populateLineItemProperties(LineItem $lineItem): array
	{
		$settings = Plugin::getInstance()->getSettings();

		// Build from the line item so purchasables without a product, such as bundles, are included
		$lineItemProperties = [
			'value' => $lineItem->subtotal,
			'LineItemID' => $lineItem->id,
			'VariantID' => $lineItem->purchasableId,
			'ProductName' => $lineItem->getDescription(),
			'ItemPrice' => $lineItem->price,
			'RowTotal' => $lineItem->subtotal,
			'Quantity' => $lineItem->qty,
			'SKU' => $lineItem->getSku(),
			'Options' => $lineItem->getOptions(),
			'Adjustments' => collect($lineItem->getAdjustments())
				->flatMap(static fn ($adjustment) => [
					$adjustment->name => $adjustment->amountAsCurrency,
				])
				->toArray(),
		];

		if ($settings->sendPricingDetail) {
			$lineItemProperties['SalePrice'] = $lineItem->salePrice;
			$lineItemProperties['Discount'] = $lineItem->getDiscount();
			$lineItemProperties['Tax'] = $lineItem->getTax();
			$lineItemProperties['Total'] = $lineItem->total;
		}

		$purchasable = $lineItem->getPurchasable();
		$product = $purchasable?->product ?? null;
		$catalogItemId = $this->catalogItemId($product instanceof Product ? $product : null, $purchasable);
		if ($catalogItemId !== null) {
			$lineItemProperties['ProductID'] = $catalogItemId;
		}

		if ($product instanceof Product) {
			$lineItemProperties = [
				...$lineItemProperties,
				'VariantTitle' => $purchasable?->title,
				'ProductName' => $product->title,
				'Slug' => $product->slug,
				'ProductURL' => $product->getUrl(),
				'ProductType' => $product->type->name,
				'ProductTypeHandle' => $product->type->handle,
				'ProductBrand' => $this->productBrand($product, $purchasable),
			];

			if ($settings->sendCategories) {
				$lineItemProperties['Categories'] = $this->productCategories($product, $purchasable);
			}
		}

		$imageUrl = $this->itemImageUrl($purchasable, $product instanceof Product ? $product : null);
		if ($imageUrl !== null) {
			$lineItemProperties['ImageURL'] = $imageUrl;
		}

		return $lineItemProperties;
	}

	/**
	 * @return array<string, mixed>
	 */
	private function orderProfile(Order $order): array
	{
		$address = $order->shippingAddress ?? $order->billingAddress;
		$location = $address instanceof Address ? [
			'city' => $address->locality,
			'region' => $address->administrativeArea,
			'country' => $this->countryName($address),
		] : null;

		$customer = $order->getCustomer();
		$profile = [
			'email' => $order->email,
			'first_name' => $customer?->firstName,
			'last_name' => $customer?->lastName,
			'location' => $location,
		];

		if ($customer instanceof User) {
			return array_replace_recursive($profile, Plugin::getInstance()->map->mapUserFields($customer, $order));
		}

		return $profile;
	}

	/**
	 * Adds the refund, added item or status properties, and returns the event name to send.
	 */
	private function addOrderEventProperties(EventProperties $eventProperties, string $eventName, ?Event $fullEvent): string
	{
		if ($fullEvent instanceof RefundTransactionEvent) {
			$refundTransaction = $fullEvent->refundTransaction;
			$eventProperties->value = (string) $refundTransaction->amount;
			$eventProperties->setCustomProperties([
				'Reason' => $refundTransaction->note,
			]);
		}

		if ($fullEvent instanceof LineItemEvent) {
			$addedItem = $this->populateLineItemProperties($fullEvent->lineItem);
			$eventProperties->setCustomProperties([
				'AddedItemProductName' => $addedItem['ProductName'] ?? null,
				'AddedItemProductID' => $addedItem['ProductID'] ?? null,
				'AddedItemSKU' => $addedItem['SKU'] ?? null,
				'AddedItemCategories' => $addedItem['Categories'] ?? [],
				'AddedItemImageURL' => $addedItem['ImageURL'] ?? null,
				'AddedItemURL' => $addedItem['ProductURL'] ?? null,
				'AddedItemPrice' => $addedItem['ItemPrice'] ?? null,
				'AddedItemQuantity' => $addedItem['Quantity'] ?? null,
			]);
		}

		if (! $fullEvent instanceof OrderStatusEvent) {
			return $eventName;
		}

		$orderHistory = $fullEvent->orderHistory;
		$newStatus = $orderHistory->getNewStatus();
		$eventProperties->setCustomProperties([
			'Reason' => $orderHistory->message,
			'Status' => $newStatus?->name,
		]);

		// Send mapped statuses under Klaviyo's reserved metric names, which its built-in flows and reports use
		$settings = Plugin::getInstance()->getSettings();

		return match (true) {
			in_array($newStatus?->handle, $settings->fulfilledOrderStatuses, true) => 'Fulfilled Order',
			in_array($newStatus?->handle, $settings->cancelledOrderStatuses, true) => 'Cancelled Order',
			default => "{$newStatus?->name} Order",
		};
	}

	/**
	 * @param array<string, mixed> $orderDetails
	 * @param array<string, mixed> $profile
	 */
	private function queueOrderedProducts(Order $order, array $orderDetails, array $profile, ?string $timestamp): void
	{
		/** @var array<int, array<string, mixed>> $items */
		$items = $orderDetails['Items'];
		foreach ($items as $index => $item) {
			$lineItemId = $item['LineItemID'] ?? null;
			$rowTotal = $item['RowTotal'] ?? null;
			$eventProperties = new EventProperties([
				'unique_id' => $order->number . '_' . (is_scalar($lineItemId) ? $lineItemId : $index),
				'value' => is_numeric($rowTotal) ? (string) $rowTotal : null,
				'value_currency' => $order->currency,
			]);
			$eventProperties->setCustomProperties($item);

			$this->queueEvent('Ordered Product', $profile, $eventProperties, $timestamp, $order->orderSiteId, $order);
		}
	}

	/**
	 * @return array<string, string|null>|null
	 */
	private function addressProperties(?Address $address): ?array
	{
		if (! $address instanceof Address) {
			return null;
		}

		return [
			'FirstName' => $address->firstName,
			'LastName' => $address->lastName,
			'Company' => $address->organization,
			'Address1' => $address->addressLine1,
			'Address2' => $address->addressLine2,
			'City' => $address->locality,
			'RegionCode' => $address->administrativeArea,
			'Zip' => $address->postalCode,
			'Country' => $this->countryName($address),
			'CountryCode' => $address->countryCode,
		];
	}

	/**
	 * Returns the ID Klaviyo matches against catalog items, so events and the store's catalog feed use the same one.
	 */
	private function catalogItemId(?Product $product, ?ElementInterface $purchasable): int|string|null
	{
		// A purchasable without a product, such as a bundle, has no product type to read the setting from
		if (! $product instanceof Product) {
			return null;
		}

		return match (Plugin::getInstance()->getSettings()->getProductTypeField($product->type, 'catalogItemId')) {
			'variantId' => $purchasable?->id,
			// Fall back to the variant ID, since a variant without a SKU would otherwise match no catalog item
			'variantSku' => $purchasable instanceof PurchasableInterface && $purchasable->getSku() !== '' ? $purchasable->getSku() : $purchasable?->id,
			default => $product->id,
		};
	}

	/**
	 * Returns the English country name, so one order sends the same value from every site language and from the queue.
	 */
	private function countryName(Address $address): string
	{
		return Craft::$app->getAddresses()->getCountryRepository()->get($address->countryCode, 'en')->getName();
	}

	private function itemImageUrl(?ElementInterface $purchasable, ?Product $product): ?string
	{
		$settings = Plugin::getInstance()->getSettings();

		// Prefer the variant's image, then the product's
		$productType = $product?->type;
		$imageFieldsByLevel = [
			[$purchasable, $settings->getProductTypeField($productType, 'variantImageField')],
			[$product, $settings->getProductTypeField($productType, 'productImageField')],
		];
		foreach ($imageFieldsByLevel as [$element, $imageFieldHandle]) {
			$images = $imageFieldHandle === '' ? null : $element?->{$imageFieldHandle} ?? null;
			$image = match (true) {
				$images instanceof ElementQueryInterface => $images->one(),
				$images instanceof Collection => $images->first(),
				default => null,
			};

			if ($image instanceof Asset) {
				return ImageUrl::forAsset($image);
			}
		}

		return null;
	}

	/**
	 * @return string[]
	 */
	private function productCategories(Product $product, ?ElementInterface $purchasable = null): array
	{
		return $this->productTypeValues('category', $product, $purchasable);
	}

	private function productBrand(Product $product, ?ElementInterface $purchasable = null): ?string
	{
		return $this->productTypeValues('brand', $product, $purchasable)[0] ?? null;
	}

	/**
	 * Returns the category or brand values the product type's mapping chooses.
	 *
	 * @return string[]
	 */
	private function productTypeValues(string $setting, Product $product, ?ElementInterface $purchasable): array
	{
		$settings = Plugin::getInstance()->getSettings();
		$productType = $product->type;
		$source = $settings->getProductTypeField($productType, $setting . 'Field');

		// A category list can be typed comma-separated, but a brand name may itself contain a comma
		$splitText = static fn (string $text): array => $setting === 'category'
			? array_values(array_filter(array_map(trim(...), explode(',', $text)), static fn (string $value): bool => $value !== ''))
			: array_values(array_filter([trim($text)], static fn (string $value): bool => $value !== ''));

		return match ($source) {
			'' => [],
			'__productType__' => [(string) $product->type->name],
			'__custom__' => $splitText($settings->getProductTypeField($productType, $setting . 'Text')),
			'__twig__' => $splitText(SandboxedTwig::render($settings->getProductTypeField($productType, $setting . 'Twig'), [
				'product' => $product,
				'variant' => $purchasable ?? $product->getDefaultVariant(),
			], "product type \"{$productType->handle}\" {$setting}")),
			default => $this->fieldTextValues($this->productFieldValue($source, $product, $purchasable)),
		};
	}

	/**
	 * @return string[]
	 */
	private function fieldTextValues(mixed $value): array
	{
		if ($value instanceof ElementQueryInterface) {
			$value = $value->collect();
		}

		$values = match (true) {
			$value instanceof Collection => $value->all(),
			$value instanceof MultiOptionsFieldData => array_map(static fn (mixed $option): string => $option instanceof OptionData ? (string) $option->label : '', $value->getArrayCopy()),
			$value instanceof SingleOptionFieldData => [(string) $value->label],
			default => [$value],
		};

		return array_values(array_filter(array_map(
			static fn (mixed $item): string => $item instanceof Stringable || is_scalar($item) ? trim((string) $item) : '',
			$values,
		), static fn (string $item): bool => $item !== ''));
	}

	/**
	 * Reads a field from the product, or from the variant when the field is on the variant layout.
	 */
	private function productFieldValue(string $fieldHandle, Product $product, ?ElementInterface $purchasable): mixed
	{
		if ($fieldHandle === '') {
			return null;
		}

		foreach ([$product, $purchasable ?? $product->getDefaultVariant()] as $element) {
			if ($element?->getFieldLayout()?->getFieldByHandle($fieldHandle) instanceof FieldInterface) {
				return $element->getFieldValue($fieldHandle);
			}
		}

		return null;
	}

	/**
	 * Returns one site ID for each Private API Key, so sites sharing a Klaviyo account get one send.
	 *
	 * @return int[]
	 */
	private function siteIdPerApiKey(): array
	{
		$settings = Plugin::getInstance()->getSettings();
		$siteIdsByApiKey = [];
		foreach (Craft::$app->getSites()->getAllSiteIds() as $allSiteId) {
			$apiKey = $settings->getApiKey($allSiteId);
			if ($apiKey !== '') {
				$siteIdsByApiKey[$apiKey] ??= $allSiteId;
			}
		}

		return array_values($siteIdsByApiKey);
	}

	private function queue(SendToKlaviyo $job): bool
	{
		// Skip sites without a Private API Key, since every request without one fails with a 401
		if (Plugin::getInstance()->getSettings()->getApiKey($job->siteId) === '') {
			return false;
		}

		Craft::$app->getQueue()->push($job);

		return true;
	}

	/**
	 * @param array<string, mixed> $profile
	 */
	private function queueEvent(string $eventName, array $profile, EventProperties $eventProperties, ?string $timestamp, ?int $siteId, ?Order $order = null): bool
	{
		$profile = $this->withValidEmail($profile, "\"{$eventName}\" event");
		if (! isset($profile['email'])) {
			return false;
		}

		$profile = $this->withValidPhone($profile, "\"{$eventName}\" event", $order, $siteId);

		return $this->queue(new SendToKlaviyo([
			'action' => SendToKlaviyo::ACTION_EVENT,
			'event' => Plugin::getInstance()->api->buildEvent($eventName, $profile, $eventProperties, $timestamp, $siteId),
			'siteId' => $siteId,
		]));
	}

	/**
	 * Trims the profile's email, and removes it when Klaviyo would reject it, so the send isn't queued only to fail.
	 *
	 * @param array<string, mixed> $profile
	 * @return array<string, mixed>
	 */
	private function withValidEmail(array $profile, string $send): array
	{
		$email = $profile['email'] ?? null;
		if ($email === null || $email === '') {
			unset($profile['email']);

			return $profile;
		}

		if (! is_string($email) || ! EmailAddress::isValid($email)) {
			Craft::warning("Klaviyo Connect skipped the email on a {$send}, since it isn't a valid address.", 'klaviyoconnect');
			unset($profile['email']);

			return $profile;
		}

		$profile['email'] = trim($email);

		return $profile;
	}

	/**
	 * Formats the profile's phone number as E.164, and removes a number it can't format, so the send isn't queued only to fail.
	 *
	 * @param array<string, mixed> $profile
	 * @return array<string, mixed>
	 */
	private function withValidPhone(array $profile, string $send, ?Order $order = null, ?int $siteId = null): array
	{
		$phoneNumber = $profile['phone_number'] ?? null;
		if ($phoneNumber === null || $phoneNumber === '') {
			unset($profile['phone_number']);

			return $profile;
		}

		$countryCode = $this->phoneCountryCode($profile, $order, $siteId);
		$e164PhoneNumber = PhoneNumber::toE164($phoneNumber, $countryCode);
		if ($e164PhoneNumber !== null) {
			$profile['phone_number'] = $e164PhoneNumber;

			return $profile;
		}

		$reason = match (true) {
			is_string($phoneNumber) && str_starts_with(trim($phoneNumber), '+') => "it isn't a valid international number.",
			$countryCode === null => 'it has no country code, and no order address, posted country or site language region gave a country to read it in. Collect the number with its country code, or post `profile[location][country]`.',
			default => "it isn't a valid number in {$countryCode}. If the number is from another country, collect it with its country code.",
		};
		Craft::warning("Klaviyo Connect ignored the phone number on a {$send}, since {$reason}", 'klaviyoconnect');
		unset($profile['phone_number']);

		return $profile;
	}

	/**
	 * Returns the country to read a phone number typed without its country code.
	 *
	 * @param array<string, mixed> $profile
	 */
	private function phoneCountryCode(array $profile, ?Order $order, ?int $siteId): ?string
	{
		$address = $order?->shippingAddress ?? $order?->billingAddress;
		if ($address instanceof Address) {
			return $address->countryCode;
		}

		// Use only a two-letter code, since a posted or handler-set country can be a name
		$postedCountry = is_array($profile['location'] ?? null) ? ($profile['location']['country'] ?? null) : null;
		if (is_string($postedCountry) && preg_match('/^[A-Za-z]{2}$/', $postedCountry) === 1) {
			return strtoupper($postedCountry);
		}

		$sitesService = Craft::$app->getSites();
		$site = $siteId === null ? $sitesService->getCurrentSite() : $sitesService->getSiteById($siteId, true);

		return $site?->getLocale()->getTerritoryID();
	}

	/**
	 * @param array<string, mixed> $profile
	 */
	private function cartFingerprint(array $profile, EventProperties $eventProperties): string
	{
		// Skip the unique ID, since it changes on every send
		$properties = $eventProperties->toArray();
		unset($properties['$unique_id']);

		return hash('sha256', Json::encode([$profile, $properties]));
	}

	private function cartFingerprintKey(Order $order): string
	{
		return 'klaviyoconnect:updated-cart:' . $order->number;
	}

	private function orderEventUniqueId(string $eventName, Order $order, ?Event $fullEvent): string
	{
		// One-time events reuse the same ID, so retries and re-syncs don't record twice
		return match (true) {
			$eventName === 'Placed Order' => (string) $order->number,
			$eventName === 'Started Checkout' => $order->number . '_started-checkout',
			$fullEvent instanceof LineItemEvent => $order->number . '_added_' . $fullEvent->lineItem->id,
			$fullEvent instanceof OrderStatusEvent => $order->number . '_status_' . $fullEvent->orderHistory->id,
			$fullEvent instanceof RefundTransactionEvent => $order->number . '_refund_' . $fullEvent->refundTransaction->id,
			default => StringHelper::UUID(),
		};
	}
}
