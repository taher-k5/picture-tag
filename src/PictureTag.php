<?php

namespace taherkathiriya\craftpicturetag;

use Craft;
use craft\base\Plugin as BasePlugin;
use craft\events\RegisterTemplateRootsEvent;
use craft\web\View;
use taherkathiriya\craftpicturetag\models\Settings;
use taherkathiriya\craftpicturetag\services\ImageService;
use taherkathiriya\craftpicturetag\services\TemplateService;
use taherkathiriya\craftpicturetag\twigextensions\PictureTagTwigExtension;
use yii\base\Event;

/**
 * Picture Tag plugin
 *
 * @author Taher Kathiriya <taher@example.com>
 * @copyright Taher Kathiriya
 * @license https://craftcms.github.io/license/ Craft License
 */
class PictureTag extends BasePlugin
{
    public string $schemaVersion = '1.0.0';
    public bool $hasCpSettings = true;

    public static function config(): array
    {
        return [
            'components' => [
                'imageService' => ImageService::class,
                'templateService' => TemplateService::class,
            ],
        ];
    }

    public function init(): void
    {
        parent::init();

        Craft::$app->onInit(function() {
            $this->attachEventHandlers();
            $this->registerTwigExtensions();
        });
    }

    protected function createSettingsModel(): ?\craft\base\Model
    {
        return new Settings();
    }

    protected function settingsHtml(): ?string
    {
        try {
            return Craft::$app->getView()->renderTemplate('picture-tag/_settings', [
                'plugin' => $this,
                'settings' => $this->getSettings(),
            ]);
        } catch (\Exception $e) {
            return '<p>Error loading settings: ' . $e->getMessage() . '</p>';
        }
    }

    private function attachEventHandlers(): void
    {
        Event::on(
            View::class,
            View::EVENT_REGISTER_SITE_TEMPLATE_ROOTS,
            function(RegisterTemplateRootsEvent $event) {
                $event->roots['picture-tag'] = $this->getBasePath() . DIRECTORY_SEPARATOR . 'templates';
            }
        );
    }
    private function registerTwigExtensions(): void
    {
        Craft::$app->getView()->registerTwigExtension(new PictureTagTwigExtension());
    }
    
}