<?php

namespace fostercommerce\klaviyoconnect\controllers;

use Craft;
use craft\web\Controller;
use fostercommerce\klaviyoconnect\fields\ListField;
use fostercommerce\klaviyoconnect\fields\ListsField;
use fostercommerce\klaviyoconnect\Plugin;
use KlaviyoAPI\ApiException;
use yii\web\BadRequestHttpException;
use yii\web\Response;

class ListsController extends Controller
{
	/**
	 * @throws BadRequestHttpException
	 */
	public function actionRefresh(): ?Response
	{
		$this->requireCpRequest();
		$this->requirePostRequest();
		$this->requireAcceptsJson();

		$fieldId = $this->request->getRequiredBodyParam('fieldId');
		$field = is_numeric($fieldId) ? Craft::$app->getFields()->getFieldById((int) $fieldId) : null;
		if (! $field instanceof ListField && ! $field instanceof ListsField) {
			throw new BadRequestHttpException('Not a Klaviyo List or Lists field.');
		}

		$siteId = $this->request->getBodyParam('siteId');
		$namespace = $this->request->getBodyParam('namespace');
		$values = $this->request->getBodyParam('values');
		$listIds = is_array($values) ? array_values(array_filter($values, is_string(...))) : [];
		$value = $field instanceof ListField ? ($listIds[0] ?? null) : $field->normalizeValue($listIds);

		$siteId = is_numeric($siteId) ? (int) $siteId : null;

		// Report a failed refresh, since the field would otherwise redraw with no lists and no reason
		try {
			Plugin::getInstance()->getApi()->getLists($siteId, true);
		} catch (ApiException $apiException) {
			return $this->asFailure(Plugin::getInstance()->getApi()->errorMessage($apiException));
		}

		$inputHtml = $field->listsInputHtml($value, $siteId);

		return $this->asJson([
			'inputHtml' => is_string($namespace) && $namespace !== '' ? Craft::$app->getView()->namespaceInputs($inputHtml, $namespace) : $inputHtml,
		]);
	}
}
