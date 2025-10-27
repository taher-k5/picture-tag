<?php

namespace taherkathiriya\craftpicturetag\controllers;

use Craft;
use craft\web\Controller;
use taherkathiriya\craftpicturetag\PictureTag;
use taherkathiriya\craftpicturetag\models\Settings;
use yii\web\Response;


class SettingsController extends Controller
{
    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        $this->requireAdmin(false);

        return parent::beforeAction($action);
    }

    /**
     * Edit the plugin settings.
     */
    public function actionEdit(): ?Response
    {
        $settings = PictureTag::$plugin->getSettings();
        Craft::info('Loaded settings: ' . print_r($settings->toArray(), true), __METHOD__);

        return $this->renderTemplate('picture-tag/_settings', [
            'settings' => $settings,
            'readOnly' => !Craft::$app->getConfig()->getGeneral()->allowAdminChanges,
        ]);
    }

    /**
     * Saves the plugin settings.
     */
    public function actionSave(): ?Response
    {
        $this->requirePostRequest();
        $this->requireAdmin(false);

        $settings = new Settings();
        $formData = Craft::$app->getRequest()->getBodyParam('settings', []);
        Craft::info('Form data submitted: ' . print_r($formData, true), __METHOD__);

        if (!is_array($formData)) {
            Craft::$app->getSession()->setError(Craft::t('picture-tag', 'Invalid settings data.'));
            return $this->renderTemplate('picture-tag/_settings', [
                'settings' => PictureTag::$plugin->getSettings(),
                'readOnly' => !Craft::$app->getConfig()->getGeneral()->allowAdminChanges,
            ]);
        }

        $settings->setAttributes($formData, false);

        if ($settings->saveSettings()) {
            Craft::$app->getSession()->setNotice(Craft::t('picture-tag', 'Settings saved.'));
            return $this->redirectToPostedUrl();
        }

        Craft::info('Validation errors: ' . print_r($settings->getErrors(), true), __METHOD__);
        Craft::$app->getSession()->setError(Craft::t('picture-tag', 'Couldn’t save settings due to validation errors: {errors}', [
            'errors' => implode(', ', array_merge(...array_values($settings->getErrors())))
        ]));
        return $this->renderTemplate('picture-tag/_settings', [
            'settings' => $settings,
            'readOnly' => !Craft::$app->getConfig()->getGeneral()->allowAdminChanges,
        ]);
    }
}