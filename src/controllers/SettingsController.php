<?php

namespace SFS\craftpicturetag\controllers;

use Craft;
use craft\web\Controller;
use SFS\craftpicturetag\PictureTag;
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

        return $this->renderTemplate('picture-tag/_settings', [
            'settings' => $settings,
            'readOnly' => !Craft::$app->getConfig()->getGeneral()->allowAdminChanges,
        ]);
    }

}