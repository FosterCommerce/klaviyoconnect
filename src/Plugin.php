<?php
namespace fostercommerce\klaviyoconnect;

use Craft;
use craft\services\Users;
use craft\elements\User;
use craft\events\RegisterComponentTypesEvent;
use craft\services\Fields;
use craft\events\UserGroupsAssignEvent;
use craft\web\twig\variables\CraftVariable;
use fostercommerce\klaviyoconnect\variables\Variable;
use yii\base\Event;

class Plugin extends \craft\base\Plugin
{
    public $hasCpSettings = true;

    public function init()
    {
        parent::init();

        $this->setComponents([
            'api' => \fostercommerce\klaviyoconnect\services\Api::class,
            'events' => \fostercommerce\klaviyoconnect\services\Events::class,
            'map' => \fostercommerce\klaviyoconnect\services\Map::class,
            'cart' => \fostercommerce\klaviyoconnect\services\Cart::class,
        ]);

        Event::on(Fields::class, Fields::EVENT_REGISTER_FIELD_TYPES, function(RegisterComponentTypesEvent $event) {
            $event->types[] = \fostercommerce\klaviyoconnect\fields\ListField::class;
            $event->types[] = \fostercommerce\klaviyoconnect\fields\ListsField::class;
        });

        Event::on(User::class, User::EVENT_AFTER_SAVE, function(Event $event) {
            Plugin::getInstance()->events->onSaveUser($event);
        });

        Event::on(Users::class, Users::EVENT_AFTER_ASSIGN_USER_TO_GROUPS, function(UserGroupsAssignEvent $event) {
            Plugin::getInstance()->events->onAssignUserToGroups($event);
        });

        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function(Event $event) {
            $variable = $event->sender;
            $variable->set('klaviyoConnect', Variable::class);
        });
    }

    protected function createSettingsModel()
    {
        return new \fostercommerce\klaviyoconnect\models\Settings();
    }

    public function settingsHtml()
    {
        return Craft::$app->getView()->renderTemplate('klaviyoconnect/settings', [
            'settings' => $this->getSettings()
        ]);
    }

    public function populateModel($modelClass, $params)
    {
        $model = new $modelClass();
        $fields = $model->fields();

        foreach ($fields as $field) {
            if (isset($params[$field])) {
                $model->$field = $params[$field];
            }
        }

        return $model;
    }
}
