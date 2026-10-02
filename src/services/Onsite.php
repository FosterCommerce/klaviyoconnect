<?php

namespace fostercommerce\klaviyoconnect\services;

use Craft;
use craft\commerce\elements\Product;
use craft\commerce\elements\Variant;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\web\Request;
use craft\web\View;
use fostercommerce\klaviyoconnect\Plugin;
use yii\base\Component;

class Onsite extends Component
{
	/**
	 * Klaviyo's snippet that queues `klaviyo` calls made before klaviyo.js loads.
	 */
	private const KLAVIYO_OBJECT_SNIPPET = '!function(){if(!window.klaviyo){window._klOnsite=window._klOnsite||[];try{window.klaviyo=new Proxy({},{get:function(n,i){return"push"===i?function(){var n;(n=window._klOnsite).push.apply(n,arguments)}:function(){for(var n=arguments.length,o=new Array(n),w=0;w<n;w++)o[w]=arguments[w];var t="function"==typeof o[o.length-1]?o.pop():void 0,e=new Promise((function(n){window._klOnsite.push([i].concat(o,[function(i){t&&t(i),n(i)}]))}));return e}}})}catch(n){window.klaviyo=window.klaviyo||[],window.klaviyo.push=function(){var n;(n=window._klOnsite).push.apply(n,arguments)}}}}();';

	public function registerViewedProduct(View $view, Product $product, ?Variant $variant = null): void
	{
		$properties = Plugin::getInstance()->track->viewedProductProperties($product, $variant);
		$viewedItem = Json::encode([
			'Title' => $properties['ProductName'],
			'ItemId' => $properties['ProductID'],
			'Categories' => $properties['Categories'] ?? [],
			'ImageUrl' => $properties['ImageURL'],
			'Url' => $properties['URL'],
			'Metadata' => [
				'Brand' => $properties['Brand'],
				'Price' => $properties['Price'],
				'CompareAtPrice' => $properties['CompareAtPrice'],
			],
		]);
		$viewedProduct = Json::encode($properties);
		$eventPrefix = Plugin::getInstance()->getSettings()->getEventPrefix(Craft::$app->getSites()->getCurrentSite()->id);
		$eventName = Json::encode($eventPrefix === '' ? 'Viewed Product' : $eventPrefix . ' Viewed Product');

		// Push the calls, so they run with the plugin's klaviyo.js or the site's own
		$view->registerJs(<<<JS
			window.klaviyo = window.klaviyo || [];
			klaviyo.push(['track', {$eventName}, {$viewedProduct}]);
			klaviyo.push(['trackViewedItem', {$viewedItem}]);
			JS, View::POS_END);
	}

	/**
	 * Converts a URI pattern to a regular expression, matching the URI pattern format Blitz settings use.
	 */
	public static function uriPatternRegex(string $uriPattern): string
	{
		$uriPattern = match ($uriPattern = trim($uriPattern, '/')) {
			'' => '^$',
			'*' => '.*',
			default => $uriPattern,
		};

		return '/' . str_replace(['\\/', '/'], ['/', '\\/'], $uriPattern) . '/';
	}

	public function registerScript(View $view): void
	{
		$publicApiKey = Plugin::getInstance()->getSettings()->getPublicApiKey();
		if ($publicApiKey === '' || $this->isExcludedUri()) {
			return;
		}

		$view->registerJs(self::KLAVIYO_OBJECT_SNIPPET, View::POS_HEAD);
		$view->registerJsFile('https://static.klaviyo.com/onsite/js/klaviyo.js?company_id=' . rawurlencode($publicApiKey), [
			'async' => true,
		]);

		// Fetch the logged-in user from the browser, so statically cached pages contain no email
		$sessionInfoUrl = Json::encode(UrlHelper::actionUrl('users/session-info'));
		$view->registerJs(<<<JS
			fetch({$sessionInfoUrl}, { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
				.then((response) => response.json())
				.then((session) => session.email && klaviyo.identify({ email: session.email }));
			JS, View::POS_END);
	}

	private function isExcludedUri(): bool
	{
		$siteUid = Craft::$app->getSites()->getCurrentSite()->uid;
		/** @var Request $request */
		$request = Craft::$app->getRequest();
		$uri = trim($request->getPathInfo(), '/');

		foreach (Plugin::getInstance()->getSettings()->excludedUriPatterns as $excludedUriPattern) {
			$rowSiteUid = $excludedUriPattern['siteUid'] ?? '';
			if (! ($excludedUriPattern['enabled'] ?? true)) {
				continue;
			}

			if ($rowSiteUid !== '' && $rowSiteUid !== $siteUid) {
				continue;
			}

			$uriPattern = $excludedUriPattern['uriPattern'] ?? '';
			if (! is_string($uriPattern)) {
				continue;
			}

			// Config file patterns skip the settings validation, so a broken one is logged instead of erroring every page
			$matched = @preg_match(self::uriPatternRegex($uriPattern), $uri);
			if ($matched === false) {
				Craft::warning("Klaviyo Connect excluded URI pattern \"{$uriPattern}\" is not a valid regular expression.", 'klaviyoconnect');
			}

			if ($matched === 1) {
				return true;
			}
		}

		return false;
	}
}
