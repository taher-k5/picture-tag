<?php

namespace taherkathiriya\craftpicturetag\controllers;

use Craft;
use craft\web\Controller;
use taherkathiriya\craftpicturetag\PictureTag;
use yii\web\Response;


class SettingsController extends Controller
{
    /**
     * @inerhitdoc
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
        \Craft::info('Loaded settings: ' . print_r($settings->toArray(), true), __METHOD__); // Debug log

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

        $settings = Craft::$app->getRequest()->getBodyParam('settings', []);
        \Craft::info('Settings received: ' . print_r($settings, true), __METHOD__); // Debug log

        if (!is_array($settings)) {
            Craft::$app->getSession()->setError(Craft::t('picture-tag', 'Invalid settings data.'));
            return $this->renderTemplate('picture-tag/_settings', [
                'settings' => PictureTag::$plugin->getSettings(),
                'readOnly' => !Craft::$app->getConfig()->getGeneral()->allowAdminChanges,
            ]);
        }

        $plugin = PictureTag::$plugin;
        $pluginSettings = $plugin->getSettings();

        // Set and validate settings
        $pluginSettings->setAttributes($settings, false);
        if (!$pluginSettings->validate()) {
            \Craft::info('Validation errors: ' . print_r($pluginSettings->getErrors(), true), __METHOD__); // Debug log
            Craft::$app->getSession()->setError(Craft::t('picture-tag', 'Couldn’t save settings due to validation errors: {errors}', [
                'errors' => implode(', ', array_merge(...array_values($pluginSettings->getErrors())))
            ]));
            return $this->renderTemplate('picture-tag/_settings', [
                'settings' => $pluginSettings,
                'readOnly' => !Craft::$app->getConfig()->getGeneral()->allowAdminChanges,
            ]);
        }

        // Save the settings
        if (Craft::$app->getPlugins()->savePluginSettings($plugin, $settings)) {
            \Craft::info('Settings saved successfully: ' . print_r($settings, true), __METHOD__); // Debug log
            Craft::$app->getSession()->setNotice(Craft::t('picture-tag', 'Settings saved.'));
            return $this->redirectToPostedUrl();
        }

        \Craft::info('Failed to save settings.', __METHOD__); // Debug log
        Craft::$app->getSession()->setError(Craft::t('picture-tag', 'Couldn’t save settings.'));
        return $this->renderTemplate('picture-tag/_settings', [
            'settings' => $pluginSettings,
            'readOnly' => !Craft::$app->getConfig()->getGeneral()->allowAdminChanges,
        ]);
    }
}