<?php
namespace verbb\formie\fields\formfields;

use verbb\formie\Formie;
use verbb\formie\base\FormField;
use verbb\formie\elements\Form;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\positions\Hidden as HiddenPosition;

use Craft;
use craft\base\ElementInterface;
use craft\base\PreviewableFieldInterface;
use craft\helpers\DateTimeHelper;
use craft\helpers\UrlHelper;
use craft\web\View;

use Throwable;
use DateTime;

class Hidden extends FormField implements PreviewableFieldInterface
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'Hidden Field');
    }

    /**
     * @inheritDoc
     */
    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/hidden-field/icon.svg';
    }


    // Properties
    // =========================================================================

    public $defaultOption;
    public $queryParameter;
    public $cookieName;
    public $columnType;


    // Public Methods
    // =========================================================================

    public function init()
    {
        parent::init();

        $currentUser = Craft::$app->getUser()->getIdentity();
        $request = Craft::$app->getRequest();

        if (!Craft::$app->getRequest()->getIsConsoleRequest()) {
            if ($this->defaultOption === 'dateUs') {
                $this->defaultValue = DateTimeHelper::toDateTime(new DateTime())->format('m/d/Y');
            } elseif ($this->defaultOption === 'dateInt') {
                $this->defaultValue = DateTimeHelper::toDateTime(new DateTime())->format('d/m/Y');
            } elseif ($this->defaultOption === 'userAgent') {
                $this->defaultValue = $request->getUserAgent();
            } elseif ($this->defaultOption === 'referUrl') {
                $this->defaultValue = $request->getReferrer();
            } elseif ($this->defaultOption === 'currentUrl') {
                $this->defaultValue = $request->getAbsoluteUrl();
            } elseif ($this->defaultOption === 'currentUrlNoQueryString') {
                $this->defaultValue = UrlHelper::stripQueryString($request->getAbsoluteUrl());
            } elseif ($this->defaultOption === 'userId') {
                $this->defaultValue = $currentUser->id ?? null;
            } elseif ($this->defaultOption === 'username') {
                $this->defaultValue = $currentUser->username ?? null;
            } elseif ($this->defaultOption === 'userEmail') {
                $this->defaultValue = $currentUser->email ?? null;
            } elseif ($this->defaultOption === 'userIp') {
                $this->defaultValue = $request->getUserIP();
            } elseif ($this->defaultOption === 'query' && $this->queryParameter) {
                $this->defaultValue = $request->getParam($this->queryParameter);
            } elseif ($this->defaultOption === 'cookie' && $this->cookieName) {
                $this->defaultValue = $_COOKIE[$this->cookieName] ?? '';
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function getContentColumnType(): string
    {
        if ($this->columnType) {
            return $this->columnType;
        }

        return parent::getContentColumnType();
    }

    /**
     * @inheritDoc
     */
    public function renderLabel(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function getIsHidden(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getFieldDefaults(): array
    {
        return [
            'labelPosition' => HiddenPosition::class,
            'defaultOption' => 'custom',
            'includeInEmail' => true,
        ];
    }

    /**
     * @inheritDoc
     */
    public function getInputHtml($value, ElementInterface $element = null): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/hidden-field/input', [
            'name' => $this->handle,
            'value' => $value,
            'field' => $this,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getPreviewInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/hidden-field/preview', [
            'field' => $this
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getFrontEndJsModules()
    {
        if ($this->defaultOption === 'cookie' && $this->cookieName) {
            return [
                'src' => Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/frontend/dist/js/fields/hidden.js', true),
                'module' => 'FormieHidden',
                'settings' => [
                    'cookieName' => $this->cookieName,
                ],
            ];
        }
    }

    /**
     * @inheritDoc
     */
    public function getFrontEndInputOptions(Form $form, $value, array $options = null): array
    {
        $inputOptions = parent::getFrontEndInputOptions($form, $value, $options);

        try {
            $defaultValue = Craft::$app->getView()->renderString(
                (string)$this->defaultValue,
                [
                    'field' => $this,
                    'form' => $form,
                ],
                View::TEMPLATE_MODE_SITE
            );
        } catch (Throwable $e) {
            $defaultValue = $this->defaultValue;
            Formie::error('Failed to render hidden field template: ' . $e->getMessage());
        }

        $inputOptions['defaultValue'] = $defaultValue;

        return $inputOptions;
    }

    /**
     * @inheritDoc
     */
    public function defineGeneralSchema(): array
    {
        return [
            SchemaHelper::labelField([
                'label' => Craft::t('formie', 'Name'),
                'help' => Craft::t('formie', 'The name of this field displayed only to you'),
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Default Value'),
                'help' => Craft::t('formie', 'Select an option for the default value.'),
                'name' => 'defaultOption',
                'options' => [
                    [ 'label' => Craft::t('formie', 'Date (mm/dd/yyyy)'), 'value' => 'dateUs' ],
                    [ 'label' => Craft::t('formie', 'Date (dd/mm/yyyy)'), 'value' => 'dateInt' ],
                    [ 'label' => Craft::t('formie', 'Current URL'), 'value' => 'currentUrl' ],
                    [ 'label' => Craft::t('formie', 'Current URL (without Query String)'), 'value' => 'currentUrlNoQueryString' ],
                    [ 'label' => Craft::t('formie', 'HTTP User Agent'), 'value' => 'userAgent' ],
                    [ 'label' => Craft::t('formie', 'HTTP Refer URL'), 'value' => 'referUrl' ],
                    [ 'label' => Craft::t('formie', 'User ID'), 'value' => 'userId' ],
                    [ 'label' => Craft::t('formie', 'Username'), 'value' => 'username' ],
                    [ 'label' => Craft::t('formie', 'User Email'), 'value' => 'userEmail' ],
                    [ 'label' => Craft::t('formie', 'User IP Address'), 'value' => 'userIp' ],
                    [ 'label' => Craft::t('formie', 'Cookie Value'), 'value' => 'cookie' ],
                    [ 'label' => Craft::t('formie', 'Query Parameter'), 'value' => 'query' ],
                    [ 'label' => Craft::t('formie', 'Custom Value'), 'value' => 'custom' ],
                ],
            ]),
            SchemaHelper::toggleContainer('settings.defaultOption=custom', [
                SchemaHelper::textField([
                    'label' => Craft::t('formie', 'Custom Value'),
                    'help' => Craft::t('formie', 'Entering a default value will place the value in the field when it loads.'),
                    'name' => 'defaultValue',
                ]),
            ]),
            SchemaHelper::toggleContainer('settings.defaultOption=query', [
                SchemaHelper::textField([
                    'label' => Craft::t('formie', 'Query Parameter'),
                    'help' => Craft::t('formie', 'Entering the query parameter to populate the value of the field when it loads.'),
                    'name' => 'queryParameter',
                ]),
            ]),
            SchemaHelper::toggleContainer('settings.defaultOption=cookie', [
                SchemaHelper::textField([
                    'label' => Craft::t('formie', 'Cookie Name'),
                    'help' => Craft::t('formie', 'Enter the name of the cookie to use as the value of this field.'),
                    'name' => 'cookieName',
                ]),
            ]),
        ];
    }

    /**
     * @inheritDoc
     */
    public function defineSettingsSchema(): array
    {
        return [
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Include in Email Notifications'),
                'help' => Craft::t('formie', 'Whether the content of this field should be included in email notifications.'),
                'name' => 'includeInEmail',
            ]),
        ];
    }

    /**
     * @inheritDoc
     */
    public function defineAdvancedSchema(): array
    {
        return [
            SchemaHelper::handleField(),
            SchemaHelper::cssClasses(),
            SchemaHelper::containerAttributesField(),
            SchemaHelper::inputAttributesField(),
            SchemaHelper::enableContentEncryptionField(),
            SchemaHelper::columnTypeField(),
        ];
    }
}
